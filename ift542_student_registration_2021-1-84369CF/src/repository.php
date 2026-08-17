<?php
require_once __DIR__ . '/db.php';

/**
 * Every function below binds user-supplied values as parameters. None of them
 * build SQL by string concatenation/interpolation with request data — this is
 * the Task 2 fix for the "raw user input combined with SQL text" pattern.
 */

function find_user_by_email(string $email): ?array
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function find_user_by_id(int $id): ?array
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function record_failed_login(string $email): void
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'UPDATE users
         SET failed_login_count = failed_login_count + 1,
             locked_until = CASE WHEN failed_login_count + 1 >= 5
                                  THEN DATE_ADD(NOW(), INTERVAL 5 MINUTE)
                                  ELSE locked_until END
         WHERE email = :email'
    );
    $stmt->execute([':email' => $email]);
}

function reset_failed_login(int $userId): void
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('UPDATE users SET failed_login_count = 0, locked_until = NULL WHERE id = :id');
    $stmt->execute([':id' => $userId]);
}

function update_profile(int $userId, string $fullName): void
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('UPDATE users SET full_name = :full_name WHERE id = :id');
    $stmt->execute([':full_name' => $fullName, ':id' => $userId]);
}

function list_courses(): array
{
    $pdo = get_pdo();
    return $pdo->query('SELECT id, code, title, capacity, resource_url FROM courses ORDER BY code')->fetchAll();
}

function find_course(int $courseId): ?array
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM courses WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $courseId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/** Enrol a user in a course. $userId always comes from the session, never from request input. */
function enrol_user(int $userId, int $courseId): bool
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'INSERT IGNORE INTO enrolments (user_id, course_id) VALUES (:user_id, :course_id)'
    );
    $stmt->execute([':user_id' => $userId, ':course_id' => $courseId]);
    return $stmt->rowCount() > 0;
}

function list_enrolments_for_user(int $userId): array
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'SELECT e.id, e.status, c.code, c.title
         FROM enrolments e JOIN courses c ON c.id = e.course_id
         WHERE e.user_id = :user_id AND e.status = "active"
         ORDER BY c.code'
    );
    $stmt->execute([':user_id' => $userId]);
    return $stmt->fetchAll();
}

/** Authorization check used to fix the IDOR in Task 1/2: caller must own the enrolment. */
function enrolment_belongs_to_user(int $enrolmentId, int $userId): bool
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT id FROM enrolments WHERE id = :id AND user_id = :user_id');
    $stmt->execute([':id' => $enrolmentId, ':user_id' => $userId]);
    return (bool) $stmt->fetch();
}

function save_document(int $userId, string $originalName, string $storedName, string $mimeType, int $size): void
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'INSERT INTO documents (user_id, original_name, stored_name, mime_type, size_bytes)
         VALUES (:user_id, :original_name, :stored_name, :mime_type, :size)'
    );
    $stmt->execute([
        ':user_id'       => $userId,
        ':original_name' => $originalName,
        ':stored_name'   => $storedName,
        ':mime_type'     => $mimeType,
        ':size'          => $size,
    ]);
}

function list_documents_for_user(int $userId): array
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM documents WHERE user_id = :user_id ORDER BY created_at DESC');
    $stmt->execute([':user_id' => $userId]);
    return $stmt->fetchAll();
}

// --- Admin-only writes. Every caller MUST have passed require_admin() first. ---

function admin_create_course(string $code, string $title, int $capacity, ?string $resourceUrl): void
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'INSERT INTO courses (code, title, capacity, resource_url) VALUES (:code, :title, :capacity, :url)'
    );
    $stmt->execute([':code' => $code, ':title' => $title, ':capacity' => $capacity, ':url' => $resourceUrl]);
}

function admin_delete_enrolment(int $enrolmentId): void
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('UPDATE enrolments SET status = "dropped" WHERE id = :id');
    $stmt->execute([':id' => $enrolmentId]);
}

/** Structured audit logging: who / what / when, no secrets or full PII. */
function audit_log(?int $actorUserId, string $actorLabel, string $action, ?string $target = null, ?string $details = null): void
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'INSERT INTO audit_log (actor_user_id, actor_label, action, target, ip_address, details)
         VALUES (:uid, :label, :action, :target, :ip, :details)'
    );
    $stmt->execute([
        ':uid'     => $actorUserId,
        ':label'   => $actorLabel,
        ':action'  => $action,
        ':target'  => $target,
        ':ip'      => $_SERVER['REMOTE_ADDR'] ?? null,
        ':details' => $details,
    ]);
}

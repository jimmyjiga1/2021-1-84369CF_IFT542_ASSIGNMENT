<?php
require __DIR__ . '/../../src/bootstrap.php';
$admin = require_admin();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $enrolmentId = filter_var($_POST['enrolment_id'] ?? '', FILTER_VALIDATE_INT);
    if ($enrolmentId === false || $enrolmentId <= 0) {
        $message = 'Invalid enrolment id.';
    } else {
        admin_delete_enrolment((int) $enrolmentId);
        audit_log((int) $admin['id'], $admin['matric_no'], 'enrolment_dropped_by_admin', (string) $enrolmentId);
        $message = 'Enrolment dropped.';
    }
}

$pdo = get_pdo();
$rows = $pdo->query(
    'SELECT e.id, u.matric_no, u.full_name, c.code, c.title
     FROM enrolments e
     JOIN users u ON u.id = e.user_id
     JOIN courses c ON c.id = e.course_id
     WHERE e.status = "active"
     ORDER BY e.id DESC'
)->fetchAll();
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Manage Enrolments</title><link rel="stylesheet" href="/assets/style.css"></head>
<body>
<nav><a href="/admin/index.php">Admin Home</a></nav>
<h1>Manage Enrolments</h1>
<?php if ($message): ?><p class="success"><?= e($message) ?></p><?php endif; ?>
<table>
<tr><th>Student</th><th>Matric</th><th>Course</th><th></th></tr>
<?php foreach ($rows as $r): ?>
<tr>
    <td><?= e($r['full_name']) ?></td>
    <td><?= e($r['matric_no']) ?></td>
    <td><?= e($r['code']) ?> — <?= e($r['title']) ?></td>
    <td>
        <form method="post" action="/admin/enrolments.php" style="display:inline">
            <?= csrf_field() ?>
            <input type="hidden" name="enrolment_id" value="<?= (int) $r['id'] ?>">
            <button type="submit">Drop</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</table>
</body>
</html>

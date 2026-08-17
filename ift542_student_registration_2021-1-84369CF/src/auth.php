<?php
require_once __DIR__ . '/repository.php';

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => (int) (getenv('SESSION_LIFETIME_SECONDS') ?: 1800),
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']), // true once served over TLS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('SRWA_SESSID'); // avoid the default PHPSESSID fingerprint
    session_start();

    // Idle timeout, enforced server-side in addition to the cookie lifetime.
    $lifetime = (int) (getenv('SESSION_LIFETIME_SECONDS') ?: 1800);
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $lifetime) {
        $_SESSION = [];
        session_destroy();
        session_start();
    }
    $_SESSION['last_activity'] = time();
}

/** Call immediately after successful authentication to defeat session fixation. */
function regenerate_session_on_login(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role']    = $user['role'];
    $_SESSION['matric']  = $user['matric_no'];
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    return find_user_by_id((int) $_SESSION['user_id']);
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        header('Location: /login.php');
        exit;
    }
    return $user;
}

/** Server-side role check — the fix for the Task 3 elevation-of-privilege threat.
 *  Role is re-read from the session (set only at login from the DB row), never
 *  trusted from a request parameter, cookie value the client can edit, or hidden field. */
function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'admin') {
        audit_log($user['id'], $user['matric_no'], 'access_denied', $_SERVER['REQUEST_URI'] ?? null);
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
    return $user;
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

<?php
require __DIR__ . '/../src/bootstrap.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email    = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    // Generic validation — reject obviously malformed input before touching the DB.
    if (!is_valid_email($email) || $password === '') {
        security_log('login_rejected_validation', ['email_len' => strlen($email)]);
        audit_log(null, $email !== '' ? substr($email, 0, 100) : 'unknown', 'validation_rejected', 'login');
        $error = 'Invalid email or password.'; // generic — never reveal which field failed
    } else {
        // find_user_by_email() uses a parameterized query (src/repository.php) —
        // $email is bound as data, so it can never change the query's meaning
        // even if it contains SQL metacharacters like ' OR '1'='1.
        $user = find_user_by_email($email);

        $locked = $user && !empty($user['locked_until']) && strtotime($user['locked_until']) > time();

        if ($locked) {
            security_log('login_blocked_lockout', ['email' => $email]);
            $error = 'Invalid email or password.'; // still generic; don't reveal lockout state
        } elseif ($user && password_verify($password, $user['password_hash'])) {
            reset_failed_login((int) $user['id']);
            regenerate_session_on_login($user); // fixes session fixation
            audit_log((int) $user['id'], $user['matric_no'], 'login_success');
            security_log('login_success', ['user_id' => $user['id']]);
            header('Location: /index.php');
            exit;
        } else {
            if ($user) {
                record_failed_login($email); // rate-limiting / lockout counter
            }
            audit_log($user['id'] ?? null, $user['matric_no'] ?? substr($email, 0, 100), 'login_failed');
            security_log('login_failed', ['email' => $email]);
            $error = 'Invalid email or password.';
            usleep(300000); // slow brute-force guessing slightly without a visible delay message
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Login</title><link rel="stylesheet" href="/assets/style.css"></head>
<body>
<h1>Student Registration Portal — Login</h1>
<?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
<form method="post" action="/login.php" autocomplete="off">
    <?= csrf_field() ?>
    <label for="email">Email</label>
    <input type="email" id="email" name="email" required maxlength="150">
    <label for="password">Password</label>
    <input type="password" id="password" name="password" required maxlength="128">
    <button type="submit">Log in</button>
</form>
<p><a href="/register.php">Create an account</a></p>
</body>
</html>

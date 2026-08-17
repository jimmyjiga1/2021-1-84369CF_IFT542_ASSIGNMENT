<?php
require __DIR__ . '/../src/bootstrap.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $matric   = trim((string) ($_POST['matric_no'] ?? ''));
    $name     = trim((string) ($_POST['full_name'] ?? ''));
    $email    = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($matric === '' || strlen($matric) > 20) {
        $error = 'Enter a valid matric number.';
    } elseif (!is_valid_full_name($name)) {
        $error = 'Enter a valid full name.';
    } elseif (!is_valid_email($email)) {
        $error = 'Enter a valid email address.';
    } elseif (!is_valid_password($password)) {
        $error = 'Password must be 8-128 characters.';
    } else {
        if (find_user_by_email($email)) {
            $error = 'That email is already registered.';
        } else {
            $pdo = get_pdo();
            $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
            $stmt = $pdo->prepare(
                'INSERT INTO users (matric_no, full_name, email, password_hash, role) VALUES (:m, :n, :e, :p, "student")'
            );
            $stmt->execute([
                ':m' => $matric,
                ':n' => $name,
                ':e' => $email,
                ':p' => password_hash($password, $algo), // never store plaintext or a fast hash
            ]);
            audit_log((int) $pdo->lastInsertId(), $matric, 'account_created');
            $success = 'Account created. You can now log in.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Register</title><link rel="stylesheet" href="/assets/style.css"></head>
<body>
<h1>Create Student Account</h1>
<?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
<?php if ($success): ?><p class="success"><?= e($success) ?> <a href="/login.php">Log in</a></p><?php endif; ?>
<?php if (!$success): ?>
<form method="post" action="/register.php" autocomplete="off">
    <?= csrf_field() ?>
    <label for="matric_no">Matric Number</label>
    <input id="matric_no" name="matric_no" required maxlength="20" value="<?= e($_POST['matric_no'] ?? '') ?>">
    <label for="full_name">Full Name</label>
    <input id="full_name" name="full_name" required maxlength="100" value="<?= e($_POST['full_name'] ?? '') ?>">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" required maxlength="150" value="<?= e($_POST['email'] ?? '') ?>">
    <label for="password">Password</label>
    <input type="password" id="password" name="password" required maxlength="128" minlength="8">
    <button type="submit">Register</button>
</form>
<?php endif; ?>
</body>
</html>

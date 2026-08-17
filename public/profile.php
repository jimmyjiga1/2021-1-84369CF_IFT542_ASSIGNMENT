<?php
require __DIR__ . '/../src/bootstrap.php';
$user = require_login();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    if (!is_valid_full_name($fullName)) {
        audit_log((int) $user['id'], $user['matric_no'], 'validation_rejected', 'profile.full_name');
        $error = 'Name must be 2-100 letters/spaces/hyphens only.';
    } else {
        update_profile((int) $user['id'], $fullName);
        audit_log((int) $user['id'], $user['matric_no'], 'profile_updated');
        $user['full_name'] = $fullName;
        $success = 'Profile updated.';
    }
}
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Profile</title><link rel="stylesheet" href="/assets/style.css"></head>
<body>
<nav><a href="/index.php">Dashboard</a></nav>
<h1>Edit Profile</h1>
<?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
<?php if ($success): ?><p class="success"><?= e($success) ?></p><?php endif; ?>

<!--
  TASK 3 XSS FIX: $user['full_name'] is user-controlled (set via the form below) and is
  rendered here on every future page load. It is passed through e() -> htmlspecialchars()
  with ENT_QUOTES before being echoed, so a value like <script>alert(1)</script> is emitted
  as literal text, not executed. This is "contextual output encoding" for the HTML-body
  context. The restrictive CSP in src/security_headers.php (script-src 'self', no
  'unsafe-inline') is a second, independent layer in case an encoding gap is ever missed.
-->
<p>Current display name: <strong><?= e($user['full_name']) ?></strong></p>

<form method="post" action="/profile.php">
    <?= csrf_field() ?>
    <label for="full_name">Full Name</label>
    <input id="full_name" name="full_name" required maxlength="100" value="<?= e($user['full_name']) ?>">
    <button type="submit">Save</button>
</form>
</body>
</html>

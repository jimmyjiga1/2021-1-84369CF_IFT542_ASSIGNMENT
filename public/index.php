<?php
require __DIR__ . '/../src/bootstrap.php';
$user = require_login();
$enrolments = list_enrolments_for_user((int) $user['id']);
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Dashboard</title><link rel="stylesheet" href="/assets/style.css"></head>
<body>
<nav>
    <a href="/index.php">Dashboard</a>
    <a href="/profile.php">Profile</a>
    <a href="/courses.php">Courses</a>
    <a href="/upload.php">Documents</a>
    <?php if ($user['role'] === 'admin'): ?><a href="/admin/index.php">Admin</a><?php endif; ?>
    <a href="/logout.php">Log out</a>
</nav>
<h1>Welcome, <?= e($user['full_name']) ?></h1>
<p>Matric No: <?= e($user['matric_no']) ?> | Role: <?= e($user['role']) ?></p>

<h2>Your Active Enrolments</h2>
<?php if (!$enrolments): ?>
    <p>No active enrolments yet. <a href="/courses.php">Browse courses</a>.</p>
<?php else: ?>
<table>
<tr><th>Code</th><th>Title</th></tr>
<?php foreach ($enrolments as $en): ?>
    <tr><td><?= e($en['code']) ?></td><td><?= e($en['title']) ?></td></tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</body>
</html>

<?php
require __DIR__ . '/../../src/bootstrap.php';
$admin = require_admin(); // server-side role re-check, fixes Task 3 elevation-of-privilege threat
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Admin</title><link rel="stylesheet" href="/assets/style.css"></head>
<body>
<nav><a href="/index.php">Dashboard</a></nav>
<h1>Admin Panel</h1>
<p>Signed in as <?= e($admin['full_name']) ?> (admin).</p>
<ul>
    <li><a href="/admin/courses.php">Manage Courses</a></li>
    <li><a href="/admin/enrolments.php">Manage Enrolments</a></li>
</ul>
</body>
</html>

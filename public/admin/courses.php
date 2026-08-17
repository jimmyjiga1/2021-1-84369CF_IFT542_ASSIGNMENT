<?php
require __DIR__ . '/../../src/bootstrap.php';
$admin = require_admin();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $code = trim((string) ($_POST['code'] ?? ''));
    $title = trim((string) ($_POST['title'] ?? ''));
    $capacity = filter_var($_POST['capacity'] ?? '', FILTER_VALIDATE_INT);
    $url = trim((string) ($_POST['resource_url'] ?? '')) ?: null;

    if ($code === '' || strlen($code) > 20 || $title === '' || strlen($title) > 150 || $capacity === false || $capacity <= 0) {
        audit_log((int) $admin['id'], $admin['matric_no'], 'validation_rejected', 'admin.courses');
        $error = 'Check the course fields and try again.';
    } elseif ($url !== null && !filter_var($url, FILTER_VALIDATE_URL)) {
        $error = 'Resource URL must be a valid URL.';
    } else {
        admin_create_course($code, $title, (int) $capacity, $url);
        audit_log((int) $admin['id'], $admin['matric_no'], 'course_created', $code);
        $success = "Course {$code} created.";
    }
}
$courses = list_courses();
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Manage Courses</title><link rel="stylesheet" href="/assets/style.css"></head>
<body>
<nav><a href="/admin/index.php">Admin Home</a></nav>
<h1>Manage Courses</h1>
<?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
<?php if ($success): ?><p class="success"><?= e($success) ?></p><?php endif; ?>
<form method="post" action="/admin/courses.php">
    <?= csrf_field() ?>
    <label for="code">Code</label><input id="code" name="code" required maxlength="20">
    <label for="title">Title</label><input id="title" name="title" required maxlength="150">
    <label for="capacity">Capacity</label><input id="capacity" name="capacity" type="number" min="1" required>
    <label for="resource_url">Resource URL (optional, https only)</label><input id="resource_url" name="resource_url" type="url">
    <button type="submit">Create Course</button>
</form>
<table>
<tr><th>Code</th><th>Title</th><th>Capacity</th></tr>
<?php foreach ($courses as $c): ?>
<tr><td><?= e($c['code']) ?></td><td><?= e($c['title']) ?></td><td><?= (int) $c['capacity'] ?></td></tr>
<?php endforeach; ?>
</table>
</body>
</html>

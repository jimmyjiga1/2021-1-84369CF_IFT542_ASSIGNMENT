<?php
require __DIR__ . '/../src/bootstrap.php';
$user = require_login();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $courseId = $_POST['course_id'] ?? '';
    if (!is_valid_course_id($courseId)) {
        audit_log((int) $user['id'], $user['matric_no'], 'validation_rejected', 'courses.course_id');
        $message = 'Invalid course selection.';
    } else {
        $course = find_course((int) $courseId);
        if (!$course) {
            $message = 'Course not found.';
        } else {
            // TASK 2/3 IDOR FIX: the enrolling user is always (int) $user['id'] taken from the
            // server-side session — never from a request field — so a client cannot register
            // a different student by editing a hidden "user_id" parameter.
            $created = enrol_user((int) $user['id'], (int) $courseId);
            audit_log((int) $user['id'], $user['matric_no'], $created ? 'enrolment_created' : 'enrolment_duplicate', $course['code']);
            $message = $created ? "Registered for {$course['code']}." : 'Already registered for that course.';
        }
    }
}

$courses = list_courses();
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Courses</title><link rel="stylesheet" href="/assets/style.css"></head>
<body>
<nav><a href="/index.php">Dashboard</a></nav>
<h1>Available Courses</h1>
<?php if ($message): ?><p class="success"><?= e($message) ?></p><?php endif; ?>
<table>
<tr><th>Code</th><th>Title</th><th>Capacity</th><th>Resource</th><th></th></tr>
<?php foreach ($courses as $c): ?>
<tr>
    <td><?= e($c['code']) ?></td>
    <td><?= e($c['title']) ?></td>
    <td><?= (int) $c['capacity'] ?></td>
    <td><?php if ($c['resource_url']): ?><a href="/url_preview.php?course_id=<?= (int) $c['id'] ?>">Preview syllabus</a><?php endif; ?></td>
    <td>
        <form method="post" action="/courses.php" style="display:inline">
            <?= csrf_field() ?>
            <input type="hidden" name="course_id" value="<?= (int) $c['id'] ?>">
            <button type="submit">Register</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</table>
</body>
</html>

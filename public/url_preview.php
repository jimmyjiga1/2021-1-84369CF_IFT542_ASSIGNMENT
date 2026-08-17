<?php
require __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/safe_fetch.php';
$user = require_login();

$courseId = $_GET['course_id'] ?? '';
$preview = '';
$error = '';

if (!is_valid_course_id($courseId)) {
    http_response_code(400);
    exit('Invalid course id.');
}

$course = find_course((int) $courseId);
if (!$course || !$course['resource_url']) {
    http_response_code(404);
    exit('No resource configured for this course.');
}

try {
    // TASK 3 SSRF FIX: see src/safe_fetch.php — scheme + hostname allowlist, blocks
    // loopback/private/link-local/metadata IPs even after DNS resolution, no redirects.
    $raw = safe_fetch_preview($course['resource_url']);
    $preview = substr($raw, 0, 2000); // cap what we render
    audit_log((int) $user['id'], $user['matric_no'], 'url_preview_fetched', $course['code']);
} catch (SsrfBlockedException $ex) {
    $error = 'This resource could not be previewed.';
    audit_log((int) $user['id'], $user['matric_no'], 'ssrf_blocked', $course['code'], $ex->getMessage());
    security_log('ssrf_blocked', ['course' => $course['code'], 'reason' => $ex->getMessage()]);
}
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Resource Preview</title><link rel="stylesheet" href="/assets/style.css"></head>
<body>
<nav><a href="/courses.php">Back to courses</a></nav>
<h1>Preview: <?= e($course['code']) ?></h1>
<?php if ($error): ?>
    <p class="error"><?= e($error) ?></p>
<?php else: ?>
    <pre style="white-space: pre-wrap; word-break: break-word;"><?= e($preview) ?></pre>
<?php endif; ?>
</body>
</html>

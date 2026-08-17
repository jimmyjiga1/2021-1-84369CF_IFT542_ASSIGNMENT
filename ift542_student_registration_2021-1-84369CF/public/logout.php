<?php
require __DIR__ . '/../src/bootstrap.php';
$user = current_user();
if ($user) {
    audit_log((int) $user['id'], $user['matric_no'], 'logout');
}
logout();
header('Location: /login.php');
exit;

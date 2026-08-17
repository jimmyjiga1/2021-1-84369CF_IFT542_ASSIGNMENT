<?php
require __DIR__ . '/../src/bootstrap.php';
$user = require_login();
$error = '';
$success = '';

const MAX_UPLOAD_BYTES = 2 * 1024 * 1024; // 2MB per file — Task 3 DoS mitigation
const ALLOWED_MIME = ['application/pdf' => 'pdf', 'image/png' => 'png', 'image/jpeg' => 'jpg'];
const MAX_FILES_PER_USER = 10; // simple per-user quota, also DoS mitigation

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $existing = list_documents_for_user((int) $user['id']);
    if (count($existing) >= MAX_FILES_PER_USER) {
        $error = 'Upload limit reached for this account.';
    } elseif (empty($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Upload failed. Choose a file and try again.';
    } else {
        $file = $_FILES['document'];

        if ($file['size'] > MAX_UPLOAD_BYTES) {
            $error = 'File exceeds the 2MB limit.';
        } else {
            // Trust the actual file bytes, not the client-supplied MIME/extension.
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $detectedMime = $finfo->file($file['tmp_name']);

            if (!isset(ALLOWED_MIME[$detectedMime])) {
                audit_log((int) $user['id'], $user['matric_no'], 'validation_rejected', 'upload.mime', $detectedMime);
                $error = 'Only PDF, PNG or JPEG files are accepted.';
            } else {
                $ext = ALLOWED_MIME[$detectedMime];
                // Random stored filename — never trust/reuse the client-supplied name for the path.
                $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
                $destDir = __DIR__ . '/../storage/uploads/';
                $dest = $destDir . $storedName;

                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    save_document(
                        (int) $user['id'],
                        substr(basename($file['name']), 0, 255),
                        $storedName,
                        $detectedMime,
                        (int) $file['size']
                    );
                    audit_log((int) $user['id'], $user['matric_no'], 'document_uploaded', $storedName);
                    $success = 'Document uploaded.';
                } else {
                    $error = 'Could not save the uploaded file.';
                }
            }
        }
    }
}

$documents = list_documents_for_user((int) $user['id']);
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Documents</title><link rel="stylesheet" href="/assets/style.css"></head>
<body>
<nav><a href="/index.php">Dashboard</a></nav>
<h1>Upload Document</h1>
<?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
<?php if ($success): ?><p class="success"><?= e($success) ?></p><?php endif; ?>
<form method="post" action="/upload.php" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <label for="document">PDF, PNG or JPEG, max 2MB</label>
    <input type="file" id="document" name="document" accept=".pdf,.png,.jpg,.jpeg" required>
    <button type="submit">Upload</button>
</form>

<h2>Your Documents</h2>
<table>
<tr><th>Name</th><th>Type</th><th>Size</th><th>Uploaded</th></tr>
<?php foreach ($documents as $d): ?>
<tr>
    <td><?= e($d['original_name']) ?></td>
    <td><?= e($d['mime_type']) ?></td>
    <td><?= (int) $d['size_bytes'] ?> bytes</td>
    <td><?= e($d['created_at']) ?></td>
</tr>
<?php endforeach; ?>
</table>
</body>
</html>

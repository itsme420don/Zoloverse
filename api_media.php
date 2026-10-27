<?php
/**
 * api_media.php — Media Library API for Zoloverse.
 * Handles safe file uploads, image previews, and asset management for administrators.
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? $_REQUEST['action'] ?? '';
if (($method === 'POST' || $method === 'GET') && $action === 'delete') {
    $method = 'DELETE';
}
if ($method === 'POST' && ($_POST['_method'] ?? '') === 'DELETE') {
    $method = 'DELETE';
}
if ($method === 'POST') {
    $dataCheck = read_json_body();
    if ($dataCheck && !empty($dataCheck->action) && $dataCheck->action === 'delete') {
        $method = 'DELETE';
    }
}

switch ($method) {
    case 'GET':
        handleGet();
        break;
    case 'POST':
        handlePost();
        break;
    case 'DELETE':
        handleDelete();
        break;
    default:
        json_error('Method Not Allowed', 405);
}

function handlePost(): void
{
    $admin = requireAuth(true);

    if (empty($_FILES['file'])) {
        json_error('No file was uploaded.');
    }

    $file = $_FILES['file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        json_error('File upload failed with error code: ' . $file['error']);
    }

    // Limit size to 5MB
    $maxBytes = 5 * 1024 * 1024;
    if ($file['size'] > $maxBytes) {
        json_error('File exceeds the maximum permitted size of 5 MB.');
    }

    // Validate MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    if (!isset($allowedMimes[$mime])) {
        json_error('Only JPG, PNG, WEBP, and GIF images are allowed. Detected: ' . htmlspecialchars($mime));
    }

    $ext = $allowedMimes[$mime];
    $safeName = 'zolo_' . time() . '_' . substr(bin2hex(random_bytes(4)), 0, 8) . '.' . $ext;
    $targetDir = __DIR__ . '/uploads';

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $targetPath = $targetDir . '/' . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        json_error('Failed to save the uploaded image to storage.');
    }

    $publicUrl = 'uploads/' . $safeName;
    logAudit('UPLOAD_MEDIA', "Uploaded media file {$safeName}", (int)$admin->id);

    json_success('Image uploaded successfully.', [
        'filename' => $safeName,
        'url' => $publicUrl,
        'size' => $file['size']
    ]);
}

function handleGet(): void
{
    requireAuth(true);
    $targetDir = __DIR__ . '/uploads';
    $files = [];

    if (is_dir($targetDir)) {
        $scan = scandir($targetDir);
        foreach ($scan as $f) {
            if ($f === '.' || $f === '..' || $f === '.htaccess') {
                continue;
            }
            $fullPath = $targetDir . '/' . $f;
            if (is_file($fullPath)) {
                $files[] = [
                    'name' => $f,
                    'url' => 'uploads/' . $f,
                    'size' => filesize($fullPath),
                    'modified_at' => date('Y-m-d H:i:s', filemtime($fullPath))
                ];
            }
        }
    }

    // Sort by modification date descending
    usort($files, fn($a, $b) => strcmp($b['modified_at'], $a['modified_at']));

    json_success('Media files retrieved', ['media' => $files]);
}

function handleDelete(): void
{
    $admin = requireAuth(true);
    $data = read_json_body();
    $filename = '';

    if ($data && !empty($data->filename)) {
        $filename = basename(trim((string)$data->filename));
    } elseif (!empty($_GET['filename'])) {
        $filename = basename(trim((string)$_GET['filename']));
    }

    if ($filename === '' || $filename === '.htaccess') {
        json_error('Valid filename is required.');
    }

    $targetPath = __DIR__ . '/uploads/' . $filename;
    if (file_exists($targetPath)) {
        unlink($targetPath);
        logAudit('DELETE_MEDIA', "Deleted media file {$filename}", (int)$admin->id);
        json_success("File {$filename} removed.");
    } else {
        json_error('File not found.', 404);
    }
}

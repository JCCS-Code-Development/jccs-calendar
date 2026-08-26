<?php
ini_set('display_errors', 0);
set_exception_handler(function ($e) { http_response_code(500); echo json_encode(['error' => $e->getMessage()]); exit; });
set_error_handler(function ($s, $m, $f, $l) { throw new ErrorException($m, 0, $s, $f, $l); });

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../middleware/auth.php';

// Reference photo for a job (Carpentry Production Calendar). One photo per
// job — uploading a new one replaces whatever was there. Stored under
// api/uploads/jobs/ (inside the api/ tree so the existing .cpanel.yml
// deploy step, which copies api/. recursively, needs no extra wiring, and
// the local PHP dev server can serve them as plain static files). Mirrors
// jccs-inventory/api/items/image.php.
const JOB_UPLOAD_DIR   = __DIR__ . '/../uploads/jobs';
const MAX_UPLOAD_BYTES = 8 * 1024 * 1024; // 8MB backstop
const ALLOWED_MIME = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

$auth = requireAuth();
requireManageEvents($auth);
$pdo    = getPDO();
$method = $_SERVER['REQUEST_METHOD'];

function jobPhotoPath(int $jobId): ?string {
    foreach (glob(JOB_UPLOAD_DIR . "/{$jobId}.*") as $existing) {
        return $existing;
    }
    return null;
}

if ($method === 'POST') {
    $jobId = isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0;
    if (!$jobId) { http_response_code(422); exit(json_encode(['error' => 'Missing job_id'])); }

    $stmt = $pdo->prepare('SELECT id FROM jobs WHERE id = ?');
    $stmt->execute([$jobId]);
    if (!$stmt->fetch()) { http_response_code(404); exit(json_encode(['error' => 'Job not found'])); }

    if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(422); exit(json_encode(['error' => 'No image uploaded']));
    }
    $file = $_FILES['image'];
    if ($file['size'] > MAX_UPLOAD_BYTES) {
        http_response_code(422); exit(json_encode(['error' => 'Image is too large (8MB max)']));
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    if (!isset(ALLOWED_MIME[$mime])) {
        http_response_code(422); exit(json_encode(['error' => 'Image must be JPEG, PNG, or WebP']));
    }
    $ext = ALLOWED_MIME[$mime];

    if (!is_dir(JOB_UPLOAD_DIR)) { mkdir(JOB_UPLOAD_DIR, 0755, true); }
    if ($old = jobPhotoPath($jobId)) { @unlink($old); }

    $filename = "{$jobId}.{$ext}";
    if (!move_uploaded_file($file['tmp_name'], JOB_UPLOAD_DIR . '/' . $filename)) {
        http_response_code(500); exit(json_encode(['error' => 'Could not save the image']));
    }

    $photoPath = "jobs/{$filename}";
    $pdo->prepare('UPDATE jobs SET photo_path = ? WHERE id = ?')->execute([$photoPath, $jobId]);

    echo json_encode(['message' => 'Photo saved', 'photo_url' => APP_URL . '/uploads/' . $photoPath]);

} elseif ($method === 'DELETE') {
    $jobId = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
    if (!$jobId) { http_response_code(422); exit(json_encode(['error' => 'Missing job_id'])); }

    if ($existing = jobPhotoPath($jobId)) { @unlink($existing); }
    $pdo->prepare('UPDATE jobs SET photo_path = NULL WHERE id = ?')->execute([$jobId]);
    echo json_encode(['message' => 'Photo removed']);

} else {
    http_response_code(405);
}

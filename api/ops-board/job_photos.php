<?php
// POST   /api/ops-board/jobs/{id}/photos    multipart: image=<file>, caption?=<text>
// DELETE /api/ops-board/jobs/{id}/photos?photo_id=NN
//
// Job-site photos for the Operations Board — shown as thumbnails on the TV
// cards and opened full-screen from there. PIN-gated (managed from Edit
// Mode). One file per POST; the Edit Mode uploader loops for multi-select.
//
// Files land under api/uploads/jobs/board/ — inside the api/ tree so the
// existing .cpanel.yml deploy step (which copies api/. recursively) needs no
// extra wiring, and the local PHP dev server serves them as plain static
// files. Mirrors api/jobs/photo.php, but many-per-job in its own table.
ini_set('display_errors', 0);
set_exception_handler(function ($e) { http_response_code(500); echo json_encode(['error' => $e->getMessage()]); exit; });
set_error_handler(function ($s, $m, $f, $l) { throw new ErrorException($m, 0, $s, $f, $l); });

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/_helpers.php';

const BOARD_PHOTO_DIR    = __DIR__ . '/../uploads/jobs/board';
const BOARD_PHOTO_MAX    = 15 * 1024 * 1024;   // 15 MB — straight-off-the-phone photos
const BOARD_PHOTO_PERJOB = 12;
const BOARD_PHOTO_MIME   = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

$method = $_SERVER['REQUEST_METHOD'];
$id     = (int)($_GET['id'] ?? 0);
if (!$id) { http_response_code(422); exit(json_encode(['error' => 'Missing job id'])); }
requireBoardPin();

$pdo = getPDO();
$jobStmt = $pdo->prepare('SELECT id, title FROM jobs WHERE id = ?');
$jobStmt->execute([$id]);
$jobRow = $jobStmt->fetch();
if (!$jobRow) { http_response_code(404); exit(json_encode(['error' => 'Trabajo no encontrado'])); }

function reloadJob(PDO $pdo, int $id): array {
    $s = $pdo->prepare(BOARD_JOB_SELECT . ' WHERE j.id = ?');
    $s->execute([$id]);
    return formatBoardJob($pdo, $s->fetch());
}

if ($method === 'POST') {
    if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(422); exit(json_encode(['error' => 'No se recibió ninguna imagen']));
    }
    $file = $_FILES['image'];
    if ($file['size'] > BOARD_PHOTO_MAX) {
        http_response_code(422); exit(json_encode(['error' => 'La imagen supera el límite de 15 MB']));
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    if (!isset(BOARD_PHOTO_MIME[$mime])) {
        http_response_code(422); exit(json_encode(['error' => 'La imagen debe ser JPEG, PNG o WebP']));
    }
    $ext = BOARD_PHOTO_MIME[$mime];

    $agg = $pdo->prepare('SELECT COUNT(*) AS c, COALESCE(MAX(sort_order), 0) AS mx FROM job_photos WHERE job_id = ?');
    $agg->execute([$id]);
    $row = $agg->fetch();
    if ((int)$row['c'] >= BOARD_PHOTO_PERJOB) {
        http_response_code(422);
        exit(json_encode(['error' => 'Este trabajo ya tiene el máximo de fotos (' . BOARD_PHOTO_PERJOB . ')']));
    }

    if (!is_dir(BOARD_PHOTO_DIR)) { mkdir(BOARD_PHOTO_DIR, 0755, true); }
    $name = $id . '-' . bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], BOARD_PHOTO_DIR . '/' . $name)) {
        http_response_code(500); exit(json_encode(['error' => 'No se pudo guardar la imagen']));
    }

    $caption = isset($_POST['caption']) ? mb_substr(trim((string)$_POST['caption']), 0, 300) : '';
    $pdo->prepare(
        'INSERT INTO job_photos (job_id, file_path, caption, sort_order, uploaded_ip)
         VALUES (?,?,?,?,?)'
    )->execute([
        $id,
        'jobs/board/' . $name,
        $caption !== '' ? $caption : null,
        (int)$row['mx'] + 1,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);

    boardAudit($pdo, 'job.photo_add', 'job', $id, ($caption ?: $name) . " → {$jobRow['title']}");
    http_response_code(201);
    echo json_encode(reloadJob($pdo, $id));
    exit;
}

if ($method === 'DELETE') {
    $photoId = (int)($_GET['photo_id'] ?? 0);
    if ($photoId <= 0) { http_response_code(422); exit(json_encode(['error' => 'Missing photo_id'])); }

    $ph = $pdo->prepare('SELECT id, file_path FROM job_photos WHERE id = ? AND job_id = ?');
    $ph->execute([$photoId, $id]);
    $prow = $ph->fetch();
    if (!$prow) { http_response_code(404); exit(json_encode(['error' => 'Foto no encontrada'])); }

    // file_path is always written by this file as "jobs/board/<hex>.<ext>";
    // basename() is a belt-and-braces guard against a doctored row.
    $target = BOARD_PHOTO_DIR . '/' . basename($prow['file_path']);
    if (is_file($target)) { @unlink($target); }
    $pdo->prepare('DELETE FROM job_photos WHERE id = ?')->execute([$photoId]);

    boardAudit($pdo, 'job.photo_remove', 'job', $id, "photo $photoId ✕ {$jobRow['title']}");
    echo json_encode(reloadJob($pdo, $id));
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

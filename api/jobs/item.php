<?php
ini_set('display_errors', 0);
set_exception_handler(function ($e) { http_response_code(500); echo json_encode(['error' => $e->getMessage()]); exit; });
set_error_handler(function ($s, $m, $f, $l) { throw new ErrorException($m, 0, $s, $f, $l); });

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../middleware/validate.php';
require_once __DIR__ . '/_helpers.php';

$auth   = requireAuth();
$pdo    = getPDO();
$method = $_SERVER['REQUEST_METHOD'];
$id     = (int)($_GET['id'] ?? 0);
if (!$id) { http_response_code(422); exit(json_encode(['error' => 'Missing id'])); }

$stmt = $pdo->prepare(JOB_SELECT . ' WHERE j.id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) { http_response_code(404); exit(json_encode(['error' => 'Job not found'])); }

if ($method === 'GET') {
    echo json_encode(formatJob($pdo, $row));

} elseif ($method === 'PUT') {
    requireManageEvents($auth);
    $body   = jsonBody();
    $allowed = ['client_id', 'title', 'estimate_number', 'address', 'scope', 'projected_start', 'projected_end', 'status', 'color', 'lead_time_days'];
    $set    = [];
    $params = [];
    foreach ($allowed as $col) {
        if (!array_key_exists($col, $body)) continue;
        $val = $body[$col];
        if ($val === '') $val = null;
        if (in_array($col, ['client_id', 'lead_time_days'], true) && $val !== null) $val = (int)$val;
        if ($col === 'title') $val = sanitizeString($val);
        $set[] = "$col = ?";
        $params[] = $val;
    }
    if ($set) {
        $params[] = $id;
        $pdo->prepare('UPDATE jobs SET ' . implode(', ', $set) . ' WHERE id = ?')->execute($params);
    }
    if (array_key_exists('worker_ids', $body)) {
        syncJobWorkers($pdo, $id, $body['worker_ids'] ?? []);
    }

    $stmt = $pdo->prepare(JOB_SELECT . ' WHERE j.id = ?');
    $stmt->execute([$id]);
    echo json_encode(formatJob($pdo, $stmt->fetch()));

} elseif ($method === 'DELETE') {
    requireManageEvents($auth);
    if (!empty($row['photo_path'])) {
        @unlink(__DIR__ . '/../uploads/' . $row['photo_path']);
    }
    $pdo->prepare('DELETE FROM jobs WHERE id = ?')->execute([$id]);
    echo json_encode(['message' => 'Deleted']);

} else {
    http_response_code(405);
}

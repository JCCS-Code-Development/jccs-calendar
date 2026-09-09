<?php
// POST   /api/ops-board/jobs/{id}/crew   body {worker_id, worker_name?}
// DELETE /api/ops-board/jobs/{id}/crew?worker_id=NN
//
// Add / remove one crew member on a job. This is the ONE board write that is
// NOT PIN-gated: it backs the drag-and-drop of names onto job cards on the
// main (read-only) board, per the office's request. It is deliberately
// narrow — it only ever touches job_workers, never any other job field, and
// every change is written to ops_board_audit.
ini_set('display_errors', 0);
set_exception_handler(function ($e) { http_response_code(500); echo json_encode(['error' => $e->getMessage()]); exit; });
set_error_handler(function ($s, $m, $f, $l) { throw new ErrorException($m, 0, $s, $f, $l); });

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/validate.php';
require_once __DIR__ . '/_helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = (int)($_GET['id'] ?? 0);
if (!$id) { http_response_code(422); exit(json_encode(['error' => 'Missing job id'])); }

$pdo = getPDO();
$job = $pdo->prepare('SELECT id, title FROM jobs WHERE id = ?');
$job->execute([$id]);
$jobRow = $job->fetch();
if (!$jobRow) { http_response_code(404); exit(json_encode(['error' => 'Trabajo no encontrado'])); }

function reloadJob(PDO $pdo, int $id): array {
    $s = $pdo->prepare(BOARD_JOB_SELECT . ' WHERE j.id = ?');
    $s->execute([$id]);
    return formatBoardJob($pdo, $s->fetch());
}

if ($method === 'POST') {
    $body     = jsonBody();
    $workerId = (int)($body['worker_id'] ?? 0);
    $name     = isset($body['worker_name']) ? mb_substr(trim((string)$body['worker_name']), 0, 150) : null;
    if ($workerId <= 0) { http_response_code(422); exit(json_encode(['error' => 'Missing worker_id'])); }

    $pdo->prepare(
        'INSERT INTO job_workers (job_id, fieldclock_user_id, name_cache)
         VALUES (?,?,?)
         ON DUPLICATE KEY UPDATE name_cache = COALESCE(VALUES(name_cache), name_cache)'
    )->execute([$id, $workerId, $name]);

    boardAudit($pdo, 'job.crew_add', 'job', $id, ($name ?: "user $workerId") . " → {$jobRow['title']}");
    echo json_encode(reloadJob($pdo, $id));
    exit;
}

if ($method === 'DELETE') {
    $workerId = (int)($_GET['worker_id'] ?? 0);
    if ($workerId <= 0) { http_response_code(422); exit(json_encode(['error' => 'Missing worker_id'])); }
    $pdo->prepare('DELETE FROM job_workers WHERE job_id = ? AND fieldclock_user_id = ?')->execute([$id, $workerId]);
    boardAudit($pdo, 'job.crew_remove', 'job', $id, "user $workerId ✕ {$jobRow['title']}");
    echo json_encode(reloadJob($pdo, $id));
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

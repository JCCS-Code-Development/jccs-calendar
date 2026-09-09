<?php
// POST /api/ops-board/jobs  — office adds a job to the board. PIN-gated.
// A brand-new job with no PO and no confirmed schedule lands in
// "Awaiting PO / Needs Scheduling".
ini_set('display_errors', 0);
set_exception_handler(function ($e) { http_response_code(500); echo json_encode(['error' => $e->getMessage()]); exit; });
set_error_handler(function ($s, $m, $f, $l) { throw new ErrorException($m, 0, $s, $f, $l); });

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/validate.php';
require_once __DIR__ . '/../middleware/auth.php';   // userColor()
require_once __DIR__ . '/_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit(json_encode(['error' => 'Method not allowed'])); }
requireBoardPin();

$pdo  = getPDO();
$body = jsonBody();

$data = boardJobWritable($body, isCreate: true);
if ($data['title'] === '') { http_response_code(422); exit(json_encode(['error' => 'El nombre del trabajo es obligatorio'])); }

$clientId = boardResolveClientId($pdo, $body['client_id'] ?? null, $body['client_name'] ?? null);
$data['date_received'] = $data['date_received'] ?: boardToday()->format('Y-m-d');

$cols   = array_keys($data);
$place  = implode(',', array_fill(0, count($cols) + 2, '?'));
$sql    = 'INSERT INTO jobs (' . implode(',', $cols) . ', client_id, color) VALUES (' . $place . ')';

$nextId = (int)$pdo->query('SELECT COALESCE(MAX(id),0)+1 AS n FROM jobs')->fetch()['n'];
$params = array_values($data);
$params[] = $clientId;
$params[] = userColor($nextId);

$pdo->prepare($sql)->execute($params);
$id = (int)$pdo->lastInsertId();

if (!empty($body['worker_ids']) && is_array($body['worker_ids'])) {
    $ins = $pdo->prepare('INSERT IGNORE INTO job_workers (job_id, fieldclock_user_id) VALUES (?,?)');
    foreach (array_unique(array_map('intval', $body['worker_ids'])) as $wid) {
        if ($wid > 0) $ins->execute([$id, $wid]);
    }
}

boardAudit($pdo, 'job.create', 'job', $id, $data['title'] . ($clientId ? '' : ''));

$stmt = $pdo->prepare(BOARD_JOB_SELECT . ' WHERE j.id = ?');
$stmt->execute([$id]);
http_response_code(201);
echo json_encode(formatBoardJob($pdo, $stmt->fetch()));

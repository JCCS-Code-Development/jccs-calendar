<?php
// PUT    /api/ops-board/jobs/{id}   — edit a job / advance it through the
//                                     PO + schedule workflow / mark complete.
// DELETE /api/ops-board/jobs/{id}   — archive (soft delete). Never hard-deletes.
// Both PIN-gated.
//
// Stale-edit guard: the client must send `expected_updated_at` (the
// updated_at it last saw). If the row moved on since, we return 409 with the
// fresh record instead of silently overwriting a newer change.
ini_set('display_errors', 0);
set_exception_handler(function ($e) { http_response_code(500); echo json_encode(['error' => $e->getMessage()]); exit; });
set_error_handler(function ($s, $m, $f, $l) { throw new ErrorException($m, 0, $s, $f, $l); });

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/validate.php';
require_once __DIR__ . '/_helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = (int)($_GET['id'] ?? 0);
if (!$id) { http_response_code(422); exit(json_encode(['error' => 'Missing id'])); }
requireBoardPin();

$pdo = getPDO();
$stmt = $pdo->prepare(BOARD_JOB_SELECT . ' WHERE j.id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) { http_response_code(404); exit(json_encode(['error' => 'Trabajo no encontrado'])); }

function reloadJob(PDO $pdo, int $id): array {
    $s = $pdo->prepare(BOARD_JOB_SELECT . ' WHERE j.id = ?');
    $s->execute([$id]);
    return formatBoardJob($pdo, $s->fetch());
}

$body = jsonBody();

// ── optimistic-lock check (skipped only for a bare archive with no expectation) ──
$expected = $body['expected_updated_at'] ?? null;
if ($expected !== null && $expected !== '' && $expected !== $row['updated_at']) {
    http_response_code(409);
    exit(json_encode([
        'error'   => 'Otra persona cambió este trabajo. Revisa la versión más reciente e inténtalo de nuevo.',
        'current' => reloadJob($pdo, $id),
    ]));
}

if ($method === 'DELETE') {
    $pdo->prepare('UPDATE jobs SET archived_at = NOW() WHERE id = ?')->execute([$id]);
    boardAudit($pdo, 'job.archive', 'job', $id, $row['title']);
    echo json_encode(['ok' => true, 'archived' => true]);
    exit;
}

if ($method !== 'PUT') { http_response_code(405); exit(json_encode(['error' => 'Method not allowed'])); }

$data = boardJobWritable($body, isCreate: false);
if (array_key_exists('title', $data) && $data['title'] === '') {
    http_response_code(422);
    exit(json_encode(['error' => 'El nombre del trabajo no puede estar vacío']));
}

// client can be moved via id or a typed name
if (array_key_exists('client_id', $body) || array_key_exists('client_name', $body)) {
    $data['client_id'] = boardResolveClientId($pdo, $body['client_id'] ?? null, $body['client_name'] ?? null);
}

// "unarchive" convenience
if (array_key_exists('archived_at', $body) && ($body['archived_at'] === null || $body['archived_at'] === '')) {
    $data['archived_at'] = null;
}

if ($data) {
    $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($data)));
    $params = array_values($data);
    $params[] = $id;
    $pdo->prepare("UPDATE jobs SET $set WHERE id = ?")->execute($params);
}

if (array_key_exists('worker_ids', $body) && is_array($body['worker_ids'])) {
    $pdo->prepare('DELETE FROM job_workers WHERE job_id = ?')->execute([$id]);
    $ins = $pdo->prepare('INSERT IGNORE INTO job_workers (job_id, fieldclock_user_id) VALUES (?,?)');
    foreach (array_unique(array_map('intval', $body['worker_ids'])) as $wid) {
        if ($wid > 0) $ins->execute([$id, $wid]);
    }
}

// human-readable audit summary
$changes = [];
foreach (['po_status', 'schedule_status', 'priority', 'status'] as $k) {
    if (array_key_exists($k, $data) && $data[$k] !== $row[$k]) $changes[] = "$k → {$data[$k]}";
}
boardAudit($pdo, 'job.update', 'job', $id, $changes ? implode('; ', $changes) : 'edited');

echo json_encode(reloadJob($pdo, $id));

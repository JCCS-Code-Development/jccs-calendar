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

$auth   = requireAuth(); // GET open to any role; writes gated below
$pdo    = getPDO();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query(JOB_SELECT . ' ORDER BY j.projected_end IS NULL, j.projected_end ASC');
    echo json_encode(array_map(fn($row) => formatJob($pdo, $row), $stmt->fetchAll()));

} elseif ($method === 'POST') {
    requireManageEvents($auth);
    $body = jsonBody();
    requireFields($body, ['title']);

    $stmt = $pdo->prepare(
        'INSERT INTO jobs (client_id, title, estimate_number, address, scope, projected_start, projected_end, status, color, lead_time_days)
         VALUES (?,?,?,?,?,?,?,?,?,?)'
    );
    $stmt->execute([
        !empty($body['client_id']) ? (int)$body['client_id'] : null,
        sanitizeString($body['title']),
        $body['estimate_number'] ?? null,
        $body['address'] ?? null,
        $body['scope'] ?? null,
        $body['projected_start'] ?? null,
        $body['projected_end'] ?? null,
        sanitizeString($body['status'] ?? 'Active'),
        $body['color'] ?? userColor((int)$pdo->query('SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM jobs')->fetch()['next_id']), // job form doesn't collect a color yet — cycle the same palette used for assignees
        isset($body['lead_time_days']) && $body['lead_time_days'] !== '' ? (int)$body['lead_time_days'] : null,
    ]);
    $id = (int)$pdo->lastInsertId();

    if (!empty($body['worker_ids'])) {
        syncJobWorkers($pdo, $id, $body['worker_ids']);
    }

    $stmt = $pdo->prepare(JOB_SELECT . ' WHERE j.id = ?');
    $stmt->execute([$id]);
    http_response_code(201);
    echo json_encode(formatJob($pdo, $stmt->fetch()));

} else {
    http_response_code(405);
}

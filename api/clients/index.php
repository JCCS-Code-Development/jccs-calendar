<?php
ini_set('display_errors', 0);
set_exception_handler(function ($e) { http_response_code(500); echo json_encode(['error' => $e->getMessage()]); exit; });
set_error_handler(function ($s, $m, $f, $l) { throw new ErrorException($m, 0, $s, $f, $l); });

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../middleware/validate.php';

$auth   = requireAuth(); // GET open to any role (JobTimelines is viewable by everyone); writes gated below
$pdo    = getPDO();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query('SELECT id, name FROM clients ORDER BY name');
    echo json_encode(array_map(fn($r) => ['id' => (int)$r['id'], 'name' => $r['name']], $stmt->fetchAll()));

} elseif ($method === 'POST') {
    requireManageEvents($auth); // Jobs/Clients share Jobs' Admin/Office management gate
    $body = jsonBody();
    requireFields($body, ['name']);
    $pdo->prepare('INSERT INTO clients (name) VALUES (?)')->execute([sanitizeString($body['name'])]);
    $id = (int)$pdo->lastInsertId();
    echo json_encode(['id' => $id, 'name' => sanitizeString($body['name'])]);

} else {
    http_response_code(405);
}

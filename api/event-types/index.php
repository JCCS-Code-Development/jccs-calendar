<?php
ini_set('display_errors', 0);
set_exception_handler(function ($e) { http_response_code(500); echo json_encode(['error' => $e->getMessage()]); exit; });
set_error_handler(function ($s, $m, $f, $l) { throw new ErrorException($m, 0, $s, $f, $l); });

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../middleware/auth.php';

$auth = requireAuth(); // any authenticated role
$pdo  = getPDO();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); exit; }

$stmt = $pdo->query('SELECT id, name FROM event_types ORDER BY name');
echo json_encode(array_map(fn($r) => ['id' => (int)$r['id'], 'name' => $r['name']], $stmt->fetchAll()));

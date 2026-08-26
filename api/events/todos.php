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

$auth = requireAuth();
requireViewAllEvents($auth); // matches v1: To-Do List is Admin/Office only
$pdo = getPDO();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); exit; }

// Same query as /events — the To-Do List is a rendering convention (Reminder
// type + is_all_day events shown as checkable items) over the same data, not
// a separate table. See schema.sql's comment on `is_all_day`.
[$filterSql, $params] = applyEventFilters();
$stmt = $pdo->prepare(EVENT_SELECT . ' WHERE 1=1' . $filterSql . ' ORDER BY e.start_datetime ASC');
$stmt->execute($params);
echo json_encode(array_map('formatEvent', $stmt->fetchAll()));

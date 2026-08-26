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

$auth = requireAuth(); // any authenticated role — hard-scoped to their own events below
$pdo  = getPDO();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); exit; }

// assigned_user_id filter from applyEventFilters() is meaningless here (this
// endpoint is always scoped to the caller), so build the query directly
// rather than reusing that helper's assigned_user_id branch.
[$rangeSql, $params] = eventDateRangeClause($_GET['date_range'] ?? 'today_tomorrow');
$sql = EVENT_SELECT . ' WHERE e.assigned_user_id = ? AND ' . $rangeSql;
array_unshift($params, $auth['user_id']);

if (!empty($_GET['event_type_id'])) { $sql .= ' AND e.event_type_id = ?'; $params[] = (int)$_GET['event_type_id']; }
if (!empty($_GET['status']))        { $sql .= ' AND e.status = ?';        $params[] = sanitizeString($_GET['status']); }

$sql .= ' ORDER BY e.start_datetime ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
echo json_encode(array_map('formatEvent', $stmt->fetchAll()));

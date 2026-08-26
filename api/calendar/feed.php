<?php
ini_set('display_errors', 0);
set_exception_handler(function ($e) { http_response_code(500); echo $e->getMessage(); exit; });
set_error_handler(function ($s, $m, $f, $l) { throw new ErrorException($m, 0, $s, $f, $l); });

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../middleware/validate.php';
require_once __DIR__ . '/../events/_helpers.php';
require_once __DIR__ . '/_ics_helpers.php';

$auth = requireAuthAllowQueryToken(); // webcal:// / plain <a> subscribe link, no custom headers — see auth.php
$pdo  = getPDO();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); exit; }

$sql    = EVENT_SELECT . ' WHERE 1=1';
$params = [];
if (!canViewAllEvents($auth)) {
    $sql .= ' AND e.assigned_user_id = ?';
    $params[] = $auth['user_id'];
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: inline; filename="calendar.ics"');
echo icsCalendarWrap(array_map('icsEventBlock', $stmt->fetchAll()));

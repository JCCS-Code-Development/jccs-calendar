<?php
ini_set('display_errors', 0);
set_exception_handler(function ($e) { http_response_code(500); echo $e->getMessage(); exit; });
set_error_handler(function ($s, $m, $f, $l) { throw new ErrorException($m, 0, $s, $f, $l); });

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../middleware/validate.php';
require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../calendar/_ics_helpers.php';

$auth = requireAuthAllowQueryToken(); // plain <a> download link, no custom headers — see auth.php
$pdo  = getPDO();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); exit; }

$id = (int)($_GET['id'] ?? 0);
if (!$id) { http_response_code(422); exit(json_encode(['error' => 'Missing id'])); }

$stmt = $pdo->prepare(EVENT_SELECT . ' WHERE e.id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) { http_response_code(404); exit(json_encode(['error' => 'Event not found'])); }

if (!canViewAllEvents($auth) && (int)$row['assigned_user_id'] !== $auth['user_id']) {
    http_response_code(403);
    exit(json_encode(['error' => 'Forbidden']));
}

$slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($row['title'])) ?: 'event';
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $slug . '.ics"');
echo icsCalendarWrap([icsEventBlock($row)]);

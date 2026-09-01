<?php
ini_set('display_errors', 0);
set_exception_handler(function ($e) { http_response_code(500); echo json_encode(['error' => $e->getMessage()]); exit; });
set_error_handler(function ($s, $m, $f, $l) { throw new ErrorException($m, 0, $s, $f, $l); });

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../middleware/validate.php';
require_once __DIR__ . '/_ics_parse.php';

// Every Calendar user who has an Outlook ICS link on their record, pulled and
// merged into one list — each event tagged with that person's id/name/colour
// and source:'outlook' so the home calendar overlays it exactly like an
// assigned JCCS event. Read-only. Admin/Office see everyone; Lead/Crew see
// only their own linked feed.
$auth = requireAuth();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); exit; }

$pdo = getPDO();
$sql = "SELECT fieldclock_user_id, name, outlook_ics_url
        FROM calendar_user_roles
        WHERE is_active = 1 AND outlook_ics_url IS NOT NULL AND outlook_ics_url <> ''";
$params = [];
if (!canViewAllEvents($auth)) {
    $sql .= ' AND fieldclock_user_id = ?';
    $params[] = $auth['user_id'];
}
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$force = isset($_GET['refresh']);
$out   = [];
foreach ($stmt->fetchAll() as $person) {
    $uid   = (int)$person['fieldclock_user_id'];
    $name  = $person['name'];
    $color = userColor($uid);
    try {
        $events = icsFetchAndParse($person['outlook_ics_url'], $force);
    } catch (RuntimeException $e) {
        continue; // one bad/unreachable feed shouldn't sink the rest
    }
    foreach ($events as $e) {
        $out[] = [
            'id'             => 'outlook-' . $uid . '-' . $e['id'],
            'title'          => $e['title'],
            'description'    => $e['description'],
            'location'       => $e['location'],
            'start_datetime' => $e['start_datetime'],
            'end_datetime'   => $e['end_datetime'],
            'status'         => $e['status'],
            'priority'       => $e['priority'],
            'assigned_user'  => ['id' => $uid, 'name' => $name],
            'color'          => $color,
            'source'         => 'outlook',
        ];
    }
}

echo json_encode($out);

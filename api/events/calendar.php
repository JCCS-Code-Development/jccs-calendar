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

// Backs the home "/" calendar widget for every role — unlike /events and
// /todos this has no outer role gate, only row-level scoping below. (v1's
// equivalent, CalendarController::index/events, had no gate AND no scoping,
// leaking every event to any authenticated user — the plan calls this out
// as a bug fix: same row-scoping the rest of the app already uses.)
$auth = requireAuth();
$pdo  = getPDO();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); exit; }

$sql    = EVENT_SELECT . ' WHERE 1=1';
$params = [];
if (!canViewAllEvents($auth)) {
    $sql .= ' AND e.assigned_user_id = ?';
    $params[] = $auth['user_id'];
}
$sql .= ' ORDER BY e.start_datetime ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$tz = new DateTimeZone(CALENDAR_TIMEZONE);
$out = array_map(function ($row) use ($tz) {
    $start = new DateTimeImmutable($row['start_datetime'], $tz);
    $end   = $row['end_datetime'] ? new DateTimeImmutable($row['end_datetime'], $tz) : $start->modify('+1 hour');
    return [
        'id'            => (int)$row['id'],
        'title'         => $row['title'],
        'start'         => $start->format('c'),
        'end'           => $end->format('c'),
        'status'        => $row['status'],
        'event_type'    => $row['event_type_name'],
        'assigned_user' => $row['assigned_user_id'] !== null
            ? ['id' => (int)$row['assigned_user_id'], 'name' => $row['assigned_user_name']]
            : null,
        'color' => $row['assigned_user_id'] !== null ? userColor((int)$row['assigned_user_id']) : ($row['event_type_color'] ?? '#64748b'),
    ];
}, $stmt->fetchAll());

echo json_encode($out);

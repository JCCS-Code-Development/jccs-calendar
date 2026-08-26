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
require_once __DIR__ . '/../push/push-helper.php';

$auth   = requireAuth();
$pdo    = getPDO();
$method = $_SERVER['REQUEST_METHOD'];
$id     = (int)($_GET['id'] ?? 0);
if (!$id) { http_response_code(422); exit(json_encode(['error' => 'Missing id'])); }

$stmt = $pdo->prepare(EVENT_SELECT . ' WHERE e.id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) { http_response_code(404); exit(json_encode(['error' => 'Event not found'])); }

if ($method === 'GET') {
    // Admin/Office see any event; everyone else only their own assigned events.
    if (!canViewAllEvents($auth) && (int)$row['assigned_user_id'] !== $auth['user_id']) {
        http_response_code(403);
        exit(json_encode(['error' => 'Forbidden']));
    }
    echo json_encode(formatEvent($row));

} elseif ($method === 'PUT') {
    requireManageEvents($auth);
    $data = parseEventPayload(jsonBody(), isUpdate: true);
    if (!$data) { echo json_encode(formatEvent($row)); exit; }

    $previousAssignee = $row['assigned_user_id'] !== null ? (int)$row['assigned_user_id'] : null;

    $set    = [];
    $params = [];
    foreach ($data as $col => $val) { $set[] = "$col = ?"; $params[] = $val; }
    $params[] = $id;
    $pdo->prepare('UPDATE events SET ' . implode(', ', $set) . ' WHERE id = ?')->execute($params);

    $newAssignee = array_key_exists('assigned_user_id', $data) ? $data['assigned_user_id'] : $previousAssignee;
    if ($newAssignee !== null && $newAssignee !== $auth['user_id']) {
        if ($newAssignee !== $previousAssignee || isset($data['start_datetime'])) {
            push_to_calendar_user($pdo, $newAssignee);
        }
    }

    $stmt = $pdo->prepare(EVENT_SELECT . ' WHERE e.id = ?');
    $stmt->execute([$id]);
    echo json_encode(formatEvent($stmt->fetch()));

} elseif ($method === 'DELETE') {
    requireManageEvents($auth);
    $pdo->prepare('DELETE FROM events WHERE id = ?')->execute([$id]);
    echo json_encode(['message' => 'Deleted']);

} else {
    http_response_code(405);
}

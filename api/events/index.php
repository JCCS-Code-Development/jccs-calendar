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

if ($method === 'GET') {
    // /events (All Events) is Admin/Office only — same visibility rule as v1.
    requireViewAllEvents($auth);

    [$filterSql, $params] = applyEventFilters();
    $stmt = $pdo->prepare(EVENT_SELECT . ' WHERE 1=1' . $filterSql . ' ORDER BY e.start_datetime ASC');
    $stmt->execute($params);
    echo json_encode(array_map('formatEvent', $stmt->fetchAll()));

} elseif ($method === 'POST') {
    requireManageEvents($auth);
    $data = parseEventPayload(jsonBody(), isUpdate: false);

    $stmt = $pdo->prepare(
        'INSERT INTO events (event_type_id, event_subtype, assigned_user_id, created_by, title, description,
                              location, start_datetime, end_datetime, is_all_day, status, priority, details)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
    );
    $stmt->execute([
        $data['event_type_id'], $data['event_subtype'] ?? null, $data['assigned_user_id'] ?? null, $auth['user_id'],
        $data['title'], $data['description'] ?? null, $data['location'] ?? null,
        $data['start_datetime'], $data['end_datetime'] ?? null, $data['is_all_day'] ?? 0,
        $data['status'], $data['priority'], $data['details'] ?? null,
    ]);
    $id = (int)$pdo->lastInsertId();

    // Notify the assignee, unless they assigned it to themselves.
    if (!empty($data['assigned_user_id']) && $data['assigned_user_id'] !== $auth['user_id']) {
        push_to_calendar_user($pdo, $data['assigned_user_id']);
    }

    $stmt = $pdo->prepare(EVENT_SELECT . ' WHERE e.id = ?');
    $stmt->execute([$id]);
    http_response_code(201);
    echo json_encode(formatEvent($stmt->fetch()));

} else {
    http_response_code(405);
}

<?php
// POST /api/ops-board/appointments  — add an appointment pinned to the board.
// Writes a normal row into `events` with board_flag = 1, so it also shows up
// in the regular Calendar. PIN-gated.
ini_set('display_errors', 0);
set_exception_handler(function ($e) { http_response_code(500); echo json_encode(['error' => $e->getMessage()]); exit; });
set_error_handler(function ($s, $m, $f, $l) { throw new ErrorException($m, 0, $s, $f, $l); });

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/validate.php';
require_once __DIR__ . '/_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit(json_encode(['error' => 'Method not allowed'])); }
requireBoardPin();

$pdo  = getPDO();
$body = jsonBody();

$title = trim((string)($body['title'] ?? ''));
$start = trim((string)($body['start_datetime'] ?? ''));
if ($title === '' || $start === '') {
    http_response_code(422);
    exit(json_encode(['error' => 'El título y la fecha/hora de inicio son obligatorios']));
}

// Default the type to "Meeting" if the caller didn't pick one.
$typeId = (int)($body['event_type_id'] ?? 0);
if ($typeId <= 0) {
    $typeId = (int)($pdo->query("SELECT id FROM event_types WHERE name = 'Meeting' LIMIT 1")->fetch()['id'] ?? 0);
    if ($typeId <= 0) $typeId = (int)$pdo->query('SELECT MIN(id) AS id FROM event_types')->fetch()['id'];
}

$enum = fn($v, array $ok, $def) => in_array($v, $ok, true) ? $v : $def;

$stmt = $pdo->prepare(
    'INSERT INTO events
        (event_type_id, assigned_user_id, created_by, title, description, location,
         start_datetime, end_datetime, is_all_day, status, priority,
         board_flag, board_importance, confirm_state, related_job_id)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,1,?,?,?)'
);
$stmt->execute([
    $typeId,
    !empty($body['assigned_user_id']) ? (int)$body['assigned_user_id'] : null,
    null, // no logged-in user behind a board edit
    $title,
    isset($body['description']) && $body['description'] !== '' ? trim((string)$body['description']) : null,
    isset($body['location']) && $body['location'] !== '' ? trim((string)$body['location']) : null,
    $start,
    isset($body['end_datetime']) && $body['end_datetime'] !== '' ? trim((string)$body['end_datetime']) : null,
    !empty($body['is_all_day']) ? 1 : 0,
    'Scheduled',
    $enum($body['board_importance'] ?? null, ['normal', 'important', 'critical'], 'normal') === 'normal' ? 'Normal' : 'High',
    $enum($body['board_importance'] ?? null, ['normal', 'important', 'critical'], 'normal'),
    $enum($body['confirm_state'] ?? null, ['tentative', 'confirmed', 'completed', 'canceled'], 'tentative'),
    !empty($body['related_job_id']) ? (int)$body['related_job_id'] : null,
]);
$id = (int)$pdo->lastInsertId();
boardAudit($pdo, 'appt.create', 'appointment', $id, $title);

$stmt = $pdo->prepare(BOARD_APPT_SELECT . ' WHERE e.id = ?');
$stmt->execute([$id]);
http_response_code(201);
echo json_encode(formatBoardAppt($stmt->fetch()));

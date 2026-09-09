<?php
// PUT    /api/ops-board/appointments/{id}  — edit / confirm / complete an
//                                            appointment.
// DELETE /api/ops-board/appointments/{id}  — mark canceled (soft) OR, with
//                                            ?hard=1, unpin from the board.
// Both PIN-gated, same stale-edit guard as jobs.
ini_set('display_errors', 0);
set_exception_handler(function ($e) { http_response_code(500); echo json_encode(['error' => $e->getMessage()]); exit; });
set_error_handler(function ($s, $m, $f, $l) { throw new ErrorException($m, 0, $s, $f, $l); });

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/validate.php';
require_once __DIR__ . '/_helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = (int)($_GET['id'] ?? 0);
if (!$id) { http_response_code(422); exit(json_encode(['error' => 'Missing id'])); }
requireBoardPin();

$pdo  = getPDO();
$stmt = $pdo->prepare(BOARD_APPT_SELECT . ' WHERE e.id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) { http_response_code(404); exit(json_encode(['error' => 'Cita no encontrada'])); }

function reloadAppt(PDO $pdo, int $id): array {
    $s = $pdo->prepare(BOARD_APPT_SELECT . ' WHERE e.id = ?');
    $s->execute([$id]);
    return formatBoardAppt($s->fetch());
}

$body     = jsonBody();
$expected = $body['expected_updated_at'] ?? null;
if ($expected !== null && $expected !== '' && $expected !== $row['updated_at']) {
    http_response_code(409);
    exit(json_encode([
        'error'   => 'Otra persona cambió esta cita. Revisa la versión más reciente e inténtalo de nuevo.',
        'current' => reloadAppt($pdo, $id),
    ]));
}

if ($method === 'DELETE') {
    if (!empty($_GET['hard'])) {
        $pdo->prepare('UPDATE events SET board_flag = 0 WHERE id = ?')->execute([$id]);
        boardAudit($pdo, 'appt.unpin', 'appointment', $id, $row['title']);
        echo json_encode(['ok' => true, 'unpinned' => true]);
    } else {
        $pdo->prepare("UPDATE events SET confirm_state = 'canceled' WHERE id = ?")->execute([$id]);
        boardAudit($pdo, 'appt.cancel', 'appointment', $id, $row['title']);
        echo json_encode(reloadAppt($pdo, $id));
    }
    exit;
}

if ($method !== 'PUT') { http_response_code(405); exit(json_encode(['error' => 'Method not allowed'])); }

$enum = fn($v, array $ok) => in_array($v, $ok, true) ? $v : null;
$str  = fn($v) => $v === null || $v === '' ? null : trim((string)$v);

$data = [];
if (array_key_exists('title', $body) && trim((string)$body['title']) !== '') $data['title'] = trim((string)$body['title']);
if (array_key_exists('description', $body))    $data['description'] = $str($body['description']);
if (array_key_exists('location', $body))       $data['location'] = $str($body['location']);
if (array_key_exists('start_datetime', $body) && $body['start_datetime'] !== '') $data['start_datetime'] = trim((string)$body['start_datetime']);
if (array_key_exists('end_datetime', $body))   $data['end_datetime'] = $str($body['end_datetime']);
if (array_key_exists('is_all_day', $body))     $data['is_all_day'] = !empty($body['is_all_day']) ? 1 : 0;
if (array_key_exists('event_type_id', $body) && (int)$body['event_type_id'] > 0) $data['event_type_id'] = (int)$body['event_type_id'];
if (array_key_exists('assigned_user_id', $body)) $data['assigned_user_id'] = !empty($body['assigned_user_id']) ? (int)$body['assigned_user_id'] : null;
if (array_key_exists('related_job_id', $body))   $data['related_job_id'] = !empty($body['related_job_id']) ? (int)$body['related_job_id'] : null;
if (($v = $enum($body['board_importance'] ?? null, ['normal', 'important', 'critical'])) !== null) {
    $data['board_importance'] = $v;
    $data['priority'] = $v === 'normal' ? 'Normal' : 'High';
}
if (($v = $enum($body['confirm_state'] ?? null, ['tentative', 'confirmed', 'completed', 'canceled'])) !== null) $data['confirm_state'] = $v;
if (!empty($body['repin']))  $data['board_flag'] = 1;

if ($data) {
    $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($data)));
    $params = array_values($data);
    $params[] = $id;
    $pdo->prepare("UPDATE events SET $set WHERE id = ?")->execute($params);
}

boardAudit($pdo, 'appt.update', 'appointment', $id,
    array_key_exists('confirm_state', $data) ? "state → {$data['confirm_state']}" : 'edited');

echo json_encode(reloadAppt($pdo, $id));

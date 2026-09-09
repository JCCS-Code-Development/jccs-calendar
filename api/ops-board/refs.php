<?php
// GET /api/ops-board/refs  — dropdown data for the Edit Mode forms:
// clients, calendar staff (id + name only), and the event-type list.
// PIN-gated, since it is only used inside Edit Mode.
ini_set('display_errors', 0);
set_exception_handler(function ($e) { http_response_code(500); echo json_encode(['error' => $e->getMessage()]); exit; });
set_error_handler(function ($s, $m, $f, $l) { throw new ErrorException($m, 0, $s, $f, $l); });

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); exit(json_encode(['error' => 'Method not allowed'])); }
requireBoardPin();

$pdo = getPDO();

$clients = array_map(
    fn($r) => ['id' => (int)$r['id'], 'name' => $r['name']],
    $pdo->query('SELECT id, name FROM clients ORDER BY name')->fetchAll()
);

$staff = array_map(
    fn($r) => ['id' => (int)$r['fieldclock_user_id'], 'name' => $r['name']],
    $pdo->query('SELECT fieldclock_user_id, name FROM calendar_user_roles WHERE is_active = 1 ORDER BY name')->fetchAll()
);

$eventTypes = array_map(
    fn($r) => ['id' => (int)$r['id'], 'name' => $r['name'], 'color' => $r['color']],
    $pdo->query('SELECT id, name, color FROM event_types ORDER BY name')->fetchAll()
);

// Full lists for the Edit Mode manager — includes archived jobs and past /
// unpinned board appointments, which the public GET /ops-board omits.
$jobRows = $pdo->query(BOARD_JOB_SELECT . ' ORDER BY j.archived_at IS NOT NULL, j.updated_at DESC')->fetchAll();
$jobs = array_map(fn($r) => formatBoardJob($pdo, $r), $jobRows);

$apptRows = $pdo->query(
    BOARD_APPT_SELECT . ' WHERE e.board_flag = 1 ORDER BY e.start_datetime DESC'
)->fetchAll();
$appointments = array_map('formatBoardAppt', $apptRows);

echo json_encode([
    'clients'      => $clients,
    'staff'        => $staff,
    'event_types'  => $eventTypes,
    'jobs'         => $jobs,
    'appointments' => $appointments,
]);

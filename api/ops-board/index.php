<?php
// GET /api/ops-board  — the whole board in one payload. PUBLIC (no JWT, no
// PIN): this is what the office TV loads and polls every ~30s. Only
// non-confidential scheduling fields are exposed here — no payroll, no
// financials, no per-person rates.
ini_set('display_errors', 0);
set_exception_handler(function ($e) { http_response_code(500); echo json_encode(['error' => $e->getMessage()]); exit; });
set_error_handler(function ($s, $m, $f, $l) { throw new ErrorException($m, 0, $s, $f, $l); });

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); exit(json_encode(['error' => 'Method not allowed'])); }

$pdo   = getPDO();
$tz    = new DateTimeZone(CALENDAR_TIMEZONE);
$now   = new DateTimeImmutable('now', $tz);
$today = boardToday();

// ── Jobs → the two primary columns ─────────────────────────────────────────
$rows = $pdo->query(BOARD_JOB_SELECT)->fetchAll();

$awaiting = [];
$scheduled = [];
foreach ($rows as $row) {
    $col = boardColumnFor($row);
    if ($col === null) continue;
    $job = formatBoardJob($pdo, $row);
    if ($col === 'awaiting') $awaiting[] = $job;
    else                     $scheduled[] = $job;
}

// Column 1: most urgent first, then longest-waiting first.
$prio = ['urgent' => 0, 'high' => 1, 'normal' => 2, 'low' => 3];
usort($awaiting, function ($a, $b) use ($prio) {
    $pa = $prio[$a['priority']] ?? 2;
    $pb = $prio[$b['priority']] ?? 2;
    if ($pa !== $pb) return $pa <=> $pb;
    return $b['days_waiting'] <=> $a['days_waiting'];
});

// Column 2: chronological; today first, undated last, completed sink to bottom.
usort($scheduled, function ($a, $b) {
    $doneA = $a['schedule_state'] === 'completed' ? 1 : 0;
    $doneB = $b['schedule_state'] === 'completed' ? 1 : 0;
    if ($doneA !== $doneB) return $doneA <=> $doneB;
    $ka = $a['projected_start'] ?: $a['projected_end'] ?: '9999-12-31';
    $kb = $b['projected_start'] ?: $b['projected_end'] ?: '9999-12-31';
    return strcmp($ka, $kb);
});

// ── Appointments → column 3 ────────────────────────────────────────────────
$stmt = $pdo->prepare(
    BOARD_APPT_SELECT .
    ' WHERE e.board_flag = 1 AND e.start_datetime >= ?
      ORDER BY e.start_datetime ASC'
);
$stmt->execute([$today->format('Y-m-d 00:00:00')]);
$appointments = array_map('formatBoardAppt', $stmt->fetchAll());

// ── Freshness marker ──────────────────────────────────────────────────────
$lastChange = $pdo->query(
    'SELECT GREATEST(
        COALESCE((SELECT MAX(updated_at) FROM jobs), 0),
        COALESCE((SELECT MAX(updated_at) FROM events), 0)
     ) AS ts'
)->fetch()['ts'];

// ── Who's on the clock (from FieldClock, best-effort) ─────────────────────
$clockedIn = boardFetchClockedIn();

echo json_encode([
    'server_time'   => $now->format('c'),
    'timezone'      => CALENDAR_TIMEZONE,
    'last_change'   => $lastChange,
    'counts'        => [
        'awaiting'     => count($awaiting),
        'scheduled'    => count($scheduled),
        'appointments' => count($appointments),
        'clocked_in'   => $clockedIn['count'] ?? null,
    ],
    'awaiting'      => $awaiting,
    'scheduled'     => $scheduled,
    'appointments'  => $appointments,
    // Always an object: { status: ok | disabled | no_url | http_401 | curl:… },
    // with workers[] only when status === 'ok'. The status is the debug hook —
    // `curl <calendar>/api/ops-board` tells you why the band is blank.
    'clocked_in'    => $clockedIn,
]);

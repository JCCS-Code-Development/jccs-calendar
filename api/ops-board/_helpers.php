<?php
// Shared by every api/ops-board/* endpoint.
//
// The board has two audiences with different gates:
//   - the read-only TV view  -> GET /ops-board, no auth at all
//   - office staff editing    -> every write checks a shared PIN (X-Board-Pin
//                                header vs OPS_BOARD_PIN in config.php)
// It deliberately does NOT use the FieldClock JWT the rest of this API runs
// on, because the board is meant to sit open on an office TV.

require_once __DIR__ . '/../config/config.php';

// ── PIN gate ────────────────────────────────────────────────────────────────

function boardPinProvided(): string {
    return (string)($_SERVER['HTTP_X_BOARD_PIN'] ?? '');
}

function boardPinValid(): bool {
    $expected = defined('OPS_BOARD_PIN') ? (string)OPS_BOARD_PIN : '';
    $given    = boardPinProvided();
    // Constant-time compare; also reject an unset/placeholder server PIN.
    return $expected !== '' && $expected !== 'CHANGE_ME' && hash_equals($expected, $given);
}

function requireBoardPin(): void {
    if (!boardPinValid()) {
        http_response_code(401);
        exit(json_encode(['error' => 'PIN del tablero inválido o ausente']));
    }
}

// ── audit ──────────────────────────────────────────────────────────────────

function boardAudit(PDO $pdo, string $action, string $entityType, ?int $entityId, ?string $detail): void {
    $stmt = $pdo->prepare(
        'INSERT INTO ops_board_audit (action, entity_type, entity_id, detail, actor_ip)
         VALUES (?,?,?,?,?)'
    );
    $stmt->execute([
        $action, $entityType, $entityId, $detail !== null ? mb_substr($detail, 0, 255) : null,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}

// ── FieldClock: who is clocked in right now ────────────────────────────────
// Read-only, best-effort. Always returns an array with a `status`:
//   ok | disabled | no_url | http_<code> | curl:<err> | bad_response
// so `curl <calendar>/api/ops-board` shows exactly why the widget is blank.
// `workers` is only present (and the band renders) when status === 'ok'.
function boardFetchClockedIn(): array {
    if (!defined('FIELDCLOCK_BOARD_TOKEN') || FIELDCLOCK_BOARD_TOKEN === '' || FIELDCLOCK_BOARD_TOKEN === 'CHANGE_ME') {
        return ['status' => 'disabled'];
    }
    if (!defined('FIELDCLOCK_API_URL') || FIELDCLOCK_API_URL === '') {
        return ['status' => 'no_url'];
    }

    $url = rtrim(FIELDCLOCK_API_URL, '/') . '/timeclock/board-active.php';
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 4,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_HTTPHEADER     => ['X-Board-Token: ' . FIELDCLOCK_BOARD_TOKEN],
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $cerr = curl_errno($ch) ? curl_error($ch) : null;
    // No curl_close(): deprecated no-op on PHP 8.0+ and this file's error
    // handler would promote the E_DEPRECATED to a fatal.

    if ($body === false || $cerr !== null) return ['status' => 'curl:' . ($cerr ?: 'unknown'), 'url' => $url];
    if ($code !== 200)                     return ['status' => 'http_' . $code, 'url' => $url, 'body' => mb_substr((string)$body, 0, 200)];

    $data = json_decode($body, true);
    if (!is_array($data) || !isset($data['workers']) || !is_array($data['workers'])) {
        return ['status' => 'bad_response', 'url' => $url, 'body' => mb_substr((string)$body, 0, 200)];
    }

    return [
        'status'  => 'ok',
        'as_of'   => $data['as_of'] ?? null,
        'count'   => (int)($data['count'] ?? count($data['workers'])),
        'workers' => $data['workers'],
    ];
}

// ── job shaping ────────────────────────────────────────────────────────────

const BOARD_JOB_SELECT = '
    SELECT j.*, c.name AS client_name
    FROM jobs j
    LEFT JOIN clients c ON c.id = j.client_id
';

function boardToday(): DateTimeImmutable {
    return new DateTimeImmutable('today', new DateTimeZone(CALENDAR_TIMEZONE));
}

function boardDaysWaiting(array $row): int {
    $ref = $row['date_received'] ?: substr((string)$row['created_at'], 0, 10);
    if (!$ref) return 0;
    try {
        $start = new DateTimeImmutable($ref, new DateTimeZone(CALENDAR_TIMEZONE));
    } catch (Exception $e) {
        return 0;
    }
    $diff = boardToday()->diff($start->setTime(0, 0));
    return max(0, (int)$diff->days);
}

// 'awaiting' | 'scheduled' | null  — a job is on at most one primary column.
// A finished job (Completed / schedule_status completed), a cancelled one, or
// an archived one leaves the board immediately. It stays in the database and
// in Edit Mode's "Completed" filter, so it's never lost — just off the wall.
function boardColumnFor(array $row): ?string {
    if ($row['archived_at'] !== null)                    return null;
    if ($row['status'] === 'Cancelled')                  return null;
    if ($row['status'] === 'Completed')                  return null;
    if ($row['schedule_status'] === 'completed')         return null;

    $scheduled = in_array($row['schedule_status'], ['confirmed', 'in_progress'], true);
    return $scheduled ? 'scheduled' : 'awaiting';
}

// Visual state hint for the Scheduled column card.
function boardScheduleState(array $row): string {
    if ($row['status'] === 'Completed' || $row['schedule_status'] === 'completed') return 'completed';

    $today = boardToday();
    $start = $row['projected_start'] ? new DateTimeImmutable($row['projected_start'], new DateTimeZone(CALENDAR_TIMEZONE)) : null;
    $end   = $row['projected_end']   ? new DateTimeImmutable($row['projected_end'],   new DateTimeZone(CALENDAR_TIMEZONE)) : null;

    if ($end && $end < $today)                              return 'delayed';
    if ($start && $start <= $today && (!$end || $end >= $today)) return 'today';
    if ($start && $start->format('Y-m-d') === $today->format('Y-m-d')) return 'today';
    return 'upcoming';
}

function boardPoMissing(array $row): bool {
    return !in_array($row['po_status'], ['received', 'approved'], true);
}

function formatBoardJob(PDO $pdo, array $row): array {
    static $workerStmt = null;
    if ($workerStmt === null) {
        $workerStmt = $pdo->prepare(
            'SELECT jw.fieldclock_user_id AS id, COALESCE(cur.name, jw.name_cache) AS name
             FROM job_workers jw
             LEFT JOIN calendar_user_roles cur ON cur.fieldclock_user_id = jw.fieldclock_user_id
             WHERE jw.job_id = ?'
        );
    }
    $workerStmt->execute([$row['id']]);
    $workers = $workerStmt->fetchAll();

    return [
        'id'                   => (int)$row['id'],
        'title'                => $row['title'],
        'client_id'            => $row['client_id'] !== null ? (int)$row['client_id'] : null,
        'client_name'          => $row['client_name'],
        'address'              => $row['address'],
        'estimate_number'      => $row['estimate_number'],
        'scope'                => $row['scope'],
        'status'               => $row['status'],
        'po_status'            => $row['po_status'],
        'po_number'            => $row['po_number'],
        'po_received_date'     => $row['po_received_date'],
        'po_missing'           => boardPoMissing($row),
        'schedule_status'      => $row['schedule_status'],
        'schedule_state'       => boardScheduleState($row),
        'priority'             => $row['priority'],
        'date_received'        => $row['date_received'] ?: substr((string)$row['created_at'], 0, 10),
        'days_waiting'         => boardDaysWaiting($row),
        'next_action_by'       => $row['next_action_by'],
        'board_notes'          => $row['board_notes'],
        'projected_start'      => $row['projected_start'],
        'projected_end'        => $row['projected_end'],
        'scheduled_start_time' => $row['scheduled_start_time'] ? substr((string)$row['scheduled_start_time'], 0, 5) : null,
        'archived_at'          => $row['archived_at'],
        'workers'              => array_map(fn($w) => ['id' => (int)$w['id'], 'name' => $w['name']], $workers),
        'worker_ids'           => array_map(fn($w) => (int)$w['id'], $workers),
        'updated_at'           => $row['updated_at'],
        'created_at'           => $row['created_at'],
    ];
}

// Resolve a client for a job write: an explicit id wins; otherwise a typed
// name is matched case-insensitively and created if new. Returns null when
// neither is given (client is optional on a job).
function boardResolveClientId(PDO $pdo, $clientId, $clientName): ?int {
    if ($clientId !== null && $clientId !== '' && (int)$clientId > 0) {
        return (int)$clientId;
    }
    $name = trim((string)($clientName ?? ''));
    if ($name === '') return null;

    $stmt = $pdo->prepare('SELECT id FROM clients WHERE LOWER(name) = LOWER(?) LIMIT 1');
    $stmt->execute([$name]);
    $existing = $stmt->fetch();
    if ($existing) return (int)$existing['id'];

    $pdo->prepare('INSERT INTO clients (name) VALUES (?)')->execute([$name]);
    return (int)$pdo->lastInsertId();
}

// Fields the board is allowed to write on a job, with light normalisation.
// Anything not in here (color, photo_path, lead_time_days, fieldclock_job_id)
// is left to the existing Jobs screens.
function boardJobWritable(array $body, bool $isCreate): array {
    $out = [];
    $str  = fn($v) => $v === null || $v === '' ? null : trim((string)$v);
    $enum = fn($v, array $ok, $def) => in_array($v, $ok, true) ? $v : $def;

    if ($isCreate || array_key_exists('title', $body))            $out['title'] = trim((string)($body['title'] ?? ''));
    if ($isCreate || array_key_exists('estimate_number', $body))  $out['estimate_number'] = $str($body['estimate_number'] ?? null);
    if ($isCreate || array_key_exists('address', $body))          $out['address'] = $str($body['address'] ?? null);
    if ($isCreate || array_key_exists('scope', $body))            $out['scope'] = $str($body['scope'] ?? null);
    if ($isCreate || array_key_exists('board_notes', $body))      $out['board_notes'] = $str($body['board_notes'] ?? null);
    if ($isCreate || array_key_exists('next_action_by', $body))   $out['next_action_by'] = $str($body['next_action_by'] ?? null);
    if ($isCreate || array_key_exists('date_received', $body))    $out['date_received'] = $str($body['date_received'] ?? null);
    if ($isCreate || array_key_exists('projected_start', $body))  $out['projected_start'] = $str($body['projected_start'] ?? null);
    if ($isCreate || array_key_exists('projected_end', $body))    $out['projected_end'] = $str($body['projected_end'] ?? null);
    if ($isCreate || array_key_exists('scheduled_start_time', $body)) $out['scheduled_start_time'] = $str($body['scheduled_start_time'] ?? null);
    if ($isCreate || array_key_exists('po_number', $body))        $out['po_number'] = $str($body['po_number'] ?? null);
    if ($isCreate || array_key_exists('po_received_date', $body)) $out['po_received_date'] = $str($body['po_received_date'] ?? null);

    if ($isCreate || array_key_exists('priority', $body))
        $out['priority'] = $enum($body['priority'] ?? null, ['low', 'normal', 'high', 'urgent'], 'normal');
    if ($isCreate || array_key_exists('po_status', $body))
        $out['po_status'] = $enum($body['po_status'] ?? null, ['none', 'requested', 'received', 'approved'], 'none');
    if ($isCreate || array_key_exists('schedule_status', $body))
        $out['schedule_status'] = $enum($body['schedule_status'] ?? null, ['unscheduled', 'tentative', 'confirmed', 'in_progress', 'completed'], 'unscheduled');
    if ($isCreate || array_key_exists('status', $body))
        $out['status'] = $enum($body['status'] ?? null, ['Active', 'On Hold', 'Completed', 'Cancelled'], 'Active');

    return $out;
}

// ── appointment shaping ────────────────────────────────────────────────────

const BOARD_APPT_SELECT = '
    SELECT e.*, et.name AS event_type_name, et.color AS event_type_color,
           cur.name AS assigned_user_name,
           j.title  AS related_job_title, j.estimate_number AS related_job_estimate
    FROM events e
    JOIN event_types et ON et.id = e.event_type_id
    LEFT JOIN calendar_user_roles cur ON cur.fieldclock_user_id = e.assigned_user_id
    LEFT JOIN jobs j ON j.id = e.related_job_id
';

function formatBoardAppt(array $row): array {
    $tz    = new DateTimeZone(CALENDAR_TIMEZONE);
    $start = new DateTimeImmutable($row['start_datetime'], $tz);
    $today = boardToday();

    return [
        'id'               => (int)$row['id'],
        'title'            => $row['title'],
        'description'      => $row['description'],
        'location'         => $row['location'],
        'event_type'       => $row['event_type_name'],
        'event_type_color' => $row['event_type_color'],
        'start_datetime'   => $row['start_datetime'],
        'end_datetime'     => $row['end_datetime'],
        'is_all_day'       => (bool)$row['is_all_day'],
        'is_today'         => $start->format('Y-m-d') === $today->format('Y-m-d'),
        'board_importance' => $row['board_importance'],
        'confirm_state'    => $row['confirm_state'],
        'responsible'      => $row['assigned_user_name'],
        'related_job_id'   => $row['related_job_id'] !== null ? (int)$row['related_job_id'] : null,
        'related_job'      => $row['related_job_title'],
        'updated_at'       => $row['updated_at'],
    ];
}

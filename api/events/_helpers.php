<?php
// Shared by every events/* endpoint. Response shapes here intentionally
// match what jccs-calendar-v2's src/api/events.js already expects (bare
// arrays/objects, not the {resource: [...]} envelope the sibling apps use)
// because that frontend code was written against the original Laravel API
// and is being reused as-is — see the migration plan, section 3.

const EVENT_SELECT = '
    SELECT e.*, et.name AS event_type_name, et.color AS event_type_color,
           cur.name AS assigned_user_name
    FROM events e
    JOIN event_types et ON et.id = e.event_type_id
    LEFT JOIN calendar_user_roles cur ON cur.fieldclock_user_id = e.assigned_user_id
';

function formatEvent(array $row): array {
    return [
        'id'               => (int)$row['id'],
        'title'            => $row['title'],
        'description'      => $row['description'],
        'location'         => $row['location'],
        'event_type_id'    => (int)$row['event_type_id'],
        'event_type'       => $row['event_type_name'],
        'event_type_color' => $row['event_type_color'],
        'event_subtype'    => $row['event_subtype'],
        'assigned_user_id' => $row['assigned_user_id'] !== null ? (int)$row['assigned_user_id'] : null,
        'assigned_user'    => $row['assigned_user_id'] !== null
            ? ['id' => (int)$row['assigned_user_id'], 'name' => $row['assigned_user_name']]
            : null,
        'assigned_user_name' => $row['assigned_user_name'],
        'start_datetime'   => $row['start_datetime'],
        'end_datetime'     => $row['end_datetime'],
        'is_all_day'       => (bool)$row['is_all_day'],
        'status'           => $row['status'],
        'priority'         => $row['priority'],
        'color'            => $row['assigned_user_id'] !== null ? userColor((int)$row['assigned_user_id']) : '#64748b',
        'details'          => $row['details'] !== null ? json_decode($row['details'], true) : new stdClass(),
        'created_at'       => $row['created_at'],
    ];
}

// Mirrors v1's date_range filter values exactly: past, today, tomorrow,
// today_tomorrow (default), this_week, this_month, rest, today_forward.
// Computed in PHP (not MySQL CURDATE()) so the boundary is unambiguous
// regardless of the MySQL session's timezone offset.
function eventDateRangeClause(string $range): array {
    $tz    = new DateTimeZone(CALENDAR_TIMEZONE);
    $today = new DateTimeImmutable('today', $tz);

    switch ($range) {
        case 'past':
            return ['e.start_datetime < ?', [$today->format('Y-m-d 00:00:00')]];
        case 'today':
            return ['e.start_datetime BETWEEN ? AND ?', [$today->format('Y-m-d 00:00:00'), $today->format('Y-m-d 23:59:59')]];
        case 'tomorrow':
            $t = $today->modify('+1 day');
            return ['e.start_datetime BETWEEN ? AND ?', [$t->format('Y-m-d 00:00:00'), $t->format('Y-m-d 23:59:59')]];
        case 'this_week':
            $start = $today->modify('monday this week');
            $end   = $start->modify('+6 days');
            return ['e.start_datetime BETWEEN ? AND ?', [$start->format('Y-m-d 00:00:00'), $end->format('Y-m-d 23:59:59')]];
        case 'this_month':
            $start = $today->modify('first day of this month');
            $end   = $today->modify('last day of this month');
            return ['e.start_datetime BETWEEN ? AND ?', [$start->format('Y-m-d 00:00:00'), $end->format('Y-m-d 23:59:59')]];
        case 'rest':
            $t = $today->modify('+2 days');
            return ['e.start_datetime >= ?', [$t->format('Y-m-d 00:00:00')]];
        case 'today_forward':
            return ['e.start_datetime >= ?', [$today->format('Y-m-d 00:00:00')]];
        case 'today_tomorrow':
        default:
            $end = $today->modify('+1 day');
            return ['e.start_datetime BETWEEN ? AND ?', [$today->format('Y-m-d 00:00:00'), $end->format('Y-m-d 23:59:59')]];
    }
}

// Applies the standard filter set (date_range, event_type_id, status,
// assigned_user_id) shared by /events, /my-events, /todos, /calendar-events.
// Returns [sql, params] to append after a base "WHERE 1=1".
function applyEventFilters(): array {
    $sql    = '';
    $params = [];

    [$rangeSql, $rangeParams] = eventDateRangeClause($_GET['date_range'] ?? 'today_tomorrow');
    $sql    .= ' AND ' . $rangeSql;
    $params  = array_merge($params, $rangeParams);

    if (!empty($_GET['event_type_id'])) {
        $sql .= ' AND e.event_type_id = ?';
        $params[] = (int)$_GET['event_type_id'];
    }
    if (!empty($_GET['status'])) {
        $sql .= ' AND e.status = ?';
        $params[] = sanitizeString($_GET['status']);
    }
    if (!empty($_GET['assigned_user_id'])) {
        $sql .= ' AND e.assigned_user_id = ?';
        $params[] = (int)$_GET['assigned_user_id'];
    }

    return [$sql, $params];
}

// Validates + normalizes the request body shared by create/update. Returns
// [columns => value] ready to bind, or exits with a 422 on bad input.
function parseEventPayload(array $body, bool $isUpdate = false): array {
    if (!$isUpdate) {
        requireFields($body, ['title', 'event_type_id', 'start_datetime']);
    }

    $data = [];
    if (isset($body['title'])) $data['title'] = sanitizeString($body['title']);
    if (isset($body['event_type_id'])) $data['event_type_id'] = (int)$body['event_type_id'];
    if (array_key_exists('event_subtype', $body)) $data['event_subtype'] = $body['event_subtype'] !== null ? sanitizeString($body['event_subtype']) : null;
    if (array_key_exists('description', $body)) $data['description'] = $body['description'] !== null ? sanitizeString($body['description']) : null;
    if (array_key_exists('location', $body)) $data['location'] = $body['location'] !== null ? sanitizeString($body['location']) : null;
    if (isset($body['start_datetime'])) $data['start_datetime'] = sanitizeString($body['start_datetime']);
    if (array_key_exists('end_datetime', $body)) $data['end_datetime'] = $body['end_datetime'] !== null && $body['end_datetime'] !== '' ? sanitizeString($body['end_datetime']) : null;
    if (array_key_exists('is_all_day', $body)) $data['is_all_day'] = !empty($body['is_all_day']) ? 1 : 0;
    if (isset($body['status'])) $data['status'] = sanitizeString($body['status']);
    elseif (!$isUpdate) $data['status'] = 'Scheduled';
    if (isset($body['priority'])) $data['priority'] = sanitizeString($body['priority']);
    elseif (!$isUpdate) $data['priority'] = 'Normal';
    if (array_key_exists('assigned_user_id', $body)) $data['assigned_user_id'] = $body['assigned_user_id'] !== null && $body['assigned_user_id'] !== '' ? (int)$body['assigned_user_id'] : null;
    if (array_key_exists('details', $body)) $data['details'] = json_encode($body['details'] ?? new stdClass());

    return $data;
}

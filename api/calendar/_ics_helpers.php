<?php
// Shared iCalendar (RFC 5545) generation for calendar/feed.php and
// events/export_ics.php. Ported from v1's CalendarExportController.

function icsEscape(string $text): string {
    return str_replace(["\\", "\n", ",", ";"], ["\\\\", "\\n", "\\,", "\\;"], $text);
}

function icsEventBlock(array $row): string {
    $tz    = new DateTimeZone(CALENDAR_TIMEZONE);
    $start = (new DateTimeImmutable($row['start_datetime'], $tz))->setTimezone(new DateTimeZone('UTC'));
    $end   = $row['end_datetime']
        ? (new DateTimeImmutable($row['end_datetime'], $tz))->setTimezone(new DateTimeZone('UTC'))
        : $start->modify('+1 hour');

    $descriptionParts = array_filter([
        $row['description'] ?: null,
        $row['event_subtype'] ? 'Sub-type: ' . $row['event_subtype'] : null,
        $row['assigned_user_name'] ? 'Assigned to: ' . $row['assigned_user_name'] : null,
        'Status: ' . $row['status'],
    ]);

    $lines = [
        'BEGIN:VEVENT',
        'UID:event-' . $row['id'] . '@jccs-calendar',
        'DTSTAMP:' . gmdate('Ymd\THis\Z'),
        'CREATED:' . (new DateTimeImmutable($row['created_at'], $tz))->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z'),
        'DTSTART:' . $start->format('Ymd\THis\Z'),
        'DTEND:' . $end->format('Ymd\THis\Z'),
        'SUMMARY:' . icsEscape($row['title']),
    ];
    if ($descriptionParts) $lines[] = 'DESCRIPTION:' . icsEscape(implode("\n", $descriptionParts));
    if (!empty($row['location'])) $lines[] = 'LOCATION:' . icsEscape($row['location']);
    if (!empty($row['event_type_name'])) $lines[] = 'CATEGORIES:' . icsEscape($row['event_type_name']);
    $lines[] = 'END:VEVENT';

    return implode("\r\n", $lines);
}

function icsCalendarWrap(array $eventBlocks): string {
    $lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//JCCS Calendar//EN', 'CALSCALE:GREGORIAN'];
    foreach ($eventBlocks as $block) $lines[] = $block;
    $lines[] = 'END:VCALENDAR';
    return implode("\r\n", $lines) . "\r\n";
}

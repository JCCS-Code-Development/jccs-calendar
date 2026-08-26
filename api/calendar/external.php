<?php
ini_set('display_errors', 0);
set_exception_handler(function ($e) { http_response_code(500); echo json_encode(['error' => $e->getMessage()]); exit; });
set_error_handler(function ($s, $m, $f, $l) { throw new ErrorException($m, 0, $s, $f, $l); });

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../middleware/auth.php';

// Fetches and parses a remote .ics feed (e.g. an Outlook calendar's public
// share link) so it can be overlaid on the home calendar alongside internal
// events. Requires auth (not a public proxy); still only ever GETs the
// user-supplied URL, never anything derived from a stored/trusted source.
$auth = requireAuth();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); exit; }

$url = $_GET['url'] ?? '';
if (!$url || !preg_match('#^https?://#i', $url)) {
    http_response_code(422);
    exit(json_encode(['error' => 'A valid http(s) url is required']));
}

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 3,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_USERAGENT      => 'JCCS-Calendar/1.0',
]);
$body = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($body === false || $code >= 400) {
    http_response_code(502);
    exit(json_encode(['error' => 'Could not fetch the calendar feed']));
}

// Unfold folded lines per RFC 5545 (a line starting with a space/tab
// continues the previous line) before splitting into VEVENT blocks.
$body = preg_replace("/\r\n[ \t]/", '', $body);
$body = str_replace("\r\n", "\n", $body);

function icsParseDate(string $raw): ?string {
    $raw = trim($raw);
    if (preg_match('/^(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})(\d{2})Z?$/', $raw, $m)) {
        $tz = str_ends_with($raw, 'Z') ? new DateTimeZone('UTC') : new DateTimeZone(CALENDAR_TIMEZONE);
        $dt = new DateTimeImmutable("$m[1]-$m[2]-$m[3] $m[4]:$m[5]:$m[6]", $tz);
        return $dt->setTimezone(new DateTimeZone(CALENDAR_TIMEZONE))->format('Y-m-d H:i:s');
    }
    if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $raw, $m)) {
        return "$m[1]-$m[2]-$m[3] 00:00:00";
    }
    return null;
}

function icsUnescape(string $text): string {
    return str_replace(['\\n', '\\,', '\\;', '\\\\'], ["\n", ',', ';', '\\'], $text);
}

preg_match_all('/BEGIN:VEVENT(.*?)END:VEVENT/s', $body, $matches);

$events = [];
foreach ($matches[1] as $block) {
    $lines = explode("\n", trim($block));
    $fields = [];
    foreach ($lines as $line) {
        if (!str_contains($line, ':')) continue;
        [$key, $value] = explode(':', $line, 2);
        $key = strtoupper(explode(';', $key)[0]); // strip params like DTSTART;TZID=...
        $fields[$key] = $value;
    }
    if (empty($fields['DTSTART']) || empty($fields['SUMMARY'])) continue;

    $start = icsParseDate($fields['DTSTART']);
    if (!$start) continue;
    $end = !empty($fields['DTEND']) ? icsParseDate($fields['DTEND']) : null;

    $events[] = [
        'id'             => 'outlook-' . md5($fields['UID'] ?? ($fields['SUMMARY'] . $start)),
        'title'          => icsUnescape($fields['SUMMARY']),
        'description'    => isset($fields['DESCRIPTION']) ? icsUnescape($fields['DESCRIPTION']) : null,
        'location'       => isset($fields['LOCATION']) ? icsUnescape($fields['LOCATION']) : null,
        'start_datetime' => $start,
        'end_datetime'   => $end,
        'status'         => 'Scheduled',
        'priority'       => 'Normal',
        'color'          => '#0078d4', // Outlook blue
        'source'         => 'outlook',
    ];
}

echo json_encode($events);

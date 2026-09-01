<?php
// Shared inbound-ICS reader: fetch a remote .ics feed (Outlook "publish
// calendar" link, Google secret address, etc.) and turn its VEVENTs into
// plain event rows. Callers decide colour / source / assignee.
//
// Results are cached to api/cache/ for a few minutes so a calendar load
// with several linked feeds doesn't fire N slow cURL calls every time.

const ICS_CACHE_TTL = 600; // seconds

function icsCacheDir(): string {
    $dir = __DIR__ . '/../cache';
    if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
    return $dir;
}

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

function icsParseBody(string $body): array {
    // Unfold folded lines (RFC 5545) then normalise newlines.
    $body = preg_replace("/\r\n[ \t]/", '', $body);
    $body = str_replace("\r\n", "\n", $body);

    preg_match_all('/BEGIN:VEVENT(.*?)END:VEVENT/s', $body, $matches);

    $events = [];
    foreach ($matches[1] as $block) {
        $fields = [];
        foreach (explode("\n", trim($block)) as $line) {
            if (!str_contains($line, ':')) continue;
            [$key, $value] = explode(':', $line, 2);
            $key = strtoupper(explode(';', $key)[0]); // drop params like DTSTART;TZID=...
            $fields[$key] = $value;
        }
        if (empty($fields['DTSTART']) || empty($fields['SUMMARY'])) continue;

        $start = icsParseDate($fields['DTSTART']);
        if (!$start) continue;
        $end = !empty($fields['DTEND']) ? icsParseDate($fields['DTEND']) : null;

        $events[] = [
            'id'             => md5($fields['UID'] ?? ($fields['SUMMARY'] . $start)),
            'title'          => icsUnescape($fields['SUMMARY']),
            'description'    => isset($fields['DESCRIPTION']) ? icsUnescape($fields['DESCRIPTION']) : null,
            'location'       => isset($fields['LOCATION']) ? icsUnescape($fields['LOCATION']) : null,
            'start_datetime' => $start,
            'end_datetime'   => $end,
            'status'         => 'Scheduled',
            'priority'       => 'Normal',
        ];
    }
    return $events;
}

/**
 * Fetch + parse a remote ICS URL. Returns a list of event rows (see
 * icsParseBody). Throws RuntimeException on a bad URL / fetch failure.
 */
function icsFetchAndParse(string $url, bool $force = false): array {
    if (!preg_match('#^https?://#i', $url)) {
        throw new RuntimeException('A valid http(s) url is required');
    }

    $cacheFile = icsCacheDir() . '/ics_' . md5($url) . '.json';
    if (!$force && is_file($cacheFile) && (time() - filemtime($cacheFile)) < ICS_CACHE_TTL) {
        $cached = json_decode((string)file_get_contents($cacheFile), true);
        if (is_array($cached)) return $cached;
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
        // Serve a stale cache rather than nothing, if we have one.
        if (is_file($cacheFile)) {
            $cached = json_decode((string)file_get_contents($cacheFile), true);
            if (is_array($cached)) return $cached;
        }
        throw new RuntimeException('Could not fetch the calendar feed');
    }

    $events = icsParseBody((string)$body);
    @file_put_contents($cacheFile, json_encode($events));
    return $events;
}

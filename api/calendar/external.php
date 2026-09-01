<?php
ini_set('display_errors', 0);
set_exception_handler(function ($e) { http_response_code(500); echo json_encode(['error' => $e->getMessage()]); exit; });
set_error_handler(function ($s, $m, $f, $l) { throw new ErrorException($m, 0, $s, $f, $l); });

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/_ics_parse.php';

// Fetches and parses a caller-supplied .ics feed (e.g. an Outlook calendar's
// public share link) so it can be overlaid on the home calendar. This is the
// per-browser "Connect Outlook" overlay; the per-person team feeds live in
// linked_outlook.php. Requires auth (not a public proxy); only ever GETs the
// url passed in.
$auth = requireAuth();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); exit; }

$url = $_GET['url'] ?? '';
if (!$url || !preg_match('#^https?://#i', $url)) {
    http_response_code(422);
    exit(json_encode(['error' => 'A valid http(s) url is required']));
}

try {
    $events = icsFetchAndParse($url, isset($_GET['refresh']));
} catch (RuntimeException $e) {
    http_response_code(502);
    exit(json_encode(['error' => $e->getMessage()]));
}

echo json_encode(array_map(fn($e) => [
    'id'             => 'outlook-' . $e['id'],
    'title'          => $e['title'],
    'description'    => $e['description'],
    'location'       => $e['location'],
    'start_datetime' => $e['start_datetime'],
    'end_datetime'   => $e['end_datetime'],
    'status'         => $e['status'],
    'priority'       => $e['priority'],
    'color'          => '#0078d4', // Outlook blue
    'source'         => 'outlook',
], $events));

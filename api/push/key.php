<?php
ini_set('display_errors', 0);
set_exception_handler(function ($e) { http_response_code(500); echo json_encode(['error' => $e->getMessage()]); exit; });
set_error_handler(function ($s, $m, $f, $l) { throw new ErrorException($m, 0, $s, $f, $l); });

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/config.php';

// Public (unauthenticated) — the service worker needs this before login to
// register a push subscription attempt, matching v1.
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); exit; }
echo json_encode(['publicKey' => VAPID_PUBLIC_KEY]);

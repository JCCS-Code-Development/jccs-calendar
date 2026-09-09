<?php
// POST /api/ops-board/verify-pin  — lets Edit Mode check the PIN before it
// shows any editing UI. Body is ignored; the PIN travels in the X-Board-Pin
// header like every other write.
ini_set('display_errors', 0);
set_exception_handler(function ($e) { http_response_code(500); echo json_encode(['error' => $e->getMessage()]); exit; });

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit(json_encode(['error' => 'Method not allowed'])); }

// Small fixed delay blunts brute-forcing a short PIN over the network.
usleep(300000);
requireBoardPin();

echo json_encode(['ok' => true]);

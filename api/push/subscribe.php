<?php
ini_set('display_errors', 0);
set_exception_handler(function ($e) { http_response_code(500); echo json_encode(['error' => $e->getMessage()]); exit; });
set_error_handler(function ($s, $m, $f, $l) { throw new ErrorException($m, 0, $s, $f, $l); });

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../middleware/validate.php';

$auth = requireAuth();
$pdo  = getPDO();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

// Body shape is the raw PushSubscription object from PushManager.subscribe():
// { endpoint, keys: { p256dh, auth } }
$body = jsonBody();
$endpoint = $body['endpoint'] ?? null;
$keys     = $body['keys'] ?? [];
if (!$endpoint || empty($keys['p256dh']) || empty($keys['auth'])) {
    http_response_code(422);
    exit(json_encode(['error' => 'endpoint and keys.p256dh/auth are required']));
}

$pdo->prepare(
    'INSERT INTO push_subscriptions (fieldclock_user_id, endpoint, p256dh_key, auth_key)
     VALUES (?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE p256dh_key = VALUES(p256dh_key), auth_key = VALUES(auth_key)'
)->execute([$auth['user_id'], sanitizeString($endpoint), sanitizeString($keys['p256dh']), sanitizeString($keys['auth'])]);

echo json_encode(['subscribed' => true]);

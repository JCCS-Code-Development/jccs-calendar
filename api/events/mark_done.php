<?php
ini_set('display_errors', 0);
set_exception_handler(function ($e) { http_response_code(500); echo json_encode(['error' => $e->getMessage()]); exit; });
set_error_handler(function ($s, $m, $f, $l) { throw new ErrorException($m, 0, $s, $f, $l); });

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../middleware/validate.php';
require_once __DIR__ . '/_helpers.php';

$auth = requireAuth();
requireManageEvents($auth); // matches v1: only Admin/Office can mark an event done, even their own
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') { http_response_code(405); exit; }

$id = (int)($_GET['id'] ?? 0);
if (!$id) { http_response_code(422); exit(json_encode(['error' => 'Missing id'])); }

$stmt = $pdo->prepare(EVENT_SELECT . ' WHERE e.id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) { http_response_code(404); exit(json_encode(['error' => 'Event not found'])); }

// Simple toggle, matching v1's markDone exactly: Completed <-> Scheduled.
// A Cancelled/Rescheduled event that gets marked done then undone becomes
// Scheduled, not restored to its prior status — same known quirk as v1.
$newStatus = strtolower($row['status']) === 'completed' ? 'Scheduled' : 'Completed';
$pdo->prepare('UPDATE events SET status = ? WHERE id = ?')->execute([$newStatus, $id]);

$stmt = $pdo->prepare(EVENT_SELECT . ' WHERE e.id = ?');
$stmt->execute([$id]);
echo json_encode(formatEvent($stmt->fetch()));

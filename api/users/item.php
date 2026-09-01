<?php
ini_set('display_errors', 0);
set_exception_handler(function ($e) { http_response_code(500); echo json_encode(['error' => $e->getMessage()]); exit; });
set_error_handler(function ($s, $m, $f, $l) { throw new ErrorException($m, 0, $s, $f, $l); });

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../middleware/validate.php';

const ITEM_CALENDAR_ROLES = ['Admin', 'Office', 'Lead', 'Crew'];

function formatUserItem(array $row): array {
    return [
        'id'              => (int)$row['fieldclock_user_id'],
        'name'            => $row['name'],
        'role'            => $row['role'],
        'role_id'         => $row['role'],
        'outlook_ics_url' => $row['outlook_ics_url'] ?? null,
    ];
}

$auth = requireAuth();
requireManageUsers($auth);
$pdo    = getPDO();
$method = $_SERVER['REQUEST_METHOD'];
$id     = (int)($_GET['id'] ?? 0);
if (!$id) { http_response_code(422); exit(json_encode(['error' => 'Missing id'])); }

$stmt = $pdo->prepare('SELECT * FROM calendar_user_roles WHERE fieldclock_user_id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) { http_response_code(404); exit(json_encode(['error' => 'User not found'])); }

if ($method === 'GET') {
    echo json_encode(formatUserItem($row));

} elseif ($method === 'PUT') {
    $body = jsonBody();
    $set    = [];
    $params = [];
    if (isset($body['name'])) { $set[] = 'name = ?'; $params[] = sanitizeString($body['name']); }
    if (isset($body['role_id'])) {
        $role = sanitizeString($body['role_id']);
        if (!in_array($role, ITEM_CALENDAR_ROLES, true)) {
            http_response_code(422);
            exit(json_encode(['error' => 'Invalid role.']));
        }
        $set[] = 'role = ?';
        $params[] = $role;
    }
    if (array_key_exists('outlook_ics_url', $body)) {
        $outlookUrl = trim((string)$body['outlook_ics_url']);
        if ($outlookUrl !== '' && !preg_match('#^https?://#i', $outlookUrl)) {
            http_response_code(422);
            exit(json_encode(['error' => 'Outlook link must be an http(s) URL.']));
        }
        $set[] = 'outlook_ics_url = ?';
        $params[] = $outlookUrl !== '' ? $outlookUrl : null;
    }
    if ($set) {
        $params[] = $id;
        $pdo->prepare('UPDATE calendar_user_roles SET ' . implode(', ', $set) . ' WHERE fieldclock_user_id = ?')->execute($params);
    }
    $stmt = $pdo->prepare('SELECT * FROM calendar_user_roles WHERE fieldclock_user_id = ?');
    $stmt->execute([$id]);
    echo json_encode(formatUserItem($stmt->fetch()));

} elseif ($method === 'DELETE') {
    if ($id === $auth['user_id']) {
        http_response_code(422);
        exit(json_encode(['error' => 'Cannot delete yourself.']));
    }
    // Soft delete (matches the sibling apps' convention) — keeps history
    // (e.g. events.created_by) resolvable rather than orphaning it.
    $pdo->prepare('UPDATE calendar_user_roles SET is_active = 0 WHERE fieldclock_user_id = ?')->execute([$id]);
    echo json_encode(['message' => 'Deleted']);

} else {
    http_response_code(405);
}

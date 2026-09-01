<?php
ini_set('display_errors', 0);
set_exception_handler(function ($e) { http_response_code(500); echo json_encode(['error' => $e->getMessage()]); exit; });
set_error_handler(function ($s, $m, $f, $l) { throw new ErrorException($m, 0, $s, $f, $l); });

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../middleware/validate.php';

const CALENDAR_ROLES = ['Admin', 'Office', 'Lead', 'Crew'];

// "Users" here means "who's provisioned for Calendar and with which role" —
// there's no local email/password (see plan section 2/3): a user is added
// by picking an existing FieldClock account (fieldclock_user_id) rather
// than filling out a signup form. `role_id` is kept as the payload/response
// key for compatibility with the existing frontend, but its value is the
// role name itself (e.g. "Admin"), not a foreign key into a roles table.
function formatUser(array $row): array {
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

if ($method === 'GET') {
    $stmt = $pdo->query('SELECT * FROM calendar_user_roles WHERE is_active = 1 ORDER BY name');
    echo json_encode(array_map('formatUser', $stmt->fetchAll()));

} elseif ($method === 'POST') {
    $body = jsonBody();
    requireFields($body, ['fieldclock_user_id', 'name', 'role_id']);

    $fieldclockUserId = (int)$body['fieldclock_user_id'];
    $role = sanitizeString($body['role_id']);
    if (!in_array($role, CALENDAR_ROLES, true)) {
        http_response_code(422);
        exit(json_encode(['error' => 'Invalid role.']));
    }

    $outlookUrl = isset($body['outlook_ics_url']) ? trim((string)$body['outlook_ics_url']) : '';
    if ($outlookUrl !== '' && !preg_match('#^https?://#i', $outlookUrl)) {
        http_response_code(422);
        exit(json_encode(['error' => 'Outlook link must be an http(s) URL.']));
    }

    $pdo->prepare(
        'INSERT INTO calendar_user_roles (fieldclock_user_id, name, role, is_active, outlook_ics_url)
         VALUES (?, ?, ?, 1, ?)
         ON DUPLICATE KEY UPDATE name = VALUES(name), role = VALUES(role), is_active = 1, outlook_ics_url = VALUES(outlook_ics_url)'
    )->execute([$fieldclockUserId, sanitizeString($body['name']), $role, $outlookUrl !== '' ? $outlookUrl : null]);

    $stmt = $pdo->prepare('SELECT * FROM calendar_user_roles WHERE fieldclock_user_id = ?');
    $stmt->execute([$fieldclockUserId]);
    http_response_code(201);
    echo json_encode(formatUser($stmt->fetch()));

} else {
    http_response_code(405);
}

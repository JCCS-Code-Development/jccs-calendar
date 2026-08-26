<?php
// Validates the JWT issued by FieldClock's login, then resolves the payload's
// user_id to this app's own role via calendar_user_roles — Calendar does
// not trust FieldClock's `role` claim (employee/admin/contractor) since
// Calendar's roles (Admin/Office/Lead/Crew) are assigned independently.

function resolveAuthToken(bool $allowQueryToken = false): string {
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (str_starts_with($auth, 'Bearer ')) {
        return substr($auth, 7);
    }
    if ($allowQueryToken && !empty($_GET['token'])) {
        return $_GET['token'];
    }
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

function resolveAuth(string $token): array {
    $payload = jwt_decode($token);
    if (!$payload) {
        http_response_code(401);
        exit(json_encode(['error' => 'Token expired or invalid']));
    }

    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM calendar_user_roles WHERE fieldclock_user_id = ? AND is_active = 1');
    $stmt->execute([$payload['user_id']]);
    $access = $stmt->fetch();

    if (!$access) {
        http_response_code(403);
        exit(json_encode(['error' => 'Not provisioned for Calendar']));
    }

    return [
        'user_id'   => (int)$payload['user_id'],
        'name'      => $access['name'],
        'role'      => $access['role'],
        'raw_token' => $token, // forwarded as-is to FieldClock on server-to-server calls (see services/fieldclock_client.php)
    ];
}

function requireAuth(): array {
    return resolveAuth(resolveAuthToken());
}

// For the two .ics endpoints only: a browser following a plain <a href> or
// webcal:// link can't send an Authorization header, so those accept the
// token as ?token=... instead. Never use this for anything but read-only
// calendar export links.
function requireAuthAllowQueryToken(): array {
    return resolveAuth(resolveAuthToken(allowQueryToken: true));
}

// Role matrix carried forward from v1's App\Models\User boolean helpers —
// Admin/Office can manage + see everything; Lead/Crew are read-only, scoped
// to their own assigned events (enforced by each endpoint's query, not here).
function canManageEvents(array $auth): bool {
    return in_array($auth['role'], ['Admin', 'Office'], true);
}

function canViewAllEvents(array $auth): bool {
    return in_array($auth['role'], ['Admin', 'Office'], true);
}

function canManageUsers(array $auth): bool {
    return $auth['role'] === 'Admin';
}

function requireManageEvents(array $auth): void {
    if (!canManageEvents($auth)) {
        http_response_code(403);
        exit(json_encode(['error' => 'Forbidden']));
    }
}

function requireViewAllEvents(array $auth): void {
    if (!canViewAllEvents($auth)) {
        http_response_code(403);
        exit(json_encode(['error' => 'Forbidden']));
    }
}

function requireManageUsers(array $auth): void {
    if (!canManageUsers($auth)) {
        http_response_code(403);
        exit(json_encode(['error' => 'Forbidden']));
    }
}

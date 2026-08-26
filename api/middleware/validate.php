<?php
function jsonBody(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}

function requireFields(array $body, array $fields): void {
    foreach ($fields as $field) {
        if (!isset($body[$field]) || $body[$field] === '') {
            http_response_code(422);
            exit(json_encode(['error' => "Missing required field: $field"]));
        }
    }
}

// A trim/normalize step, not HTML-escaping — every write goes through PDO
// prepared statements (no SQL injection concern) and every read is rendered
// by React via plain JSX text nodes, which already escapes correctly at
// render time. See jccs-fieldclock's middleware/validate.php for the fuller
// story on why this app family deliberately doesn't double-escape at storage.
function sanitizeString(mixed $val): string {
    return trim((string)$val);
}

// Deterministic per-user color, 10-color palette cycling by (fieldclock_user_id - 1) % 10.
// Ported from v1's EventApiController::formatEvent() so assignee colors stay
// stable across the rewrite for anyone who already associates a person with a color.
function userColor(int $fieldclockUserId): string {
    static $palette = ['#2563eb', '#16a34a', '#f97316', '#7c3aed', '#dc2626', '#0891b2', '#ca8a04', '#db2777', '#4f46e5', '#059669'];
    return $palette[($fieldclockUserId - 1) % count($palette)];
}

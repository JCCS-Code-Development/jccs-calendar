<?php
/**
 * VAPID-signed Web Push sender — ported from jccs-fieldclock/api/push/push-helper.php.
 *
 * Sends every push with an empty payload (no AES-GCM encryption implemented
 * here — this app family hand-rolls its own crypto rather than pulling in a
 * composer dependency, and full RFC 8291 payload encryption needs real EC
 * point arithmetic PHP's openssl extension doesn't expose). The service
 * worker (src/sw.js) shows one fixed "You have a calendar update" notification
 * regardless of what triggered it — same trade-off FieldClock already ships
 * in production. If per-event notification text is ever needed, this is the
 * place to add real payload encryption (or pull in a composer web-push lib).
 *
 * Requires config.php to define:
 *   VAPID_PUBLIC_KEY      – base64url-encoded uncompressed P-256 point (65 bytes)
 *   VAPID_PRIVATE_KEY_PEM – EC private key in PEM format
 *   VAPID_SUBJECT         – mailto: or https: URI identifying the sender
 */

function push_base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function push_der_to_raw_ecdsa(string $der): string {
    $pos = 2;
    if (ord($der[1]) & 0x80) $pos += (ord($der[1]) & 0x7F);
    $pos++; // skip 0x02
    $r_len = ord($der[$pos++]);
    $r = substr($der, $pos, $r_len); $pos += $r_len;
    $pos++; // skip 0x02
    $s_len = ord($der[$pos++]);
    $s = substr($der, $pos, $s_len);
    $r = str_pad(ltrim($r, "\x00"), 32, "\x00", STR_PAD_LEFT);
    $s = str_pad(ltrim($s, "\x00"), 32, "\x00", STR_PAD_LEFT);
    return $r . $s;
}

function push_vapid_jwt(string $endpoint): string {
    $parts    = parse_url($endpoint);
    $audience = $parts['scheme'] . '://' . $parts['host'];

    $header  = push_base64url_encode(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
    $payload = push_base64url_encode(json_encode([
        'aud' => $audience,
        'sub' => VAPID_SUBJECT,
        'exp' => time() + 43200,
    ]));

    $signing_input = "$header.$payload";
    openssl_sign($signing_input, $der_sig, VAPID_PRIVATE_KEY_PEM, OPENSSL_ALGO_SHA256);
    $raw_sig = push_der_to_raw_ecdsa($der_sig);

    return "$signing_input." . push_base64url_encode($raw_sig);
}

/** Sends an empty-payload push to one subscription row. Returns the HTTP status code. */
function send_push(array $sub): int {
    $jwt = push_vapid_jwt($sub['endpoint']);
    $ch  = curl_init($sub['endpoint']);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => '',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => [
            'Authorization: vapid t=' . $jwt . ', k=' . VAPID_PUBLIC_KEY,
            'Content-Type: application/octet-stream',
            'Content-Length: 0',
            'TTL: 86400',
            'Urgency: normal',
        ],
    ]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code;
}

/**
 * Sends a push to every subscription on file for a Calendar user. A push
 * failure here (missing table, dead subscription, network hiccup) must
 * never bubble up and abort the caller's real work (creating/updating an
 * event), so every failure is caught and swallowed. Stale subscriptions
 * (404/410 — the push service says this endpoint no longer exists) are
 * pruned automatically.
 */
function push_to_calendar_user(PDO $pdo, int $fieldclockUserId): void {
    try {
        $stmt = $pdo->prepare('SELECT id, endpoint, p256dh_key, auth_key FROM push_subscriptions WHERE fieldclock_user_id = ?');
        $stmt->execute([$fieldclockUserId]);
        foreach ($stmt->fetchAll() as $sub) {
            try {
                $code = send_push($sub);
                if ($code === 404 || $code === 410) {
                    $pdo->prepare('DELETE FROM push_subscriptions WHERE id = ?')->execute([$sub['id']]);
                }
            } catch (\Throwable $e) {
                error_log('[push] send_push failed: ' . $e->getMessage());
            }
        }
    } catch (\Throwable $e) {
        error_log('[push] push_to_calendar_user failed for fieldclock_user_id=' . $fieldclockUserId . ': ' . $e->getMessage());
    }
}

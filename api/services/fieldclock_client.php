<?php
// Server-to-server calls into FieldClock's API. Forwards the calling user's
// own FieldClock bearer token rather than a service account — this works
// because both apps validate against the same JWT_SECRET; FieldClock's
// endpoints just see "a valid FieldClock user" and apply their own gating
// (e.g. an admin-only endpoint 403s if the caller isn't a FieldClock admin
// too, independent of their Calendar role). No CORS involved — this is a
// PHP curl call, not a browser request. Mirrors jccs-projects's
// api/services/inventory_client.php.

function fieldclockRequest(string $method, string $path, string $bearerToken, ?array $body = null): array {
    $ch = curl_init(rtrim(FIELDCLOCK_API_URL, '/') . $path);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $bearerToken, 'Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 8,
    ];
    if ($body !== null) $opts[CURLOPT_POSTFIELDS] = json_encode($body);
    curl_setopt_array($ch, $opts);

    $response = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errno    = curl_errno($ch);
    curl_close($ch);

    if ($errno || $response === false) {
        return ['status' => 502, 'data' => ['error' => 'Could not reach FieldClock']];
    }
    return ['status' => $status, 'data' => json_decode($response, true) ?? []];
}

function fieldclockListJobs(string $bearerToken): array {
    return fieldclockRequest('GET', '/jobs/index.php', $bearerToken);
}

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
require_once __DIR__ . '/../services/fieldclock_client.php';

$auth = requireAuth();
requireManageEvents($auth);
$pdo = getPDO();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$result = fieldclockListJobs($auth['raw_token']);
if ($result['status'] !== 200) {
    http_response_code(502);
    exit(json_encode(['error' => 'Could not sync from FieldClock']));
}

// Upsert by fieldclock_job_id — never overwrites scheduling fields
// (projected_start/end, status, color, lead_time_days, photo) an admin has
// already set here; only fills in name/client/address on first sync and
// keeps them refreshed on later ones.
$stmt = $pdo->prepare('SELECT id FROM jobs WHERE fieldclock_job_id = ?');
$upsertExisting = $pdo->prepare('UPDATE jobs SET title = ?, address = ? WHERE id = ?');
$insertNew = $pdo->prepare('INSERT INTO jobs (title, address, status, fieldclock_job_id) VALUES (?, ?, ?, ?)');

$synced = 0;
foreach ($result['data']['jobs'] ?? [] as $fcJob) {
    if (empty($fcJob['id']) || empty($fcJob['name'])) continue;

    $stmt->execute([$fcJob['id']]);
    $existing = $stmt->fetch();

    if ($existing) {
        $upsertExisting->execute([sanitizeString($fcJob['name']), $fcJob['address'] ?? null, $existing['id']]);
    } else {
        $insertNew->execute([sanitizeString($fcJob['name']), $fcJob['address'] ?? null, 'Active', $fcJob['id']]);
    }
    $synced++;
}

echo json_encode(['message' => "Synced $synced job(s) from FieldClock"]);

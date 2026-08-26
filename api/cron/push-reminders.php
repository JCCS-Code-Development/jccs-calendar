<?php
/**
 * Run every 5 minutes via cron (replaces Laravel's Schedule::call in v1's
 * routes/console.php). Finds events starting ~60 minutes out (a 55-65
 * minute window, matching v1's tolerance for the 5-minute polling cadence)
 * and pushes an "upcoming" notification to the assignee.
 *
 * Invoke directly: php api/cron/push-reminders.php
 * Or over HTTP with ?secret=CRON_SECRET if the host can't run real cron.
 */
if (php_sapi_name() !== 'cli') {
    require_once __DIR__ . '/../config/config.php';
    if (($_GET['secret'] ?? '') !== CRON_SECRET) {
        http_response_code(403);
        exit('Forbidden');
    }
} else {
    require_once __DIR__ . '/../config/config.php';
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../push/push-helper.php';

$pdo = getPDO();
$tz  = new DateTimeZone(CALENDAR_TIMEZONE);
$now = new DateTimeImmutable('now', $tz);

$windowStart = $now->modify('+55 minutes')->format('Y-m-d H:i:s');
$windowEnd   = $now->modify('+65 minutes')->format('Y-m-d H:i:s');

$stmt = $pdo->prepare(
    "SELECT id, assigned_user_id FROM events
     WHERE assigned_user_id IS NOT NULL
       AND status NOT IN ('Completed', 'Cancelled')
       AND start_datetime BETWEEN ? AND ?"
);
$stmt->execute([$windowStart, $windowEnd]);

$count = 0;
foreach ($stmt->fetchAll() as $event) {
    push_to_calendar_user($pdo, (int)$event['assigned_user_id']);
    $count++;
}

echo "Sent $count reminder(s)\n";

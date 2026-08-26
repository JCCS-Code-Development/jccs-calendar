<?php
// ─────────────────────────────────────────────────
// JCCS Calendar — server configuration TEMPLATE
// Copy this to config.php on the server (never commit config.php itself —
// it's gitignored, same as FieldClock's). Protect with .htaccess: Deny from all
// ─────────────────────────────────────────────────

// Database — this is Calendar's OWN separate database, not FieldClock's.
define('DB_HOST', 'localhost');
define('DB_NAME', 'jccs_calendar');
define('DB_USER', 'calendar_user');
define('DB_PASS', 'CHANGE_ME');

// JWT — MUST be copied verbatim from FieldClock's production config.php
// (api/config/config.php, JWT_SECRET constant) so a token issued by
// FieldClock's login validates here too. Do not generate a new one.
define('JWT_SECRET', 'COPY_FROM_FIELDCLOCK_CONFIG_PHP');

// App
define('FRONTEND_ORIGIN', 'https://calendar.jccs-services.com');

// The API's own public base URL — used to build absolute URLs for uploaded
// job photos (api/uploads/jobs/...). No trailing slash.
define('APP_URL', 'https://calendar.jccs-services.com/api');

// Cross-app: FieldClock's API base, used by services/fieldclock_client.php
// for the Jobs "Sync from FieldClock" action.
define('FIELDCLOCK_API_URL', 'https://fieldclock.jccs-services.com/api');

// Web Push (generate with: php api/push/generate-vapid.php)
define('VAPID_PUBLIC_KEY', 'GENERATE_ME');
define('VAPID_PRIVATE_KEY_PEM', <<<'EOK'
GENERATE_ME
EOK);
define('VAPID_SUBJECT', 'mailto:juliannaccalle@jccs-services.com');

// Secret for cron scripts hit over HTTP instead of CLI (defense-in-depth
// only — not needed when a real cron job invokes the script directly via
// `php api/cron/push-reminders.php`).
define('CRON_SECRET', 'CHANGE_ME');

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

// Office Operations Board — shared PIN that unlocks Edit Mode on the board.
// The board itself (the read-only TV view) needs no login; every board write
// endpoint checks this value against the request's X-Board-Pin header.
// Pick something short the office can type on a TV remote / touch screen.
define('OPS_BOARD_PIN', 'CHANGE_ME');

// The API's own public base URL — used to build absolute URLs for uploaded
// job photos (api/uploads/jobs/...). No trailing slash.
define('APP_URL', 'https://calendar.jccs-services.com/api');

// Cross-app: FieldClock's API base, used by services/fieldclock_client.php
// for the Jobs "Sync from FieldClock" action.
define('FIELDCLOCK_API_URL', 'https://fieldclock.jccs-services.com/api');

// One shared token for the read-only "board" endpoints on FieldClock,
// Inventory and Projects (timeclock/board-active.php, projects/board-lookup.php,
// projects/board-summary.php). Generate one
// (php -r "echo bin2hex(random_bytes(24));") and paste the SAME value into
// each of those apps' config.php as OPS_BOARD_TOKEN. Leave CHANGE_ME to
// disable all three integrations.
define('OPS_BOARD_TOKEN', 'CHANGE_ME');
// Legacy alias — if only this is set, it's used as the service token too.
define('FIELDCLOCK_BOARD_TOKEN', 'CHANGE_ME');

// Sibling apps the board reads from server-to-server (no trailing slash).
define('INVENTORY_API_URL', 'https://inventory.jccs-services.com/api');
define('PROJECTS_API_URL',  'https://projects.jccs-services.com/api');

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

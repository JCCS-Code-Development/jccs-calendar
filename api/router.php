<?php
// Router for the local PHP dev server only (php -S localhost:8787 -t . router.php
// run from inside api/, matching jccs-fieldclock's "dev:api" script). The
// built-in server ignores .htaccess, so this mirrors that file's route
// table — keep the two in sync. Production (Apache/cPanel) never touches
// this file.

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = '/' . ltrim($uri, '/');

// Let the built-in server serve real files (uploaded photos, etc.) as-is.
$asFile = __DIR__ . $uri;
if ($uri !== '/' && is_file($asFile)) {
    return false;
}

$routes = [
    '#^/events/(\d+)/mark-done$#' => ['events/mark_done.php', 'id'],
    '#^/events/(\d+)/export\.ics$#' => ['events/export_ics.php', 'id'],
    '#^/events/(\d+)$#'           => ['events/item.php', 'id'],
    '#^/events$#'                 => ['events/index.php', null],
    '#^/my-events$#'              => ['events/my.php', null],
    '#^/todos$#'                  => ['events/todos.php', null],
    '#^/calendar-events$#'        => ['events/calendar.php', null],
    '#^/event-types$#'            => ['event-types/index.php', null],
    '#^/external-calendar$#'      => ['calendar/external.php', null],
    '#^/calendar\.ics$#'          => ['calendar/feed.php', null],

    '#^/users/(\d+)$#' => ['users/item.php', 'id'],
    '#^/users$#'       => ['users/index.php', null],
    '#^/roles$#'       => ['roles/index.php', null],

    '#^/clients/(\d+)$#' => ['clients/item.php', 'id'],
    '#^/clients$#'       => ['clients/index.php', null],

    '#^/jobs/sync-fieldclock$#' => ['jobs/sync_fieldclock.php', null],
    '#^/jobs/photo$#'           => ['jobs/photo.php', null],
    '#^/jobs/(\d+)$#'           => ['jobs/item.php', 'id'],
    '#^/jobs$#'                 => ['jobs/index.php', null],

    '#^/push/key$#'         => ['push/key.php', null],
    '#^/push/subscribe$#'   => ['push/subscribe.php', null],
    '#^/push/unsubscribe$#' => ['push/unsubscribe.php', null],

    '#^/auth/verify$#'  => ['auth/verify.php', null],
    '#^/auth/login$#'   => ['auth/login.php', null],
    '#^/auth/refresh$#' => ['auth/refresh.php', null],
    '#^/auth/logout$#'  => ['auth/logout.php', null],
];

foreach ($routes as $pattern => [$file, $param]) {
    if (preg_match($pattern, $uri, $m)) {
        if ($param) $_GET[$param] = $m[1];
        require __DIR__ . '/' . $file;
        return true;
    }
}

http_response_code(404);
echo json_encode(['error' => 'Not found']);
return true;

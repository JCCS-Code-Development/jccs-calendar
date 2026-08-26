<?php
ini_set('display_errors', 0);
set_exception_handler(function ($e) { http_response_code(500); echo json_encode(['error' => $e->getMessage()]); exit; });
set_error_handler(function ($s, $m, $f, $l) { throw new ErrorException($m, 0, $s, $f, $l); });

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../middleware/auth.php';

// Static — role is now an enum on calendar_user_roles, not a database table.
// `id` is the role name itself so getRoles()'s existing {id,name} shape
// (built for v1's roles.id foreign key) keeps working unchanged.
requireAuth();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); exit; }

echo json_encode([
    ['id' => 'Admin',  'name' => 'Admin'],
    ['id' => 'Office', 'name' => 'Office'],
    ['id' => 'Lead',   'name' => 'Lead'],
    ['id' => 'Crew',   'name' => 'Crew'],
]);

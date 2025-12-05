<?php
// vehicle_management/api/config/seed_session_user.php
// Temporary testing endpoint: set $_SESSION['user'] for the current session if correct secret provided.
// USAGE (temporary):
// fetch('/vehicle_management/api/config/seed_session_user.php?secret=YOUR_SECRET', { credentials:'include' })
// WARNING: remove this file after testing.

require_once __DIR__ . '/session.php'; // reuse session config
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

header('Content-Type: application/json; charset=utf-8');

// secret (change to a random strong value, remove file later)
$expected = 'CHANGE_ME_STRONG_SECRET_2025';
$provided = $_GET['secret'] ?? '';

if ($provided !== $expected) {
    echo json_encode(['success'=>false,'message'=>'Invalid secret (for safety)']);
    exit;
}

// seed a test user (use real user data or the one you want)
$_SESSION['user'] = [
    'id' => 15,
    'emp_id' => '28332',
    'username' => 'mahmoud zidan',
    'email' => 'zedanmahmoud99@gmail.com',
    'phone' => '971559740334',
    'preferred_language' => 'ar',
    'role_id' => 2,
    'is_active' => 1,
    'department_id' => 1,
    'section_id' => 1,
    'division_id' => 2
];

session_write_close();

echo json_encode(['success'=>true,'message'=>'Session user injected','user'=>$_SESSION['user'] ?? null]);
exit;
?>
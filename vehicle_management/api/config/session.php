<?php
// vehicle_management/api/config/session.php
// Central session configuration — include this before any session_start() call.
// FIX: SameSite='Lax' دائمًا للتوافق، خاصة في HTTPS mixed.

error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

$cookieLifetime = 0; // session cookie
$cookiePath = '/'; // تغطية كاملة
$cookieDomain = $_SERVER['HTTP_HOST'];
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
$httponly = true;
$sameSite = 'Lax'; // FIX: Lax دائمًا (أفضل من None للتوافق، يمنع CSRF دون حظر cross-site GET)

if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params([
        'lifetime' => $cookieLifetime,
        'path' => $cookiePath,
        'domain' => $cookieDomain,
        'secure' => $secure,
        'httponly' => $httponly,
        'samesite' => $sameSite
    ]);
} else {
    ini_set('session.cookie_lifetime', $cookieLifetime);
    ini_set('session.cookie_path', $cookiePath);
    ini_set('session.cookie_domain', $cookieDomain);
    ini_set('session.cookie_secure', $secure ? '1' : '0');
    ini_set('session.cookie_httponly', $httponly ? '1' : '0');
}

$isInit = isset($_GET['init']) && $_GET['init'] == '1';
if ($isInit) {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    echo json_encode([
        'success' => true,
        'message' => 'Session config applied',
        'session_id' => session_id(),
        'cookie_path' => $cookiePath,
        'secure' => $secure,
        'samesite' => $sameSite
    ]);
    exit;
}
?>

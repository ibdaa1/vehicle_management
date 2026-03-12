<?php
// vehicle_management/api/users/session_check.php
// session_check: supports debug (?debug=1), PHP session auth OR token auth.
// Returns JSON { success: true, user: { ... }, isLoggedIn: true } or { success:false, message: 'Not authenticated', isLoggedIn: false }.

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// تضمين ملف قاعدة البيانات (المسار الصحيح المؤكد)
require_once __DIR__ . '/../config/db.php'; // تم تثبيت المسار بناءً على طلبك

// helper to read headers (لا حاجة للتعديل)
function get_headers_normalized() {
    $h = [];
    if (function_exists('getallheaders')) {
        foreach (getallheaders() as $k => $v) $h[strtolower($k)] = $v;
    } else {
        foreach ($_SERVER as $k => $v) {
            if (substr($k, 0, 5) === 'HTTP_') {
                $name = strtolower(str_replace(' ', '-', str_replace('_', ' ', substr($k,5))));
                $h[$name] = $v;
            }
        }
    }
    return $h;
}

// محاولة جلب التوكن من Authorization: Bearer أو X-Auth-Token
$headers = get_headers_normalized();
$token = null;
if (!empty($headers['authorization'])) {
    if (preg_match('/Bearer\s+(.+)/i', $headers['authorization'], $m)) $token = trim($m[1]);
}
if (!$token && !empty($headers['x-auth-token'])) $token = trim($headers['x-auth-token']);
if (!$token && !empty($headers['x-session-token'])) $token = trim($headers['x-session-token']);

// قبول معرف الجلسة عبر cookie أو X-Session-Id header
$sessionName = session_name();
$providedSid = null;
if (!empty($_COOKIE[$sessionName])) $providedSid = $_COOKIE[$sessionName];
elseif (!empty($headers['x-session-id'])) $providedSid = $headers['x-session-id'];
elseif (!empty($_REQUEST['PHPSESSID'])) $providedSid = $_REQUEST['PHPSESSID'];

if ($providedSid && preg_match('/^[a-zA-Z0-9,-]{5,128}$/', $providedSid)) {
    session_id($providedSid);
}
// بدء الجلسة
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// debug flag
$debug = (isset($_GET['debug']) && $_GET['debug'] == '1');

// -----------------------------------------------------------------
// 💡 دالة لجلب بيانات المستخدم كاملة من قاعدة البيانات
// -----------------------------------------------------------------
function getUserDataById(mysqli $conn, $userId) {
    if (!$userId) return null;

    // 💡 تم تعديل الاستعلام ليشمل: email, phone, preferred_language, department_id, section_id, division_id
    $stmt = $conn->prepare("
        SELECT id, emp_id, username, email, phone, role_id, preferred_language, department_id, section_id, division_id
        FROM users
        WHERE id = ?
        LIMIT 1
    ");
    
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($row) {
            return [
                'id' => (int)$row['id'],
                'emp_id' => $row['emp_id'],
                'username' => $row['username'],
                'email' => $row['email'],
                'phone' => $row['phone'],
                'role_id' => (int)$row['role_id'],
                'preferred_language' => $row['preferred_language'] ?? 'ar',
                'department_id' => (int)$row['department_id'],
                'section_id' => (int)$row['section_id'],
                'division_id' => (int)$row['division_id']
            ];
        }
    }
    return null;
}

// التحقق من التوكن (يتطلب قاعدة البيانات)
$user = null;
$userIdFromToken = null;

if ($token && isset($conn)) {
    try {
        // استعلام لجلب معرف المستخدم (ID) من التوكن
        $stmt = $conn->prepare("
            SELECT user_id
            FROM user_sessions
            WHERE token = ? AND revoked = 0 AND expires_at > NOW()
            LIMIT 1
        ");
        if ($stmt) {
            $stmt->bind_param('s', $token);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            $stmt->close();
            if ($row) {
                $userIdFromToken = (int)$row['user_id'];
                $user = getUserDataById($conn, $userIdFromToken);
            }
        }
    } catch (Exception $e) {
        error_log("session_check token lookup error: " . $e->getMessage());
    }
}

// إذا فشلت مصادقة التوكن، الرجوع إلى مستخدم الجلسة وتحديث بياناته
if (!$user) {
    $sessUser = $_SESSION['user'] ?? null;
    if ($sessUser && !empty($sessUser['id'])) {
        $userIdFromSession = (int)$sessUser['id'];
        
        // 💡 تحديث بيانات المستخدم من DB لضمان الحصول على email/phone/department
        $freshUser = getUserDataById($conn, $userIdFromSession);
        
        if ($freshUser) {
            $user = $freshUser;
            // 💡 تحديث الجلسة بالبيانات الجديدة (لتجنب البحث في DB في كل مرة)
            $_SESSION['user'] = $user; 
        } else {
            // بيانات الجلسة قديمة أو غير صالحة
            session_destroy();
        }
    }
}

// -----------------------------------------------------------------
// مخرجات Debug (لا تعديل)
// -----------------------------------------------------------------
if ($debug) {
    echo json_encode([
        'debug' => true,
        'request_time' => date('c'),
        'session_name' => session_name(),
        'active_session_id' => session_id(),
        'session_status' => session_status(),
        'session_contents' => isset($_SESSION) ? $_SESSION : null,
        'token_received' => $token ?: null,
        'user_data' => $user ?: null,
        'headers' => $headers,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// -----------------------------------------------------------------
// الاستجابة النهائية العادية (المُعدَّلة)
// -----------------------------------------------------------------
if ($user && !empty($user['id'])) {
    // استجابة النجاح: إضافة isLoggedIn: true
    echo json_encode([
        'success' => true, 
        'user' => $user,
        'isLoggedIn' => true
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// استجابة الفشل: إضافة isLoggedIn: false
echo json_encode([
    'success' => false, 
    'message' => 'Not authenticated',
    'isLoggedIn' => false
], JSON_UNESCAPED_UNICODE);
exit;
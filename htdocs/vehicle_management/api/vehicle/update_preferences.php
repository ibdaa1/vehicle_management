<?php
// vehicle_management/api/users/update_preferences.php

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

// --- 1. بدء الجلسة والتحقق من المستخدم ---
session_start();
if (!isset($_SESSION['user']) || empty($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'غير مسجل الدخول. يرجى تسجيل الدخول أولاً.',
        'isLoggedIn' => false
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$user = $_SESSION['user'];
$user_id = $user['id'];
$user_emp_id = $user['emp_id'];

// --- 2. السماح بـ POST فقط ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'الطريقة غير مسموحة. استخدم POST.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- 3. قراءة بيانات JSON المدخلة ---
$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'بيانات JSON غير صالحة.',
        'json_error' => json_last_error_msg()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($input['preferred_language'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'الحقل preferred_language مطلوب.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// التحقق من CSRF token (اختياري)
if (isset($input['csrf_token']) && (!isset($_SESSION['csrf_token']) || $input['csrf_token'] !== $_SESSION['csrf_token'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'رمز الحماية غير صالح أو منتهي الصلاحية.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// التحقق من صحة اللغة
$allowed_languages = ['ar', 'en'];
$preferred_language = trim($input['preferred_language']);
if (!in_array($preferred_language, $allowed_languages)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'اللغة غير مدعومة. اللغات المدعومة: ' . implode(', ', $allowed_languages)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- 4. تضمين DB + Timezone ---
$dbPaths = [
    __DIR__ . '/../../config/db.php',
    __DIR__ . '/../config/db.php',
    __DIR__ . '/config/db.php'
];
$dbIncluded = false;
foreach ($dbPaths as $p) {
    if (file_exists($p)) {
        require_once $p;
        $dbIncluded = true;
        break;
    }
}

if (!$dbIncluded || !function_exists('getConnection')) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'ملف إعدادات قاعدة البيانات مفقود أو الدالة getConnection غير موجود.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$conn = getConnection();
if (!$conn || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'فشل الاتصال بقاعدة البيانات.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// تضمين timezone إذا وجد
$timezonePaths = [
    __DIR__ . '/../../config/timezone.php',
    __DIR__ . '/../config/timezone.php',
    __DIR__ . '/config/timezone.php'
];
foreach ($timezonePaths as $tz) {
    if (file_exists($tz)) { require_once $tz; break; }
}

try {
    // --- 5. تحديث تفضيل اللغة للمستخدم ---
    $sql = "UPDATE users SET preferred_language = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception("فشل في إعداد الاستعلام: " . $conn->error);

    $stmt->bind_param("si", $preferred_language, $user_id);
    if (!$stmt->execute()) throw new Exception("فشل في تحديث التفضيلات: " . $stmt->error);
    $stmt->close();

    // تحديث الجلسة
    $_SESSION['user']['preferred_language'] = $preferred_language;

    // --- 6. تسجيل النشاط في سجل النشاطات ---
    $activity_stmt = $conn->prepare("
        INSERT INTO activity_logs (
            user_id, emp_id, activity_type, description, table_name, record_id, ip_address, user_agent, created_at
        ) VALUES (?, ?, ?, ?, 'users', ?, ?, ?, NOW())
    ");
    if ($activity_stmt) {
        $activity_type = 'update_preferences';
        $activity_description = "قام بتحديث تفضيلات اللغة إلى {$preferred_language}";
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $activity_stmt->bind_param("isssisss", $user_id, $user_emp_id, $activity_type, $activity_description, $user_id, $ip_address, $user_agent);
        $activity_stmt->execute();
        $activity_stmt->close();
    }

    // --- 7. إعادة بيانات المستخدم المحدثة ---
    $user_stmt = $conn->prepare("
        SELECT id, emp_id, username, email, phone, role_id, 
               preferred_language, profile_image, is_active,
               department_id, section_id, division_id,
               created_at, updated_at
        FROM users WHERE id = ?
    ");
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $updated_user = $user_stmt->get_result()->fetch_assoc();
    $user_stmt->close();
    $conn->close();

    $_SESSION['user'] = array_merge($_SESSION['user'], $updated_user);

    echo json_encode([
        'success' => true,
        'message' => 'تم تحديث التفضيلات بنجاح.',
        'preferred_language' => $preferred_language,
        'user' => $updated_user,
        'session_updated' => true
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ في الخادم: ' . $e->getMessage(),
        'error_details' => $conn->error ?? 'Unknown error'
    ], JSON_UNESCAPED_UNICODE);
    error_log("Error in update_preferences.php: " . $e->getMessage() . " - " . ($conn->error ?? ''));
}

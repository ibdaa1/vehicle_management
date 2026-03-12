<?php
// vehicle_management/api/vehicle/delete_violation.php

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

session_start();

// التحقق من الجلسة والصلاحيات
if (!isset($_SESSION['user']) || empty($_SESSION['user']['emp_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غير مسجل الدخول']);
    exit;
}

$user = $_SESSION['user'];
$user_role_id = $user['role_id'] ?? 0;
$is_admin = in_array($user_role_id, [1, 2]);

if (!$is_admin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ليس لديك صلاحية']);
    exit;
}

// تضمين DB
$paths = [
    __DIR__ . '/../../config/db.php',
    __DIR__ . '/../config/db.php',
    __DIR__ . '/config/db.php'
];
foreach ($paths as $p) {
    if (file_exists($p)) {
        require_once $p;
        break;
    }
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server misconfiguration: DB connection missing']);
    exit;
}

// الحصول على البيانات
$violation_id = isset($_POST['violation_id']) ? intval($_POST['violation_id']) : 0;

if ($violation_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'رقم المخالفة غير صالح']);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM vehicle_violations WHERE id = ?");
    $stmt->bind_param("i", $violation_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'تم حذف المخالفة بنجاح'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'المخالفة غير موجودة'
        ], JSON_UNESCAPED_UNICODE);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ: ' . $e->getMessage()
    ]);
}
?>
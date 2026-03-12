<?php
// vehicle_management/api/vehicle/update_violation.php

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
$violation_amount = isset($_POST['violation_amount']) ? floatval($_POST['violation_amount']) : null;
$violation_status = isset($_POST['violation_status']) ? trim($_POST['violation_status']) : null;
$payment_datetime = isset($_POST['payment_datetime']) ? trim($_POST['payment_datetime']) : null;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;

if ($violation_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'رقم المخالفة غير صالح']);
    exit;
}

try {
    // بناء استعلام التحديث الديناميكي
    $updates = [];
    $params = [];
    $types = "";
    
    if ($violation_amount !== null) {
        $updates[] = "violation_amount = ?";
        $params[] = $violation_amount;
        $types .= "d";
    }
    
    if ($violation_status !== null) {
        $updates[] = "violation_status = ?";
        $params[] = $violation_status;
        $types .= "s";
        
        if ($violation_status === 'paid') {
            $updates[] = "paid_by_emp_id = ?";
            $params[] = $user['emp_id'];
            $types .= "s";
            
            if ($payment_datetime) {
                $updates[] = "payment_datetime = ?";
                $params[] = $payment_datetime;
                $types .= "s";
            } else {
                $updates[] = "payment_datetime = NOW()";
            }
        }
    }
    
    if ($notes !== null) {
        $updates[] = "notes = ?";
        $params[] = $notes;
        $types .= "s";
    }
    
    $updates[] = "updated_at = NOW()";
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'لا توجد بيانات للتحديث']);
        exit;
    }
    
    $sql = "UPDATE vehicle_violations SET " . implode(", ", $updates) . " WHERE id = ?";
    $params[] = $violation_id;
    $types .= "i";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'تم تحديث المخالفة بنجاح'
    ], JSON_UNESCAPED_UNICODE);
    
    $stmt->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ: ' . $e->getMessage()
    ]);
}
?>
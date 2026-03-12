<?php
// vehicle_management/api/vehicle/add_vehicle_violations.php

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

// --- 1. بدء الجلسة والتحقق من المستخدم ---
session_start();
if (!isset($_SESSION['user']) || empty($_SESSION['user']['emp_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'غير مسجل الدخول. يرجى تسجيل الدخول أولاً.',
        'isLoggedIn' => false
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$user = $_SESSION['user'];
$user_emp_id = $user['emp_id'];
$user_role_id = $user['role_id'] ?? 0;
$user_id = $user['id'] ?? 0;
$is_admin = in_array($user_role_id, [1,2]);

// --- 2. دعم GET للاختبار فقط ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!$is_admin) {
        http_response_code(403);
        echo json_encode(['success'=>false,'message'=>'ليس لديك صلاحية للوصول إلى هذه الصفحة.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo json_encode([
        'success' => true,
        'message' => 'طلب GET: استخدم POST لتسجيل المخالفة.',
        'user_emp_id' => $user_emp_id,
        'user_role_id' => $user_role_id,
        'is_admin' => $is_admin
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- 3. التحقق من POST ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'الطريقة غير مسموحة. استخدم POST.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- 4. تضمين DB + Timezone ---
$dbPaths = [
    __DIR__ . '/../../config/db.php',
    __DIR__ . '/../config/db.php',
    __DIR__ . '/config/db.php'
];
$dbIncluded = false;
foreach ($dbPaths as $p) { if(file_exists($p)) { require_once $p; $dbIncluded = true; break; } }
if(!$dbIncluded || !isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'ملف إعدادات قاعدة البيانات مفقود أو الاتصال غير موجود.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$tzPaths = [
    __DIR__ . '/../../config/timezone.php',
    __DIR__ . '/../config/timezone.php',
    __DIR__ . '/config/timezone.php'
];
foreach ($tzPaths as $p) { if(file_exists($p)) require_once $p; }

// --- 5. صلاحيات المسؤول ---
if(!$is_admin) {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'ليس لديك صلاحية لإضافة مخالفات مركبات.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- 6. التحقق من البيانات المطلوبة ---
$required_fields = ['vehicle_code','violation_datetime','violation_amount'];
$missing_fields = [];
foreach($required_fields as $f) { if(!isset($_POST[$f]) || trim($_POST[$f])==='') $missing_fields[]=$f; }
if(!empty($missing_fields)) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'بيانات ناقصة: '.implode(', ',$missing_fields)], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- 7. تنظيف البيانات ---
$vehicle_code = trim($_POST['vehicle_code']);
$violation_datetime = trim($_POST['violation_datetime']);
$violation_amount = floatval($_POST['violation_amount']);
$violation_status = isset($_POST['violation_status']) ? trim($_POST['violation_status']) : 'unpaid';
$payment_datetime = isset($_POST['payment_datetime']) ? trim($_POST['payment_datetime']) : null;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
$issued_by_emp_id = $user_emp_id;

if($violation_amount <= 0) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'مبلغ المخالفة يجب أن يكون أكبر من صفر.'], JSON_UNESCAPED_UNICODE);
    exit;
}
if(!in_array($violation_status,['unpaid','paid'])) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'حالة المخالفة غير صالحة.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // --- 8. التحقق من وجود المركبة ---
    $stmt = $conn->prepare("SELECT id FROM vehicles WHERE vehicle_code=?");
    $stmt->bind_param("s",$vehicle_code);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows===0) {
        http_response_code(404);
        echo json_encode(['success'=>false,'message'=>'المركبة غير موجودة في النظام.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $vehicle = $result->fetch_assoc();
    $vehicle_id = $vehicle['id'];
    $stmt->close();

    // --- 9. إدخال المخالفة ---
    $stmt = $conn->prepare("
        INSERT INTO vehicle_violations 
        (vehicle_id, vehicle_code, violation_datetime, violation_amount, violation_status, payment_datetime, issued_by_emp_id, notes, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("issdssss", $vehicle_id, $vehicle_code, $violation_datetime, $violation_amount, $violation_status, $payment_datetime, $issued_by_emp_id, $notes);
    if(!$stmt->execute()) throw new Exception("فشل في إضافة المخالفة: ".$conn->error);
    $violation_id = $conn->insert_id;
    $stmt->close();

    // --- 10. تسجيل النشاط ---
    $activity_stmt = $conn->prepare("
        INSERT INTO activity_logs 
        (user_id, emp_id, activity_type, description, table_name, record_id, ip_address, user_agent, created_at)
        VALUES (?, ?, ?, ?, 'vehicle_violations', ?, ?, ?, NOW())
    ");
    $activity_type = 'add_violation';
    $activity_description = "تمت إضافة مخالفة للمركبة {$vehicle_code} بمبلغ {$violation_amount} درهم";
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $activity_stmt->bind_param("isssiss",$user_id,$user_emp_id,$activity_type,$activity_description,$violation_id,$ip_address,$user_agent);
    $activity_stmt->execute();
    $activity_stmt->close();

    // --- 11. جلب بيانات المخالفة المضافة ---
    $stmt = $conn->prepare("
        SELECT vv.*, v.driver_name, v.emp_id AS vehicle_emp_id, u.username AS issued_by_name 
        FROM vehicle_violations vv 
        LEFT JOIN vehicles v ON vv.vehicle_id=v.id
        LEFT JOIN users u ON vv.issued_by_emp_id=u.emp_id
        WHERE vv.id=?
    ");
    $stmt->bind_param("i",$violation_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $violation_details = $res->fetch_assoc();
    $stmt->close();
    $conn->close();

    http_response_code(201);
    echo json_encode([
        'success'=>true,
        'message'=>'تمت إضافة المخالفة بنجاح.',
        'violation_id'=>$violation_id,
        'violation'=>$violation_details
    ], JSON_UNESCAPED_UNICODE);

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'حدث خطأ في الخادم: '.$e->getMessage()], JSON_UNESCAPED_UNICODE);
    error_log("Error in add_vehicle_violations.php: ".$e->getMessage());
}
?>

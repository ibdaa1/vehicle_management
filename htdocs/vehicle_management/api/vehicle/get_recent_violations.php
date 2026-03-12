<?php
// vehicle_management/api/vehicle/get_recent_violations.php

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
$is_admin = in_array($user_role_id, [1, 2]);

// --- 2. السماح بـ GET فقط ---
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'الطريقة غير مسموحة. استخدم GET.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- 3. تضمين DB ---
$dbPaths = [
    __DIR__ . '/../../config/db.php',
    __DIR__ . '/../config/db.php',
    __DIR__ . '/config/db.php'
];

$dbIncluded = false;
foreach ($dbPaths as $p) {
    if (file_exists($p)) { require_once $p; $dbIncluded = true; break; }
}

if (!$dbIncluded || !isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'ملف إعدادات قاعدة البيانات مفقود أو الاتصال غير موجود.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- 4. الحصول على المعاملات ---
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
$days = isset($_GET['days']) ? intval($_GET['days']) : 7;
$vehicle_id = isset($_GET['vehicle_id']) ? intval($_GET['vehicle_id']) : null;
$status = isset($_GET['status']) ? $_GET['status'] : null;

$limit = ($limit < 1 || $limit > 100) ? 5 : $limit;
$days = ($days < 1 || $days > 365) ? 7 : $days;

try {
    // --- 5. بناء شروط البحث ---
    $where = ["1=1"];
    $paramTypes = "";
    $params = [];

    $dateThreshold = date('Y-m-d H:i:s', strtotime("-$days days"));
    $where[] = "vv.created_at >= ?";
    $paramTypes .= "s";
    $params[] = $dateThreshold;

    if ($vehicle_id !== null) {
        $where[] = "vv.vehicle_id = ?";
        $paramTypes .= "i";
        $params[] = $vehicle_id;
    }

    if ($status !== null && in_array($status, ['unpaid', 'paid'])) {
        $where[] = "vv.violation_status = ?";
        $paramTypes .= "s";
        $params[] = $status;
    }

    if (!$is_admin) {
        $user_dept_id = $user['department_id'] ?? 0;
        $user_section_id = $user['section_id'] ?? 0;
        $user_division_id = $user['division_id'] ?? 0;

        $where[] = "(v.department_id = ? OR v.section_id = ? OR v.division_id = ? OR ? = 0)";
        $paramTypes .= "iiii";
        $params[] = $user_dept_id;
        $params[] = $user_section_id;
        $params[] = $user_division_id;
        $params[] = $user_dept_id;
    }

    $whereClause = implode(" AND ", $where);

    // --- 6. استعلام البيانات ---
    $sql = "
        SELECT 
            vv.id, vv.vehicle_id, vv.vehicle_code, vv.violation_datetime,
            vv.violation_amount, vv.violation_status, vv.issued_by_emp_id,
            vv.paid_by_emp_id, vv.payment_datetime, vv.payment_attachment,
            vv.notes, vv.created_at, vv.updated_at,
            v.driver_name, v.type as vehicle_type, v.emp_id as vehicle_emp_id,
            v.status as vehicle_status, v.department_id, v.section_id, v.division_id,
            u1.username as issued_by_name, u2.username as paid_by_name
        FROM vehicle_violations vv
        LEFT JOIN vehicles v ON vv.vehicle_id = v.id
        LEFT JOIN users u1 ON vv.issued_by_emp_id = u1.emp_id
        LEFT JOIN users u2 ON vv.paid_by_emp_id = u2.emp_id
        WHERE $whereClause
        ORDER BY vv.created_at DESC
        LIMIT ?
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception("فشل في إعداد الاستعلام: " . $conn->error);

    $paramTypes .= "i";
    $params[] = $limit;
    $stmt->bind_param($paramTypes, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $violations = [];
    $total_amount = 0;
    $unpaid_amount = 0;

    while ($row = $result->fetch_assoc()) {
        $row['violation_amount'] = floatval($row['violation_amount']);
        $row['violation_amount_formatted'] = number_format($row['violation_amount'], 2);

        $row['vehicle_code'] = htmlspecialchars($row['vehicle_code'] ?? '', ENT_QUOTES, 'UTF-8');
        $row['driver_name'] = htmlspecialchars($row['driver_name'] ?? '', ENT_QUOTES, 'UTF-8');
        $row['issued_by_name'] = htmlspecialchars($row['issued_by_name'] ?? '', ENT_QUOTES, 'UTF-8');
        $row['paid_by_name'] = htmlspecialchars($row['paid_by_name'] ?? '', ENT_QUOTES, 'UTF-8');
        $row['vehicle_type'] = htmlspecialchars($row['vehicle_type'] ?? '', ENT_QUOTES, 'UTF-8');
        $row['notes'] = htmlspecialchars($row['notes'] ?? '', ENT_QUOTES, 'UTF-8');

        $row['violation_datetime_formatted'] = !empty($row['violation_datetime']) ? date('Y-m-d H:i:s', strtotime($row['violation_datetime'])) : '';
        $row['payment_datetime_formatted'] = !empty($row['payment_datetime']) ? date('Y-m-d H:i:s', strtotime($row['payment_datetime'])) : '';
        $row['created_at_formatted'] = !empty($row['created_at']) ? date('Y-m-d H:i:s', strtotime($row['created_at'])) : '';
        $row['updated_at_formatted'] = !empty($row['updated_at']) ? date('Y-m-d H:i:s', strtotime($row['updated_at'])) : '';

        switch ($row['violation_status']) {
            case 'unpaid':
                $row['violation_status_ar'] = 'غير مدفوعة';
                $row['violation_status_en'] = 'Unpaid';
                $row['violation_status_class'] = 'status-unpaid';
                $unpaid_amount += $row['violation_amount'];
                break;
            case 'paid':
                $row['violation_status_ar'] = 'مدفوعة';
                $row['violation_status_en'] = 'Paid';
                $row['violation_status_class'] = 'status-paid';
                break;
        }

        $total_amount += $row['violation_amount'];
        $violations[] = $row;
    }

    $stmt->close();
    $conn->close();

    echo json_encode([
        'success' => true,
        'message' => count($violations) > 0 ? 'تم تحميل المخالفات' : 'لا توجد مخالفات حديثة',
        'count' => count($violations),
        'total_amount' => $total_amount,
        'unpaid_amount' => $unpaid_amount,
        'violations' => $violations,
        'limit' => $limit,
        'days' => $days,
        'is_admin' => $is_admin,
        'generated_at' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ في الخادم: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    error_log("Error in get_recent_violations.php: " . $e->getMessage());
}

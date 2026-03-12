<?php
// vehicle_management/api/vehicle/get_violation_details.php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

// -------------------------------
// 1) تضمين إعدادات قاعدة البيانات
// -------------------------------
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

if (!$dbIncluded || !isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'ملف إعدادات قاعدة البيانات مفقود أو الاتصال غير موجود.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------------------
// 2) بدء الجلسة والتحقق من المستخدم
// -------------------------------
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

// -------------------------------
// 3) السماح بـ GET فقط
// -------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'الطريقة غير مسموحة. استخدم GET.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------------------
// 4) قراءة ومعالجة ID
// -------------------------------
$violation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($violation_id <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'رقم المخالفة غير صالح.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------------------
// 5) تنفيذ الاستعلام
// -------------------------------
try {
    $sql = "
        SELECT 
            vv.*,
            v.driver_name,
            v.type AS vehicle_type,
            v.status AS vehicle_status,
            v.emp_id AS vehicle_emp_id,
            u1.username AS issued_by_name,
            u2.username AS paid_by_name
        FROM vehicle_violations vv
        LEFT JOIN vehicles v ON vv.vehicle_id = v.id
        LEFT JOIN users u1 ON vv.issued_by_emp_id = u1.emp_id
        LEFT JOIN users u2 ON vv.paid_by_emp_id = u2.emp_id
        WHERE vv.id = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('فشل في إعداد الاستعلام: ' . $conn->error);
    }

    $stmt->bind_param('i', $violation_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'المخالفة غير موجودة.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $violation = $result->fetch_assoc();
    $stmt->close();

    // -------------------------------
    // 6) تنسيق وتنظيف البيانات
    // -------------------------------
    $violation['violation_amount'] = floatval($violation['violation_amount']);

    $violation['violation_amount_formatted'] =
        number_format($violation['violation_amount'], 2);

    $violation['violation_datetime_formatted'] =
        !empty($violation['violation_datetime'])
            ? date('Y-m-d H:i:s', strtotime($violation['violation_datetime']))
            : null;

    $violation['payment_datetime_formatted'] =
        !empty($violation['payment_datetime'])
            ? date('Y-m-d H:i:s', strtotime($violation['payment_datetime']))
            : null;

    $violation['created_at_formatted'] =
        !empty($violation['created_at'])
            ? date('Y-m-d H:i:s', strtotime($violation['created_at']))
            : null;

    $violation['updated_at_formatted'] =
        !empty($violation['updated_at'])
            ? date('Y-m-d H:i:s', strtotime($violation['updated_at']))
            : null;

    // الحالة (المخالفة)
    if ($violation['violation_status'] === 'paid') {
        $violation['violation_status_ar'] = 'مدفوعة';
        $violation['violation_status_en'] = 'Paid';
    } else {
        $violation['violation_status_ar'] = 'غير مدفوعة';
        $violation['violation_status_en'] = 'Unpaid';
    }

    // حالة المركبة
    $violation['vehicle_status_ar'] = match ($violation['vehicle_status']) {
        'operational'   => 'مشغولة',
        'maintenance'   => 'صيانة',
        'out_of_service'=> 'خارج الخدمة',
        default         => 'غير محدد'
    };

    // تنظيف النصوص
    foreach (['vehicle_code','driver_name','issued_by_name','paid_by_name','notes','vehicle_type'] as $field) {
        if (isset($violation[$field])) {
            $violation[$field] = htmlspecialchars(
                (string)$violation[$field],
                ENT_QUOTES,
                'UTF-8'
            );
        }
    }

    // -------------------------------
    // 7) الإخراج النهائي
    // -------------------------------
    echo json_encode([
        'success' => true,
        'violation' => $violation
    ], JSON_UNESCAPED_UNICODE);

    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ في الخادم: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

    error_log('Error in get_violation_details.php: ' . $e->getMessage());
}

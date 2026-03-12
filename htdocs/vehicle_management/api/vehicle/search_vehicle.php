<?php
// vehicle_management/api/vehicle/search_vehicle.php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

// --- includes DB ---
$dbPaths = [
    __DIR__ . '/../../config/db.php',
    __DIR__ . '/../config/db.php',
    __DIR__ . '/config/db.php'
];
foreach ($dbPaths as $p) {
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

// --- includes session ---
$scPaths = [
    __DIR__ . '/../../config/session.php',
    __DIR__ . '/../config/session.php',
    __DIR__ . '/config/session.php'
];
foreach ($scPaths as $p) {
    if (file_exists($p)) {
        require_once $p;
        break;
    }
}

// --- بدء الجلسة والتحقق من المستخدم ---
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
$is_admin = in_array($user_role_id, [1,2]);

// السماح بـ GET فقط
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'الطريقة غير مسموحة. استخدم GET.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- الحصول على معامل البحث ---
$searchTerm = isset($_GET['code']) ? trim($_GET['code']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

if (empty($searchTerm)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'معامل البحث مطلوب. استخدم ?code=رقم_المركبة'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- تنفيذ الاستعلام ---
try {

    $vehicles = [];
    $searchPattern = "%$searchTerm%";

    if ($is_admin) {
        // المسؤول يمكنه رؤية جميع المركبات
        $stmt = $conn->prepare("
            SELECT id, vehicle_code, type, manufacture_year, emp_id, driver_name, driver_phone, status,
                   department_id, section_id, division_id, vehicle_mode, notes, created_at
            FROM vehicles
            WHERE vehicle_code LIKE ? OR driver_name LIKE ? OR emp_id LIKE ? OR type LIKE ?
            LIMIT ?
        ");
        $stmt->bind_param("ssssi", $searchPattern, $searchPattern, $searchPattern, $searchPattern, $limit);
    } else {
        // الموظف العادي يرى مركبات قسمه/إدارته/شعبته فقط
        $user_dept_id = $user['department_id'] ?? 0;
        $user_section_id = $user['section_id'] ?? 0;
        $user_division_id = $user['division_id'] ?? 0;

        $stmt = $conn->prepare("
            SELECT id, vehicle_code, type, manufacture_year, emp_id, driver_name, driver_phone, status,
                   department_id, section_id, division_id, vehicle_mode, notes, created_at
            FROM vehicles
            WHERE (vehicle_code LIKE ? OR driver_name LIKE ? OR emp_id LIKE ? OR type LIKE ?)
              AND (department_id = ? OR section_id = ? OR division_id = ? OR ? = 0)
            LIMIT ?
        ");
        $stmt->bind_param(
            "ssssiiii",
            $searchPattern, $searchPattern, $searchPattern, $searchPattern,
            $user_dept_id, $user_section_id, $user_division_id, $user_dept_id,
            $limit
        );
    }

    if (!$stmt) {
        throw new Exception("فشل في إعداد الاستعلام: " . $conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $row['vehicle_code'] = htmlspecialchars($row['vehicle_code'] ?? '', ENT_QUOTES, 'UTF-8');
        $row['driver_name'] = htmlspecialchars($row['driver_name'] ?? '', ENT_QUOTES, 'UTF-8');
        $row['type'] = htmlspecialchars($row['type'] ?? '', ENT_QUOTES, 'UTF-8');
        $row['status'] = htmlspecialchars($row['status'] ?? '', ENT_QUOTES, 'UTF-8');
        $row['vehicle_mode'] = htmlspecialchars($row['vehicle_mode'] ?? '', ENT_QUOTES, 'UTF-8');

        if (!empty($row['created_at'])) {
            $row['created_at_formatted'] = date('Y-m-d H:i:s', strtotime($row['created_at']));
        }

        // ترجمة الحالة
        switch ($row['status']) {
            case 'operational': $row['status_ar']='مشغولة'; $row['status_en']='Operational'; $row['status_class']='status-operational'; break;
            case 'maintenance': $row['status_ar']='صيانة'; $row['status_en']='Maintenance'; $row['status_class']='status-maintenance'; break;
            case 'out_of_service': $row['status_ar']='خارج الخدمة'; $row['status_en']='Out of Service'; $row['status_class']='status-out-of-service'; break;
        }

        // ترجمة وضع المركبة
        switch ($row['vehicle_mode']) {
            case 'private': $row['vehicle_mode_ar']='خاصة'; $row['vehicle_mode_en']='Private'; break;
            case 'shift': $row['vehicle_mode_ar']='وردية'; $row['vehicle_mode_en']='Shift'; break;
        }

        $vehicles[] = $row;
    }

    $stmt->close();
    $conn->close();

    echo json_encode([
        'success'=>true,
        'message'=>count($vehicles) > 0 ? 'تم العثور على نتائج' : 'لا توجد نتائج',
        'count'=>count($vehicles),
        'vehicles'=>$vehicles,
        'search_term'=>$searchTerm,
        'is_admin'=>$is_admin,
        'user_dept_id'=>$user['department_id'] ?? null
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success'=>false,
        'message'=>'حدث خطأ في الخادم: '.$e->getMessage(),
        'error_details'=>$conn->error ?? 'Unknown error'
    ], JSON_UNESCAPED_UNICODE);
    error_log("Error in search_vehicle.php: ".$e->getMessage()." - ".($conn->error ?? ''));
}

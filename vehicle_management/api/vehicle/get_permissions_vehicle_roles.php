<?php
// vehicle_management/api/vehicle/get_permissions_vehicle_roles.php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);
date_default_timezone_set('Asia/Dubai');

// CORS headers
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Auth-Token');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Include session config & DB (tries two paths)
$sessionPaths = [__DIR__ . '/../../config/session.php', __DIR__ . '/../config/session.php'];
foreach ($sessionPaths as $p) if (file_exists($p)) { require_once $p; break; }
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$dbPaths = [__DIR__ . '/../../config/db.php', __DIR__ . '/../config/db.php'];
foreach ($dbPaths as $p) if (file_exists($p)) { require_once $p; break; }
if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB connection missing'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ------------------ Authenticate & reload user ------------------
$currentUser = null;
$userId = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? null;
if ($userId) {
    $uid = (int)$userId;
    $stmt = $conn->prepare("SELECT id, role_id, department_id, section_id, division_id, emp_id, username FROM users WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $currentUser = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($currentUser) $_SESSION['user'] = $currentUser;
    }
}
if (!$currentUser || empty($currentUser['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ------------------ Load role permissions & override sections ------------------
$roleId = intval($currentUser['role_id'] ?? 0);
$permissions = [
    'can_view_all_vehicles' => false, // عرض كل السيارات بدون قيود
    'can_view_department_vehicles' => false, // عرض سيارات الإدارة (بناءً على department_id)
    'can_assign_vehicle' => false, // تسجيل (pickup) للمركبات باسمه فقط (نفس section_id)
    'can_receive_vehicle' => false, // استرجاع (return) لنفسه فقط
    'can_self_assign_vehicle' => false, // تسجيل واسترجاع للجميع بدون قيود
    'can_override_department' => false, // التعامل مع أقسام محددة من description (section_id مثل 1+2+3)
    'allow_registration' => false // فتح النموذج (add_vehicle_movements.html)
];
$overrideSections = [];
if ($roleId > 0) {
    $stmt = $conn->prepare("SELECT * FROM roles WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $roleId);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        if ($r) {
            $permissions['can_view_all_vehicles'] = (bool)($r['can_view_all_vehicles'] ?? 0);
            $permissions['can_view_department_vehicles'] = (bool)($r['can_view_department_vehicles'] ?? 0);
            $permissions['can_assign_vehicle'] = (bool)($r['can_assign_vehicle'] ?? 0);
            $permissions['can_receive_vehicle'] = (bool)($r['can_receive_vehicle'] ?? 0);
            $permissions['can_self_assign_vehicle'] = (bool)($r['can_self_assign_vehicle'] ?? 0);
            $permissions['can_override_department'] = (bool)($r['can_override_department'] ?? 0);
            $permissions['allow_registration'] = (bool)($r['allow_registration'] ?? 0);
            if ($permissions['can_override_department'] && !empty($r['description'])) {
                // description format: "1+2+5" للأقسام (section_id)
                $parts = explode('+', $r['description']);
                $overrideSections = array_values(array_filter(array_map('intval', $parts), function($v) { return $v > 0; }));
            }
        }
        $stmt->close();
    }
}
$empId = $currentUser['emp_id'] ?? '';

// Only log debug info when debug=1 parameter is present
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    error_log("Debug Vehicle Permissions: User ID={$currentUser['id']} | Role ID={$roleId} | Permissions: " . json_encode($permissions) . " | Override Sections: " . implode(',', $overrideSections));
}

// ------------------ Check for private vehicle (للظهور: إذا كان لديه سيارة خاصة، أظهرها) ------------------
$hasPrivateVehicle = false;
$privateVehicleCode = null;
$privateStmt = $conn->prepare("SELECT vehicle_code, type, driver_name, driver_phone FROM vehicles WHERE vehicle_mode = 'private' AND emp_id = ? AND status = 'operational' LIMIT 1");
$privateStmt->bind_param('s', $empId);
$privateStmt->execute();
$privateResult = $privateStmt->get_result()->fetch_assoc();
if ($privateResult) {
    $hasPrivateVehicle = true;
    $privateVehicleCode = $privateResult['vehicle_code'];
    $privateVehicleDetails = [
        'code' => $privateResult['vehicle_code'],
        'type' => $privateResult['type'] ?? '',
        'driver_name' => $privateResult['driver_name'] ?? '',
        'driver_phone' => $privateResult['driver_phone'] ?? ''
    ];
} else {
    $privateVehicleDetails = null;
}
$privateStmt->close();

// ------------------ Check if user has checked out vehicle (للظهور: إذا مستلمة، أظهر زر إرجاع) ------------------
$hasCheckedOutVehicle = false;
$checkedOutVehicleCode = null;
$checkedOutVehicleDetails = null;
$checkoutStmt = $conn->prepare("
    SELECT vm.vehicle_code, v.type, v.driver_name, v.driver_phone
    FROM vehicle_movements vm
    JOIN vehicles v ON v.vehicle_code = vm.vehicle_code
    WHERE vm.performed_by = ? AND vm.operation_type = 'pickup'
      AND NOT EXISTS (
          SELECT 1 FROM vehicle_movements vm2
          WHERE vm2.vehicle_code = vm.vehicle_code
            AND vm2.operation_type = 'return'
            AND vm2.movement_datetime > vm.movement_datetime
      )
    ORDER BY vm.movement_datetime DESC LIMIT 1
");
$checkoutStmt->bind_param('s', $empId);
$checkoutStmt->execute();
$checkoutResult = $checkoutStmt->get_result()->fetch_assoc();
if ($checkoutResult) {
    $hasCheckedOutVehicle = true;
    $checkedOutVehicleCode = $checkoutResult['vehicle_code'];
    $checkedOutVehicleDetails = [
        'code' => $checkoutResult['vehicle_code'],
        'type' => $checkoutResult['type'] ?? '',
        'driver_name' => $checkoutResult['driver_name'] ?? '',
        'driver_phone' => $checkoutResult['driver_phone'] ?? ''
    ];
}
$checkoutStmt->close();

// ------------------ Additional flags for display and raffle ------------------
// show_raffle_button: للمفتش أو من لديه can_assign_vehicle (للقرعة العشوائية)
$showRaffleButton = $permissions['can_assign_vehicle'] || $permissions['can_self_assign_vehicle'];
// can_view_raffle_vehicles: للعرض في وضع القرعة (بناءً على visibility rules)
$canViewRaffleVehicles = !empty($currentUser['section_id']) || $permissions['can_view_department_vehicles'] || !empty($overrideSections) || $permissions['can_view_all_vehicles'];

// Only log debug info when debug=1 parameter is present
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    error_log("Debug Vehicle Flags: showRaffleButton={$showRaffleButton} | canViewRaffleVehicles={$canViewRaffleVehicles} | Has Private={$hasPrivateVehicle} | Has Checked Out={$hasCheckedOutVehicle}");
}

// ------------------ Response (مخصص للعرض والقرعة) ------------------
echo json_encode([
    'success' => true,
    'permissions' => $permissions,
    'override_sections' => $overrideSections,
    'current_user' => [
        'id' => intval($currentUser['id']),
        'emp_id' => $currentUser['emp_id'],
        'username' => $currentUser['username'],
        'section_id' => intval($currentUser['section_id'] ?? 0),
        'department_id' => intval($currentUser['department_id'] ?? 0),
        'division_id' => intval($currentUser['division_id'] ?? 0)
    ],
    'has_private_vehicle' => $hasPrivateVehicle,
    'private_vehicle' => $privateVehicleDetails,
    'has_checked_out_vehicle' => $hasCheckedOutVehicle,
    'checked_out_vehicle' => $checkedOutVehicleDetails,
    'show_raffle_button' => $showRaffleButton, // لإظهار زر القرعة في الـ frontend
    'can_view_raffle_vehicles' => $canViewRaffleVehicles // للتحقق من وجود سيارات متاحة للقرعة
], JSON_UNESCAPED_UNICODE);
?>

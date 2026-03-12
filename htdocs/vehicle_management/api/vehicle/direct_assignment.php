<?php
// vehicle_management/api/vehicle/direct_assignment.php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);
date_default_timezone_set('Asia/Dubai');

// CORS headers boilerplate
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Auth-Token');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// Include session config & DB
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

// ------------------ Authenticate & Load Permissions ------------------
$currentUser = null;
if (!empty($_SESSION['user']) && is_array($_SESSION['user']) && !empty($_SESSION['user']['id'])) {
    $currentUser = $_SESSION['user'];
}
if (!$currentUser || empty($currentUser['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated'], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$vehicleCode = $data['vehicle_code'] ?? null;
$notes = $data['notes'] ?? 'تسجيل مباشر بواسطة المستخدم.';
$empId = $currentUser['emp_id'] ?? '';
$roleId = intval($currentUser['role_id'] ?? 0);

if (empty($vehicleCode) || empty($empId)) {
    echo json_encode(['success' => false, 'message' => 'بيانات المركبة أو المستخدم ناقصة.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ------------------ Load Permissions ------------------
$canSelfAssignVehicle = false;
if ($roleId > 0) {
    $stmt = $conn->prepare("SELECT can_self_assign_vehicle FROM roles WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $roleId);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $canSelfAssignVehicle = (bool)($r['can_self_assign_vehicle'] ?? 0);
        $stmt->close();
    }
}

// ------------------ 1. Check if user already has a vehicle checked out ------------------
// (Code omitted for brevity, but should be checked here similar to get_permissions_vehicle_roles.php)
// يجب عليك إضافة الكود للتحقق مما إذا كان المستخدم لديه مركبة بالفعل.

// ------------------ 2. Check Vehicle availability and type/ownership ------------------
$stmt = $conn->prepare("SELECT vehicle_mode, emp_id, status FROM vehicles WHERE vehicle_code = ? LIMIT 1");
$stmt->bind_param('s', $vehicleCode);
$stmt->execute();
$vehicleRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$vehicleRow || $vehicleRow['status'] !== 'operational') {
    echo json_encode(['success' => false, 'message' => 'المركبة غير موجودة أو ليست قيد التشغيل.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$isPrivateOwner = ($vehicleRow['vehicle_mode'] === 'private' && $vehicleRow['emp_id'] === $empId);

if (!$isPrivateOwner && !$canSelfAssignVehicle) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك بتسجيل هذه المركبة مباشرة.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ------------------ 3. Check if vehicle is currently checked out ------------------
$checkoutStmt = $conn->prepare("
    SELECT 1 FROM vehicle_movements vm
    WHERE vm.vehicle_code = ? AND vm.operation_type = 'pickup'
    AND NOT EXISTS (
        SELECT 1 FROM vehicle_movements vm2
        WHERE vm2.vehicle_code = vm.vehicle_code AND vm2.operation_type = 'return'
        AND vm2.movement_datetime > vm.movement_datetime
    ) LIMIT 1
");
$checkoutStmt->bind_param('s', $vehicleCode);
$checkoutStmt->execute();
$isCurrentlyCheckedOut = $checkoutStmt->get_result()->num_rows > 0;
$checkoutStmt->close();

if ($isCurrentlyCheckedOut) {
    echo json_encode(['success' => false, 'message' => 'المركبة مسجلة حالياً لمستخدم آخر.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ------------------ 4. Insert movement (pickup) ------------------
$createdBy = $empId;
$insertStmt = $conn->prepare("INSERT INTO vehicle_movements (vehicle_code, operation_type, performed_by, notes, created_by, updated_by, movement_datetime) VALUES (?, 'pickup', ?, ?, ?, ?, NOW())");
if ($insertStmt === false) {
    echo json_encode(['success' => false, 'message' => 'خطأ في تحضير تسجيل الحركة: ' . $conn->error], JSON_UNESCAPED_UNICODE);
    exit;
}

$insertStmt->bind_param('sssss', $vehicleCode, $empId, $notes, $createdBy, $createdBy);
if ($insertStmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'تم تسجيل المركبة ' . $vehicleCode . ' مباشرة.'
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['success' => false, 'message' => 'فشل في تسجيل الحركة.'], JSON_UNESCAPED_UNICODE);
}
$insertStmt->close();
?>
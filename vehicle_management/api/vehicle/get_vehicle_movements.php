<?php
// vehicle_management/api/vehicle/get_vehicle_movements.php
// Returns available vehicles for pickup/return based on permissions and current state

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

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

// Include session config first
$sessionPaths = [
    __DIR__ . '/../../config/session.php',
    __DIR__ . '/../config/session.php'
];
foreach ($sessionPaths as $p) {
    if (file_exists($p)) {
        require_once $p;
        break;
    }
}

// Start session if not active
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Include DB
$dbPaths = [
    __DIR__ . '/../../config/db.php',
    __DIR__ . '/../config/db.php'
];
foreach ($dbPaths as $p) {
    if (file_exists($p)) {
        require_once $p;
        break;
    }
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB connection missing']);
    exit;
}

// Include permissions helper
$permPath = __DIR__ . '/../permissions/perm_helper.php';
if (file_exists($permPath)) require_once $permPath;

// ----------------------------------------------------
// Get current user from session
// ----------------------------------------------------
$currentUser = null;

// First check for $_SESSION['user'] which stores complete user data
if (!empty($_SESSION['user']) && is_array($_SESSION['user']) && !empty($_SESSION['user']['id'])) {
    $currentUser = $_SESSION['user'];
}
// Fallback to get_current_session_user helper if available
elseif (function_exists('get_current_session_user')) {
    $currentUser = get_current_session_user($conn);
}
// Fallback to $_SESSION['user_id']
elseif (!empty($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id, role_id, department_id, section_id, division_id, emp_id, username FROM users WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $currentUser = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

if (!$currentUser || empty($currentUser['id'])) {
    http_response_code(401);
    error_log('get_vehicle_movements.php: No user in session');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Get role permissions - get ALL permission fields
$roleId = intval($currentUser['role_id'] ?? 0);
$permissions = [
    'can_view_all_vehicles' => false,
    'can_view_department_vehicles' => false,
    'can_assign_vehicle' => false,
    'can_receive_vehicle' => false,
    'can_self_assign_vehicle' => false,
    'can_override_department' => false
];

if ($roleId > 0) {
    $stmt = $conn->prepare("SELECT can_view_all_vehicles, can_view_department_vehicles, can_assign_vehicle, can_receive_vehicle, can_self_assign_vehicle, can_override_department FROM roles WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $roleId);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        if ($r) {
            foreach ($permissions as $key => $val) {
                $permissions[$key] = (bool)($r[$key] ?? 0);
            }
        }
        $stmt->close();
    }
}

// Check if user has an active vehicle (unreturned pickup)
$currentEmpId = $currentUser['emp_id'] ?? '';
$hasActiveVehicle = false;
$activeVehicleCode = null;

if ($currentEmpId) {
    // Find if user has any pickup without a subsequent return
    $checkSql = "SELECT vm.vehicle_code 
                 FROM vehicle_movements vm
                 WHERE vm.performed_by = ?
                 AND vm.operation_type = 'pickup'
                 AND NOT EXISTS (
                     SELECT 1 FROM vehicle_movements vm2
                     WHERE vm2.vehicle_code = vm.vehicle_code
                     AND vm2.performed_by = vm.performed_by
                     AND vm2.operation_type = 'return'
                     AND vm2.id > vm.id
                 )
                 LIMIT 1";
    
    $checkStmt = $conn->prepare($checkSql);
    if ($checkStmt) {
        $checkStmt->bind_param('s', $currentEmpId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        if ($checkRow = $checkResult->fetch_assoc()) {
            $hasActiveVehicle = true;
            $activeVehicleCode = $checkRow['vehicle_code'];
        }
        $checkStmt->close();
    }
}

// Read filter params
$filterDepartment = $_GET['department_id'] ?? '';
$filterSection = $_GET['section_id'] ?? '';
$filterDivision = $_GET['division_id'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$q = $_GET['q'] ?? '';

// Build WHERE clause for vehicles
$where = [];
$params = [];
$types = '';

// Permission-based filtering
if (!$permissions['can_view_all_vehicles']) {
    if ($permissions['can_view_department_vehicles'] && !empty($currentUser['department_id'])) {
        // Filter by user's department
        $where[] = "v.department_id = ?";
        $params[] = intval($currentUser['department_id']);
        $types .= 'i';
    } else {
        // Only see vehicles assigned to them
        $where[] = "v.emp_id = ?";
        $params[] = $currentUser['emp_id'] ?? '';
        $types .= 's';
    }
}

// Additional filters
if ($filterDepartment !== '') {
    $where[] = "v.department_id = ?";
    $params[] = intval($filterDepartment);
    $types .= 'i';
}

if ($filterSection !== '') {
    $where[] = "v.section_id = ?";
    $params[] = intval($filterSection);
    $types .= 'i';
}

if ($filterDivision !== '') {
    $where[] = "v.division_id = ?";
    $params[] = intval($filterDivision);
    $types .= 'i';
}

if ($filterStatus !== '') {
    $where[] = "v.status = ?";
    $params[] = $filterStatus;
    $types .= 's';
}

// Search filter
if ($q !== '') {
    $qLike = '%' . $q . '%';
    $where[] = "(v.vehicle_code LIKE ? OR v.driver_name LIKE ? OR v.type LIKE ?)";
    $params[] = $qLike;
    $params[] = $qLike;
    $params[] = $qLike;
    $types .= 'sss';
}

$whereSql = '';
if (!empty($where)) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

// Query vehicles with their latest movement status
$sql = "SELECT v.*,
        d.name_ar AS department_name,
        s.name_ar AS section_name,
        dv.name_ar AS division_name,
        (SELECT vm.operation_type 
         FROM vehicle_movements vm 
         WHERE vm.vehicle_code = v.vehicle_code 
         ORDER BY vm.id DESC LIMIT 1) AS last_operation,
        (SELECT vm.performed_by 
         FROM vehicle_movements vm 
         WHERE vm.vehicle_code = v.vehicle_code 
         ORDER BY vm.id DESC LIMIT 1) AS last_performed_by
        FROM vehicles v
        LEFT JOIN departments d ON d.id = v.department_id
        LEFT JOIN sections s ON s.id = v.section_id
        LEFT JOIN divisions dv ON dv.id = v.division_id
        $whereSql
        ORDER BY v.id DESC";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Server error']);
    exit;
}

if (!empty($params)) {
    $bindNames = [];
    $bindNames[] = & $types;
    for ($i = 0; $i < count($params); $i++) {
        $bindNames[] = & $params[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bindNames);
}

$stmt->execute();
$result = $stmt->get_result();

$vehicles = [];
while ($r = $result->fetch_assoc()) {
    // Determine availability status
    $lastOp = $r['last_operation'];
    $lastPerformedBy = $r['last_performed_by'];
    $vehicleCode = $r['vehicle_code'];
    
    $availabilityStatus = 'available'; // default: can pickup
    
    if ($lastOp === 'pickup') {
        // Vehicle is currently checked out
        if ($lastPerformedBy === $currentEmpId) {
            $availabilityStatus = 'checked_out_by_me'; // user can return
        } else {
            $availabilityStatus = 'checked_out_by_other'; // not available
        }
    } elseif ($lastOp === 'return' || $lastOp === null) {
        // Vehicle is available for pickup
        $availabilityStatus = 'available';
    }
    
    // Business rule: User can only pickup if they don't have an active vehicle
    // OR if admin/staff with special permissions
    $canPickup = false;
    $canReturn = false;
    
    if ($availabilityStatus === 'available') {
        // Can pickup only if:
        // 1. User doesn't have an active vehicle OR
        // 2. User has can_assign_vehicle permission (admin/staff)
        if (!$hasActiveVehicle || $permissions['can_assign_vehicle']) {
            $canPickup = true;
        }
    } elseif ($availabilityStatus === 'checked_out_by_me') {
        // User can return their own vehicle
        $canReturn = true;
    }
    
    $vehicles[] = [
        'id' => (int)$r['id'],
        'vehicle_code' => $vehicleCode,
        'type' => $r['type'] ?? null,
        'manufacture_year' => $r['manufacture_year'] ? (int)$r['manufacture_year'] : null,
        'driver_name' => $r['driver_name'] ?? null,
        'driver_phone' => $r['driver_phone'] ?? null,
        'status' => $r['status'] ?? null,
        'vehicle_mode' => $r['vehicle_mode'] ?? null,
        'department_id' => isset($r['department_id']) ? (int)$r['department_id'] : null,
        'department_name' => $r['department_name'] ?? null,
        'section_id' => isset($r['section_id']) ? (int)$r['section_id'] : null,
        'section_name' => $r['section_name'] ?? null,
        'division_id' => isset($r['division_id']) ? (int)$r['division_id'] : null,
        'division_name' => $r['division_name'] ?? null,
        'notes' => $r['notes'] ?? null,
        'availability_status' => $availabilityStatus,
        'last_operation' => $lastOp,
        'can_pickup' => $canPickup,
        'can_return' => $canReturn
    ];
}
$stmt->close();

error_log('get_vehicle_movements.php: Returning ' . count($vehicles) . ' vehicles. User has active vehicle: ' . ($hasActiveVehicle ? 'yes' : 'no'));

echo json_encode([
    'success' => true,
    'vehicles' => $vehicles,
    'permissions' => $permissions,
    'current_user' => [
        'emp_id' => $currentUser['emp_id'] ?? null,
        'username' => $currentUser['username'] ?? null,
        'department_id' => $currentUser['department_id'] ?? null
    ],
    'has_active_vehicle' => $hasActiveVehicle,
    'active_vehicle_code' => $activeVehicleCode,
    'debug' => [
        'total_vehicles' => count($vehicles),
        'where_clause' => $whereSql,
        'filters_applied' => [
            'department' => $filterDepartment ?? null,
            'section' => $filterSection ?? null,
            'division' => $filterDivision ?? null,
            'status' => $filterStatus ?? null,
            'search' => $q ?? null
        ]
    ]
], JSON_UNESCAPED_UNICODE);
exit;
?>

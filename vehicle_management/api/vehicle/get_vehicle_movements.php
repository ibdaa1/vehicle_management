<?php
// vehicle_management/api/vehicle/get_vehicle_movements.php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);
// ضبط المنطقة الزمنية لتوقيت الإمارات
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
// Include session config
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
// ----------------------------------------------------
// تصحيح قراءة الجلسة والمصادقة
// ----------------------------------------------------
$currentUser = null;
if (!empty($_SESSION['user']) && is_array($_SESSION['user']) && !empty($_SESSION['user']['id'])) {
    $currentUser = $_SESSION['user'];
} elseif (!empty($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id, role_id, department_id, section_id, division_id, emp_id, username FROM users WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $currentUser = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($currentUser) {
            $_SESSION['user'] = $currentUser;
        }
    }
}
if (!$currentUser || empty($currentUser['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}
// ----------------------------------------------------
// جلب الأذونات و override sections
// ----------------------------------------------------
$roleId = intval($currentUser['role_id'] ?? 0);
$permissions = [
    'can_view_all_vehicles' => false,
    'can_view_department_vehicles' => false,
    'can_assign_vehicle' => false,
    'can_receive_vehicle' => false,
    'can_self_assign_vehicle' => false,
    'can_override_department' => false
];
$overrideSections = []; // قائمة الأقسام الإضافية من description
if ($roleId > 0) {
    $stmt = $conn->prepare("SELECT can_view_all_vehicles, can_view_department_vehicles, can_assign_vehicle, can_receive_vehicle, can_self_assign_vehicle, can_override_department, description FROM roles WHERE id = ? LIMIT 1");
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
            
            // Parse override sections from description (format: "1+2+5")
            if ($permissions['can_override_department'] && !empty($r['description'])) {
                $parts = explode('+', $r['description']);
                $overrideSections = array_values(array_filter(array_map('intval', $parts), function($v) { return $v > 0; }));
            }
        }
        $stmt->close();
    }
}
// ----------------------------------------------------
// التحقق من وجود سيارة مستلمة لدى المستخدم (مستلمة ولم يتم إرجاعها)
// ----------------------------------------------------
$userHasVehicleCheckedOut = false;
$userCheckedOutVehicleCode = null;
$currentEmpId = $currentUser['emp_id'] ?? '';
$checkStmt = $conn->prepare("
    SELECT vm.vehicle_code, vm.movement_datetime
    FROM vehicle_movements vm
    WHERE vm.performed_by = ?
    AND vm.operation_type = 'pickup'
    AND NOT EXISTS (
        SELECT 1
        FROM vehicle_movements vm2
        WHERE vm2.vehicle_code = vm.vehicle_code
        AND vm2.operation_type = 'return'
        AND vm2.movement_datetime > vm.movement_datetime
    )
    ORDER BY vm.movement_datetime DESC
    LIMIT 1
");
$checkStmt->bind_param('s', $currentEmpId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result()->fetch_assoc();
if ($checkResult) {
    $userHasVehicleCheckedOut = true;
    $userCheckedOutVehicleCode = $checkResult['vehicle_code'];
}
$checkStmt->close();
// ----------------------------------------------------
// التحقق من المركبات الخاصة للمستخدم
// ----------------------------------------------------
$userHasPrivateVehicle = false;
$userPrivateVehicleCode = null;
$privateStmt = $conn->prepare("
    SELECT vehicle_code
    FROM vehicles
    WHERE vehicle_mode = 'private'
    AND emp_id = ?
    AND status = 'operational'
    LIMIT 1
");
$privateStmt->bind_param('s', $currentEmpId);
$privateStmt->execute();
$privateResult = $privateStmt->get_result()->fetch_assoc();
if ($privateResult) {
    $userHasPrivateVehicle = true;
    $userPrivateVehicleCode = $privateResult['vehicle_code'];
}
$privateStmt->close();
// ----------------------------------------------------
// التحقق من المركبات التي استلمها المستخدم في آخر 24 ساعة
// ----------------------------------------------------
$recentlyAssignedVehicles = [];
$recentStmt = $conn->prepare("
    SELECT DISTINCT vehicle_code
    FROM vehicle_movements
    WHERE performed_by = ?
    AND operation_type = 'pickup'
    AND movement_datetime >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    AND vehicle_code NOT IN (
        SELECT vehicle_code
        FROM vehicle_movements
        WHERE operation_type = 'return'
        AND performed_by = ?
        AND movement_datetime >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        AND movement_datetime > (
            SELECT movement_datetime
            FROM vehicle_movements
            WHERE vehicle_code = vehicle_movements.vehicle_code
            AND operation_type = 'pickup'
            AND performed_by = ?
            ORDER BY movement_datetime DESC
            LIMIT 1
        )
    )
");
$recentStmt->bind_param('sss', $currentEmpId, $currentEmpId, $currentEmpId);
$recentStmt->execute();
$recentResult = $recentStmt->get_result();
while ($row = $recentResult->fetch_assoc()) {
    $recentlyAssignedVehicles[] = $row['vehicle_code'];
}
$recentStmt->close();
// ----------------------------------------------------
// بناء استعلام جلب المركبات
// ----------------------------------------------------
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

// Permission-based filtering with proper visibility rules:
// CRITICAL RULES:
// 1. Only operational vehicles visible by default (status='operational')
// 2. Users with can_view_all_vehicles, can_self_assign_vehicle, or can_override_department can see all statuses
// 3. Private vehicles only visible to: owner (emp_id match) OR users with special permissions above
// 4. Regular users (can_assign_vehicle only) see only their section's operational vehicles

$visibilityClauses = [];
$canViewAllStatuses = $permissions['can_view_all_vehicles'] || $permissions['can_self_assign_vehicle'] || $permissions['can_override_department'];

// Status filter: Only operational vehicles unless user has special permissions
if (!$canViewAllStatuses) {
    $where[] = "v.status = 'operational'";
}

if ($permissions['can_view_all_vehicles'] || $permissions['can_self_assign_vehicle']) {
    // Users with these permissions can see all vehicles (including all statuses and private)
    // No specific visibility restriction needed
} else {
    // Build visibility based on section, department, and override sections
    // IMPORTANT: Regular users (Inspector) should NOT see private vehicles in the main list
    // Private vehicles are not part of the lottery system
    
    // 1. Default: User sees SHIFT vehicles in their section_id
    // Users without section_id will see NO vehicles
    if (!empty($currentUser['section_id'])) {
        $visibilityClauses[] = "(v.section_id = ? AND v.vehicle_mode = 'shift')";
        $params[] = intval($currentUser['section_id']);
        $types .= 'i';
    }
    
    // 2. If can_view_department_vehicles, add department SHIFT vehicles
    if ($permissions['can_view_department_vehicles'] && !empty($currentUser['department_id'])) {
        $visibilityClauses[] = "(v.department_id = ? AND v.vehicle_mode = 'shift')";
        $params[] = intval($currentUser['department_id']);
        $types .= 'i';
    }
    
    // 3. If can_override_department, add override sections SHIFT vehicles
    if (!empty($overrideSections)) {
        $placeholders = implode(',', array_fill(0, count($overrideSections), '?'));
        $visibilityClauses[] = "(v.section_id IN ($placeholders) AND v.vehicle_mode = 'shift')";
        foreach ($overrideSections as $sec) {
            $params[] = intval($sec);
            $types .= 'i';
        }
    }
    
    // 4. Admin users with can_override_department can see all private vehicles
    if ($permissions['can_override_department']) {
        $visibilityClauses[] = "v.vehicle_mode = 'private'";
    }
    // Note: Regular Inspector users do NOT see private vehicles in main list
    // Private vehicles are handled separately (not in lottery system)
    
    // Add visibility clause to WHERE
    if (!empty($visibilityClauses)) {
        $where[] = '(' . implode(' OR ', $visibilityClauses) . ')';
    } else {
        // If no visibility clauses, user sees nothing
        // This prevents users with null section_id from seeing any vehicles
        $where[] = "1=0";  // Always false - no vehicles visible
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
// Status filter: Only allow filtering if user has permission to see all statuses
if ($filterStatus !== '') {
    if ($canViewAllStatuses) {
        // User can filter by any status
        $where[] = "v.status = ?";
        $params[] = $filterStatus;
        $types .= 's';
    } else {
        // User can only filter operational vehicles (already enforced above)
        // Ignore the filter parameter for security
    }
}
// Search filter
if ($q !== '') {
    $qLike = '%' . $q . '%';
    $where[] = "(v.vehicle_code LIKE ? OR v.driver_name LIKE ? OR v.type LIKE ? OR v.emp_id LIKE ?)";
    $params[] = $qLike;
    $params[] = $qLike;
    $params[] = $qLike;
    $params[] = $qLike;
    $types .= 'ssss';
}
$whereSql = '';
if (!empty($where)) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}
// استعلام محسن لجلب المركبات مع حالتها الحالية
$sql = "SELECT
        v.*,
        d.name_ar AS department_name,
        s.name_ar AS section_name,
        dv.name_ar AS division_name,
        last_mov.operation_type AS last_operation,
        last_mov.performed_by AS last_performed_by,
        last_mov.movement_datetime AS last_movement_date,
        last_mov.created_by AS last_created_by,
        last_mov.updated_by AS last_updated_by,
        last_mov.notes AS last_notes,
        (
            SELECT COUNT(*)
            FROM vehicle_movements vm
            WHERE vm.vehicle_code = v.vehicle_code
            AND vm.operation_type = 'pickup'
            AND NOT EXISTS (
                SELECT 1
                FROM vehicle_movements vm2
                WHERE vm2.vehicle_code = v.vehicle_code
                AND vm2.operation_type = 'return'
                AND vm2.movement_datetime > vm.movement_datetime
            )
        ) AS is_currently_checked_out,
        (
            SELECT performed_by
            FROM vehicle_movements vm
            WHERE vm.vehicle_code = v.vehicle_code
            AND vm.operation_type = 'pickup'
            AND NOT EXISTS (
                SELECT 1
                FROM vehicle_movements vm2
                WHERE vm2.vehicle_code = v.vehicle_code
                AND vm2.operation_type = 'return'
                AND vm2.movement_datetime > vm.movement_datetime
            )
            ORDER BY vm.movement_datetime DESC
            LIMIT 1
        ) AS current_checkout_by
        FROM vehicles v
        LEFT JOIN Departments d ON d.department_id = v.department_id
        LEFT JOIN Sections s ON s.section_id = v.section_id
        LEFT JOIN Divisions dv ON dv.division_id = v.division_id
        LEFT JOIN (
            SELECT vm1.*
            FROM vehicle_movements vm1
            WHERE vm1.movement_datetime = (
                SELECT MAX(vm2.movement_datetime)
                FROM vehicle_movements vm2
                WHERE vm2.vehicle_code = vm1.vehicle_code
            )
        ) last_mov ON v.vehicle_code = last_mov.vehicle_code
        $whereSql
        ORDER BY v.id DESC";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: Prepare failed. MySQL Error: ' . $conn->error], JSON_UNESCAPED_UNICODE);
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
// ----------------------------------------------------
// معالجة النتائج وإخراج JSON
// ----------------------------------------------------
// Check if user has elevated permissions (calculated once for all vehicles)
$hasElevatedPermissions = $permissions['can_self_assign_vehicle'] 
                        || $permissions['can_view_all_vehicles'] 
                        || $permissions['can_override_department'];

$vehicles = [];
while ($r = $result->fetch_assoc()) {
    $lastOp = $r['last_operation'];
    $lastPerformedBy = $r['last_performed_by'];
    $lastMovementDate = $r['last_movement_date'];
    $isCurrentlyCheckedOut = (bool)($r['is_currently_checked_out'] ?? 0);
    $currentCheckoutBy = $r['current_checkout_by'] ?? null;
   
    // حالة السيارة بناءً على آخر حركة وحالتها الحالية
    $availabilityStatus = 'available';
    $canPickup = false;
    $canReturn = false;
    $canOpenForm = false;
   
    // تحقق إذا كانت السيارة خاصة
    if ($r['vehicle_mode'] === 'private') {
        if ($r['emp_id'] === $currentEmpId) {
            // السيارة خاصة ومخصصة لهذا المستخدم
            if ($isCurrentlyCheckedOut) {
                if ($currentCheckoutBy === $currentEmpId) {
                    $availabilityStatus = 'checked_out_by_me';
                    $canReturn = true;
                } else {
                    $availabilityStatus = 'checked_out_by_other';
                    $canReturn = $permissions['can_receive_vehicle'];
                }
            } else {
                $availabilityStatus = 'private';
                // Private vehicles should NOT be directly pickable by Inspector role
                // They are not part of the shift/lottery system
                // Only elevated permissions can manage private vehicles
                if ($hasElevatedPermissions) {
                    $canPickup = true;
                } else {
                    $canPickup = false;  // Inspector cannot pickup even their own private vehicle via this interface
                }
            }
        } else {
            // السيارة خاصة ولكنها ليست مخصصة لهذا المستخدم
            $availabilityStatus = 'private_unavailable';
            $canPickup = false;
            $canReturn = false;
        }
    } else {
        // السيارة بنظام الورديات (shift)
        if ($isCurrentlyCheckedOut) {
            if ($currentCheckoutBy === $currentEmpId) {
                $availabilityStatus = 'checked_out_by_me';
                $canReturn = true;
            } else {
                $availabilityStatus = 'checked_out_by_other';
                $canReturn = $permissions['can_receive_vehicle'];
            }
        } else {
            $availabilityStatus = 'available';
            
            // NEW LOGIC: Shift vehicles can only be picked up via random assignment for regular users
            // Only users with can_self_assign_vehicle or admin permissions can directly pickup
            // Regular users (can_assign_vehicle + can_receive_vehicle only) must use random button
            $canPickup = $hasElevatedPermissions;
        }
    }
    // زر فتح النموذج - فقط للمستخدمين الذين لديهم صلاحيات إدارية
    // المستخدمون العاديون (Inspector) لا يمكنهم فتح النموذج
    if ($hasElevatedPermissions) {
        $canOpenForm = true;
    }
    $vehicles[] = [
        'id' => (int)($r['id'] ?? 0),
        'vehicle_code' => $r['vehicle_code'] ?? null,
        'type' => $r['type'] ?? null,
        'manufacture_year' => $r['manufacture_year'] ? (int)$r['manufacture_year'] : null,
        'driver_name' => $r['driver_name'] ?? null,
        'driver_phone' => $r['driver_phone'] ?? null,
        'emp_id' => $r['emp_id'] ?? null,
        'status' => $r['status'] ?? null,
        'vehicle_mode' => $r['vehicle_mode'] ?? null,
        'department_id' => isset($r['department_id']) ? (int)$r['department_id'] : null,
        'department_name' => $r['department_name'] ?? null,
        'section_id' => isset($r['section_id']) ? (int)$r['section_id'] : null,
        'section_name' => $r['section_name'] ?? null,
        'division_id' => isset($r['division_id']) ? (int)$r['division_id'] : null,
        'division_name' => $r['division_name'] ?? null,
        'notes' => $r['notes'] ?? null,
        'last_operation' => $lastOp,
        'last_performed_by' => $lastPerformedBy,
        'last_created_by' => $r['last_created_by'] ?? null,
        'last_updated_by' => $r['last_updated_by'] ?? null,
        'last_movement_date' => $lastMovementDate,
        'last_notes' => $r['last_notes'] ?? null,
        'is_currently_checked_out' => $isCurrentlyCheckedOut,
        'current_checkout_by' => $currentCheckoutBy,
        'availability_status' => $availabilityStatus,
        'can_pickup' => $canPickup,
        'can_return' => $canReturn,
        'can_open_form' => $canOpenForm
    ];
}
$stmt->close();

// Build response object
$response = [
    'success' => true,
    'vehicles' => $vehicles,
    'permissions' => $permissions,
    'current_user' => [
        'emp_id' => $currentUser['emp_id'] ?? null,
        'username' => $currentUser['username'] ?? null,
        'department_id' => $currentUser['department_id'] ?? null,
        'section_id' => $currentUser['section_id'] ?? null
    ],
    'user_has_vehicle_checked_out' => $userHasVehicleCheckedOut,
    'user_checked_out_vehicle_code' => $userCheckedOutVehicleCode,
    'user_has_private_vehicle' => $userHasPrivateVehicle,
    'user_private_vehicle_code' => $userPrivateVehicleCode,
    'recently_assigned_vehicles' => $recentlyAssignedVehicles
];

// Only add debug info if debug=1 parameter is present
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    $response['debug'] = [
        'total_vehicles' => count($vehicles),
        'where_clause' => $whereSql,
        'timezone' => date_default_timezone_get(),
        'current_time' => date('Y-m-d H:i:s'),
        'override_sections' => $overrideSections
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>

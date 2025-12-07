<?php
// vehicle_management/api/vehicle/random_assignment.php
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
// ------------------ Load role and build permissions (NEW role-based system) ------------------
$roleId = intval($currentUser['role_id'] ?? 0);
$roleName = '';
$roleDescription = '';
$overrideSections = [];

if ($roleId > 0) {
    $stmt = $conn->prepare("SELECT name_en, description FROM roles WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $roleId);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        if ($r) {
            $roleName = strtolower(trim($r['name_en'] ?? ''));
            $roleDescription = trim($r['description'] ?? '');
            
            // Parse override sections for custom_user from description (format: "1+2+5")
            if ($roleName === 'custom_user' && !empty($roleDescription)) {
                $parts = explode('+', $roleDescription);
                $overrideSections = array_values(array_filter(array_map('intval', $parts), function($v) { return $v > 0; }));
            }
        }
        $stmt->close();
    }
}

// Determine role type
$adminRoles = ['super_admin', 'admin', 'shift_supervisor', 'maintenance_supervisor'];
$isAdminRole = in_array($roleName, $adminRoles);
$isCustomUser = ($roleName === 'custom_user');
// Treat any unrecognized role as regular_user (fallback)
$isRegularUser = ($roleName === 'regular_user') || (!$isAdminRole && !$isCustomUser && !empty($roleName));

$empId = $currentUser['emp_id'] ?? '';

// Only log debug info when debug=1 parameter is present
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    error_log("Debug: User ID={$currentUser['id']} | Role={$roleName} | section_id=" . ($currentUser['section_id'] ?? 'NULL') . " | department_id=" . ($currentUser['department_id'] ?? 'NULL') . " | overrideSections=" . implode(',', $overrideSections) . " | isAdmin=" . ($isAdminRole ? 'true' : 'false'));
}

// ------------------ Prevent user who already has picked vehicle ------------------
$activeCheckStmt = $conn->prepare("
    SELECT vm.vehicle_code
    FROM vehicle_movements vm
    WHERE vm.performed_by = ? AND vm.operation_type = 'pickup'
      AND NOT EXISTS (
          SELECT 1 FROM vehicle_movements vm2
          WHERE vm2.vehicle_code = vm.vehicle_code
            AND vm2.operation_type = 'return'
            AND vm2.movement_datetime > vm.movement_datetime
      )
    ORDER BY vm.movement_datetime DESC LIMIT 1
");
$activeCheckStmt->bind_param('s', $empId);
$activeCheckStmt->execute();
$activeResult = $activeCheckStmt->get_result()->fetch_assoc();
$activeCheckStmt->close();

// Only prevent if user is not admin
// Admin can assign multiple vehicles, regular/custom users cannot
if ($activeResult && !$isAdminRole) {
    echo json_encode(['success' => false, 'message' => 'لديك سيارة مستلمة (' . $activeResult['vehicle_code'] . '). أرجعها أولاً.'], JSON_UNESCAPED_UNICODE);
    exit;
}
// ------------------ Prevent if user already has a private vehicle assigned ------------------
$privateStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM vehicles WHERE vehicle_mode = 'private' AND emp_id = ? AND status = 'operational'");
$privateStmt->bind_param('s', $empId);
$privateStmt->execute();
$privateResult = $privateStmt->get_result()->fetch_assoc();
$privateStmt->close();
if ($privateResult['cnt'] > 0) {
    echo json_encode(['success' => false, 'message' => 'لديك سيارة خاصة. لا يمكن القرعة.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ------------------ Get recently assigned vehicles (within 24 hours) ------------------
$recentlyAssignedVehicles = [];
$recentStmt = $conn->prepare("
    SELECT DISTINCT vehicle_code
    FROM vehicle_movements
    WHERE performed_by = ?
    AND operation_type = 'pickup'
    AND movement_datetime >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
");
$recentStmt->bind_param('s', $empId);
$recentStmt->execute();
$recentResult = $recentStmt->get_result();
while ($row = $recentResult->fetch_assoc()) {
    $recentlyAssignedVehicles[] = $row['vehicle_code'];
}
$recentStmt->close();
// ------------------ Build WHERE filters for visible vehicles ------------------
$where = [];
$params = [];
$types = '';
// Important: default visibility rule you specified:
// - ANY user sees vehicles that belong to the user's same section (v.section_id = user.section_id).
// - Additionally, if role allows view by department, include department vehicles.
// - Additionally, if role allows override, include override sections from roles.description.
// - If can_view_all_vehicles = true, see ALL operational shift vehicles (no section/dept restriction).
// - Exclude private vehicles by requiring vehicle_mode = 'shift' (or explicitly excluding 'private').
// Start with operational & not private & shift
$where[] = "v.status = 'operational'";
$where[] = "v.vehicle_mode = 'shift'"; // ensures we do not include vehicle_mode = 'private'
// Exclude vehicles currently checked-out (same logic as before)
$where[] = "NOT EXISTS (
    SELECT 1 FROM vehicle_movements vm
    WHERE vm.vehicle_code = v.vehicle_code
      AND vm.operation_type = 'pickup'
      AND NOT EXISTS (
          SELECT 1 FROM vehicle_movements vm2
          WHERE vm2.vehicle_code = vm.vehicle_code
            AND vm2.operation_type = 'return'
            AND vm2.movement_datetime > vm.movement_datetime
      )
)";
// Visibility clause group (NEW role-based logic)
$visibilityClauses = [];
if ($isAdminRole) {
    // Admin: See all shift vehicles
    $visibilityClauses[] = "1=1";
} else {
    // Non-admin: regular_user or custom_user
    if ($isCustomUser && !empty($overrideSections)) {
        // custom_user: See shift vehicles in specified sections from description
        $ph = implode(',', array_fill(0, count($overrideSections), '?'));
        $visibilityClauses[] = "v.section_id IN ($ph)";
        foreach ($overrideSections as $sec) { $params[] = intval($sec); $types .= 'i'; }
    } elseif ($isRegularUser && !empty($currentUser['section_id'])) {
        // regular_user: See shift vehicles in their section only
        $visibilityClauses[] = "v.section_id = ?";
        $params[] = intval($currentUser['section_id']);
        $types .= 'i';
    }
}
// If no visibility clause ended up, deny
if (empty($visibilityClauses)) {
    // No visible vehicles for user
    echo json_encode(['success' => false, 'message' => 'لا صلاحية رؤية أي سيارات.'], JSON_UNESCAPED_UNICODE);
    exit;
}
$where[] = '(' . implode(' OR ', $visibilityClauses) . ')';
// Optional GET filter for specific section_id (restrict results)
$filterSection = isset($_GET['section_id']) ? trim($_GET['section_id']) : '';
if ($filterSection !== '') {
    $where[] = "v.section_id = ?";
    $params[] = intval($filterSection);
    $types .= 'i';
}
$whereSql = 'WHERE ' . implode(' AND ', $where);
// ------------------ Random selection SQL ------------------
$sql = "SELECT v.*, d.name_ar AS department_name, s.name_ar AS section_name, dv.name_ar AS division_name
        FROM vehicles v
        LEFT JOIN Departments d ON d.department_id = v.department_id
        LEFT JOIN Sections s ON s.section_id = v.section_id
        LEFT JOIN Divisions dv ON dv.division_id = v.division_id
        $whereSql
        ORDER BY RAND()
        LIMIT 1";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطأ في الاستعلام: ' . $conn->error], JSON_UNESCAPED_UNICODE);
    exit;
}
// bind params dynamically if any
if (!empty($params)) {
    // Prepare bind_param arguments (references)
    $bindNames = [];
    $bindNames[] = & $types;
    for ($i = 0; $i < count($params); $i++) {
        $bindNames[] = & $params[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bindNames);
}
$stmt->execute();
$result = $stmt->get_result();
$availableVehicle = $result->fetch_assoc();
$stmt->close();
if (!$availableVehicle) {
    echo json_encode(['success' => false, 'message' => 'لا سيارات متاحة في قسمك للقرعة.'], JSON_UNESCAPED_UNICODE);
    exit;
}
// ------------------ Assignment authorization checks (NEW role-based) ------------------
// Rule: 
// - Admin roles: Can assign any available vehicle
// - regular_user/custom_user: Can assign (they use random assignment)
$canAssignThis = false;
if ($isAdminRole) {
    $canAssignThis = true; // Admin can assign any vehicle
} elseif ($isRegularUser || $isCustomUser) {
    // Regular and custom users can assign via random (already filtered by visibility)
    $canAssignThis = true;
} else {
    $canAssignThis = false;
}
if (!$canAssignThis) {
    echo json_encode(['success' => false, 'message' => 'لا توجد صلاحية لاستلام هذه السيارة.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ------------------ Check if vehicle was recently assigned (24 hours) ------------------
$vehicleCode = $availableVehicle['vehicle_code'];
if (in_array($vehicleCode, $recentlyAssignedVehicles) && !$isAdminRole) {
    echo json_encode(['success' => false, 'message' => 'لا يمكن استلام نفس السيارة خلال 24 ساعة. السيارة: ' . $vehicleCode], JSON_UNESCAPED_UNICODE);
    exit;
}

// ------------------ Insert movement (pickup) ------------------
$createdBy = $currentUser['emp_id'] ?? $empId;
$notes = 'قرعة عشوائية - المستخدم: ' . $createdBy;
$insertStmt = $conn->prepare("INSERT INTO vehicle_movements (vehicle_code, operation_type, performed_by, notes, created_by, updated_by, movement_datetime) VALUES (?, 'pickup', ?, ?, ?, ?, NOW())");
if ($insertStmt === false) {
    echo json_encode(['success' => false, 'message' => 'خطأ في تحضير تسجيل الحركة: ' . $conn->error], JSON_UNESCAPED_UNICODE);
    exit;
}
$insertStmt->bind_param('sssss', $vehicleCode, $empId, $notes, $createdBy, $createdBy);
if ($insertStmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'تم تعيين ' . $vehicleCode . ' عبر القرعة.',
        'vehicle' => [
            'code' => $vehicleCode,
            'type' => $availableVehicle['type'] ?? '',
            'driver_name' => $availableVehicle['driver_name'] ?? '',
            'driver_phone' => $availableVehicle['driver_phone'] ?? ''
        ],
        'assigned_by' => $createdBy
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['success' => false, 'message' => 'خطأ في التسجيل: ' . $conn->error], JSON_UNESCAPED_UNICODE);
}
$insertStmt->close();
?>

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
// ------------------ Load role permissions & override sections ------------------
$roleId = intval($currentUser['role_id'] ?? 0);
$permissions = [
    'can_view_all_vehicles' => false, // غير مفعل افتراضياً كما طلبت، لكن يُحدث من DB إذا وُجد
    'can_view_department_vehicles' => false,
    'can_assign_vehicle' => false,
    'can_self_assign_vehicle' => false,
    'can_override_department' => false,
    'allow_registration' => false
];
$overrideSections = [];
if ($roleId > 0) {
    $stmt = $conn->prepare("SELECT * FROM roles WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $roleId);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        if ($r) {
            $permissions['can_view_all_vehicles'] = (bool)($r['can_view_all_vehicles'] ?? 0); // إضافة تحميل هذه الصلاحية
            $permissions['can_view_department_vehicles'] = (bool)($r['can_view_department_vehicles'] ?? 0);
            $permissions['can_assign_vehicle'] = (bool)($r['can_assign_vehicle'] ?? 0);
            $permissions['can_self_assign_vehicle'] = (bool)($r['can_self_assign_vehicle'] ?? 0);
            $permissions['can_override_department'] = (bool)($r['can_override_department'] ?? 0);
            $permissions['allow_registration'] = (bool)($r['allow_registration'] ?? 0);
            if ($permissions['can_override_department'] && !empty($r['description'])) {
                // description format: "1+2+5"
                $overrideSections = array_map('intval', array_filter(explode('+', $r['description'])));
            }
        }
        $stmt->close();
    }
}
$empId = $currentUser['emp_id'] ?? '';

// Only log debug info when debug=1 parameter is present
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    error_log("Debug: User ID={$currentUser['id']} | section_id=" . ($currentUser['section_id'] ?? 'NULL') . " | department_id=" . ($currentUser['department_id'] ?? 'NULL') . " | overrideSections=" . implode(',', $overrideSections) . " | can_view_all_vehicles=" . ($permissions['can_view_all_vehicles'] ? 'true' : 'false'));
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

// Only prevent if user doesn't have can_assign_vehicle permission
if ($activeResult && !$permissions['can_assign_vehicle']) {
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
// Visibility clause group
$visibilityClauses = [];
if ($permissions['can_view_all_vehicles']) {
    // إذا كانت الصلاحية مفعلة (رغم أنها غير مفعلة افتراضياً)، أضف شرطاً يسمح برؤية كل السيارات
    $visibilityClauses[] = "1=1";
} else {
    // الشروط العادية
    // 1) Vehicles in same section are visible to everyone (default)
    if (!empty($currentUser['section_id'])) {
        $visibilityClauses[] = "v.section_id = ?";
        $params[] = intval($currentUser['section_id']);
        $types .= 'i';
    }
    // 2) If role grants department view, include department vehicles
    if ($permissions['can_view_department_vehicles'] && !empty($currentUser['department_id'])) {
        $visibilityClauses[] = "v.department_id = ?";
        $params[] = intval($currentUser['department_id']);
        $types .= 'i';
    }
    // 3) If role has override sections, include them
    if (!empty($overrideSections)) {
        // build placeholders
        $ph = implode(',', array_fill(0, count($overrideSections), '?'));
        $visibilityClauses[] = "v.section_id IN ($ph)";
        foreach ($overrideSections as $sec) { $params[] = intval($sec); $types .= 'i'; }
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
// ------------------ Assignment authorization checks ------------------
// Rule implemented as per your specs:
// - If role has can_self_assign_vehicle => can pick any available vehicle (no section restriction).
// - Else if role has can_assign_vehicle => can pick only if vehicle.section_id == user.section_id (same section only).
// - Else => cannot assign (even if visible).
$canAssignThis = false;
if ($permissions['can_self_assign_vehicle']) {
    $canAssignThis = true; // يسمح للجميع (تسليم واستلام بدون قيود)
} elseif ($permissions['can_assign_vehicle']) {
    // can assign, but only from same section
    if (intval($availableVehicle['section_id']) === intval($currentUser['section_id'])) {
        $canAssignThis = true;
    } else {
        $canAssignThis = false;
    }
} else {
    $canAssignThis = false;
}
if (!$canAssignThis) {
    echo json_encode(['success' => false, 'message' => 'لا توجد صلاحية لاستلام هذه السيارة.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ------------------ Check if vehicle was recently assigned (24 hours) ------------------
$vehicleCode = $availableVehicle['vehicle_code'];
if (in_array($vehicleCode, $recentlyAssignedVehicles) && !$permissions['can_assign_vehicle']) {
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

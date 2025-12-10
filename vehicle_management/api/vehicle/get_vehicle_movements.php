<?php
/**
 * vehicle_management/api/vehicle/get_vehicle_movements.php
 *
 * Returns:
 * - vehicles[] with localized department/section/division names (department_name, department_name_en, department_name_ar, ...)
 * - permissions object (all can_* flags)
 * - is_admin (boolean)
 * - show_raffle_button (boolean)
 * - can_register_new_vehicle (boolean)
 * - current_user object (emp_id, username, department_id, section_id, preferred_language)
 * - user_has_vehicle_checked_out, user_checked_out_vehicle_code
 * - recently_assigned_vehicles (last 24h)
 * - override_sections (from roles.description if applicable)
 *
 * Query params:
 * - lang=en|ar (optional; defaults to user's preferred_language or 'ar')
 * - department_id, section_id, division_id, status, q (search)
 *
 * Notes:
 * - Server is authoritative about permissions and which actions are allowed for each vehicle.
 * - The frontend must still rely on server-side validation when performing actions.
 * - NEW: Visibility Logic Simplified to rely only on role/section/department permissions.
 */

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);
date_default_timezone_set('Asia/Dubai');

// Allow CORS for same-origin or dynamic origin if required (adjust in production)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// include session config and DB (try multiple paths)
$sessionPaths = [
    __DIR__ . '/../../config/session.php',
    __DIR__ . '/../config/session.php',
    __DIR__ . '/../../../config/session.php'
];
foreach ($sessionPaths as $p) {
    if (file_exists($p)) { require_once $p; break; }
}
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$dbPaths = [
    __DIR__ . '/../../config/db.php',
    __DIR__ . '/../config/db.php',
    __DIR__ . '/../../../config/db.php'
];
foreach ($dbPaths as $p) {
    if (file_exists($p)) { require_once $p; break; }
}
if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB connection missing'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ----------------- Input / Defaults -----------------
$lang = (isset($_GET['lang']) && $_GET['lang'] === 'en') ? 'en' : null;
$filterDepartment = isset($_GET['department_id']) ? trim($_GET['department_id']) : '';
$filterSection = isset($_GET['section_id']) ? trim($_GET['section_id']) : '';
$filterDivision = isset($_GET['division_id']) ? trim($_GET['division_id']) : '';
$filterStatus = isset($_GET['status']) ? trim($_GET['status']) : '';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

// ----------------- Authentication -----------------
$currentUser = null;
if (!empty($_SESSION['user']) && is_array($_SESSION['user']) && !empty($_SESSION['user']['id'])) {
    $currentUser = $_SESSION['user'];
} elseif (!empty($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id, role_id, emp_id, username, department_id, section_id, preferred_language FROM users WHERE id = ? LIMIT 1");
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

// determine effective lang: GET param wins, else user's preferred_language or default 'ar'
if ($lang === null) {
    $lang = (!empty($currentUser['preferred_language']) && $currentUser['preferred_language'] === 'en') ? 'en' : 'ar';
}

// ----------------- Load role/permissions -----------------
$roleId = intval($currentUser['role_id'] ?? 0);
$permissions = [
    'can_view_all_vehicles' => false,
    'can_view_department_vehicles' => false,
    'can_assign_vehicle' => false,
    'can_receive_vehicle' => false,
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
        $roleRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($roleRow) {
            // Map expected permission columns (safe use of isset)
            foreach ($permissions as $k => $_) {
                if (isset($roleRow[$k])) $permissions[$k] = (bool)$roleRow[$k];
            }
            // allow_registration may be separate name on some systems
            if (isset($roleRow['allow_registration'])) $permissions['allow_registration'] = (bool)$roleRow['allow_registration'];

            // override sections from description (e.g., "2+3+4")
            if (!empty($roleRow['description']) && $permissions['can_override_department']) {
                $parts = preg_split('/\D+/', $roleRow['description']);
                foreach ($parts as $p) {
                    $p = intval($p);
                    if ($p > 0) $overrideSections[] = $p;
                }
                $overrideSections = array_values(array_unique($overrideSections));
            }
        }
    }
}

// is_admin flag definition (server decides)
$is_admin = (bool)$permissions['can_view_all_vehicles'];

// ----------------- User state checks -----------------
$currentEmpId = $currentUser['emp_id'] ?? '';
$userSectionId = intval($currentUser['section_id'] ?? 0);
$userDepartmentId = intval($currentUser['department_id'] ?? 0);

// 1) currently checked-out vehicle by this user (latest pickup without return)
$userHasVehicleCheckedOut = false;
$userCheckedOutVehicleCode = null;
$checkStmt = $conn->prepare("
    SELECT vm.vehicle_code
    FROM vehicle_movements vm
    WHERE vm.performed_by = ?
      AND vm.operation_type = 'pickup'
      AND NOT EXISTS (
          SELECT 1 FROM vehicle_movements vm2
          WHERE vm2.vehicle_code = vm.vehicle_code
            AND vm2.operation_type = 'return'
            AND vm2.movement_datetime > vm.movement_datetime
      )
    ORDER BY vm.movement_datetime DESC
    LIMIT 1
");
if ($checkStmt) {
    $checkStmt->bind_param('s', $currentEmpId);
    $checkStmt->execute();
    $cr = $checkStmt->get_result()->fetch_assoc();
    if ($cr) {
        $userHasVehicleCheckedOut = true;
        $userCheckedOutVehicleCode = $cr['vehicle_code'];
    }
    $checkStmt->close();
}

// 2) vehicles the user has picked up in the last 24 hours (for recentlyAssigned check)
$recentlyAssignedVehicles = [];
$recentStmt = $conn->prepare("
    SELECT DISTINCT vehicle_code
    FROM vehicle_movements
    WHERE performed_by = ?
      AND operation_type = 'pickup'
      AND movement_datetime >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
");
if ($recentStmt) {
    $recentStmt->bind_param('s', $currentEmpId);
    $recentStmt->execute();
    $rr = $recentStmt->get_result();
    while ($row = $rr->fetch_assoc()) {
        $recentlyAssignedVehicles[] = $row['vehicle_code'];
    }
    $recentStmt->close();
}

// ----------------- Build query: filters + visibility (SIMPLIFIED LOGIC) -----------------
$whereClauses = [];
$params = [];
$types = '';

// Default status filter: Always prefer operational vehicles unless client filters differently
if ($filterStatus === '') {
    $whereClauses[] = "v.status = 'operational'";
} else {
    $whereClauses[] = "v.status = ?";
    $params[] = $filterStatus; $types .= 's';
}

// Apply client filters
if ($filterDepartment !== '') {
    $whereClauses[] = "v.department_id = ?";
    $params[] = intval($filterDepartment); $types .= 'i';
}
if ($filterSection !== '') {
    $whereClauses[] = "v.section_id = ?";
    $params[] = intval($filterSection); $types .= 'i';
}
if ($filterDivision !== '') {
    $whereClauses[] = "v.division_id = ?";
    $params[] = intval($filterDivision); $types .= 'i';
}
if ($q !== '') {
    $like = '%' . $q . '%';
    $whereClauses[] = "(v.vehicle_code LIKE ? OR v.driver_name LIKE ? OR v.type LIKE ? OR v.emp_id LIKE ?)";
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'ssss';
}

// Build VISIBILITY clauses based on simplified role logic (OR conditions)
$visibility = [];

if ($permissions['can_view_all_vehicles']) {
    // 1. Super Admin / Admin: See ALL vehicles (excluding client filters)
    $visibility[] = "1=1";
} else {
    // 2. Override Sections: User sees vehicles in explicitly assigned sections (highest priority for delegated staff)
    if (!empty($overrideSections)) {
        $ph = implode(',', array_fill(0, count($overrideSections), '?'));
        $visibility[] = "v.section_id IN ($ph)";
        foreach ($overrideSections as $s) { $params[] = intval($s); $types .= 'i'; }
    }

    // 3. Department View: User sees vehicles in their department (if role permits)
    if ($permissions['can_view_department_vehicles'] && $userDepartmentId > 0) {
        $visibility[] = "v.department_id = ?";
        $params[] = $userDepartmentId; $types .= 'i';
    }

    // 4. Default / Normal User: User sees vehicles in their specific section
    if ($userSectionId > 0 && empty($overrideSections) && !$permissions['can_view_department_vehicles']) {
        $visibility[] = "v.section_id = ?";
        $params[] = $userSectionId; $types .= 'i';
    }
    
    // Safety check: if no visibility rule applies, default to seeing nothing
    if (empty($visibility)) {
         $visibility[] = "1=0";
    }
}

// Combine visibility into main WHERE clause (using OR for visibility rules)
if (!empty($visibility)) {
    $whereClauses[] = '(' . implode(' OR ', $visibility) . ')';
} else {
    // Should not happen, but safe fallback
    $whereClauses[] = "1=0";
}

// If user currently has a checked-out vehicle that wouldn't be included by the above filters,
// ensure it is included so they can see/return it. This OR condition is applied at the very end.
$includeUserCheckedOutClause = '';
if (!empty($userCheckedOutVehicleCode)) {
    $includeUserCheckedOutClause = " OR v.vehicle_code = ?";
    $params[] = $userCheckedOutVehicleCode; $types .= 's';
}

// Final WHERE clause construction
$whereSql = '';
if (!empty($whereClauses)) {
    // Join all visibility and filter clauses with AND
    $whereSql = 'WHERE (' . implode(') AND (', $whereClauses) . ')';
    
    // Append the user's currently checked-out vehicle with an OR condition outside the main logic
    if ($includeUserCheckedOutClause !== '') {
        $whereSql = $whereSql . $includeUserCheckedOutClause;
    }
}


// ----------------- SQL: fetch vehicles with localized names -----------------
$sql = "
SELECT
    v.*,
    d.name_en AS department_name_en, d.name_ar AS department_name_ar,
    s.name_en AS section_name_en, s.name_ar AS section_name_ar,
    dv.name_en AS division_name_en, dv.name_ar AS division_name_ar,
    last_mov.operation_type AS last_operation,
    last_mov.performed_by AS last_performed_by,
    last_mov.movement_datetime AS last_movement_date,
    last_mov.notes AS last_notes,
    last_mov.latitude AS last_latitude,
    last_mov.longitude AS last_longitude,
    last_mov.id AS last_movement_id,
    (
        SELECT COUNT(*) FROM vehicle_movements vm
        WHERE vm.vehicle_code = v.vehicle_code
          AND vm.operation_type = 'pickup'
          AND NOT EXISTS (
            SELECT 1 FROM vehicle_movements vm2
            WHERE vm2.vehicle_code = vm.vehicle_code
              AND vm2.operation_type = 'return'
              AND vm2.movement_datetime > vm.movement_datetime
          )
    ) AS is_currently_checked_out,
    (
        SELECT performed_by FROM vehicle_movements vm
        WHERE vm.vehicle_code = v.vehicle_code
          AND vm.operation_type = 'pickup'
          AND NOT EXISTS (
            SELECT 1 FROM vehicle_movements vm2
            WHERE vm2.vehicle_code = vm.vehicle_code
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
    SELECT vm1.* FROM vehicle_movements vm1
    WHERE vm1.movement_datetime = (
        SELECT MAX(vm2.movement_datetime) FROM vehicle_movements vm2 WHERE vm2.vehicle_code = vm1.vehicle_code
    )
) last_mov ON v.vehicle_code = last_mov.vehicle_code
{$whereSql}
ORDER BY v.vehicle_code ASC
";

// Prepare statement
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Prepare failed', 'debug' => $conn->error], JSON_UNESCAPED_UNICODE);
    exit;
}

// Bind params if any
if (!empty($params)) {
    // call_user_func_array requires references
    $bindParams = [];
    $bindParams[] = & $types;
    for ($i = 0; $i < count($params); $i++) {
        $bindParams[] = & $params[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bindParams);
}

// Execute
$stmt->execute();
$result = $stmt->get_result();

// ----------------- Process results -----------------
$vehicles = [];
while ($row = $result->fetch_assoc()) {
    $vehicleCode = $row['vehicle_code'] ?? null;
    $isCurrentlyCheckedOut = (bool)($row['is_currently_checked_out'] ?? 0);
    $currentCheckoutBy = $row['current_checkout_by'] ?? null;

    // localized names: prefer column according to $lang
    $dept_en = $row['department_name_en'] ?? '';
    $dept_ar = $row['department_name_ar'] ?? '';
    $section_en = $row['section_name_en'] ?? '';
    $section_ar = $row['section_name_ar'] ?? '';
    $div_en = $row['division_name_en'] ?? '';
    $div_ar = $row['division_name_ar'] ?? '';

    $department_name = ($lang === 'en') ? ($dept_en !== '' ? $dept_en : $dept_ar) : ($dept_ar !== '' ? $dept_ar : $dept_en);
    $section_name = ($lang === 'en') ? ($section_en !== '' ? $section_en : $section_ar) : ($section_ar !== '' ? $section_ar : $section_en);
    $division_name = ($lang === 'en') ? ($div_en !== '' ? $div_en : $div_ar) : ($div_ar !== '' ? $div_ar : $div_en);

    // Determine availability and allowed actions (server-side)
    $availabilityStatus = 'available';
    $canPickup = false;
    $canReturn = false;
    $canOpenForm = false;

    // Simplified Action Logic (removed old private/shift mode complexity)
    
    $isOwnedByCurrentUser = ($row['emp_id'] === $currentEmpId);

    if ($isCurrentlyCheckedOut) {
        if ($currentCheckoutBy === $currentEmpId) {
            // User currently has the vehicle (regardless of mode)
            $availabilityStatus = 'checked_out_by_me';
            $canReturn = true;
        } else {
            // Vehicle checked out by another user
            $availabilityStatus = 'checked_out_by_other';
            $canReturn = (bool)$permissions['can_receive_vehicle'];
        }
    } else {
        // Vehicle is available
        $availabilityStatus = 'available';
        $canUserPickup = true;
        
        // Restriction 1: User already has a vehicle checked out (unless they can assign)
        if ($userHasVehicleCheckedOut && !$permissions['can_assign_vehicle']) {
             $canUserPickup = false;
        }
        // Restriction 2: Prevent rapid re-assignment if user just assigned one (unless they can assign)
        if (in_array($vehicleCode, $recentlyAssignedVehicles) && !$permissions['can_assign_vehicle']) {
            $canUserPickup = false;
        }
        
        // Allow pickup if user has rights
        if ($canUserPickup && ($permissions['can_assign_vehicle'] || $permissions['can_self_assign_vehicle'])) {
            $canPickup = true;
        }
    }

    // can_open_form (admin or assign/receive perms)
    // Note: This flag controls per-vehicle form actions, not the general registration button.
    if ($permissions['can_assign_vehicle'] || $permissions['can_receive_vehicle'] || $is_admin) {
        $canOpenForm = true;
    }

    $vehicles[] = [
        'id' => isset($row['id']) ? (int)$row['id'] : null,
        'vehicle_code' => $vehicleCode,
        'type' => $row['type'] ?? null,
        'manufacture_year' => isset($row['manufacture_year']) ? (int)$row['manufacture_year'] : null,
        'driver_name' => $row['driver_name'] ?? null,
        'driver_phone' => $row['driver_phone'] ?? null,
        'emp_id' => $row['emp_id'] ?? null,
        'status' => $row['status'] ?? null,
        'vehicle_mode' => $row['vehicle_mode'] ?? null,
        'department_id' => isset($row['department_id']) ? (int)$row['department_id'] : null,
        'department_name' => $department_name,
        'department_name_en' => $dept_en,
        'department_name_ar' => $dept_ar,
        'section_id' => isset($row['section_id']) ? (int)$row['section_id'] : null,
        'section_name' => $section_name,
        'section_name_en' => $section_en,
        'section_name_ar' => $section_ar,
        'division_id' => isset($row['division_id']) ? (int)$row['division_id'] : null,
        'division_name' => $division_name,
        'division_name_en' => $div_en,
        'division_name_ar' => $div_ar,
        'notes' => $row['notes'] ?? null,
        'last_operation' => $row['last_operation'] ?? null,
        'last_performed_by' => $row['last_performed_by'] ?? null,
        'last_movement_date' => $row['last_movement_date'] ?? null,
        'last_notes' => $row['last_notes'] ?? null,
        'last_movement_id' => isset($row['last_movement_id']) ? (int)$row['last_movement_id'] : null,
        'latitude' => $row['last_latitude'] ?? null,
        'longitude' => $row['last_longitude'] ?? null,
        'is_currently_checked_out' => $isCurrentlyCheckedOut,
        'current_checkout_by' => $currentCheckoutBy,
        'availability_status' => $availabilityStatus,
        'can_pickup' => (bool)$canPickup,
        'can_return' => (bool)$canReturn,
        'can_open_form' => (bool)$canOpenForm,
        'recently_assigned_by_user' => in_array($vehicleCode, $recentlyAssignedVehicles)
    ];
}
$stmt->close();

// ----------------- Build response -----------------
// Show the Raffle button to all by default, unless restricted by business logic.
$show_raffle_button = true;

// New Logic: Only Admin/SuperAdmin should see the general "Register New Vehicle" button.
$can_register_new_vehicle = $is_admin;

$response = [
    'success' => true,
    'vehicles' => $vehicles,
    'permissions' => $permissions,
    'is_admin' => $is_admin,
    'override_sections' => $overrideSections,
    'can_register_new_vehicle' => (bool)$can_register_new_vehicle,
    'current_user' => [
        'emp_id' => $currentUser['emp_id'] ?? null,
        'username' => $currentUser['username'] ?? null,
        'department_id' => $currentUser['department_id'] ?? null,
        'section_id' => $currentUser['section_id'] ?? null,
        'preferred_language' => $currentUser['preferred_language'] ?? $lang
    ],
    'user_has_vehicle_checked_out' => $userHasVehicleCheckedOut,
    'user_checked_out_vehicle_code' => $userCheckedOutVehicleCode,
    'recently_assigned_vehicles' => $recentlyAssignedVehicles,
    'show_raffle_button' => (bool)$show_raffle_button
];

// Output JSON
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>

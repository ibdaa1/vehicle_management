<?php
/**
 * vehicle_management/api/vehicle/get_vehicle_movements.php
 *
 * التعديلات النهائية:
 * - إظهار السيارة الخاصة لمالكها دائمًا (حتى بدون قسم أو صلاحيات)
 * - إرسال owner_emp_id كـ string
 * - السماح بـ can_pickup لمالك السيارة الخاصة بدون صلاحيات
 * - دعم كامل لـ can_receive_vehicle
 */

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);
date_default_timezone_set('Asia/Dubai');

// CORS
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

// Session & DB
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

// Language
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
            foreach ($permissions as $k => $_) {
                if (isset($roleRow[$k])) $permissions[$k] = (bool)$roleRow[$k];
            }
            // تأكيد قراءة allow_registration و can_receive_vehicle
            if (isset($roleRow['allow_registration'])) $permissions['allow_registration'] = (bool)$roleRow['allow_registration'];
            if (isset($roleRow['can_receive_vehicle'])) $permissions['can_receive_vehicle'] = (bool)$roleRow['can_receive_vehicle'];

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
$is_admin = (bool)$permissions['can_view_all_vehicles'];

// ----------------- User state checks -----------------
$currentEmpId = $currentUser['emp_id'] ?? '';
$userSectionId = intval($currentUser['section_id'] ?? 0);
$userDepartmentId = intval($currentUser['department_id'] ?? 0);

// Currently checked-out vehicle
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

// Recently assigned
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

// ----------------- Visibility (مع إجبار إظهار السيارة الخاصة) -----------------
$whereClauses = [];
$params = [];
$types = '';

if ($filterStatus === '') {
    $whereClauses[] = "v.status = 'operational'";
} else {
    $whereClauses[] = "v.status = ?";
    $params[] = $filterStatus; $types .= 's';
}

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

// Visibility rules
$visibility = [];
if ($permissions['can_view_all_vehicles']) {
    $visibility[] = "1=1";
} else {
    if (!empty($overrideSections)) {
        $ph = implode(',', array_fill(0, count($overrideSections), '?'));
        $visibility[] = "v.section_id IN ($ph)";
        foreach ($overrideSections as $s) { $params[] = intval($s); $types .= 'i'; }
    }
    if ($permissions['can_view_department_vehicles'] && $userDepartmentId > 0) {
        $visibility[] = "v.department_id = ?";
        $params[] = $userDepartmentId; $types .= 'i';
    }
    if ($userSectionId > 0 && empty($overrideSections) && !$permissions['can_view_department_vehicles']) {
        $visibility[] = "v.section_id = ?";
        $params[] = $userSectionId; $types .= 'i';
    }

    // إجبار إظهار السيارة الخاصة لمالكها دائمًا
    if (!empty($currentEmpId)) {
        $visibility[] = "(v.vehicle_mode = 'private' AND v.emp_id = ?)";
        $params[] = $currentEmpId;
        $types .= 's';
    }

    if (empty($visibility)) {
        $visibility[] = "1=0";
    }
}
$whereClauses[] = '(' . implode(' OR ', $visibility) . ')';

// Include current checked-out
$includeUserCheckedOutClause = '';
if (!empty($userCheckedOutVehicleCode)) {
    $includeUserCheckedOutClause = " OR v.vehicle_code = ?";
    $params[] = $userCheckedOutVehicleCode; $types .= 's';
}

$whereSql = '';
if (!empty($whereClauses)) {
    $whereSql = 'WHERE (' . implode(') AND (', $whereClauses) . ')';
    if ($includeUserCheckedOutClause !== '') {
        $whereSql .= $includeUserCheckedOutClause;
    }
}

// ----------------- SQL -----------------
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

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Prepare failed', 'debug' => $conn->error], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!empty($params)) {
    $bindParams = [];
    $bindParams[] = &$types;
    for ($i = 0; $i < count($params); $i++) {
        $bindParams[] = &$params[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bindParams);
}
$stmt->execute();
$result = $stmt->get_result();

// ----------------- Process vehicles -----------------
$vehicles = [];
while ($row = $result->fetch_assoc()) {
    $vehicleCode = $row['vehicle_code'] ?? null;
    $isCurrentlyCheckedOut = (bool)($row['is_currently_checked_out'] ?? 0);
    $currentCheckoutBy = $row['current_checkout_by'] ?? null;

    // owner_emp_id كـ string
    $owner_emp_id = null;
    if ($row['vehicle_mode'] === 'private' && !empty($row['emp_id'])) {
        $owner_emp_id = (string) trim($row['emp_id']);
    }

    // Localized names
    $department_name = ($lang === 'en')
        ? ($row['department_name_en'] ?? $row['department_name_ar'] ?? '')
        : ($row['department_name_ar'] ?? $row['department_name_en'] ?? '');
    $section_name = ($lang === 'en')
        ? ($row['section_name_en'] ?? $row['section_name_ar'] ?? '')
        : ($row['section_name_ar'] ?? $row['section_name_en'] ?? '');
    $division_name = ($lang === 'en')
        ? ($row['division_name_en'] ?? $row['division_name_ar'] ?? '')
        : ($row['division_name_ar'] ?? $row['division_name_en'] ?? '');

    // Actions
    $availabilityStatus = 'available';
    $canPickup = false;
    $canReturn = false;
    $canOpenForm = false;

    $isOwnedByCurrentUser = ($owner_emp_id === $currentEmpId);

    if ($isCurrentlyCheckedOut) {
        if ($currentCheckoutBy === $currentEmpId) {
            $availabilityStatus = 'checked_out_by_me';
            $canReturn = true;
        } else {
            $availabilityStatus = 'checked_out_by_other';
            $canReturn = (bool)$permissions['can_receive_vehicle'];
        }
    } else {
        $availabilityStatus = 'available';

        $canUserPickup = true;

        if ($userHasVehicleCheckedOut && !$permissions['can_assign_vehicle']) {
            $canUserPickup = false;
        }
        if (in_array($vehicleCode, $recentlyAssignedVehicles) && !$permissions['can_assign_vehicle']) {
            $canUserPickup = false;
        }

        // مالك السيارة الخاصة + can_receive_vehicle + الصلاحيات الأخرى
        if ($canUserPickup && (
            $permissions['can_assign_vehicle'] ||
            $permissions['can_self_assign_vehicle'] ||
            $permissions['can_receive_vehicle'] ||
            $isOwnedByCurrentUser
        )) {
            $canPickup = true;
        }
    }

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
        'department_name_en' => $row['department_name_en'] ?? '',
        'department_name_ar' => $row['department_name_ar'] ?? '',
        'section_id' => isset($row['section_id']) ? (int)$row['section_id'] : null,
        'section_name' => $section_name,
        'section_name_en' => $row['section_name_en'] ?? '',
        'section_name_ar' => $row['section_name_ar'] ?? '',
        'division_id' => isset($row['division_id']) ? (int)$row['division_id'] : null,
        'division_name' => $division_name,
        'division_name_en' => $row['division_name_en'] ?? '',
        'division_name_ar' => $row['division_name_ar'] ?? '',
        'notes' => $row['notes'] ?? null,
        'last_operation' => $row['last_operation'] ?? null,
        'last_performed_by' => $row['last_performed_by'] ?? null,
        'last_movement_date' => $row['last_movement_date'] ?? null,
        'last_notes' => $row['last_notes'] ?? null,
        'is_currently_checked_out' => $isCurrentlyCheckedOut,
        'current_checkout_by' => $currentCheckoutBy,
        'availability_status' => $availabilityStatus,
        'can_pickup' => (bool)$canPickup,
        'can_return' => (bool)$canReturn,
        'can_open_form' => (bool)$canOpenForm,
        'recently_assigned_by_user' => in_array($vehicleCode, $recentlyAssignedVehicles),
        'owner_emp_id' => $owner_emp_id,
    ];
}
$stmt->close();

// ----------------- Response -----------------
$show_raffle_button = true;
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

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>
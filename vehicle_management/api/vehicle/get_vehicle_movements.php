<?php
// vehicle_management/api/vehicle/get_vehicle_movements.php
// ---------------- CORS ----------------
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Auth-Token');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ---------------- DB CONNECTION ----------------
$paths = [
    __DIR__ . '/../../config/db.php',
    __DIR__ . '/../config/db.php',
    __DIR__ . '/config/db.php'
];
foreach ($paths as $p) { if (file_exists($p)) { require_once $p; break; } }

if (!isset($conn)) {
    echo json_encode(['success' => false, 'message' => 'DB connection missing']);
    exit;
}

// ---------------- SESSION (optional) ----------------
$scPaths = [
    __DIR__ . '/../../config/session.php',
    __DIR__ . '/../config/session.php',
    __DIR__ . '/config/session.php'
];
foreach ($scPaths as $p) { if (file_exists($p)) { require_once $p; break; } }

// ---------------- HEADER FUNCTION ----------------
function get_all_headers_normalized(): array {
    $h = [];
    if (function_exists('getallheaders')) {
        foreach (getallheaders() as $k => $v) $h[strtolower($k)] = $v;
    } else {
        foreach ($_SERVER as $k => $v) {
            if (strpos($k, 'HTTP_') === 0) {
                $name = strtolower(str_replace(' ', '-', str_replace('_', ' ', substr($k,5))));
                $h[$name] = $v;
            }
        }
    }
    return $h;
}

// ---------------- AUTH ----------------
$headers = get_all_headers_normalized();

if (!isset($headers['x-auth-token'])) {
    echo json_encode(['success' => false, 'message' => 'Missing token']);
    exit;
}

$token = $conn->real_escape_string($headers['x-auth-token']);

$userQ = $conn->query("
    SELECT u.*, r.can_view_all_vehicles, r.can_view_department_vehicles,
           r.can_assign_vehicle, r.can_receive_vehicle,
           r.can_override_department, r.can_self_assign_vehicle
    FROM users u
    LEFT JOIN roles r ON r.id = u.role_id
    WHERE u.token = '$token'
    LIMIT 1
");

if ($userQ->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}

$user = $userQ->fetch_assoc();
$empId = $user['emp_id'];

// ---------------- ACTIVE VEHICLE CHECK ----------------
$activeQ = $conn->query("
    SELECT vm.vehicle_code
    FROM vehicle_movements vm
    WHERE vm.performed_by = '$empId'
      AND vm.operation_type = 'pickup'
      AND NOT EXISTS (
           SELECT 1 FROM vehicle_movements vm2
           WHERE vm2.vehicle_code = vm.vehicle_code
             AND vm2.performed_by = vm.performed_by
             AND vm2.operation_type = 'return'
             AND vm2.id > vm.id
      )
    LIMIT 1
");

$hasActiveVehicle = ($activeQ->num_rows > 0);

// ---------------- VEHICLE QUERY BUILD ----------------
$where = [];

// 1 — إذا لديه صلاحية رؤية جميع السيارات
if ($user['can_view_all_vehicles'] == 1) {
    $where[] = "1=1";

// 2 — إذا يشاهد سيارات إدارته فقط
} elseif ($user['can_view_department_vehicles'] == 1) {
    $where[] = "
        (
            v.department_id = '{$user['department_id']}'
            OR v.section_id = '{$user['section_id']}'
            OR v.division_id = '{$user['division_id']}'
        )
    ";
}

// 3 — وضع السيارة
$where[] = "
    (
        v.vehicle_mode = 'shift'
        OR
        (v.vehicle_mode = 'private' AND v.emp_id = '$empId')
    )
";

$finalWhere = implode(" AND ", $where);

// ---------------- BASE SQL ----------------
$sql = "
    SELECT v.*
    FROM vehicles v
    WHERE $finalWhere
";

// عشوائية فقط إذا كان موظف عادي ولا يمتلك سيارة حالية
$isNormalEmployee = ($user['can_view_all_vehicles'] == 0);

if ($isNormalEmployee && !$hasActiveVehicle) {
    $sql .= " ORDER BY RAND() LIMIT 1";
}

$res = $conn->query($sql);
$vehicles = [];
while ($row = $res->fetch_assoc()) {
    $vehicles[] = $row;
}

// ---------------- RESPONSE ----------------
echo json_encode([
    'success' => true,
    'has_active_vehicle' => $hasActiveVehicle,
    'vehicles' => $vehicles,
    'permissions' => [
        'can_view_all_vehicles' => $user['can_view_all_vehicles'],
        'can_assign_vehicle' => $user['can_assign_vehicle'],
        'can_receive_vehicle' => $user['can_receive_vehicle'],
        'can_self_assign_vehicle' => $user['can_self_assign_vehicle']
    ]
]);

exit;

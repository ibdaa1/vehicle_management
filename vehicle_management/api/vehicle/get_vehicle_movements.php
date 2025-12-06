<?php
// vehicle_management/api/vehicle/get_vehicle_movements.php
// GET endpoint for paginated/filtered vehicle movements with permission checks

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

// Include DB
$paths = [
    __DIR__ . '/../../config/db.php',
    __DIR__ . '/../config/db.php',
    __DIR__ . '/config/db.php'
];
foreach ($paths as $p) {
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

// Start session if needed
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Get current user
$currentUser = null;
if (function_exists('get_current_session_user')) {
    $currentUser = get_current_session_user($conn);
} elseif (!empty($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id, role_id, department_id, emp_id, username FROM users WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $currentUser = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

if (!$currentUser || empty($currentUser['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Get role permissions
$roleId = intval($currentUser['role_id'] ?? 0);
$canViewAll = false;
$canViewDepartment = false;

if ($roleId > 0) {
    $stmt = $conn->prepare("SELECT can_view_all_vehicles, can_view_department_vehicles FROM roles WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $roleId);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        if ($r) {
            $canViewAll = (bool)($r['can_view_all_vehicles'] ?? 0);
            $canViewDepartment = (bool)($r['can_view_department_vehicles'] ?? 0);
        }
        $stmt->close();
    }
}

// Read params
$q = $_GET['q'] ?? '';
$operationType = $_GET['operation_type'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = max(1, min(200, intval($_GET['per_page'] ?? 30)));
$offset = ($page - 1) * $per_page;

// Build WHERE clause
$where = [];
$params = [];
$types = '';

// Permission-based filtering
if (!$canViewAll) {
    if ($canViewDepartment && !empty($currentUser['department_id'])) {
        // Filter by user's department - show movements of vehicles in same department
        $where[] = "v.department_id = ?";
        $deptId = intval($currentUser['department_id']);
        $params[] = $deptId;
        $types .= 'i';
    } else {
        // Only see own movements
        $where[] = "vm.performed_by = ?";
        $params[] = $currentUser['emp_id'] ?? '';
        $types .= 's';
    }
}

// Search filter
if ($q !== '') {
    $qLike = '%' . $q . '%';
    $where[] = "(vm.vehicle_code LIKE ? OR vm.performed_by LIKE ? OR vm.location LIKE ?)";
    $params[] = $qLike;
    $params[] = $qLike;
    $params[] = $qLike;
    $types .= 'sss';
}

// Operation type filter
if ($operationType !== '') {
    $where[] = "vm.operation_type = ?";
    $params[] = $operationType;
    $types .= 's';
}

$whereSql = '';
if (!empty($where)) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

// Count total
$countSql = "SELECT COUNT(*) AS cnt FROM vehicle_movements vm 
             LEFT JOIN vehicles v ON v.vehicle_code = vm.vehicle_code 
             $whereSql";

$stmt = $conn->prepare($countSql);
if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Server error']);
    exit;
}

if (!empty($params)) {
    $bindNames = [];
    $bindNames[] = & $types;
    $vals = $params;
    for ($i = 0; $i < count($vals); $i++) {
        $bindNames[] = & $vals[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bindNames);
}

$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$total = intval($row['cnt'] ?? 0);
$stmt->close();

// Data query
$sql = "SELECT vm.*, 
        v.driver_name, v.driver_phone, v.department_id, v.section_id, v.division_id
        FROM vehicle_movements vm
        LEFT JOIN vehicles v ON v.vehicle_code = vm.vehicle_code
        $whereSql
        ORDER BY vm.id DESC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Server error']);
    exit;
}

// Bind params
$allParams = $params;
$allTypes = $types;
$allParams[] = $per_page;
$allParams[] = $offset;
$allTypes .= 'ii';

$bindNames = [];
$bindNames[] = & $allTypes;
for ($i = 0; $i < count($allParams); $i++) {
    $bindNames[] = & $allParams[$i];
}
call_user_func_array([$stmt, 'bind_param'], $bindNames);

$stmt->execute();
$result = $stmt->get_result();

$movements = [];
while ($r = $result->fetch_assoc()) {
    $photos = [];
    if (!empty($r['photo_path'])) {
        // Handle multiple photos if stored as JSON or comma-separated
        $photoData = $r['photo_path'];
        if (is_string($photoData)) {
            // Try JSON decode first
            $decoded = json_decode($photoData, true);
            if (is_array($decoded)) {
                $photos = $decoded;
            } else {
                // Split by comma
                $photos = array_filter(array_map('trim', explode(',', $photoData)));
            }
        }
    }
    
    $movements[] = [
        'id' => (int)$r['id'],
        'vehicle_code' => $r['vehicle_code'] ?? null,
        'operation_type' => $r['operation_type'] ?? null,
        'performed_by' => $r['performed_by'] ?? null,
        'movement_date' => $r['movement_date'] ?? null,
        'location' => $r['location'] ?? null,
        'latitude' => $r['latitude'] ?? null,
        'longitude' => $r['longitude'] ?? null,
        'notes' => $r['notes'] ?? null,
        'photos' => $photos,
        'user_id' => isset($r['user_id']) ? (int)$r['user_id'] : null,
    ];
}
$stmt->close();

echo json_encode([
    'success' => true,
    'total' => $total,
    'page' => $page,
    'per_page' => $per_page,
    'movements' => $movements
], JSON_UNESCAPED_UNICODE);
exit;
?>

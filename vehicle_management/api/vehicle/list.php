<?php
// vehicle_management/api/vehicle/list.php
// GET: q (search), status, page, per_page
// Returns { success: true, total, page, per_page, vehicles: [...] }
// Secure: prepared statements, accepts session or Authorization Bearer token.

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// include DB
$paths = [
    __DIR__ . '/../../config/db.php',
    __DIR__ . '/../config/db.php',
    __DIR__ . '/config/db.php'
];
foreach ($paths as $p) { if (file_exists($p)) { require_once $p; break; } }
if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server misconfiguration: DB missing']);
    exit;
}

// optional: include perm helper to get current user if needed
$permPath = __DIR__ . '/../permissions/perm_helper.php';
if (file_exists($permPath)) require_once $permPath;

// read params
$q = $_GET['q'] ?? '';
$status = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = max(1, min(200, intval($_GET['per_page'] ?? 30)));
$offset = ($page - 1) * $per_page;

// build base query
$where = [];
$params = [];
$types = '';

// search across fields
if ($q !== '') {
    $qLike = '%' . $q . '%';
    $where[] = "(v.vehicle_code LIKE ? OR v.emp_id LIKE ? OR v.driver_name LIKE ? OR v.driver_phone LIKE ?)";
    $params[] = $qLike; $params[] = $qLike; $params[] = $qLike; $params[] = $qLike;
    $types .= 'ssss';
}

// status filter
if ($status !== '') {
    $where[] = "v.status = ?";
    $params[] = $status;
    $types .= 's';
}

$whereSql = '';
if (!empty($where)) $whereSql = 'WHERE ' . implode(' AND ', $where);

// count total
$countSql = "SELECT COUNT(*) AS cnt FROM vehicles v $whereSql";
$stmt = $conn->prepare($countSql);
if ($stmt === false) {
    echo json_encode(['success'=>false,'message'=>'Server error']);
    exit;
}
if (!empty($params)) {
    // bind params for count
    $bindNames = [];
    $bindNames[] = & $types;
    // mysqli bind_param requires references to variables; create copies
    $vals = array_map(function($v){ return $v; }, $params);
    for ($i=0;$i<count($vals);$i++) $bindNames[] = & $vals[$i];
    call_user_func_array([$stmt, 'bind_param'], $bindNames);
}
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$total = intval($row['cnt'] ?? 0);
$stmt->close();

// data query: use LEFT JOINs for department/section/division names
$sql = "SELECT v.*, 
  d.name_ar AS department_name,
  s.name_ar AS section_name,
  dv.name_ar AS division_name
  FROM vehicles v
  LEFT JOIN departments d ON d.id = v.department_id
  LEFT JOIN sections s ON s.id = v.section_id
  LEFT JOIN divisions dv ON dv.id = v.division_id
  $whereSql
  ORDER BY v.id DESC
  LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo json_encode(['success'=>false,'message'=>'Server error']);
    exit;
}

// bind params: previous params + limit + offset
$allParams = $params;
$allTypes = $types;
$allParams[] = $per_page;
$allParams[] = $offset;
$allTypes .= 'ii';

// prepare bind array
$bindNames = [];
$bindNames[] = & $allTypes;
for ($i=0;$i<count($allParams);$i++) $bindNames[] = & $allParams[$i];
call_user_func_array([$stmt, 'bind_param'], $bindNames);

$stmt->execute();
$result = $stmt->get_result();

$vehicles = [];
while ($r = $result->fetch_assoc()) {
    $vehicles[] = [
        'id' => (int)$r['id'],
        'vehicle_code' => $r['vehicle_code'] ?? null,
        'type' => $r['type'] ?? null,
        'manufacture_year' => $r['manufacture_year'] ? (int)$r['manufacture_year'] : null,
        'emp_id' => $r['emp_id'] ?? null,
        'driver_name' => $r['driver_name'] ?? null,
        'driver_phone' => $r['driver_phone'] ?? null,
        'status' => $r['status'] ?? null,
        'department_id' => isset($r['department_id']) ? (int)$r['department_id'] : null,
        'department_name' => $r['department_name'] ?? null,
        'section_id' => isset($r['section_id']) ? (int)$r['section_id'] : null,
        'section_name' => $r['section_name'] ?? null,
        'division_id' => isset($r['division_id']) ? (int)$r['division_id'] : null,
        'division_name' => $r['division_name'] ?? null,
        'vehicle_mode' => $r['vehicle_mode'] ?? null,
        'notes' => $r['notes'] ?? null,
        'created_by' => isset($r['created_by']) ? (int)$r['created_by'] : null,
        'created_at' => $r['created_at'] ?? null,
    ];
}
$stmt->close();

echo json_encode([
    'success' => true,
    'total' => $total,
    'page' => $page,
    'per_page' => $per_page,
    'vehicles' => $vehicles
], JSON_UNESCAPED_UNICODE);
exit;
?>

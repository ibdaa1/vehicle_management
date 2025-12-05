<?php
// vehicle_management/api/vehicle/get.php
// GET: id - returns a single vehicle by ID
// Returns { success: true, vehicle: { ... } }

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

// get id from query
$id = $_GET['id'] ?? null;
$id = $id ? intval($id) : 0;

if (!$id) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Invalid id']);
    exit;
}

// fetch vehicle with related names
$sql = "SELECT v.*, 
  (SELECT name_ar FROM departments d WHERE d.id = v.department_id LIMIT 1) AS department_name,
  (SELECT name_ar FROM sections s WHERE s.id = v.section_id LIMIT 1) AS section_name,
  (SELECT name_ar FROM divisions dv WHERE dv.id = v.division_id LIMIT 1) AS division_name
  FROM vehicles v
  WHERE v.id = ?
  LIMIT 1";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo json_encode(['success'=>false,'message'=>'Server error']);
    exit;
}

$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    http_response_code(404);
    echo json_encode(['success'=>false,'message'=>'Vehicle not found']);
    exit;
}

$vehicle = [
    'id' => (int)$row['id'],
    'vehicle_code' => $row['vehicle_code'] ?? null,
    'type' => $row['type'] ?? null,
    'manufacture_year' => $row['manufacture_year'] ? (int)$row['manufacture_year'] : null,
    'emp_id' => $row['emp_id'] ?? null,
    'driver_name' => $row['driver_name'] ?? null,
    'driver_phone' => $row['driver_phone'] ?? null,
    'status' => $row['status'] ?? null,
    'department_id' => isset($row['department_id']) ? (int)$row['department_id'] : null,
    'department_name' => $row['department_name'] ?? null,
    'section_id' => isset($row['section_id']) ? (int)$row['section_id'] : null,
    'section_name' => $row['section_name'] ?? null,
    'division_id' => isset($row['division_id']) ? (int)$row['division_id'] : null,
    'division_name' => $row['division_name'] ?? null,
    'vehicle_mode' => $row['vehicle_mode'] ?? null,
    'notes' => $row['notes'] ?? null,
    'created_by' => isset($row['created_by']) ? (int)$row['created_by'] : null,
    'created_at' => $row['created_at'] ?? null,
    'updated_by' => isset($row['updated_by']) ? (int)$row['updated_by'] : null,
    'updated_at' => $row['updated_at'] ?? null,
];

echo json_encode([
    'success' => true,
    'vehicle' => $vehicle
], JSON_UNESCAPED_UNICODE);
exit;
?>

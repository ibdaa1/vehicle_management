<?php
/**
 * vehicle_management/api/vehicle/update_movement_coords.php
 * 
 * Updates GPS coordinates (latitude, longitude) for a vehicle movement.
 * Only the movement owner or admin/super admin can update coordinates.
 * 
 * POST parameters:
 * - movement_id (required): ID of the vehicle movement
 * - vehicle_code (optional): Vehicle code for additional validation
 * - latitude (required): GPS latitude
 * - longitude (required): GPS longitude
 * 
 * Returns JSON with success status
 */

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Include timezone config
$timezonePath = __DIR__ . '/../config/timezone.php';
if (file_exists($timezonePath)) {
    require_once $timezonePath;
} else {
    date_default_timezone_set('Asia/Dubai');
}

// CORS headers
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Use POST.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Include session and DB
$sessionPaths = [
    __DIR__ . '/../config/session.php',
    __DIR__ . '/../../config/session.php'
];
foreach ($sessionPaths as $p) {
    if (file_exists($p)) {
        require_once $p;
        break;
    }
}
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$dbPaths = [
    __DIR__ . '/../config/db.php',
    __DIR__ . '/../../config/db.php'
];
foreach ($dbPaths as $p) {
    if (file_exists($p)) {
        require_once $p;
        break;
    }
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection not available'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Authentication check
$currentUser = null;
if (!empty($_SESSION['user']) && is_array($_SESSION['user']) && !empty($_SESSION['user']['id'])) {
    $currentUser = $_SESSION['user'];
} elseif (!empty($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id, role_id, emp_id, username FROM users WHERE id = ? LIMIT 1");
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

if (!$currentUser || empty($currentUser['emp_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$empId = $currentUser['emp_id'];
$roleId = intval($currentUser['role_id'] ?? 0);

// Get role permissions - check if admin/super admin
$isAdmin = false;
if ($roleId > 0) {
    $stmt = $conn->prepare("SELECT can_view_all_vehicles FROM roles WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $roleId);
        $stmt->execute();
        $roleRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($roleRow) {
            $isAdmin = (bool)($roleRow['can_view_all_vehicles'] ?? false);
        }
    }
}

// Get POST data - support both JSON and form data
$input = file_get_contents('php://input');
$jsonData = json_decode($input, true);

if ($jsonData && json_last_error() === JSON_ERROR_NONE) {
    $movementId = !empty($jsonData['movement_id']) ? intval($jsonData['movement_id']) : null;
    $vehicleCode = trim($jsonData['vehicle_code'] ?? '');
    $latitude = isset($jsonData['latitude']) ? floatval($jsonData['latitude']) : null;
    $longitude = isset($jsonData['longitude']) ? floatval($jsonData['longitude']) : null;
} else {
    $movementId = !empty($_POST['movement_id']) ? intval($_POST['movement_id']) : null;
    $vehicleCode = trim($_POST['vehicle_code'] ?? '');
    $latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : null;
    $longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : null;
}

// Validate input
if (empty($movementId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Movement ID is required'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($latitude === null || $longitude === null) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Latitude and longitude are required'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Validate coordinate ranges
if ($latitude < -90 || $latitude > 90) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid latitude. Must be between -90 and 90'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($longitude < -180 || $longitude > 180) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid longitude. Must be between -180 and 180'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Get movement details and check permissions
$stmt = $conn->prepare("
    SELECT id, vehicle_code, performed_by, operation_type
    FROM vehicle_movements
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database query failed'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param('i', $movementId);
$stmt->execute();
$movement = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$movement) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Movement not found'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Permission check: User must own the movement or be admin
$hasPermission = false;

if ($isAdmin) {
    // Admin can update any movement
    $hasPermission = true;
} elseif ($movement['performed_by'] === $empId) {
    // Owner can update their own movement
    $hasPermission = true;
}

if (!$hasPermission) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'You do not have permission to update coordinates for this movement'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Additional validation: vehicle_code must match if provided
if (!empty($vehicleCode) && $movement['vehicle_code'] !== $vehicleCode) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Vehicle code does not match the movement'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Update coordinates
$updateStmt = $conn->prepare("
    UPDATE vehicle_movements
    SET latitude = ?, longitude = ?
    WHERE id = ?
");

if (!$updateStmt) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to prepare update statement'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$updateStmt->bind_param('ddi', $latitude, $longitude, $movementId);

if ($updateStmt->execute()) {
    $updateStmt->close();
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Coordinates updated successfully',
        'data' => [
            'movement_id' => $movementId,
            'vehicle_code' => $movement['vehicle_code'],
            'latitude' => $latitude,
            'longitude' => $longitude,
            'updated_at' => date('Y-m-d H:i:s')
        ]
    ], JSON_UNESCAPED_UNICODE);
} else {
    $updateStmt->close();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update coordinates: ' . $conn->error
    ], JSON_UNESCAPED_UNICODE);
}

exit;

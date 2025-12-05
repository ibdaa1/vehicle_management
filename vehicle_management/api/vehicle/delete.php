<?php
// vehicle_management/api/vehicle/delete.php
// POST: id (vehicle id) - deletes the vehicle if user has permission.
// Uses perm helper (role flags) to authorize delete or allows owner to delete own created resource.

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
    echo json_encode(['success'=>false,'message'=>'Server misconfiguration.']);
    exit;
}

// include permissions helper
$permPath = __DIR__ . '/../permissions/perm_helper.php';
if (file_exists($permPath)) require_once $permPath;

// get current user using perm helper if available, else session
$currentUser = null;
if (function_exists('get_current_user')) {
    $currentUser = get_current_user($conn);
} else {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (!empty($_SESSION['user'])) $currentUser = $_SESSION['user'];
}

if (!$currentUser || empty($currentUser['id'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Not authenticated']);
    exit;
}

$id = $_POST['id'] ?? $_REQUEST['id'] ?? null;
$id = $id ? intval($id) : 0;
if (!$id) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Invalid id']);
    exit;
}

// fetch vehicle to know owner/created_by
$stmt = $conn->prepare("SELECT id, vehicle_code, created_by FROM vehicles WHERE id = ? LIMIT 1");
if (!$stmt) {
    echo json_encode(['success'=>false,'message'=>'Server error']);
    exit;
}
$stmt->bind_param('i', $id);
$stmt->execute();
$vehicle = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$vehicle) {
    http_response_code(404);
    echo json_encode(['success'=>false,'message'=>'Vehicle not found']);
    exit;
}

// authorize: if role_flags indicates delete OR current user is creator
$allowDelete = false;
if (function_exists('role_flags')) {
    $roleId = intval($currentUser['role_id'] ?? 0);
    $flags = role_flags($conn, $roleId);
    if (is_array($flags) && !empty($flags['delete'])) $allowDelete = true;
}
if (!$allowDelete && intval($currentUser['id']) === intval($vehicle['created_by'])) $allowDelete = true;

if (!$allowDelete) {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Forbidden: insufficient permissions']);
    exit;
}

// perform deletion (soft delete could be implemented; here hard delete)
$del = $conn->prepare("DELETE FROM vehicles WHERE id = ? LIMIT 1");
if (!$del) {
    echo json_encode(['success'=>false,'message'=>'Server error']);
    exit;
}
$del->bind_param('i', $id);
if (!$del->execute()) {
    echo json_encode(['success'=>false,'message'=>'Server error: ' . $del->error]);
    $del->close();
    exit;
}
$del->close();

echo json_encode(['success'=>true,'message'=>'تم الحذف'], JSON_UNESCAPED_UNICODE);
exit;
?>

<?php
// vehicle_management/api/permissions/roles/update.php
// Update permission flags for a role (Admin/SuperAdmin only)
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }
if (empty($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Not authenticated']); exit; }

// only admin/superadmin allowed
$st = $conn->prepare("SELECT role_id FROM users WHERE id = ? LIMIT 1");
$st->bind_param('i', $_SESSION['user_id']);
$st->execute();
$r = $st->get_result()->fetch_assoc(); $st->close();
$actor_role = (int)($r['role_id'] ?? 0);
if (!in_array($actor_role, [1,2], true)) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Forbidden']); exit; }

$role_id = isset($_POST['role_id']) ? (int)$_POST['role_id'] : 0;
$can_create = isset($_POST['can_create']) ? (int)$_POST['can_create'] : 0;
$can_edit = isset($_POST['can_edit']) ? (int)$_POST['can_edit'] : 0;
$can_delete = isset($_POST['can_delete']) ? (int)$_POST['can_delete'] : 0;

if ($role_id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid role_id']); exit; }

try {
    $stmt = $conn->prepare("UPDATE roles SET can_create = ?, can_edit = ?, can_delete = ?, description = COALESCE(NULLIF(?,''), description) WHERE id = ? LIMIT 1");
    if (!$stmt) throw new Exception($conn->error);
    $description = trim($_POST['description'] ?? '');
    $stmt->bind_param('iiisi', $can_create, $can_edit, $can_delete, $description, $role_id);
    if ($stmt->execute()) echo json_encode(['success'=>true]);
    else echo json_encode(['success'=>false,'message'=>'DB error']);
    $stmt->close();
    exit;
} catch (Exception $e) {
    error_log('roles update error: '.$e->getMessage());
    http_response_code(500); echo json_encode(['success'=>false,'message'=>'Server error']); exit;
}
?>
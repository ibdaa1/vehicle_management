<?php
// vehicle_management/api/permissions/roles/list.php
// Returns all roles with permission flags (Admin/SuperAdmin only)
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../../config/db.php';

if (empty($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Not authenticated']); exit; }
// only admin/superadmin allowed to manage roles
$st = $conn->prepare("SELECT role_id FROM users WHERE id = ? LIMIT 1");
$st->bind_param('i', $_SESSION['user_id']);
$st->execute();
$r = $st->get_result()->fetch_assoc(); $st->close();
$role = (int)($r['role_id'] ?? 0);
if (!in_array($role, [1,2], true)) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Forbidden']); exit; }

$res = $conn->query("SELECT id, name_en, name_ar, can_create, can_edit, can_delete, description FROM roles ORDER BY id ASC");
$roles = [];
while ($row = $res->fetch_assoc()) {
    $roles[] = $row;
}
echo json_encode(['success'=>true,'roles'=>$roles], JSON_UNESCAPED_UNICODE);
exit;
?>
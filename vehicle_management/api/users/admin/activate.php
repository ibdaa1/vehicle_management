<?php
// vehicle_management/api/users/admin/activate.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../../config/db.php';
if (empty($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Not authenticated']); exit; }
$uid = (int)$_SESSION['user_id'];
$st = $conn->prepare("SELECT role_id FROM users WHERE id = ? LIMIT 1"); $st->bind_param('i',$uid); $st->execute(); $r = $st->get_result()->fetch_assoc(); $st->close();
$role = (int)($r['role_id'] ?? 0);
if (!in_array($role,[1,2],true)) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Forbidden']); exit; }

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid id']); exit; }
try {
    // toggle active state
    $stmt = $conn->prepare("SELECT is_active FROM users WHERE id = ? LIMIT 1"); $stmt->bind_param('i',$id); $stmt->execute(); $cur = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$cur) { echo json_encode(['success'=>false,'message'=>'Not found']); exit; }
    $new = $cur['is_active'] ? 0 : 1;
    $u = $conn->prepare("UPDATE users SET is_active = ?, updated_at = NOW() WHERE id = ? LIMIT 1");
    $u->bind_param('ii',$new,$id);
    if ($u->execute()) echo json_encode(['success'=>true,'is_active'=>$new]);
    else echo json_encode(['success'=>false,'message'=>'DB error']);
    $u->close();
    exit;
} catch (Exception $e) {
    error_log('admin activate error: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error']);
    exit;
}
?>
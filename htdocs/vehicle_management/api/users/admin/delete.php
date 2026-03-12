<?php
// vehicle_management/api/users/admin/delete.php
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
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i',$id);
    if ($stmt->execute()) echo json_encode(['success'=>true]);
    else echo json_encode(['success'=>false,'message'=>'DB error']);
    $stmt->close();
    exit;
} catch (Exception $e) {
    error_log('admin delete error: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error']);
    exit;
}
?>
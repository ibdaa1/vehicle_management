<?php
// vehicle_management/api/users/admin/activate.php
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../../config/db.php';

// Determine authenticated user id from session (support both styles)
$uid = null;
if (!empty($_SESSION['user']) && is_array($_SESSION['user']) && !empty($_SESSION['user']['id'])) {
    $uid = (int) $_SESSION['user']['id'];
} elseif (!empty($_SESSION['user_id'])) {
    $uid = (int) $_SESSION['user_id'];
}

if (empty($uid)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Fetch the role of the acting user (use DB to be safe)
$st = $conn->prepare("SELECT role_id FROM users WHERE id = ? LIMIT 1");
if (!$st) {
    error_log('activate prepare error: ' . $conn->error);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error (prepare)'], JSON_UNESCAPED_UNICODE);
    exit;
}
$st->bind_param('i', $uid);
$st->execute();
$r = $st->get_result()->fetch_assoc();
$st->close();

$role = (int)($r['role_id'] ?? 0);
// Allow only admins (role 1) and super-admins (role 2) — adjust role ids if different
if (!in_array($role, [1, 2], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Read target user id from POST
$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid id'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // get current state
    $stmt = $conn->prepare("SELECT is_active FROM users WHERE id = ? LIMIT 1");
    if (!$stmt) {
        error_log('activate select prepare: ' . $conn->error);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error (prepare select)'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $cur = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$cur) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $new = ($cur['is_active'] ? 0 : 1);

    $u = $conn->prepare("UPDATE users SET is_active = ?, updated_at = NOW() WHERE id = ? LIMIT 1");
    if (!$u) {
        error_log('activate update prepare: ' . $conn->error);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error (prepare update)'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $u->bind_param('ii', $new, $id);
    if ($u->execute()) {
        // Optionally, if you maintain a users list in session for admins, you could refresh it here.
        echo json_encode(['success' => true, 'is_active' => (int)$new], JSON_UNESCAPED_UNICODE);
    } else {
        error_log('activate update execute: ' . $u->error);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'DB error'], JSON_UNESCAPED_UNICODE);
    }
    $u->close();
    exit;
} catch (Exception $e) {
    error_log('admin activate exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error'], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
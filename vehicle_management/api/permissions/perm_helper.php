<?php
// vehicle_management/api/permissions/perm_helper.php
// Simple helper that reads permission flags from roles table.
// Usage: require_once __DIR__ . '/perm_helper.php'; then call can_create($conn), can_edit_user($conn, $target_id), can_delete_user($conn, $target_id)

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

define('ROLE_SUPERADMIN', 1);
define('ROLE_ADMIN', 2);

// get current user minimal row
function current_user_row($conn) {
    if (empty($_SESSION['user_id'])) return null;
    $uid = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id, role_id, department_id, username FROM users WHERE id = ? LIMIT 1");
    if (!$stmt) return null;
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $u = $res->fetch_assoc();
    $stmt->close();
    return $u ?: null;
}

// get current user (alias for compatibility with delete.php)
function get_current_user($conn) {
    // try session first
    if (!empty($_SESSION['user'])) {
        return $_SESSION['user'];
    }
    // fallback to user_id lookup
    $row = current_user_row($conn);
    if ($row) {
        // return compatible structure
        return [
            'id' => $row['id'],
            'role_id' => $row['role_id'],
            'department_id' => $row['department_id'] ?? null,
            'username' => $row['username']
        ];
    }
    return null;
}

// read role flags from roles table
function role_flags($conn, $role_id) {
    if ($role_id === null) return null;
    $stmt = $conn->prepare("SELECT can_create, can_edit, can_delete FROM roles WHERE id = ? LIMIT 1");
    if (!$stmt) return null;
    $stmt->bind_param('i', $role_id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$r) return null;
    return [
        'create' => (bool)($r['can_create'] ?? 0),
        'edit'   => (bool)($r['can_edit'] ?? 0),
        'delete' => (bool)($r['can_delete'] ?? 0)
    ];
}

// checkers

function can_create($conn) {
    // choose policy: allow authenticated users if role flag true, or allow all auth users
    $u = current_user_row($conn);
    if (!$u) return false;
    $flags = role_flags($conn, (int)$u['role_id']);
    if ($flags !== null) return $flags['create'];
    return true; // fallback: allow
}

function can_edit_user($conn, $target_user_id) {
    $u = current_user_row($conn);
    if (!$u) return false;
    $current_id = (int)$u['id'];
    if ($current_id === (int)$target_user_id) return true; // owner can edit own profile
    $flags = role_flags($conn, (int)$u['role_id']);
    if ($flags !== null) return $flags['edit'];
    // fallback: only admins
    return in_array((int)$u['role_id'], [ROLE_ADMIN, ROLE_SUPERADMIN], true);
}

function can_delete_user($conn, $target_user_id) {
    $u = current_user_row($conn);
    if (!$u) return false;
    $current_id = (int)$u['id'];
    if ($current_id === (int)$target_user_id) return false; // disallow self-delete
    $flags = role_flags($conn, (int)$u['role_id']);
    if ($flags !== null) return $flags['delete'];
    return in_array((int)$u['role_id'], [ROLE_ADMIN, ROLE_SUPERADMIN], true);
}
?>
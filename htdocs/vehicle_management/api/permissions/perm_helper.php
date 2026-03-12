<?php
// vehicle_management/api/permissions/perm_helper.php

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

define('ROLE_SUPERADMIN', 1);
define('ROLE_ADMIN', 2);

/**
 * Get current user basic row
 */
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

/**
 * Fetch ALL role permissions dynamically.
 * Works for any new future columns automatically.
 */
function role_flags($conn, $role_id) {
    if ($role_id === null) return null;

    // Read column list from roles table
    $cols = [];
    $res = $conn->query("SHOW COLUMNS FROM roles");
    if (!$res) return null;
    while ($c = $res->fetch_assoc()) {
        $cols[] = $c['Field'];
    }

    // Build safe SELECT
    $select = implode(", ", array_map(fn($f)=>"`$f`", $cols));

    $stmt = $conn->prepare("SELECT $select FROM roles WHERE id = ? LIMIT 1");
    if (!$stmt) return null;

    $stmt->bind_param('i', $role_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) return null;

    // Build result: convert all tinyint fields to boolean
    $flags = [];
    foreach ($row as $k => $v) {
        if (preg_match('/^can_/', $k) || $k === 'allow_registration') {
            $flags[$k] = (bool)$v;
        } else {
            $flags[$k] = $v;
        }
    }

    return $flags;
}

/**
 * Permission checks (you can add more below)
 */
function can_create($conn) {
    $u = current_user_row($conn);
    if (!$u) return false;
    $flags = role_flags($conn, (int)$u['role_id']);
    return $flags['can_create'] ?? false;
}

function can_edit_user($conn, $target_user_id) {
    $u = current_user_row($conn);
    if (!$u) return false;

    if ((int)$u['id'] === (int)$target_user_id) {
        return true; // user can edit his own profile
    }

    $flags = role_flags($conn, (int)$u['role_id']);
    return $flags['can_edit'] ?? false;
}

function can_delete_user($conn, $target_user_id) {
    $u = current_user_row($conn);
    if (!$u) return false;

    if ((int)$u['id'] === (int)$target_user_id) {
        return false; // self delete not allowed
    }

    $flags = role_flags($conn, (int)$u['role_id']);
    return $flags['can_delete'] ?? false;
}
?>

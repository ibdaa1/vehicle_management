<?php
// vehicle_management/api/permissions/get_permissions.php

header('Content-Type: application/json; charset=utf-8');
session_start();

$debug = isset($_GET['debug']) && $_GET['debug'] == '1';

// Try including DB config
$dbCandidates = [
    __DIR__ . '/../../config/db.php',
    __DIR__ . '/../config/db.php',
    __DIR__ . '/../../../config/db.php',
];
$included = false;
foreach ($dbCandidates as $p) {
    if (file_exists($p)) { require_once $p; $included = true; break; }
}
if (!$included) {
    http_response_code(500);
    echo json_encode([
        'success'=>false,
        'message'=>'Server error',
        'debug'=>$debug?'DB config not found':null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!isset($conn)) {
    http_response_code(500);
    echo json_encode([
        'success'=>false,
        'message'=>'Server error',
        'debug'=>$debug?'$conn not defined':null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {

    // Determine requested role_id
    $requestedRoleId = null;
    if (isset($_GET['role_id']) && is_numeric($_GET['role_id'])) {
        $requestedRoleId = (int)$_GET['role_id'];
    }

    // Determine current user & role
    $currentUserId = null;
    $currentRoleId = null;

    if (!empty($_SESSION['user']['id'])) {
        $currentUserId = (int)$_SESSION['user']['id'];
        if (!empty($_SESSION['user']['role_id'])) {
            $currentRoleId = (int)$_SESSION['user']['role_id'];
        }
    } elseif (!empty($_SESSION['user_id'])) {
        $currentUserId = (int)$_SESSION['user_id'];
    }

    // Fetch role if user_id found but role not loaded in session
    if ($currentUserId && $currentRoleId === null) {
        if ($conn instanceof mysqli) {
            $st = $conn->prepare("SELECT role_id FROM users WHERE id = ? LIMIT 1");
            $st->bind_param('i', $currentUserId);
            $st->execute();
            $r = $st->get_result()->fetch_assoc();
            $st->close();
            $currentRoleId = $r['role_id'] ?? null;
        } else {
            $stmt = $conn->prepare("SELECT role_id FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$currentUserId]);
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            $currentRoleId = $r['role_id'] ?? null;
        }
    }

    // Permission to request role_id
    if ($requestedRoleId !== null) {
        if ($currentRoleId === null) {
            http_response_code(401);
            echo json_encode(['success'=>false,'message'=>'Authentication required']);
            exit;
        }
        if (!in_array($currentRoleId, [1,2], true)) {
            http_response_code(403);
            echo json_encode(['success'=>false,'message'=>'Only admin can view roles']);
            exit;
        }
        $roleIdToUse = $requestedRoleId;
    } else {
        if ($currentRoleId === null) {
            http_response_code(401);
            echo json_encode(['success'=>false,'message'=>'Not authenticated']);
            exit;
        }
        $roleIdToUse = $currentRoleId;
    }

    /**
     * 1) Get all columns from roles table
     */
    if ($conn instanceof mysqli) {
        $resCols = $conn->query("SHOW COLUMNS FROM `roles`");
        if ($resCols === false) {
            throw new Exception('SHOW COLUMNS failed: ' . $conn->error);
        }

        $cols = [];
        while ($c = $resCols->fetch_assoc()) {
            $cols[] = $c['Field'];
        }

        // Build safe SELECT fields
        $selectFields = array_map(fn($f)=>"`$f`", $cols);

        $sql = "SELECT " . implode(", ", $selectFields) . " FROM roles WHERE id = ? LIMIT 1";
        $st = $conn->prepare($sql);
        if (!$st) throw new Exception("Prepare failed: ".$conn->error);
        $st->bind_param('i',$roleIdToUse);
        $st->execute();
        $roleRow = $st->get_result()->fetch_assoc();
        $st->close();

    } else {

        // PDO path: fetch column list first
        $cols = [];
        $q = $conn->query("SHOW COLUMNS FROM roles");
        foreach ($q as $r) {
            $cols[] = $r['Field'];
        }

        $selectFields = implode(", ", array_map(fn($f)=>"`$f`", $cols));

        $stmt = $conn->prepare("SELECT $selectFields FROM roles WHERE id = ? LIMIT 1");
        $stmt->execute([$roleIdToUse]);
        $roleRow = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$roleRow) {
        http_response_code(404);
        echo json_encode(['success'=>false,'message'=>'Role not found']);
        exit;
    }

    /**
     * 2) Convert **all tinyint columns** automatically to boolean
     *    and return everything dynamically.
     */
    $roleOut = [];
    foreach ($roleRow as $k => $v) {

        // if tinyint or boolean-like → cast to bool
        if (preg_match('/^can_/', $k) || $k === 'allow_registration') {
            $roleOut[$k] = (bool)$v;
        } else {
            $roleOut[$k] = $v;
        }
    }

    // Ensure id is numeric
    if (isset($roleOut['id'])) {
        $roleOut['id'] = (int)$roleOut['id'];
    }

    echo json_encode([
        'success'=>true,
        'role_id'=>$roleOut['id'],
        'role'=>$roleOut
    ], JSON_UNESCAPED_UNICODE);

    exit;

} catch (Throwable $e) {
    error_log('get_permissions.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success'=>false,
        'message'=>'Server error',
        'debug'=>$debug ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>

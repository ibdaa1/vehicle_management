<?php
// vehicle_management/api/users/admin/get.php
// Robust debug-enabled endpoint to fetch a single user by id.
// Usage for debugging: /vehicle_management/api/users/admin/get.php?id=15&debug=1
// Replace existing file with this temporarily, call with &debug=1, then paste the JSON output here.

header('Content-Type: application/json; charset=utf-8');
session_start();

$debug = isset($_GET['debug']) && $_GET['debug'] == '1';
if ($debug) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(E_ALL);
}

set_exception_handler(function($e) use ($debug) {
    error_log('admin/get.php exception: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    $resp = ['success' => false, 'message' => 'Server error'];
    if ($debug) $resp['debug'] = $e->getMessage();
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
});
set_error_handler(function($errno, $errstr, $errfile, $errline) use ($debug) {
    $msg = "PHP error ({$errno}) {$errstr} in {$errfile} on line {$errline}";
    error_log('admin/get.php error: ' . $msg);
    if ($debug) {
        http_response_code(500);
        echo json_encode(['success'=>false,'message'=>'Server error','debug'=>$msg], JSON_UNESCAPED_UNICODE);
        exit;
    }
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// include DB config (robust path)
$dbPathCandidates = [
    __DIR__ . '/../../config/db.php',
    __DIR__ . '/../config/db.php',
    __DIR__ . '/../../../config/db.php',
];

$included = false;
foreach ($dbPathCandidates as $p) {
    if (file_exists($p)) {
        require_once $p;
        $included = true;
        break;
    }
}
if (!$included) {
    $msg = 'DB config not found. Tried: ' . implode(', ', $dbPathCandidates);
    error_log('admin/get.php: ' . $msg);
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error','debug'=>$debug ? $msg : null], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($conn)) {
    $msg = 'Database connection ($conn) not defined by config/db.php';
    error_log('admin/get.php: ' . $msg);
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error','debug'=>$debug ? $msg : null], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // basic auth check
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success'=>false,'message'=>'Not authenticated']);
        exit;
    }

    $actorId = (int)$_SESSION['user_id'];
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        echo json_encode(['success'=>false,'message'=>'Invalid id']);
        exit;
    }

    // ensure actor is admin/superadmin
    if ($conn instanceof mysqli) {
        $st = $conn->prepare("SELECT role_id FROM users WHERE id = ? LIMIT 1");
        if ($st === false) throw new Exception('Prepare failed for actor role check: ' . $conn->error);
        $st->bind_param('i', $actorId);
        $st->execute();
        $res = $st->get_result();
        $actorRow = $res ? $res->fetch_assoc() : null;
        $st->close();
    } else {
        $stmt = $conn->prepare("SELECT role_id FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$actorId]);
        $actorRow = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    $actorRole = (int)($actorRow['role_id'] ?? 0);
    if (!in_array($actorRole, [1,2], true)) {
        http_response_code(403);
        echo json_encode(['success'=>false,'message'=>'Forbidden']);
        exit;
    }

    // discover columns in users table
    $availableCols = [];
    if ($conn instanceof mysqli) {
        $colRes = $conn->query("SHOW COLUMNS FROM `users`");
        if ($colRes === false) throw new Exception('SHOW COLUMNS failed: ' . $conn->error);
        while ($c = $colRes->fetch_assoc()) $availableCols[] = $c['Field'];
    } else {
        $stmt = $conn->query("SHOW COLUMNS FROM `users`");
        $availableCols = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }
    $has = function($name) use ($availableCols) { return in_array($name, $availableCols, true); };

    // build select list (include fields that exist)
    $selectParts = [];
    $preferNameExpr = null;
    // decide display name expression
    if ($has('display_name') && $has('full_name')) {
        $preferNameExpr = "COALESCE(`display_name`,`full_name`,`username`) AS `display_name`";
    } elseif ($has('display_name')) {
        $preferNameExpr = "`display_name` AS `display_name`";
    } elseif ($has('full_name')) {
        $preferNameExpr = "COALESCE(`full_name`,`username`) AS `display_name`";
    } elseif ($has('username')) {
        $preferNameExpr = "`username` AS `display_name`";
    }

    // add other columns if present
    $candidates = ['id','emp_id','username','email','phone','preferred_language','role_id','is_active','department_id','section_id','division_id','created_at'];
    foreach ($candidates as $col) {
        if ($col === 'username') continue; // username handled by preferNameExpr to avoid duplication
        if ($has($col)) $selectParts[] = "`{$col}`";
    }

    if ($preferNameExpr !== null) array_unshift($selectParts, $preferNameExpr);

    if (empty($selectParts)) throw new Exception('No selectable columns found in users table');

    $selectSQL = implode(', ', $selectParts);
    $q = "SELECT {$selectSQL} FROM `users` WHERE `id` = ? LIMIT 1";

    // fetch user
    if ($conn instanceof mysqli) {
        $stmt = $conn->prepare($q);
        if ($stmt === false) throw new Exception('Prepare failed (select user): ' . $conn->error);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } else {
        $stmt = $conn->prepare($q);
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$user) {
        echo json_encode(['success'=>false,'message'=>'Not found']);
        exit;
    }

    echo json_encode(['success'=>true,'user'=>$user], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $t) {
    error_log('admin/get.php throwable: ' . $t->getMessage());
    http_response_code(500);
    $resp = ['success'=>false,'message'=>'Server error'];
    if ($debug) $resp['debug'] = $t->getMessage();
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}
?>
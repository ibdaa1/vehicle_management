<?php
// vehicle_management/api/users/admin/list.php
// Robust admin users list that adapts to the actual columns present in the users table.
// Use ?debug=1 to show detailed errors (temporary).

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
    error_log('admin/list.php exception: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    $resp = ['success' => false, 'message' => 'Server error'];
    if ($debug) $resp['debug'] = $e->getMessage();
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
});
set_error_handler(function($errno, $errstr, $errfile, $errline) use ($debug) {
    $msg = "PHP error ({$errno}) {$errstr} in {$errfile} on line {$errline}";
    error_log('admin/list.php error: ' . $msg);
    if ($debug) {
        http_response_code(500);
        echo json_encode(['success'=>false,'message'=>'Server error','debug'=>$msg], JSON_UNESCAPED_UNICODE);
        exit;
    }
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// include DB config
$dbPath = __DIR__ . '/../../config/db.php';
if (!file_exists($dbPath)) {
    $msg = "DB config not found at {$dbPath}";
    error_log($msg);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error', 'debug' => $debug ? $msg : null], JSON_UNESCAPED_UNICODE);
    exit;
}
require_once $dbPath;

if (!isset($conn)) {
    $msg = 'Database connection ($conn) is not defined by config/db.php';
    error_log($msg);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error', 'debug' => $debug ? $msg : null], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }

    $uid = (int)$_SESSION['user_id'];

    // check role
    if ($conn instanceof mysqli) {
        $st = $conn->prepare("SELECT role_id FROM users WHERE id = ? LIMIT 1");
        if ($st === false) throw new Exception('Prepare failed for role check: ' . $conn->error);
        $st->bind_param('i', $uid);
        $st->execute();
        $res = $st->get_result();
        $r = $res ? $res->fetch_assoc() : null;
        $st->close();
    } else {
        $stmt = $conn->prepare("SELECT role_id FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$uid]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    $role = (int)($r['role_id'] ?? 0);
    if (!in_array($role, [1,2], true)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }

    // --- Discover available columns in users table ---
    $availableCols = [];
    if ($conn instanceof mysqli) {
        $colRes = $conn->query("SHOW COLUMNS FROM `users`");
        if ($colRes === false) throw new Exception('SHOW COLUMNS failed: ' . $conn->error);
        while ($c = $colRes->fetch_assoc()) {
            $availableCols[] = $c['Field'];
        }
    } else {
        $stmt = $conn->query("SHOW COLUMNS FROM `users`");
        $availableCols = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    // helper to check existence
    $has = function($name) use ($availableCols) {
        return in_array($name, $availableCols, true);
    };

    // build select list dynamically
    $selectParts = [];
    // id always
    if ($has('id')) $selectParts[] = '`id`';
    if ($has('emp_id')) $selectParts[] = '`emp_id`';
    if ($has('username')) $selectParts[] = '`username`';
    if ($has('email')) $selectParts[] = '`email`';
    if ($has('phone')) $selectParts[] = '`phone`';
    if ($has('preferred_language')) $selectParts[] = '`preferred_language`';
    if ($has('role_id')) $selectParts[] = '`role_id`';
    if ($has('is_active')) $selectParts[] = '`is_active`';
    if ($has('department_id')) $selectParts[] = '`department_id`';
    if ($has('section_id')) $selectParts[] = '`section_id`';
    if ($has('division_id')) $selectParts[] = '`division_id`';
    if ($has('created_at')) $selectParts[] = '`created_at`';

    // build a safe display_name expression based on available name columns
    // prefer: display_name, full_name, username
    $nameExpr = null;
    if ($has('display_name') && $has('full_name')) {
        $nameExpr = "COALESCE(`display_name`,`full_name`,`username`) AS `display_name`";
    } elseif ($has('display_name')) {
        $nameExpr = "`display_name` AS `display_name`";
    } elseif ($has('full_name')) {
        $nameExpr = "COALESCE(`full_name`,`username`) AS `display_name`";
    } elseif ($has('username')) {
        $nameExpr = "`username` AS `display_name`";
    }

    if ($nameExpr !== null) {
        // ensure we don't duplicate username/display_name if already included
        // remove plain username from selectParts if we will include it via nameExpr to avoid duplicate key
        $selectParts = array_filter($selectParts, function($p) {
            return trim($p, '`') !== 'username';
        });
        array_unshift($selectParts, $nameExpr);
    }

    if (empty($selectParts)) {
        throw new Exception('No selectable columns found in users table');
    }

    $selectSQL = implode(', ', $selectParts);
    $q = "SELECT {$selectSQL} FROM `users` ORDER BY id DESC LIMIT 1000";

    // execute query
    if ($conn instanceof mysqli) {
        $res2 = $conn->query($q);
        if ($res2 === false) throw new Exception('Query failed: ' . $conn->error);
        $users = [];
        while ($row = $res2->fetch_assoc()) $users[] = $row;
    } else {
        $stmt = $conn->query($q);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode(['success' => true, 'users' => $users], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $t) {
    error_log('admin/list.php throwable: ' . $t->getMessage());
    http_response_code(500);
    $resp = ['success' => false, 'message' => 'Server error'];
    if ($debug) $resp['debug'] = $t->getMessage();
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}
?>
<?php
// vehicle_management/api/users/admin/update.php
// Debug-enabled admin update endpoint.
// Usage: POST to this URL with form fields (id, display_name, email, phone, department_id, section_id, division_id, role_id, is_active, ...)
// Add ?debug=1 to receive detailed debug JSON (temporary).

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
    error_log('admin/update.php exception: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    $resp = ['success' => false, 'message' => 'Server error'];
    if ($debug) $resp['debug'] = $e->getMessage();
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
});
set_error_handler(function($errno, $errstr, $errfile, $errline) use ($debug) {
    $msg = "PHP error ({$errno}) {$errstr} in {$errfile} on line {$errline}";
    error_log('admin/update.php error: ' . $msg);
    if ($debug) {
        http_response_code(500);
        echo json_encode(['success'=>false,'message'=>'Server error','debug'=>$msg], JSON_UNESCAPED_UNICODE);
        exit;
    }
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// include DB config (try common relative paths)
$dbCandidates = [
    __DIR__ . '/../../config/db.php',
    __DIR__ . '/../config/db.php',
    __DIR__ . '/../../../config/db.php',
];
$included = false;
foreach ($dbCandidates as $p) {
    if (file_exists($p)) { require_once $p; $included = true; break; }
}
if (!$included || !isset($conn)) {
    $msg = 'DB config not found or $conn not defined. Tried: ' . implode(', ', $dbCandidates);
    error_log('admin/update.php: ' . $msg);
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error','debug'=>$debug ? $msg : null], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Method not allowed']);
    exit;
}

try {
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success'=>false,'message'=>'Not authenticated']);
        exit;
    }
    $actorId = (int)$_SESSION['user_id'];

    // fetch actor role
    if ($conn instanceof mysqli) {
        $st = $conn->prepare("SELECT role_id FROM users WHERE id = ? LIMIT 1");
        if ($st === false) throw new Exception('Prepare failed (actor role): ' . $conn->error);
        $st->bind_param('i', $actorId);
        $st->execute();
        $ar = $st->get_result()->fetch_assoc();
        $st->close();
    } else {
        $stmt = $conn->prepare("SELECT role_id FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$actorId]);
        $ar = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    $actorRole = (int)($ar['role_id'] ?? 0);
    $isAdmin = in_array($actorRole, [1,2], true);

    $targetId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($targetId <= 0) {
        echo json_encode(['success'=>false,'message'=>'Invalid id']);
        exit;
    }
    if (!$isAdmin && $actorId !== $targetId) {
        http_response_code(403);
        echo json_encode(['success'=>false,'message'=>'Forbidden: cannot edit other users']);
        exit;
    }

    // Discover columns in users table
    $availableCols = [];
    if ($conn instanceof mysqli) {
        $colRes = $conn->query("SHOW COLUMNS FROM `users`");
        if ($colRes === false) throw new Exception('SHOW COLUMNS failed: ' . $conn->error);
        while ($c = $colRes->fetch_assoc()) $availableCols[] = $c['Field'];
    } else {
        $stmt = $conn->query("SHOW COLUMNS FROM `users`");
        $availableCols = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }
    // For debug: include available columns
    $debug_info = ['available_columns' => $availableCols];

    // Define candidate fields and types (update these if your DB uses different names)
    $possibleFields = [
        'display_name' => 's',
        'full_name' => 's',
        'emp_id' => 's',
        'username' => 's',
        'email' => 's',
        'phone' => 's',
        'preferred_language' => 's',
        'department_id' => 'i',
        'section_id' => 'i',
        'division_id' => 'i',
        'role_id' => 'i',
        'is_active' => 'i'
    ];

    // Build updatable list based on actual columns
    $updatable = [];
    foreach ($possibleFields as $f => $t) {
        if (in_array($f, $availableCols, true)) $updatable[$f] = $t;
    }

    // Non-admins cannot change role_id/is_active/department/section/division
    if (!$isAdmin) {
        unset($updatable['role_id'], $updatable['is_active'], $updatable['department_id'], $updatable['section_id'], $updatable['division_id']);
    }

    $debug_info['updatable_fields_detected'] = $updatable;
    $debug_info['post_data'] = $_POST;

    // collect provided fields
    $fields = [];
    $types = '';
    $values = [];
    foreach ($updatable as $field => $type) {
        if (isset($_POST[$field])) {
            $val = $_POST[$field];
            if ($type === 'i') $val = ($val === '' ? null : (int)$val);
            else $val = trim((string)$val);
            // allow NULL for empty ints: bind as NULL by using null value and later adjust binding types
            $fields[] = "`{$field}` = ?";
            $types .= $type;
            $values[] = $val;
        }
    }

    if (empty($fields)) {
        // nothing to update -> return debug info if requested
        $resp = ['success'=>false,'message'=>'No updatable fields provided','debug_info'=>$debug_info];
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Validate role_id existence if included and actor is admin
    if ($isAdmin && isset($_POST['role_id']) && in_array('role_id', array_keys($updatable), true)) {
        $newRole = (int)$_POST['role_id'];
        $existsRole = false;
        if ($conn instanceof mysqli) {
            $rstm = $conn->prepare("SELECT id FROM roles WHERE id = ? LIMIT 1");
            if ($rstm) {
                $rstm->bind_param('i', $newRole);
                $rstm->execute();
                $rr = $rstm->get_result()->fetch_assoc();
                $rstm->close();
                if ($rr) $existsRole = true;
            }
        } else {
            $stmt = $conn->prepare("SELECT id FROM roles WHERE id = ? LIMIT 1");
            $stmt->execute([$newRole]);
            if ($stmt->fetch()) $existsRole = true;
        }
        if (!$existsRole) {
            echo json_encode(['success'=>false,'message'=>'Invalid role_id','debug_info'=>$debug_info], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // Build SQL
    $types_for_bind = $types . 'i'; // last param is id
    $values[] = $targetId;
    $sql = "UPDATE `users` SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ? LIMIT 1";

    $debug_info['sql'] = $sql;
    $debug_info['bind_types'] = $types_for_bind;
    $debug_info['bind_values'] = $values;

    // Execute
    if ($conn instanceof mysqli) {
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            $err = 'Prepare failed (update): ' . $conn->error;
            error_log('admin/update.php: ' . $err);
            echo json_encode(['success'=>false,'message'=>'Server error','debug'=>$debug ? $err : null,'debug_info'=>$debug_info], JSON_UNESCAPED_UNICODE);
            exit;
        }
        // bind params - mysqli requires variables. Ensure nulls are bound properly by using bind_param with types; empty strings for 's' are okay.
        // For integer nulls, we bind as NULL by setting value to null (mysqli will send empty string unless using MYSQLI_TYPE_NULL which is complicated).
        // We'll convert null int to 0 if DB not accept nulls; but return debug to inspect.
        $stmt->bind_param($types_for_bind, ...$values);
        $ok = $stmt->execute();
        if ($ok === false) {
            $err = 'Execute failed: ' . $stmt->error;
            error_log('admin/update.php: ' . $err);
            echo json_encode(['success'=>false,'message'=>'Server error','debug'=>$debug ? $err : null,'debug_info'=>$debug_info], JSON_UNESCAPED_UNICODE);
            $stmt->close();
            exit;
        }
        $affected = $stmt->affected_rows;
        $stmt->close();
    } else {
        $stmt = $conn->prepare($sql);
        $ok = $stmt->execute($values);
        $affected = $stmt->rowCount();
    }

    // success
    $resp = ['success'=>true,'affected'=>$affected];
    if ($debug) $resp['debug_info'] = $debug_info;
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $t) {
    error_log('admin/update.php throwable: ' . $t->getMessage());
    http_response_code(500);
    $resp = ['success'=>false,'message'=>'Server error'];
    if ($debug) $resp['debug'] = $t->getMessage();
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}
?>
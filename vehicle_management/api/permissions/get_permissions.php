<?php
// vehicle_management/api/permissions/get_permissions.php
// Returns permission flags for a role pulled from `roles` table.
// Usage:
//  - GET /.../get_permissions.php           -> returns permissions for current session user's role
//  - GET /.../get_permissions.php?role_id=X -> returns permissions for role X (allowed only for Admin/SuperAdmin)
//  - Add &debug=1 to include debug details in error responses (temporary)

// Response: { success: true, role_id: X, role: { id, name_en, name_ar, can_create, can_edit, can_delete, description } }

header('Content-Type: application/json; charset=utf-8');
session_start();

$debug = isset($_GET['debug']) && $_GET['debug'] == '1';

// attempt to include DB config from common locations
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
    $msg = 'DB config not found';
    echo json_encode(['success'=>false,'message'=>'Server error','debug'=>$debug?$msg:null], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!isset($conn)) {
    http_response_code(500);
    $msg = 'Database connection ($conn) not defined';
    echo json_encode(['success'=>false,'message'=>'Server error','debug'=>$debug?$msg:null], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // determine role_id to inspect
    $requestedRoleId = null;
    if (isset($_GET['role_id']) && is_numeric($_GET['role_id'])) {
        $requestedRoleId = (int)$_GET['role_id'];
    }

    // determine current user's role (if authenticated)
    $currentRoleId = null;
    $currentUserId = null;
    if (!empty($_SESSION['user_id'])) {
        $currentUserId = (int)$_SESSION['user_id'];
        if ($conn instanceof mysqli) {
            $st = $conn->prepare("SELECT role_id FROM users WHERE id = ? LIMIT 1");
            if ($st) {
                $st->bind_param('i', $currentUserId);
                $st->execute();
                $r = $st->get_result()->fetch_assoc();
                $st->close();
                $currentRoleId = isset($r['role_id']) ? (int)$r['role_id'] : null;
            }
        } else {
            $stmt = $conn->prepare("SELECT role_id FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$currentUserId]);
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            $currentRoleId = isset($r['role_id']) ? (int)$r['role_id'] : null;
        }
    }

    // If role_id requested explicitly, require that the caller is authenticated and admin/superadmin
    if ($requestedRoleId !== null) {
        if ($currentRoleId === null) {
            http_response_code(401);
            echo json_encode(['success'=>false,'message'=>'Authentication required to view role by id.']);
            exit;
        }
        if (!in_array($currentRoleId, [1,2], true)) {
            http_response_code(403);
            echo json_encode(['success'=>false,'message'=>'Forbidden: only Admin/SuperAdmin can view arbitrary role permissions.']);
            exit;
        }
        $roleIdToUse = $requestedRoleId;
    } else {
        // no explicit role_id -> must be authenticated to get own permissions
        if ($currentRoleId === null) {
            http_response_code(401);
            echo json_encode(['success'=>false,'message'=>'Not authenticated']);
            exit;
        }
        $roleIdToUse = $currentRoleId;
    }

    // read role row from roles table
    // look for standard columns and fallbacks
    if ($conn instanceof mysqli) {
        // we select common fields if present; use a defensive SELECT listing known columns
        $resCols = $conn->query("SHOW COLUMNS FROM `roles`");
        if ($resCols === false) throw new Exception('SHOW COLUMNS failed: ' . $conn->error);
        $cols = [];
        while ($c = $resCols->fetch_assoc()) $cols[] = $c['Field'];
        // prepare field list that exists
        $sel = [];
        $map = ['id'=>'id','name_en'=>'name_en','name_ar'=>'name_ar','can_create'=>'can_create','can_edit'=>'can_edit','can_delete'=>'can_delete','description'=>'description'];
        foreach ($map as $field => $alias) {
            if (in_array($field, $cols, true)) $sel[] = "`{$field}`";
        }
        if (empty($sel)) throw new Exception('No selectable columns found in roles table');
        $sql = 'SELECT ' . implode(', ', $sel) . ' FROM `roles` WHERE `id` = ? LIMIT 1';
        $st = $conn->prepare($sql);
        if ($st === false) throw new Exception('Prepare failed: ' . $conn->error);
        $st->bind_param('i', $roleIdToUse);
        $st->execute();
        $roleRow = $st->get_result()->fetch_assoc();
        $st->close();
    } else {
        // PDO path: attempt to select standard fields (may fail if columns missing -> PDO will throw)
        $stmt = $conn->prepare("SELECT id, name_en, name_ar, can_create, can_edit, can_delete, description FROM roles WHERE id = ? LIMIT 1");
        $stmt->execute([$roleIdToUse]);
        $roleRow = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$roleRow) {
        http_response_code(404);
        echo json_encode(['success'=>false,'message'=>'Role not found']);
        exit;
    }

    // normalize boolean flags (if columns missing, default conservative values)
    $roleOut = [];
    $roleOut['id'] = isset($roleRow['id']) ? (int)$roleRow['id'] : $roleIdToUse;
    $roleOut['name_en'] = $roleRow['name_en'] ?? null;
    $roleOut['name_ar'] = $roleRow['name_ar'] ?? null;
    $roleOut['description'] = $roleRow['description'] ?? null;
    $roleOut['can_create'] = isset($roleRow['can_create']) ? (bool)$roleRow['can_create'] : false;
    $roleOut['can_edit'] = isset($roleRow['can_edit']) ? (bool)$roleRow['can_edit'] : false;
    $roleOut['can_delete'] = isset($roleRow['can_delete']) ? (bool)$roleRow['can_delete'] : false;

    echo json_encode(['success'=>true, 'role_id'=>$roleOut['id'], 'role'=>$roleOut], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    error_log('get_permissions.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error','debug'=>$debug ? $e->getMessage() : null], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
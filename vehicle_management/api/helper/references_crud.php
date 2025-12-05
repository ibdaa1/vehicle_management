<?php
// vehicle_management/api/helper/references_crud.php
// Simple, secure CRUD for Departments/Sections/Divisions
// Methods:
// - GET  -> delegates to get_references.php (read-only, listing)
// - POST -> create new record      (requires admin role)
// - PUT  -> update existing record (requires admin role)  (use POST with _method=PUT if client cannot send PUT)
// - DELETE -> delete record        (requires admin role)  (use POST with _method=DELETE)
// Params:
// - type=departments|sections|divisions
// - id (for update/delete)
// - name_ar, name_en, parent_id (sections -> department_id, divisions -> section_id)
// Security: requires session user and role check (role_id 1/2 are admins). Use same-origin credentials.

header('Content-Type: application/json; charset=utf-8');
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // CORS preflight if needed
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    exit;
}

// include DB (adjust path if needed)
$dbPaths = [
    __DIR__ . '/../../config/db.php',
    __DIR__ . '/../config/db.php',
    __DIR__ . '/../../../config/db.php'
];
$included=false;
foreach($dbPaths as $p){ if(file_exists($p)){ require_once $p; $included=true; break; } }
if (!$included || !isset($conn)) {
    echo json_encode(['success'=>false,'message'=>'DB config missing']); exit;
}

// Basic auth: ensure logged in
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Not authenticated']); exit;
}
// fetch actor role (simple; adapt if you store role in session)
$actorRole = 0;
if (isset($_SESSION['role_id'])) {
    $actorRole = (int)$_SESSION['role_id'];
} else {
    // fetch from DB as fallback
    if ($conn instanceof mysqli) {
        $st = $conn->prepare("SELECT role_id FROM users WHERE id = ? LIMIT 1");
        if ($st) {
            $st->bind_param('i', $_SESSION['user_id']);
            $st->execute();
            $r = $st->get_result()->fetch_assoc();
            $actorRole = (int)($r['role_id'] ?? 0);
            $st->close();
        }
    } else {
        $st = $conn->prepare("SELECT role_id FROM users WHERE id = ? LIMIT 1");
        $st->execute([$_SESSION['user_id']]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        $actorRole = (int)($r['role_id'] ?? 0);
    }
}
$isAdmin = in_array($actorRole, [1,2], true);

// allowed logical types map -> table names (reuse same candidate logic as get_references if needed)
$candidates_map = [
    'departments' => ['Departments','departments','tbl_departments','ref_departments','department'],
    'sections'    => ['Sections','sections','tbl_sections','ref_sections','section'],
    'divisions'   => ['Divisions','divisions','tbl_divisions','ref_divisions','division']
];

// helper: find first existing table name from candidates (case-sensitive as in DB)
function find_table_name_simple($conn, $candidates) {
    foreach ($candidates as $cand) {
        $res = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($cand) . "'");
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_row();
            return $row[0];
        }
    }
    return null;
}

// detect pk & name fields similar to get_references (but simpler)
function detect_columns($conn, $table) {
    $cols = [];
    $res = $conn->query("SHOW COLUMNS FROM `{$table}`");
    if (!$res) return null;
    while ($c = $res->fetch_assoc()) $cols[] = $c['Field'];
    $pk = null; $name = null;
    foreach ($cols as $c) if (strtolower($c) === 'id' || preg_match('/_id$/i', $c)) { $pk = $c; break; }
    foreach ($cols as $c) if (in_array(strtolower($c), ['name_ar','name_en','name','title','label'])) { $name = $c; break; }
    if (!$name) {
        // fallback: first varchar/text column
        foreach ($cols as $c) {
            if (preg_match('/(varchar|text|char)/i', $c)) { $name = $c; break; }
        }
    }
    return ['pk'=>$pk, 'name'=>$name, 'cols'=>$cols];
}

// parse request
$method = $_SERVER['REQUEST_METHOD'];
// allow _method override from POST (for clients that can only send POST)
if ($method === 'POST' && !empty($_POST['_method'])) {
    $m = strtoupper($_POST['_method']);
    if (in_array($m, ['PUT','DELETE'])) $method = $m;
}

// get type param
$type = isset($_REQUEST['type']) ? strtolower(trim($_REQUEST['type'])) : '';
if (!in_array($type, ['departments','sections','divisions'])) {
    if ($method === 'GET') {
        // fallback: delegate to helper index (read-only) - reuse get_references.php logic
        include __DIR__ . '/get_references.php';
        exit;
    }
    echo json_encode(['success'=>false,'message'=>'Invalid type']); exit;
}

// find table
$table = find_table_name_simple($conn, $candidates_map[$type]);
if (!$table) {
    echo json_encode(['success'=>false,'message'=>"Reference table for {$type} not found"]); exit;
}

$meta = detect_columns($conn, $table);
if (!$meta || !$meta['pk'] || !$meta['name']) {
    echo json_encode(['success'=>false,'message'=>"Cannot detect id/name columns for table {$table}"]); exit;
}
$pk = $meta['pk'];
$nameField = $meta['name'];

// helpers for parent column
$parentCol = null;
if ($type === 'sections') $parentCol = 'department_id';
if ($type === 'divisions') $parentCol = 'section_id';

// ---------- READ ----------
if ($method === 'GET') {
    // optional parent filter
    $parent_id = isset($_GET['parent_id']) && $_GET['parent_id'] !== '' ? (int)$_GET['parent_id'] : null;
    $sql = "SELECT `{$pk}` AS id, `{$nameField}` AS name";
    if ($parentCol && in_array($parentCol, $meta['cols'])) $sql .= ", `{$parentCol}` AS parent_id";
    $sql .= " FROM `{$table}`";
    if ($parent_id !== null && $parentCol && in_array($parentCol, $meta['cols'])) {
        $sql .= " WHERE `{$parentCol}` = " . intval($parent_id);
    }
    $sql .= " ORDER BY `{$nameField}` ASC";
    $res = $conn->query($sql);
    $items = [];
    while ($r = $res->fetch_assoc()) $items[] = $r;
    echo json_encode(['success'=>true, 'items'=>$items], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------- require admin for create/update/delete ----------
if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Forbidden: admin only']); exit;
}

// collect input (for POST/PUT)
$input = [];
// for PUT/DELETE (when not sent via POST override) try to parse JSON body
if (in_array($method, ['PUT','DELETE'])) {
    $raw = file_get_contents('php://input');
    $parsed = json_decode($raw, true);
    if (is_array($parsed)) $input = $parsed;
} else {
    $input = $_POST;
}

// sanitize name fields
$name_ar = isset($input['name_ar']) ? trim($input['name_ar']) : null;
$name_en = isset($input['name_en']) ? trim($input['name_en']) : null;
$parent_val = ($parentCol && isset($input[$parentCol])) ? (int)$input[$parentCol] : (isset($input['parent_id']) ? (int)$input['parent_id'] : null);

// ---------- CREATE ----------
if ($method === 'POST') {
    if (!$name_ar && !$name_en) { echo json_encode(['success'=>false,'message'=>'name_ar or name_en required']); exit; }
    // validate parent exists if provided
    if ($parent_val !== null && $parentCol) {
        $res = $conn->query("SELECT 1 FROM `". ($parentCol === 'department_id' ? 'Departments' : 'Sections') . "` WHERE ". ($parentCol === 'department_id' ? 'department_id' : 'section_id') ." = ". intval($parent_val) ." LIMIT 1");
        if (!$res || $res->num_rows === 0) {
            echo json_encode(['success'=>false,'message'=>'Invalid parent_id']); exit;
        }
    }
    // build insert
    $fields = [];
    $vals = [];
    $types = '';
    $params = [];
    if (in_array('name_ar', $meta['cols'])) { $fields[] = '`name_ar`'; $vals[] = '?'; $types .= 's'; $params[] = $name_ar; }
    if (in_array('name_en', $meta['cols'])) { $fields[] = '`name_en`'; $vals[] = '?'; $types .= 's'; $params[] = $name_en; }
    if ($parentCol && in_array($parentCol, $meta['cols']) && $parent_val !== null) { $fields[] = "`{$parentCol}`"; $vals[] = '?'; $types .= 'i'; $params[] = $parent_val; }
    $sql = "INSERT INTO `{$table}` (" . implode(',', $fields) . ") VALUES (" . implode(',', $vals) . ")";
    $stmt = $conn->prepare($sql);
    if (!$stmt) { echo json_encode(['success'=>false,'message'=>'Prepare failed']); exit; }
    $stmt->bind_param($types, ...$params);
    $ok = $stmt->execute();
    if (!$ok) { echo json_encode(['success'=>false,'message'=>'Execute failed','debug'=>$stmt->error]); $stmt->close(); exit; }
    $newId = $stmt->insert_id;
    $stmt->close();
    echo json_encode(['success'=>true,'id'=>$newId]); exit;
}

// ---------- UPDATE ----------
if ($method === 'PUT') {
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    if ($id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid id']); exit; }
    $parts = []; $types=''; $params=[];
    if ($name_ar !== null && in_array('name_ar', $meta['cols'])) { $parts[] = "`name_ar` = ?"; $types .='s'; $params[] = $name_ar; }
    if ($name_en !== null && in_array('name_en', $meta['cols'])) { $parts[] = "`name_en` = ?"; $types .='s'; $params[] = $name_en; }
    if ($parentCol && $parent_val !== null && in_array($parentCol, $meta['cols'])) { $parts[] = "`{$parentCol}` = ?"; $types .='i'; $params[] = $parent_val; }
    if (empty($parts)) { echo json_encode(['success'=>false,'message'=>'Nothing to update']); exit; }
    $types .= 'i'; $params[] = $id;
    $sql = "UPDATE `{$table}` SET " . implode(', ', $parts) . " WHERE `{$pk}` = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) { echo json_encode(['success'=>false,'message'=>'Prepare failed']); exit; }
    $stmt->bind_param($types, ...$params);
    $ok = $stmt->execute();
    if (!$ok) { echo json_encode(['success'=>false,'message'=>'Execute failed','debug'=>$stmt->error]); $stmt->close(); exit; }
    $affected = $stmt->affected_rows; $stmt->close();
    echo json_encode(['success'=>true,'affected'=>$affected]); exit;
}

// ---------- DELETE ----------
if ($method === 'DELETE') {
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    if ($id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid id']); exit; }
    // (Optional) Prevent deleting parents with children — check references
    if ($type === 'departments') {
        $check = $conn->query("SELECT 1 FROM Sections WHERE department_id = ".intval($id)." LIMIT 1");
        if ($check && $check->num_rows > 0) {
            echo json_encode(['success'=>false,'message'=>'Cannot delete department with sections']); exit;
        }
    }
    if ($type === 'sections') {
        $check = $conn->query("SELECT 1 FROM Divisions WHERE section_id = ".intval($id)." LIMIT 1");
        if ($check && $check->num_rows > 0) {
            echo json_encode(['success'=>false,'message'=>'Cannot delete section with divisions']); exit;
        }
    }
    $stmt = $conn->prepare("DELETE FROM `{$table}` WHERE `{$pk}` = ? LIMIT 1");
    if (!$stmt) { echo json_encode(['success'=>false,'message'=>'Prepare failed']); exit; }
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    if (!$ok) { echo json_encode(['success'=>false,'message'=>'Execute failed','debug'=>$stmt->error]); $stmt->close(); exit; }
    $affected = $stmt->affected_rows; $stmt->close();
    echo json_encode(['success'=>true,'affected'=>$affected]); exit;
}

echo json_encode(['success'=>false,'message'=>'Unsupported method']);
exit;
?>
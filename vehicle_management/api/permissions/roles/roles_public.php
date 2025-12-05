<?php
// vehicle_management/api/permissions/roles/roles_public.php
// Public roles endpoint: returns roles allowed for self-registration (if column exists).
// Robust: detects table name and id/name columns dynamically, avoids fatal errors.

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

// include DB config - try several likely paths
$paths = [
    __DIR__ . '/../../../config/db.php',
    __DIR__ . '/../../config/db.php',
    __DIR__ . '/config/db.php'
];
$included = false;
foreach ($paths as $p) {
    if (file_exists($p)) {
        require_once $p;
        $included = true;
        break;
    }
}
if (!$included || !isset($conn) || !$conn) {
    echo json_encode(['success' => false, 'message' => 'DB config missing or connection failed']);
    exit;
}

// helper: try candidate table names for roles
$candidates = ['roles','Roles','permissions_roles','permissions_roles','user_roles','roles_tbl','tbl_roles','role'];

function find_table($conn, $cands) {
    foreach ($cands as $c) {
        $safe = $conn->real_escape_string($c);
        $res = $conn->query("SHOW TABLES LIKE '{$safe}'");
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_row();
            return $row[0];
        }
    }
    return null;
}

$table = find_table($conn, $candidates);
if (!$table) {
    echo json_encode(['success'=>false,'message'=>'Roles table not found on server']);
    exit;
}

// get columns
$cols = [];
$resCols = $conn->query("SHOW COLUMNS FROM `{$table}`");
if ($resCols) {
    while ($r = $resCols->fetch_assoc()) $cols[] = $r;
} else {
    echo json_encode(['success'=>false,'message'=>'Unable to inspect roles table columns']);
    exit;
}

// find id column
$colNames = array_column($cols, 'Field');
$pk = null;
foreach ($colNames as $c) {
    if (strtolower($c) === 'id') { $pk = $c; break; }
}
if (!$pk) {
    foreach ($colNames as $c) {
        if (preg_match('/_id$/i', $c)) { $pk = $c; break; }
    }
}
if (!$pk) {
    // fallback to first column
    $pk = $colNames[0] ?? null;
}
if (!$pk) {
    echo json_encode(['success'=>false,'message'=>'No id column found in roles table']);
    exit;
}

// find name fields
$name_ar_col = null; $name_en_col = null; $name_col = null;
foreach ($colNames as $c) {
    $lc = strtolower($c);
    if (!$name_ar_col && ($lc === 'name_ar' || $lc === 'arabic_name' || $lc === 'name_arabic')) $name_ar_col = $c;
    if (!$name_en_col && ($lc === 'name_en' || $lc === 'english_name' || $lc === 'name_english')) $name_en_col = $c;
}
foreach ($colNames as $c) {
    $lc = strtolower($c);
    if (!$name_col && in_array($lc, ['name','title','label'])) { $name_col = $c; break; }
}
// fallback: any varchar/text column other than id
if (!$name_col && !$name_ar_col && !$name_en_col) {
    foreach ($cols as $colInfo) {
        $f = $colInfo['Field'];
        $type = strtolower($colInfo['Type']);
        if ($f === $pk) continue;
        if (strpos($type, 'varchar') !== false || strpos($type, 'text') !== false || strpos($type, 'char') !== false) {
            $name_col = $f;
            break;
        }
    }
}

// detect allow_registration column
$allow_registration_col = null;
foreach ($colNames as $c) {
    if (in_array(strtolower($c), ['allow_registration','allow_reg','self_register','self_registration'])) {
        $allow_registration_col = $c; break;
    }
}

// build select list (safe)
$selectParts = [];
$selectParts[] = "`{$pk}` AS id";
if ($name_ar_col) $selectParts[] = "`{$name_ar_col}` AS name_ar";
if ($name_en_col) $selectParts[] = "`{$name_en_col}` AS name_en";
if ($name_col) $selectParts[] = "`{$name_col}` AS name";
if (in_array('description', $colNames)) $selectParts[] = "`description`";
$selectSQL = implode(',', $selectParts);

// prepare WHERE if allow_registration exists
$whereSQL = '';
if ($allow_registration_col) {
    $whereSQL = "WHERE COALESCE(`{$allow_registration_col}`,0) = 1";
}

// final query
$sql = "SELECT {$selectSQL} FROM `{$table}` {$whereSQL} ORDER BY ";
// choose order column
if ($name_ar_col) $sql .= "`{$name_ar_col}` ASC";
elseif ($name_col) $sql .= "`{$name_col}` ASC";
elseif ($name_en_col) $sql .= "`{$name_en_col}` ASC";
else $sql .= "id ASC";

try {
    $qres = $conn->query($sql);
    if (!$qres) {
        // if query failed (e.g., unknown column names), try a simpler SELECT *
        $qres = $conn->query("SELECT * FROM `{$table}` LIMIT 1000");
        if (!$qres) {
            echo json_encode(['success'=>false,'message'=>'Query failed','debug'=>$conn->error]);
            exit;
        }
    }
    $rows = [];
    while ($r = $qres->fetch_assoc()) {
        // normalize to id, name_ar, name_en, name, description (if present)
        $row = ['id' => $r['id'] ?? ($r[$pk] ?? null)];
        if (isset($r['name_ar'])) $row['name_ar'] = $r['name_ar'];
        if (isset($r['name_en'])) $row['name_en'] = $r['name_en'];
        if (isset($r['name'])) $row['name'] = $r['name'];
        if (isset($r['description'])) $row['description'] = $r['description'];
        // if nothing for name fields, attempt to pick any text-like column
        if (empty($row['name_ar']) && empty($row['name_en']) && empty($row['name'])) {
            foreach ($r as $k=>$v) {
                if ($k === 'id' || $k === $pk) continue;
                if (is_string($v) && strlen($v) > 0) { $row['name'] = $v; break; }
            }
        }
        $rows[] = $row;
    }

    $resp = ['success' => true, 'roles' => $rows];
    if (!$allow_registration_col) {
        $resp['warning'] = 'allow_registration column not found; returning all roles. Consider adding allow_registration column to restrict self-registration roles.';
    }
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Server error','debug'=>$e->getMessage()]);
    exit;
}
?>
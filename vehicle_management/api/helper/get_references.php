<?php
// vehicle_management/api/helper/get_references.php
// Returns references: departments, sections, divisions.
// Supports: ?lang=ar|en  and optional ?type=departments|sections|divisions  and ?parent_id=NN
header('Content-Type: application/json; charset=utf-8');
session_start();

// include DB config (adjust path)
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

// optional auth: allow only logged-in admins or any authenticated user depending on policy
$lang = (isset($_GET['lang']) && $_GET['lang']==='en') ? 'en' : 'ar';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$parent_id = isset($_GET['parent_id']) ? (int)$_GET['parent_id'] : null;

$mapName = function($row) use ($lang) {
    $name = $row['name_ar'] ?? $row['name_en'] ?? $row['name'];
    if ($lang === 'en') {
        return $row['name_en'] ?? $row['name_ar'] ?? $row['name'];
    }
    return $row['name_ar'] ?? $row['name_en'] ?? $row['name'];
};

$out = ['success'=>true];

try {
    // helper to fetch table rows with id + localized name + parent id if present
    $fetch = function($table, $idCol, $nameCols = ['name_ar','name_en'], $parentCol = null, $parentFilter = null) use ($conn, $mapName) {
        $rows = [];
        // Build query
        $q = "SELECT * FROM `{$table}`";
        if ($parentFilter !== null) {
            $q .= " WHERE `{$parentFilter['col']}` = " . intval($parentFilter['val']);
        }
        $q .= " ORDER BY " . ($nameCols[0] ?? $idCol) . " ASC";
        $res = $conn->query($q);
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $rows[] = $r;
            }
        }
        return $rows;
    };

    // Decide what to return
    if ($type === 'departments' || $type === '') {
        if ($type === 'departments') {
            $rows = [];
            $res = $conn->query("SELECT department_id AS id, name_ar, name_en FROM Departments ORDER BY name_ar ASC");
            while ($r = $res->fetch_assoc()) {
                $r['name'] = ($lang === 'en') ? ($r['name_en'] ?: $r['name_ar']) : ($r['name_ar'] ?: $r['name_en']);
                $rows[] = $r;
            }
            $out['departments'] = $rows;
            if ($type === 'departments') { echo json_encode($out, JSON_UNESCAPED_UNICODE); exit; }
        } else {
            $res = $conn->query("SELECT department_id AS id, name_ar, name_en FROM Departments ORDER BY name_ar ASC");
            $rows = [];
            while ($r = $res->fetch_assoc()) {
                $r['name'] = ($lang === 'en') ? ($r['name_en'] ?: $r['name_ar']) : ($r['name_ar'] ?: $r['name_en']);
                $rows[] = $r;
            }
            $out['departments'] = $rows;
        }
    }

    if ($type === 'sections' || $type === '') {
        $rows = [];
        $sql = "SELECT section_id AS id, department_id, name_ar, name_en FROM Sections";
        if ($parent_id) $sql .= " WHERE department_id = " . intval($parent_id);
        $sql .= " ORDER BY name_ar ASC";
        $res = $conn->query($sql);
        while ($r = $res->fetch_assoc()) {
            $r['name'] = ($lang === 'en') ? ($r['name_en'] ?: $r['name_ar']) : ($r['name_ar'] ?: $r['name_en']);
            $rows[] = $r;
        }
        $out['sections'] = $rows;
        if ($type === 'sections') { echo json_encode($out, JSON_UNESCAPED_UNICODE); exit; }
    }

    if ($type === 'divisions' || $type === '') {
        $rows = [];
        $sql = "SELECT division_id AS id, section_id, name_ar, name_en FROM Divisions";
        if ($parent_id) $sql .= " WHERE section_id = " . intval($parent_id);
        $sql .= " ORDER BY name_ar ASC";
        $res = $conn->query($sql);
        while ($r = $res->fetch_assoc()) {
            $r['name'] = ($lang === 'en') ? ($r['name_en'] ?: $r['name_ar']) : ($r['name_ar'] ?: $r['name_en']);
            $rows[] = $r;
        }
        $out['divisions'] = $rows;
        if ($type === 'divisions') { echo json_encode($out, JSON_UNESCAPED_UNICODE); exit; }
    }

    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;
} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Server error','debug'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}
?>

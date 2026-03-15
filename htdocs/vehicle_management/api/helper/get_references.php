<?php
/**
 * vehicle_management/api/helper/get_references.php
 * Returns references: sectors, departments, sections, divisions
 * Supports:
 *  ?lang=ar|en
 *  ?type=sectors|departments|sections|divisions
 *  ?parent_id=NN
 */

/* =========================
   1. Safe output buffering
   ========================= */
ob_start();

/* =========================
   2. Force UTF-8 JSON header
   ========================= */
header('Content-Type: application/json; charset=UTF-8');

/* =========================
   3. Start session safely
   ========================= */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================
   4. Include DB config
   ========================= */
$dbPaths = [
    __DIR__ . '/../../config/db.php',
    __DIR__ . '/../config/db.php',
    __DIR__ . '/../../../config/db.php'
];

$included = false;
foreach ($dbPaths as $p) {
    if (file_exists($p)) {
        require_once $p;
        $included = true;
        break;
    }
}

if (!$included || !isset($conn)) {
    echo json_encode([
        'success' => false,
        'message' => 'DB config missing'
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit;
}

/* =========================
   5. Force MySQL UTF-8
   ========================= */
mysqli_set_charset($conn, 'utf8mb4');

/* =========================
   6. Inputs
   ========================= */
$lang = (isset($_GET['lang']) && $_GET['lang'] === 'en') ? 'en' : 'ar';
$type = $_GET['type'] ?? '';
$parent_id = isset($_GET['parent_id']) ? (int)$_GET['parent_id'] : null;

$out = ['success' => true];

try {

    /* =========================
       7. Sectors
       ========================= */
    if ($type === 'sectors' || $type === '') {

        $rows = [];
        $sql = "SELECT id, sector_code, name, name_en FROM sectors WHERE is_active = 1 ORDER BY id ASC";
        $res = $conn->query($sql);

        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $rows[] = [
                    'id'          => (int)$r['id'],
                    'sector_code' => $r['sector_code'],
                    'name'        => ($lang === 'en')
                        ? ($r['name_en'] ?: $r['name'])
                        : ($r['name'] ?: $r['name_en'])
                ];
            }
        }

        $out['sectors'] = $rows;

        if ($type === 'sectors') {
            echo json_encode($out, JSON_UNESCAPED_UNICODE);
            ob_end_flush();
            exit;
        }
    }

    /* =========================
       8. Departments
       ========================= */
    if ($type === 'departments' || $type === '') {

        $rows = [];
        $sql = "SELECT department_id AS id, name_ar, name_en FROM Departments ORDER BY id ASC";
        $res = $conn->query($sql);

        while ($r = $res->fetch_assoc()) {
            $rows[] = [
                'id'   => (int)$r['id'],
                'name' => ($lang === 'en')
                    ? ($r['name_en'] ?: $r['name_ar'])
                    : ($r['name_ar'] ?: $r['name_en'])
            ];
        }

        $out['departments'] = $rows;

        if ($type === 'departments') {
            echo json_encode($out, JSON_UNESCAPED_UNICODE);
            ob_end_flush();
            exit;
        }
    }

    /* =========================
       9. Sections
       ========================= */
    if ($type === 'sections' || $type === '') {

        $rows = [];
        $sql = "SELECT section_id AS id, department_id, name_ar, name_en FROM Sections";
        if ($parent_id) {
            $sql .= " WHERE department_id = " . intval($parent_id);
        }
        $sql .= " ORDER BY id ASC";

        $res = $conn->query($sql);

        while ($r = $res->fetch_assoc()) {
            $rows[] = [
                'id'            => (int)$r['id'],
                'department_id' => (int)$r['department_id'],
                'name'          => ($lang === 'en')
                    ? ($r['name_en'] ?: $r['name_ar'])
                    : ($r['name_ar'] ?: $r['name_en'])
            ];
        }

        $out['sections'] = $rows;

        if ($type === 'sections') {
            echo json_encode($out, JSON_UNESCAPED_UNICODE);
            ob_end_flush();
            exit;
        }
    }

    /* =========================
       10. Divisions
       ========================= */
    if ($type === 'divisions' || $type === '') {

        $rows = [];
        $sql = "SELECT division_id AS id, section_id, name_ar, name_en FROM Divisions";
        if ($parent_id) {
            $sql .= " WHERE section_id = " . intval($parent_id);
        }
        $sql .= " ORDER BY id ASC";

        $res = $conn->query($sql);

        while ($r = $res->fetch_assoc()) {
            $rows[] = [
                'id'         => (int)$r['id'],
                'section_id' => (int)$r['section_id'],
                'name'       => ($lang === 'en')
                    ? ($r['name_en'] ?: $r['name_ar'])
                    : ($r['name_ar'] ?: $r['name_en'])
            ];
        }

        $out['divisions'] = $rows;

        if ($type === 'divisions') {
            echo json_encode($out, JSON_UNESCAPED_UNICODE);
            ob_end_flush();
            exit;
        }
    }

    /* =========================
       11. Final output
       ========================= */
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit;

} catch (Throwable $e) {

    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ], JSON_UNESCAPED_UNICODE);

    ob_end_flush();
    exit;
}

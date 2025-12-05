<?php
// vehicle_management/api/vehicle/add_Vehicles.php
// Final fixed add/update vehicle endpoint with robust dynamic binding and clearer error handling.

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

// CORS (adjust origin in production)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Auth-Token');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// --- includes DB + optional session config ---
$paths = [ __DIR__ . '/../../config/db.php', __DIR__ . '/../config/db.php', __DIR__ . '/config/db.php' ];
foreach ($paths as $p) { if (file_exists($p)) { require_once $p; break; } }
if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server misconfiguration: DB connection missing']);
    exit;
}
$scPaths = [ __DIR__ . '/../../config/session.php', __DIR__ . '/../config/session.php', __DIR__ . '/config/session.php' ];
foreach ($scPaths as $p) { if (file_exists($p)) { require_once $p; break; } }

// --- helpers ---
function get_all_headers_normalized(): array {
    $h = [];
    if (function_exists('getallheaders')) {
        foreach (getallheaders() as $k => $v) $h[strtolower($k)] = $v;
    } else {
        foreach ($_SERVER as $k => $v) {
            if (strpos($k, 'HTTP_') === 0) {
                $name = strtolower(str_replace(' ', '-', str_replace('_', ' ', substr($k,5))));
                $h[$name] = $v;
            }
        }
    }
    return $h;
}
function get_bearer_token(): ?string {
    $h = get_all_headers_normalized();
    if (!empty($h['authorization']) && preg_match('/Bearer\s+(.+)/i', $h['authorization'], $m)) return trim($m[1]);
    if (!empty($h['x-auth-token'])) return trim($h['x-auth-token']);
    if (!empty($_SERVER['HTTP_X_SESSION_TOKEN'])) return trim($_SERVER['HTTP_X_SESSION_TOKEN']);
    return null;
}
function input_get($k, &$json = null) {
    if ($json === null) { $raw = file_get_contents('php://input'); $decoded = json_decode($raw, true); $json = is_array($decoded) ? $decoded : []; }
    if (isset($_POST[$k])) return $_POST[$k];
    if (isset($_REQUEST[$k])) return $_REQUEST[$k];
    if (is_array($json) && array_key_exists($k, $json)) return $json[$k];
    return null;
}
// determine param type for bind_param
function param_type($v): string {
    if (is_int($v)) return 'i';
    if (is_float($v)) return 'd';
    return 's';
}
// helper to bind params dynamically (works with PHP 7+)
function bind_params_dynamic(mysqli_stmt $stmt, string $types, array &$params): bool {
    // mysqli_stmt::bind_param requires references
    $bind_names = [];
    $bind_names[] = $types;
    for ($i = 0; $i < count($params); $i++) {
        // ensure each param is a variable (not expression)
        $bind_names[] = & $params[$i];
    }
    return call_user_func_array([$stmt, 'bind_param'], $bind_names);
}

// --- authenticate (token or session) ---
$user = null;
$token = get_bearer_token();
if ($token) {
    $stmt = $conn->prepare("
        SELECT u.id, u.emp_id, u.username, u.email, u.phone, u.role_id, u.preferred_language
        FROM user_sessions s
        JOIN users u ON u.id = s.user_id
        WHERE s.token = ? AND s.revoked = 0 AND s.expires_at > NOW()
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        if ($row) {
            $user = [
                'id' => (int)$row['id'],
                'emp_id' => $row['emp_id'],
                'username' => $row['username'],
                'email' => $row['email'],
                'phone' => $row['phone'],
                'role_id' => (int)$row['role_id'],
                'preferred_language' => $row['preferred_language'] ?? 'ar'
            ];
        }
    }
}
if (!$user) {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $sessUser = $_SESSION['user'] ?? null;
    if ($sessUser && !empty($sessUser['id'])) $user = $sessUser;
}
if (!$user || empty($user['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// permission helper
function has_perm(array $user, string $perm): bool {
    $role = intval($user['role_id'] ?? 0);
    if ($perm === 'vehicles_add' && in_array($role, [1,2])) return true;
    if ($perm === 'vehicles_edit' && in_array($role, [1,2])) return true;
    return false;
}

// parse input
$raw = file_get_contents('php://input');
$json = null;
if ($raw) { $tmp = json_decode($raw, true); if (is_array($tmp)) $json = $tmp; }
$vehicle_id = input_get('id', $json);
$vehicle_code = trim((string)(input_get('vehicle_code', $json) ?? input_get('plate_number', $json) ?? ''));
$type = input_get('type', $json) ?? '';
$manufacture_year = input_get('manufacture_year', $json) ?? input_get('year', $json) ?? null;
$emp_id_field = input_get('emp_id', $json) ?? input_get('assigned_user_id', $json) ?? null;
$driver_name = input_get('driver_name', $json) ?? null;
$driver_phone = input_get('driver_phone', $json) ?? null;
$status = input_get('status', $json) ?? 'operational';
$department_id = input_get('department_id', $json) ?? null;
$section_id = input_get('section_id', $json) ?? null;
$division_id = input_get('division_id', $json) ?? null;
$vehicle_mode = input_get('vehicle_mode', $json) ?? 'shift';
$notes = input_get('notes', $json) ?? null;

if ($vehicle_code === '') {
    echo json_encode(['success' => false, 'message' => 'vehicle_code required']);
    exit;
}

$isUpdate = !empty($vehicle_id);
if ($isUpdate) {
    if (!has_perm($user, 'vehicles_edit')) {
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }
} else {
    if (!has_perm($user, 'vehicles_add')) {
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }
}

// convert numeric fields
$manufacture_year_i = is_numeric($manufacture_year) ? intval($manufacture_year) : null;
$department_i = is_numeric($department_id) ? intval($department_id) : ( $department_id === null ? null : $department_id );
$section_i = is_numeric($section_id) ? intval($section_id) : ( $section_id === null ? null : $section_id );
$division_i = is_numeric($division_id) ? intval($division_id) : ( $division_id === null ? null : $division_id );
$emp_to_store = $emp_id_field ?: ($user['emp_id'] ?? null);

try {
    if ($isUpdate) {
        $fields = [
            'vehicle_code' => $vehicle_code,
            'type' => $type ?: null,
            'manufacture_year' => $manufacture_year_i,
            'emp_id' => $emp_to_store,
            'driver_name' => $driver_name,
            'driver_phone' => $driver_phone,
            'status' => $status,
            'department_id' => $department_i,
            'section_id' => $section_i,
            'division_id' => $division_i,
            'vehicle_mode' => $vehicle_mode,
            'notes' => $notes,
            'updated_by' => intval($user['id'])
        ];
        $setParts = []; $params = []; $types = '';
        foreach ($fields as $col => $val) {
            $setParts[] = "`$col` = ?";
            $params[] = $val;
            $types .= param_type($val);
        }
        // id param
        $params[] = intval($vehicle_id);
        $types .= 'i';
        $sql = "UPDATE vehicles SET " . implode(', ', $setParts) . ", updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);

        if (!bind_params_dynamic($stmt, $types, $params)) throw new Exception('bind_param failed');

        if (!$stmt->execute()) {
            throw new Exception('Execute failed: ' . $stmt->error);
        }
        $affected = $stmt->affected_rows;
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'تم التحديث', 'vehicle_id' => intval($vehicle_id), 'affected_rows' => $affected]);
        exit;
    } else {
        $values = [
            'vehicle_code' => $vehicle_code,
            'type' => $type ?: null,
            'manufacture_year' => $manufacture_year_i,
            'emp_id' => $emp_to_store,
            'driver_name' => $driver_name,
            'driver_phone' => $driver_phone,
            'status' => $status,
            'department_id' => $department_i,
            'section_id' => $section_i,
            'division_id' => $division_i,
            'vehicle_mode' => $vehicle_mode,
            'notes' => $notes,
            'created_by' => intval($user['id'])
        ];
        $cols = array_keys($values);
        $placeholders = array_fill(0, count($cols), '?');
        $sql = "INSERT INTO vehicles (" . implode(', ', $cols) . ", created_at) VALUES (" . implode(', ', $placeholders) . ", NOW())";
        $params = array_values($values);
        $types = '';
        foreach ($params as $p) $types .= param_type($p);

        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);

        if (!bind_params_dynamic($stmt, $types, $params)) throw new Exception('bind_param failed');

        if (!$stmt->execute()) {
            // duplicate key? give clearer message
            $errno = $stmt->errno;
            $err = $stmt->error;
            if ($errno === 1062) {
                // duplicate entry
                throw new Exception('Duplicate entry (vehicle_code may already exist).');
            }
            throw new Exception('Execute failed: ' . $err);
        }
        $newId = $stmt->insert_id;
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'تم إضافة المركبة', 'vehicle_id' => intval($newId)]);
        exit;
    }
} catch (Throwable $ex) {
    error_log('add_Vehicles error: ' . $ex->getMessage());
    // user-facing message (no SQL internals)
    echo json_encode(['success' => false, 'message' => 'Server error: ' . ($ex->getMessage())]);
    exit;
}
?>
<?php
// vehicle_management/api/vehicle/Vehicle_Maintenance.php
// CRUD API for vehicle_maintenance table
// Actions: list, get, create, update, delete
// Supports search by vehicle_code and location, filter by maintenance_type

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

// CORS
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Auth-Token');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// Include DB
$paths = [ __DIR__ . '/../../config/db.php', __DIR__ . '/../config/db.php', __DIR__ . '/config/db.php' ];
foreach ($paths as $p) { if (file_exists($p)) { require_once $p; break; } }
if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server misconfiguration: DB connection missing']);
    exit;
}

// Include session config
$scPaths = [ __DIR__ . '/../../config/session.php', __DIR__ . '/../config/session.php', __DIR__ . '/config/session.php' ];
foreach ($scPaths as $p) { if (file_exists($p)) { require_once $p; break; } }

// Helper to get all headers
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

// Authenticate (token or session)
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

// Parse input
function input_get($k) {
    if (isset($_POST[$k])) return $_POST[$k];
    if (isset($_GET[$k])) return $_GET[$k];
    if (isset($_REQUEST[$k])) return $_REQUEST[$k];
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json) && array_key_exists($k, $json)) return $json[$k];
    return null;
}

$action = input_get('action');

try {
    // LIST action
    if ($action === 'list') {
        $q = $_GET['q'] ?? '';
        $type = $_GET['type'] ?? '';
        $page = max(1, intval($_GET['page'] ?? 1));
        $per_page = max(1, min(200, intval($_GET['per_page'] ?? 30)));
        $offset = ($page - 1) * $per_page;
        
        $where = [];
        $params = [];
        $types = '';
        
        if ($q !== '') {
            $qLike = '%' . $q . '%';
            $where[] = "(vehicle_code LIKE ? OR location LIKE ?)";
            $params[] = $qLike;
            $params[] = $qLike;
            $types .= 'ss';
        }
        
        if ($type !== '') {
            $where[] = "maintenance_type = ?";
            $params[] = $type;
            $types .= 's';
        }
        
        $whereSql = '';
        if (!empty($where)) $whereSql = 'WHERE ' . implode(' AND ', $where);
        
        // Count total
        $countSql = "SELECT COUNT(*) AS cnt FROM vehicle_maintenance $whereSql";
        $stmt = $conn->prepare($countSql);
        if ($stmt === false) {
            echo json_encode(['success'=>false,'message'=>'Server error']);
            exit;
        }
        if (!empty($params)) {
            $bindNames = [];
            $bindNames[] = & $types;
            $vals = $params;
            for ($i=0;$i<count($vals);$i++) $bindNames[] = & $vals[$i];
            call_user_func_array([$stmt, 'bind_param'], $bindNames);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $total = intval($row['cnt'] ?? 0);
        $stmt->close();
        
        // Data query
        $sql = "SELECT * FROM vehicle_maintenance $whereSql ORDER BY visit_date DESC, id DESC LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            echo json_encode(['success'=>false,'message'=>'Server error']);
            exit;
        }
        
        $allParams = $params;
        $allTypes = $types;
        $allParams[] = $per_page;
        $allParams[] = $offset;
        $allTypes .= 'ii';
        
        $bindNames = [];
        $bindNames[] = & $allTypes;
        for ($i=0;$i<count($allParams);$i++) $bindNames[] = & $allParams[$i];
        call_user_func_array([$stmt, 'bind_param'], $bindNames);
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $records = [];
        while ($r = $result->fetch_assoc()) {
            $records[] = [
                'id' => (int)$r['id'],
                'vehicle_code' => $r['vehicle_code'] ?? null,
                'visit_date' => $r['visit_date'] ?? null,
                'next_visit_date' => $r['next_visit_date'] ?? null,
                'maintenance_type' => $r['maintenance_type'] ?? null,
                'location' => $r['location'] ?? null,
                'notes' => $r['notes'] ?? null,
                'created_by' => $r['created_by'] ?? null,
                'updated_by' => $r['updated_by'] ?? null,
                'created_at' => $r['created_at'] ?? null,
                'updated_at' => $r['updated_at'] ?? null,
            ];
        }
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'records' => $records
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // GET action
    if ($action === 'get') {
        $id = intval(input_get('id'));
        if (!$id) {
            echo json_encode(['success'=>false,'message'=>'Invalid id']);
            exit;
        }
        
        $stmt = $conn->prepare("SELECT * FROM vehicle_maintenance WHERE id = ? LIMIT 1");
        if (!$stmt) {
            echo json_encode(['success'=>false,'message'=>'Server error']);
            exit;
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if (!$row) {
            http_response_code(404);
            echo json_encode(['success'=>false,'message'=>'Record not found']);
            exit;
        }
        
        $record = [
            'id' => (int)$row['id'],
            'vehicle_code' => $row['vehicle_code'] ?? null,
            'visit_date' => $row['visit_date'] ?? null,
            'next_visit_date' => $row['next_visit_date'] ?? null,
            'maintenance_type' => $row['maintenance_type'] ?? null,
            'location' => $row['location'] ?? null,
            'notes' => $row['notes'] ?? null,
            'created_by' => $row['created_by'] ?? null,
            'updated_by' => $row['updated_by'] ?? null,
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
        ];
        
        echo json_encode(['success' => true, 'record' => $record], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // CREATE action
    if ($action === 'create') {
        $vehicle_code = trim((string)input_get('vehicle_code'));
        $visit_date = input_get('visit_date');
        $next_visit_date = input_get('next_visit_date');
        $maintenance_type = input_get('maintenance_type');
        $location = input_get('location');
        $notes = input_get('notes');
        $created_by = input_get('created_by') ?: $user['emp_id'] ?: $user['username'];
        
        if ($vehicle_code === '') {
            echo json_encode(['success' => false, 'message' => 'vehicle_code required']);
            exit;
        }
        
        $sql = "INSERT INTO vehicle_maintenance (vehicle_code, visit_date, next_visit_date, maintenance_type, location, notes, created_by, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Server error']);
            exit;
        }
        
        $stmt->bind_param('sssssss', $vehicle_code, $visit_date, $next_visit_date, $maintenance_type, $location, $notes, $created_by);
        
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
            $stmt->close();
            exit;
        }
        
        $newId = $stmt->insert_id;
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'تم إضافة سجل الصيانة', 'id' => intval($newId)]);
        exit;
    }
    
    // UPDATE action
    if ($action === 'update') {
        $id = intval(input_get('id'));
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid id']);
            exit;
        }
        
        $vehicle_code = trim((string)input_get('vehicle_code'));
        $visit_date = input_get('visit_date');
        $next_visit_date = input_get('next_visit_date');
        $maintenance_type = input_get('maintenance_type');
        $location = input_get('location');
        $notes = input_get('notes');
        $updated_by = $user['emp_id'] ?: $user['username'];
        
        if ($vehicle_code === '') {
            echo json_encode(['success' => false, 'message' => 'vehicle_code required']);
            exit;
        }
        
        $sql = "UPDATE vehicle_maintenance SET 
                vehicle_code = ?, visit_date = ?, next_visit_date = ?, maintenance_type = ?, 
                location = ?, notes = ?, updated_by = ?, updated_at = NOW() 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Server error']);
            exit;
        }
        
        $stmt->bind_param('sssssssi', $vehicle_code, $visit_date, $next_visit_date, $maintenance_type, $location, $notes, $updated_by, $id);
        
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
            $stmt->close();
            exit;
        }
        
        $affected = $stmt->affected_rows;
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'تم تحديث سجل الصيانة', 'affected_rows' => $affected]);
        exit;
    }
    
    // DELETE action
    if ($action === 'delete') {
        $id = intval(input_get('id'));
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success'=>false,'message'=>'Invalid id']);
            exit;
        }
        
        // Optional: Check permissions using perm_helper
        $permPath = __DIR__ . '/../permissions/perm_helper.php';
        if (file_exists($permPath)) require_once $permPath;
        
        // Authorize delete
        $allowDelete = false;
        if (function_exists('role_flags')) {
            $roleId = intval($user['role_id'] ?? 0);
            $flags = role_flags($conn, $roleId);
            if (is_array($flags) && !empty($flags['delete'])) $allowDelete = true;
        }
        // Also allow if user is admin (role_id 1 or 2)
        if (!$allowDelete && in_array(intval($user['role_id'] ?? 0), [1, 2])) $allowDelete = true;
        
        if (!$allowDelete) {
            http_response_code(403);
            echo json_encode(['success'=>false,'message'=>'Forbidden: insufficient permissions']);
            exit;
        }
        
        $stmt = $conn->prepare("DELETE FROM vehicle_maintenance WHERE id = ? LIMIT 1");
        if (!$stmt) {
            echo json_encode(['success'=>false,'message'=>'Server error']);
            exit;
        }
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) {
            echo json_encode(['success'=>false,'message'=>'Server error: ' . $stmt->error]);
            $stmt->close();
            exit;
        }
        $stmt->close();
        
        echo json_encode(['success'=>true,'message'=>'تم الحذف'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Invalid action
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
    
} catch (Throwable $ex) {
    error_log('Vehicle_Maintenance.php error: ' . $ex->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . ($ex->getMessage())]);
    exit;
}
?>

<?php
// vehicle_management/api/vehicle/get.php
// Endpoint to fetch a single vehicle by ID for editing.
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

// permission helper (assuming roles 1,2 can read/edit)
function has_perm(array $user, string $perm): bool {
    $role = intval($user['role_id'] ?? 0);
    if ($perm === 'vehicles_read' && in_array($role, [1,2])) return true;
    if ($perm === 'vehicles_edit' && in_array($role, [1,2])) return true;
    return false;
}

if (!has_perm($user, 'vehicles_read')) {
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

// Get ID from query params
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid vehicle ID']);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT 
            id, vehicle_code, type, manufacture_year, emp_id, driver_name, driver_phone, 
            status, department_id, section_id, division_id, vehicle_mode, notes,
            created_at, created_by, updated_at, updated_by
        FROM vehicles 
        WHERE id = ?
    ");
    if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);
    
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) throw new Exception('Execute failed: ' . $stmt->error);
    
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    
    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Vehicle not found']);
        exit;
    }
    
    // Convert to int where appropriate
    $row['id'] = intval($row['id']);
    $row['manufacture_year'] = $row['manufacture_year'] ? intval($row['manufacture_year']) : null;
    $row['department_id'] = $row['department_id'] ? intval($row['department_id']) : null;
    $row['section_id'] = $row['section_id'] ? intval($row['section_id']) : null;
    $row['division_id'] = $row['division_id'] ? intval($row['division_id']) : null;
    $row['created_by'] = $row['created_by'] ? intval($row['created_by']) : null;
    $row['updated_by'] = $row['updated_by'] ? intval($row['updated_by']) : null;
    
    echo json_encode(['success' => true, 'vehicle' => $row]);
} catch (Throwable $ex) {
    error_log('get vehicle error: ' . $ex->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $ex->getMessage()]);
}
?>
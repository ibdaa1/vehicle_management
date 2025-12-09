<?php
// vehicle_management/api/users/session_check.php
// session_check: supports debug (?debug=1), PHP session auth OR token auth (Authorization Bearer / X-Auth-Token).
// Returns JSON { success: true, user: { ... } } or { success:false, message: 'Not authenticated' }.
// Temporary debug output enabled when ?debug=1 (remove after troubleshooting).

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// include DB (needed for token lookup). Adjust paths if necessary.
$paths = [
    __DIR__ . '/../../config/db.php',
    __DIR__ . '/../config/db.php',
    __DIR__ . '/config/db.php'
];
foreach ($paths as $p) { if (file_exists($p)) { require_once $p; break; } }

// helper to read headers
function get_headers_normalized() {
    $h = [];
    if (function_exists('getallheaders')) {
        foreach (getallheaders() as $k => $v) $h[strtolower($k)] = $v;
    } else {
        foreach ($_SERVER as $k => $v) {
            if (substr($k, 0, 5) === 'HTTP_') {
                $name = strtolower(str_replace(' ', '-', str_replace('_', ' ', substr($k,5))));
                $h[$name] = $v;
            }
        }
    }
    return $h;
}

// try to get token from Authorization: Bearer or X-Auth-Token
$headers = get_headers_normalized();
$token = null;
if (!empty($headers['authorization'])) {
    if (preg_match('/Bearer\s+(.+)/i', $headers['authorization'], $m)) $token = trim($m[1]);
}
if (!$token && !empty($headers['x-auth-token'])) $token = trim($headers['x-auth-token']);
if (!$token && !empty($headers['x-session-token'])) $token = trim($headers['x-session-token']);

// Accept session id via cookie or X-Session-Id header for debug/fallback
$sessionName = session_name();
$providedSid = null;
if (!empty($_COOKIE[$sessionName])) $providedSid = $_COOKIE[$sessionName];
elseif (!empty($headers['x-session-id'])) $providedSid = $headers['x-session-id'];
elseif (!empty($_REQUEST['PHPSESSID'])) $providedSid = $_REQUEST['PHPSESSID'];

if ($providedSid && preg_match('/^[a-zA-Z0-9,-]{5,128}$/', $providedSid)) {
    session_id($providedSid);
}
// Start session (if present)
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// debug flag
$debug = (isset($_GET['debug']) && $_GET['debug'] == '1');

// If token provided, validate it (requires DB)
$user = null;
if ($token && isset($conn)) {
    try {
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
    } catch (Exception $e) {
        // ignore token DB errors in normal flow; they will result in Not authenticated
        error_log("session_check token lookup error: " . $e->getMessage());
    }
}

// If token auth failed, fallback to session user
if (!$user) {
    $sessUser = $_SESSION['user'] ?? null;
    if ($sessUser && !empty($sessUser['id']) && isset($conn)) {
        // Query database to get fresh user info including preferred_language
        try {
            $stmt = $conn->prepare("
                SELECT id, emp_id, username, email, phone, role_id,
                       department_id, section_id, division_id, preferred_language
                FROM users WHERE id = ? LIMIT 1
            ");
            if ($stmt) {
                $userId = (int)$sessUser['id'];
                $stmt->bind_param('i', $userId);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($row) {
                    $user = [
                        'id' => (int)$row['id'],
                        'emp_id' => $row['emp_id'],
                        'username' => $row['username'],
                        'email' => $row['email'],
                        'phone' => $row['phone'],
                        'role_id' => (int)$row['role_id'],
                        'department_id' => $row['department_id'] ? (int)$row['department_id'] : null,
                        'section_id' => $row['section_id'] ? (int)$row['section_id'] : null,
                        'division_id' => $row['division_id'] ? (int)$row['division_id'] : null,
                        'preferred_language' => $row['preferred_language'] ?? 'ar'
                    ];
                }
            }
        } catch (Exception $e) {
            error_log("session_check DB lookup error: " . $e->getMessage());
            // Fallback to session data if DB query fails
            $user = $sessUser;
        }
    } elseif ($sessUser && !empty($sessUser['id'])) {
        // No DB connection, use session data as-is
        $user = $sessUser;
    }
}

// debug output
if ($debug) {
    echo json_encode([
        'debug' => true,
        'request_time' => date('c'),
        'session_name' => session_name(),
        'cookie_received' => $_COOKIE,
        'provided_session_id' => $providedSid ?: null,
        'active_session_id' => session_id(),
        'session_status' => session_status(),
        'session_contents' => isset($_SESSION) ? $_SESSION : null,
        'token_received' => $token ?: null,
        'user_via_token' => $user ?: null,
        'headers' => $headers,
        'server' => [
            'host' => $_SERVER['HTTP_HOST'] ?? '',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? ''
        ]
    ], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
    exit;
}

// normal response
if ($user && !empty($user['id'])) {
    echo json_encode(['success' => true, 'user' => $user]);
    exit;
}
echo json_encode(['success' => false, 'message' => 'Not authenticated']);
exit;
?>
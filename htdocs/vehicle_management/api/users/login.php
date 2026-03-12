<?php
// vehicle_management/api/users/login.php
// Login handler that ensures token is persisted before returning it.

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS (adjust for production)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Auth-Token');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

// load DB and session config
$paths = [
    __DIR__ . '/../../config/db.php',
    __DIR__ . '/../config/db.php',
    __DIR__ . '/config/db.php'
];
foreach ($paths as $p) { if (file_exists($p)) { require_once $p; break; } }

$scPaths = [
    __DIR__ . '/../../config/session.php',
    __DIR__ . '/../config/session.php',
    __DIR__ . '/config/session.php'
];
foreach ($scPaths as $p) { if (file_exists($p)) { require_once $p; break; } }

// start session (if needed for rate-limiting)
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// read input (JSON or form)
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) $data = $_POST ?? [];

$identifier = trim((string)($data['username'] ?? $data['login'] ?? $data['emp_id'] ?? $data['email'] ?? $data['phone'] ?? ''));
$password = (string)($data['password'] ?? '');

if ($identifier === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'emp_id/email/phone/username and password are required.']);
    exit;
}

try {
    // lookup user
    $stmt = $conn->prepare("SELECT id, emp_id, username, email, phone, password_hash, is_active, role_id, preferred_language FROM users WHERE emp_id = ? OR email = ? OR username = ? OR phone = ? LIMIT 1");
    if ($stmt === false) throw new Exception('Prepare failed: ' . $conn->error);
    $stmt->bind_param('ssss', $identifier, $identifier, $identifier, $identifier);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();

    if (!$user || !isset($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
        exit;
    }

    if ((int)$user['is_active'] !== 1) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Account not active.']);
        exit;
    }

    // prepare token and insert session record atomically
    $token = bin2hex(random_bytes(32));
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $tz = new DateTimeZone('Asia/Dubai');
    $nowDt = new DateTime('now', $tz);
    $created_at = $nowDt->format('Y-m-d H:i:s');
    $expires_at = (clone $nowDt)->modify('+7 days')->format('Y-m-d H:i:s');

    $ins = $conn->prepare("INSERT INTO user_sessions (user_id, token, user_agent, ip, created_at, expires_at, revoked, updated_at) VALUES (?, ?, ?, ?, ?, ?, 0, ?)");
    if ($ins === false) {
        error_log('login: prepare failed for session insert: ' . $conn->error);
        throw new Exception('Server error');
    }
    $ins->bind_param('issssss', $user['id'], $token, $user_agent, $ip, $created_at, $expires_at, $created_at);
    if (!$ins->execute()) {
        // insertion failed -> do NOT return token
        error_log('login: session insert failed: ' . $ins->error);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error: failed to create session token.']);
        $ins->close();
        exit;
    }
    $ins->close();

    // success: write session user (optional)
    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'emp_id' => $user['emp_id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'role_id' => (int)$user['role_id'],
        'preferred_language' => $user['preferred_language'] ?? 'ar'
    ];
    // ensure session saved
    session_write_close();

    // return token and user
    unset($user['password_hash']);
    echo json_encode([
        'success' => true,
        'message' => 'Login successful.',
        'token' => $token,
        'session_id' => session_id(),
        'user' => [
            'id' => (int)$user['id'],
            'emp_id' => $user['emp_id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'role_id' => (int)$user['role_id'],
            'preferred_language' => $user['preferred_language'] ?? 'ar'
        ]
    ], JSON_UNESCAPED_UNICODE);

    exit;
} catch (Exception $e) {
    error_log("login exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.']);
    exit;
}
?>
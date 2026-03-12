<?php
// vehicle_management/api/users/admin/get.php
// معدل ومحدث بالكامل - يعمل مع $_SESSION['user_id'] أو $_SESSION['user']['id']
header('Content-Type: application/json; charset=utf-8');

// تأكيد أن الـ session cookie يشتغل على كل المسارات (مهم جدًا للـ subfolders)
ini_set('session.cookie_path', '/');
ini_set('session.cookie_secure', true);     // غيّر إلى false لو بتجرب على localhost http
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_samesite', 'Lax');

session_start();

$debug = isset($_GET['debug']) && $_GET['debug'] == '1';

if ($debug) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(E_ALL);
}

set_exception_handler(function($e) use ($debug) {
    error_log('admin/get.php exception: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    $resp = ['success' => false, 'message' => 'Server error'];
    if ($debug) $resp['debug'] = $e->getMessage();
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
});

set_error_handler(function($errno, $errstr, $errfile, $errline) use ($debug) {
    $msg = "PHP error ({$errno}) {$errstr} in {$errfile} on line {$errline}";
    error_log('admin/get.php error: ' . $msg);
    if ($debug) {
        http_response_code(500);
        echo json_encode(['success'=>false,'message'=>'Server error','debug'=>$msg], JSON_UNESCAPED_UNICODE);
        exit;
    }
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// تضمين ملف الاتصال بالقاعدة
$dbPathCandidates = [
    __DIR__ . '/../../config/db.php',
    __DIR__ . '/../config/db.php',
    __DIR__ . '/../../../config/db.php',
];
$included = false;
foreach ($dbPathCandidates as $p) {
    if (file_exists($p)) {
        require_once $p;
        $included = true;
        break;
    }
}
if (!$included || !isset($conn)) {
    $msg = 'DB config not found or $conn not defined';
    error_log('admin/get.php: ' . $msg);
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error','debug'=>$debug ? $msg : null], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // ==== إصلاح مشكلة الـ Session ====
    // ندعم الحالتين: القديمة ($_SESSION['user_id']) والجديدة ($_SESSION['user']['id'])
    $actorId = 0;
    if (!empty($_SESSION['user_id'])) {
        $actorId = (int)$_SESSION['user_id'];
    } elseif (!empty($_SESSION['user']['id'])) {
        $actorId = (int)$_SESSION['user']['id'];
        // نضيفها للتوافق مع باقي الكود القديم
        $_SESSION['user_id'] = $actorId;
    }

    if ($actorId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid id'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ==== التحقق من صلاحية المستخدم (admin أو superadmin) ====
    if ($conn instanceof mysqli) {
        $st = $conn->prepare("SELECT role_id FROM users WHERE id = ? LIMIT 1");
        if (!$st) throw new Exception('Prepare failed (role check): ' . $conn->error);
        $st->bind_param('i', $actorId);
        $st->execute();
        $res = $st->get_result();
        $actorRow = $res->fetch_assoc();
        $st->close();
    } else { // PDO
        $stmt = $conn->prepare("SELECT role_id FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$actorId]);
        $actorRow = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    $actorRole = (int)($actorRow['role_id'] ?? 0);
    if (!in_array($actorRole, [1, 2], true)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden: Admin access required'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ==== اكتشاف أعمدة جدول users ====
    $availableCols = [];
    if ($conn instanceof mysqli) {
        $colRes = $conn->query("SHOW COLUMNS FROM `users`");
        while ($c = $colRes->fetch_assoc()) $availableCols[] = $c['Field'];
    } else {
        $stmt = $conn->query("SHOW COLUMNS FROM `users`");
        $availableCols = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }
    $has = fn($name) => in_array($name, $availableCols, true);

    // ==== تحديد اسم العرض (display_name) ====
    $preferNameExpr = "`username` AS `display_name`"; // fallback
    if ($has('display_name') && $has('full_name')) {
        $preferNameExpr = "COALESCE(`display_name`, `full_name`, `username`) AS `display_name`";
    } elseif ($has('display_name')) {
        $preferNameExpr = "`display_name` AS `display_name`";
    } elseif ($has('full_name')) {
        $preferNameExpr = "COALESCE(`full_name`, `username`) AS `display_name`";
    } elseif ($has('username')) {
        $preferNameExpr = "`username` AS `display_name`";
    }

    // ==== بناء قائمة الأعمدة المطلوبة ====
    $candidates = ['id','emp_id','username','email','phone','preferred_language','role_id','is_active','department_id','section_id','division_id','created_at'];
    $selectParts = [$preferNameExpr];

    foreach ($candidates as $col) {
        if ($col === 'username') continue; // تم التعامل معه في الأعلى
        if ($has($col)) $selectParts[] = "`{$col}`";
    }

    $selectSQL = implode(', ', $selectParts);
    $q = "SELECT {$selectSQL} FROM `users` WHERE `id` = ? LIMIT 1";

    // ==== جلب بيانات المستخدم ====
    if ($conn instanceof mysqli) {
        $stmt = $conn->prepare($q);
        if (!$stmt) throw new Exception('Prepare failed (select user): ' . $conn->error);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } else {
        $stmt = $conn->prepare($q);
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // نجاح!
    echo json_encode([
        'success' => true,
        'user'    => $user
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $t) {
    error_log('admin/get.php throwable: ' . $t->getMessage() . "\n" . $t->getTraceAsString());
    http_response_code(500);
    $resp = ['success' => false, 'message' => 'Server error'];
    if ($debug) $resp['debug'] = $t->getMessage() . ' in ' . $t->getFile() . ':' . $t->getLine();
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
}
?>
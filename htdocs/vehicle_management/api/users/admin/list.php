<?php
// vehicle_management/api/users/admin/list.php
// قائمة المستخدمين الإدارية مع تحسين الجلسة ودعم pagination وتصفية emp_id بالضبط.
header('Content-Type: application/json; charset=utf-8');
$debug = isset($_GET['debug']) && $_GET['debug'] == '1';
if ($debug) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(E_ALL);
}

// Exception handler
set_exception_handler(function($e) use ($debug) {
    error_log('admin/list.php exception: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    $resp = ['success' => false, 'message' => 'خطأ في الخادم'];
    if ($debug) $resp['debug'] = $e->getMessage();
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
});

// Error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) use ($debug) {
    $msg = "PHP error ({$errno}) {$errstr} in {$errfile} on line {$errline}";
    error_log('admin/list.php error: ' . $msg);
    if ($debug) {
        http_response_code(500);
        echo json_encode(['success'=>false,'message'=>'خطأ في الخادم','debug'=>$msg], JSON_UNESCAPED_UNICODE);
        exit;
    }
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// تضمين DB
$dbPath = __DIR__ . '/../../config/db.php';
if (!file_exists($dbPath)) {
    $msg = "DB config not found at {$dbPath}";
    error_log($msg);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطأ في الخادم', 'debug' => $debug ? $msg : null], JSON_UNESCAPED_UNICODE);
    exit;
}
require_once $dbPath;
if (!isset($conn)) {
    $msg = 'Database connection ($conn) is not defined by config/db.php';
    error_log($msg);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطأ في الخادم', 'debug' => $debug ? $msg : null], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // 🚨 تحسين: بدء الجلسة وتحقق شامل
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    // تحقق من الجلسة: دعم user_id أو user array
    $userId = $_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? null);
    if (empty($userId)) {
        http_response_code(401);
        echo json_encode([
            'success' => false, 
            'message' => 'غير مصرح للوصول. يرجى تسجيل الدخول.',
            'debug' => $debug ? ['session_keys' => array_keys($_SESSION ?? [])] : null
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // جلب بيانات المستخدم للدور
    $stmt = $conn->prepare("SELECT role_id FROM users WHERE id = ? LIMIT 1");
    if (!$stmt) throw new Exception('Prepare failed for role check: ' . $conn->error);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $userData = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$userData) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'المستخدم غير موجود']);
        exit;
    }

    $role = (int)($userData['role_id'] ?? 0);
    if (!in_array($role, [1, 2], true)) {  // 1=Super Admin, 2=Admin
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'غير مصرح لهذا الدور']);
        exit;
    }

    // اكتشاف الأعمدة المتاحة
    $availableCols = [];
    $colRes = $conn->query("SHOW COLUMNS FROM `users`");
    if ($colRes === false) throw new Exception('SHOW COLUMNS failed: ' . $conn->error);
    while ($c = $colRes->fetch_assoc()) {
        $availableCols[] = $c['Field'];
    }

    $has = function($name) use ($availableCols) {
        return in_array($name, $availableCols, true);
    };

    // بناء SELECT ديناميكي
    $selectParts = [];
    if ($has('id')) $selectParts[] = '`id`';
    if ($has('emp_id')) $selectParts[] = '`emp_id`';
    if ($has('username')) $selectParts[] = '`username`';
    if ($has('email')) $selectParts[] = '`email`';
    if ($has('phone')) $selectParts[] = '`phone`';
    if ($has('preferred_language')) $selectParts[] = '`preferred_language`';
    if ($has('role_id')) $selectParts[] = '`role_id`';
    if ($has('is_active')) $selectParts[] = '`is_active`';
    if ($has('department_id')) $selectParts[] = '`department_id`';
    if ($has('section_id')) $selectParts[] = '`section_id`';
    if ($has('division_id')) $selectParts[] = '`division_id`';
    if ($has('created_at')) $selectParts[] = '`created_at`';

    // تعبير الاسم (display_name)
    $nameExpr = null;
    if ($has('display_name')) {
        $nameExpr = "`display_name` AS `display_name`";
    } elseif ($has('full_name')) {
        $nameExpr = "`full_name` AS `display_name`";
    } elseif ($has('username')) {
        $nameExpr = "`username` AS `display_name`";
    }
    if ($nameExpr) {
        array_unshift($selectParts, $nameExpr);
    }

    if (empty($selectParts)) {
        throw new Exception('No selectable columns found in users table');
    }

    $selectSQL = implode(', ', $selectParts);

    // 🚨 معالجة معلمات التصفية
    $empIdFilter = isset($_GET['emp_id']) && trim($_GET['emp_id']) !== '' ? trim($_GET['emp_id']) : null;
    
    // 🚨 إضافة: Pagination (page/per_page)
    $page = max(1, intval($_GET['page'] ?? 1));
    $perPage = min(100, max(10, intval($_GET['per_page'] ?? 50)));
    $offset = ($page - 1) * $perPage;

    // بناء استعلامات SQL مع التصفية
    $whereClause = '';
    $whereParams = [];
    
    if ($empIdFilter !== null) {
        $whereClause = " WHERE `emp_id` = ?";
        $whereParams[] = $empIdFilter;
    }

    // Query العد مع التصفية
    $countSql = "SELECT COUNT(*) as total FROM `users`" . $whereClause;
    $countRes = null;
    
    if (!empty($whereParams)) {
        $countStmt = $conn->prepare($countSql);
        if ($countStmt === false) throw new Exception('Count prepare failed: ' . $conn->error);
        $countStmt->bind_param('s', $empIdFilter);
        $countStmt->execute();
        $countRes = $countStmt->get_result();
    } else {
        $countRes = $conn->query($countSql);
    }
    
    $total = $countRes ? $countRes->fetch_assoc()['total'] : 0;

    // Query البيانات مع التصفية
    $dataSql = "SELECT {$selectSQL} FROM `users` {$whereClause} ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}";
    
    $users = [];
    if (!empty($whereParams)) {
        // استخدام prepared statement مع التصفية
        $stmt = $conn->prepare($dataSql);
        if ($stmt === false) throw new Exception('Data prepare failed: ' . $conn->error);
        $stmt->bind_param('s', $empIdFilter);
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        // بدون تصفية
        $res = $conn->query($dataSql);
    }
    
    if ($res === false) throw new Exception('Query failed: ' . $conn->error);
    
    while ($row = $res->fetch_assoc()) {
        $users[] = $row;
    }

    echo json_encode([
        'success' => true, 
        'users' => $users,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => (int)$total,
            'total_pages' => ceil($total / $perPage)
        ],
        'filter' => [
            'emp_id' => $empIdFilter
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $t) {
    error_log('admin/list.php throwable: ' . $t->getMessage());
    http_response_code(500);
    $resp = ['success' => false, 'message' => 'خطأ في الخادم'];
    if ($debug) $resp['debug'] = $t->getMessage();
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}
?>
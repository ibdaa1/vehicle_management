<?php
// ================================================
// نظام إدارة مخالفات المركبات - واجهة برمجة التطبيقات
// الإصدار: 2.1 (محدث ومُصلح بالكامل - تم حل ArgumentCountError)
// ================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

// ================================================
// 1. التحقق من الجلسة والمصادقة
// ================================================
session_start();

if (!isset($_SESSION['user']) || empty($_SESSION['user']['emp_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'غير مسجل الدخول. يرجى تسجيل الدخول أولاً.',
        'isLoggedIn' => false
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$user = $_SESSION['user'];
$user_emp_id = $user['emp_id'];
$user_role_id = $user['role_id'] ?? 0;
$user_department_id = $user['department_id'] ?? null;
$is_admin = in_array($user_role_id, [1, 2]); // المديرون والمشرفون

// ================================================
// 2. تضمين ملفات الإعدادات
// ================================================
$timezonePath = __DIR__ . '/../config/timezone.php';
$dbPath = __DIR__ . '/../config/db.php';

if (!file_exists($timezonePath) || !file_exists($dbPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'ملف الإعدادات مفقود.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once $timezonePath;
require_once $dbPath;

global $nowDt;
$current_datetime = $nowDt->format('Y-m-d H:i:s');

// ================================================
// 3. تحديد طريقة الطلب ومعالجتها
// ================================================
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST' && isset($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
}

try {
    switch ($method) {
        case 'GET':
            handleGetRequest();
            break;
        case 'POST':
            handlePostRequest();
            break;
        case 'PUT':
            handlePutRequest();
            break;
        case 'DELETE':
            handleDeleteRequest();
            break;
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'الطريقة غير مسموحة.'
            ], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ في الخادم.',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

// ================================================
// 4. الدوال المساعدة
// ================================================

/**
 * دالة لرفع ملفات المرفقات
 */
function uploadViolationAttachment($fileInputName) {
    $uploadDir = __DIR__ . '/../../../uploads/vehicle_violations/';
   
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
   
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
   
    $file = $_FILES[$fileInputName];
   
    $maxFileSize = 10 * 1024 * 1024; // 10MB
    if ($file['size'] > $maxFileSize) {
        throw new Exception('حجم الملف كبير جداً. الحد الأقصى 10MB.');
    }
   
    $allowedTypes = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
        'application/pdf', 'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    $fileType = mime_content_type($file['tmp_name']);
   
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception('نوع الملف غير مدعوم. يرجى رفع صورة أو ملف PDF أو Word.');
    }
   
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $fileExtension;
    $filepath = $uploadDir . $filename;
   
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return 'vehicle_violations/' . $filename;
    } else {
        throw new Exception('فشل رفع الملف.');
    }
}

/**
 * دالة لتسجيل نشاطات المستخدم
 */
function logActivity($action, $details) {
    global $conn, $user_emp_id, $current_datetime;
   
    try {
        $sql = "INSERT INTO activity_log (emp_id, action, details, created_at)
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
       
        if ($stmt) {
            $stmt->bind_param('ssss', $user_emp_id, $action, $details, $current_datetime);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {
        error_log("Error logging activity: " . $e->getMessage());
    }
}

/**
 * دالة لبناء شروط WHERE للفلترة
 */
function buildWhereConditions(&$params, &$types, $is_admin, $user_emp_id, $apply_user_filter = true) {
    $whereConditions = [];
   
    if (!$is_admin && $apply_user_filter) {
        $whereConditions[] = "vv.issued_by_emp_id = ?";
        $params[] = $user_emp_id;
        $types .= 's';
    }
   
    if (isset($_GET['department_id']) && $_GET['department_id'] !== '' && $_GET['department_id'] !== '0') {
        $whereConditions[] = "v.department_id = ?";
        $params[] = intval($_GET['department_id']);
        $types .= 'i';
    }
   
    if (isset($_GET['section_id']) && $_GET['section_id'] !== '' && $_GET['section_id'] !== '0') {
        $whereConditions[] = "v.section_id = ?";
        $params[] = intval($_GET['section_id']);
        $types .= 'i';
    }
   
    if (isset($_GET['division_id']) && $_GET['division_id'] !== '' && $_GET['division_id'] !== '0') {
        $whereConditions[] = "v.division_id = ?";
        $params[] = intval($_GET['division_id']);
        $types .= 'i';
    }
   
    if (isset($_GET['violation_status']) && in_array($_GET['violation_status'], ['paid', 'unpaid'])) {
        $whereConditions[] = "vv.violation_status = ?";
        $params[] = $_GET['violation_status'];
        $types .= 's';
    }
   
    if (isset($_GET['vehicle_code']) && $_GET['vehicle_code'] !== '') {
        $whereConditions[] = "vv.vehicle_code LIKE ?";
        $params[] = "%" . trim($_GET['vehicle_code']) . "%";
        $types .= 's';
    }
   
    if (isset($_GET['start_date']) && $_GET['start_date'] !== '') {
        $whereConditions[] = "DATE(vv.violation_datetime) >= ?";
        $params[] = $_GET['start_date'];
        $types .= 's';
    }
   
    if (isset($_GET['end_date']) && $_GET['end_date'] !== '') {
        $whereConditions[] = "DATE(vv.violation_datetime) <= ?";
        $params[] = $_GET['end_date'];
        $types .= 's';
    }
   
    return $whereConditions;
}

// ================================================
// 5. معالجة طلبات GET
// ================================================
function handleGetRequest() {
    global $conn, $user_emp_id, $is_admin;

    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'statistics':
            getStatistics();
            break;
        case 'single':
            getViolationById();
            break;
        case 'vehicles':
            getVehicles();
            break;
        case 'all_vehicles':
            getAllVehiclesForSearch();
            break;
        case 'attachment':
            getAttachment();
            break;
        case 'departments':
            getDepartments();
            break;
        case 'sections':
            getSections();
            break;
        case 'divisions':
            getDivisions();
            break;
        default:
            getAllViolations();
    }
}

// ================================================
// 6. دوال معالجة البيانات
// ================================================

/**
 * جلب جميع المخالفات مع الفلترة (مُصلحة بالكامل)
 */
function getAllViolations() {
    global $conn, $user_emp_id, $is_admin;

    try {
        $sql = "SELECT
                    vv.*,
                    v.driver_name,
                    v.driver_phone,
                    v.type as vehicle_type,
                    d.name_ar as department_name,
                    s.name_ar as section_name,
                    dv.name_ar as division_name,
                    issued_user.username as issued_by_name,
                    paid_user.username as paid_by_name,
                    issued_user.profile_image as issued_profile_image,
                    paid_user.profile_image as paid_profile_image
                FROM vehicle_violations vv
                LEFT JOIN vehicles v ON vv.vehicle_id = v.id
                LEFT JOIN Departments d ON v.department_id = d.department_id
                LEFT JOIN Sections s ON v.section_id = s.section_id
                LEFT JOIN Divisions dv ON v.division_id = dv.division_id
                LEFT JOIN users issued_user ON vv.issued_by_emp_id = issued_user.emp_id
                LEFT JOIN users paid_user ON vv.paid_by_emp_id = paid_user.emp_id";

        $params = [];
        $types = '';
        $whereConditions = buildWhereConditions($params, $types, $is_admin, $user_emp_id, true);

        if (count($whereConditions) > 0) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }

        $sql .= " ORDER BY vv.violation_datetime DESC";

        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 10;
        $offset = ($page - 1) * $per_page;

        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $per_page;
        $types .= 'i';
        $params[] = $offset;
        $types .= 'i';

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("خطأ في تحضير الاستعلام: " . $conn->error);
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $violations = [];
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['payment_attachment'])) {
                $row['attachment_url'] = '../../uploads/' . $row['payment_attachment'];
                $row['attachment_name'] = basename($row['payment_attachment']);
            }
            $violations[] = $row;
        }

        // حساب العدد الإجمالي (بمتغيرات منفصلة تماماً لتجنب التداخل)
        $count_sql = "SELECT COUNT(*) as total_count 
                      FROM vehicle_violations vv 
                      LEFT JOIN vehicles v ON vv.vehicle_id = v.id";

        $count_params = [];
        $count_types = '';
        $count_where = buildWhereConditions($count_params, $count_types, $is_admin, $user_emp_id, true);

        if (count($count_where) > 0) {
            $count_sql .= " WHERE " . implode(' AND ', $count_where);
        }

        $count_stmt = $conn->prepare($count_sql);
        if (!$count_stmt) {
            throw new Exception("خطأ في تحضير استعلام العدد: " . $conn->error);
        }

        if (!empty($count_params)) {
            $count_stmt->bind_param($count_types, ...$count_params);
        }

        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count_row = $count_result->fetch_assoc();
        $total_count = $count_row['total_count'] ?? 0;

        $stmt->close();
        $count_stmt->close();

        echo json_encode([
            'success' => true,
            'data' => $violations,
            'count' => count($violations),
            'total_count' => $total_count,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total_count / $per_page)
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'حدث خطأ في جلب البيانات',
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * جلب الإحصائيات مع تطبيق نفس الفلترات (مُحسّنة)
 */
function getStatistics() {
    global $conn, $user_emp_id, $is_admin;

    try {
        $params = [];
        $types = '';
        $whereConditions = buildWhereConditions($params, $types, $is_admin, $user_emp_id, false);

        $where_clause = count($whereConditions) > 0 ? " WHERE " . implode(' AND ', $whereConditions) : "";
        $and_clause = count($whereConditions) > 0 ? " AND " . implode(' AND ', $whereConditions) : "";

        $base_from = " FROM vehicle_violations vv LEFT JOIN vehicles v ON vv.vehicle_id = v.id";

        // إجمالي
        $sql_total = "SELECT COUNT(*) as total_count, SUM(violation_amount) as total_amount, AVG(violation_amount) as average_amount" . $base_from . $where_clause;

        // مدفوعة
        $sql_paid = "SELECT COUNT(*) as paid_count, SUM(violation_amount) as paid_amount" . $base_from . " WHERE vv.violation_status = 'paid'" . $and_clause;

        // غير مدفوعة
        $sql_unpaid = "SELECT COUNT(*) as unpaid_count, SUM(violation_amount) as unpaid_amount" . $base_from . " WHERE vv.violation_status = 'unpaid'" . $and_clause;

        $statistics = [
            'total_count' => 0, 'total_amount' => 0, 'average_amount' => 0,
            'paid_count' => 0, 'paid_amount' => 0,
            'unpaid_count' => 0, 'unpaid_amount' => 0
        ];

        // تنفيذ الإجمالي
        $stmt = $conn->prepare($sql_total);
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $statistics['total_count'] = $row['total_count'] ?? 0;
            $statistics['total_amount'] = $row['total_amount'] ?? 0;
            $statistics['average_amount'] = $row['average_amount'] ?? 0;
        }
        $stmt->close();

        // تنفيذ المدفوعة
        $stmt = $conn->prepare($sql_paid);
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $statistics['paid_count'] = $row['paid_count'] ?? 0;
            $statistics['paid_amount'] = $row['paid_amount'] ?? 0;
        }
        $stmt->close();

        // تنفيذ غير المدفوعة
        $stmt = $conn->prepare($sql_unpaid);
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $statistics['unpaid_count'] = $row['unpaid_count'] ?? 0;
            $statistics['unpaid_amount'] = $row['unpaid_amount'] ?? 0;
        }
        $stmt->close();

        echo json_encode([
            'success' => true,
            'statistics' => $statistics,
            'filters_applied' => $_GET
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'حدث خطأ في جلب الإحصائيات',
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * جلب مخالفة واحدة بواسطة ID
 */
function getViolationById() {
    global $conn, $user_emp_id, $is_admin;

    try {
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            throw new Exception("معرف المخالفة غير صالح");
        }

        $id = intval($_GET['id']);

        $sql = "SELECT
                    vv.*,
                    v.driver_name,
                    v.driver_phone,
                    v.type as vehicle_type,
                    v.department_id,
                    v.section_id,
                    v.division_id,
                    d.name_ar as department_name,
                    s.name_ar as section_name,
                    dv.name_ar as division_name,
                    issued_user.username as issued_by_name,
                    paid_user.username as paid_by_name
                FROM vehicle_violations vv
                LEFT JOIN vehicles v ON vv.vehicle_id = v.id
                LEFT JOIN Departments d ON v.department_id = d.department_id
                LEFT JOIN Sections s ON v.section_id = s.section_id
                LEFT JOIN Divisions dv ON v.division_id = dv.division_id
                LEFT JOIN users issued_user ON vv.issued_by_emp_id = issued_user.emp_id
                LEFT JOIN users paid_user ON vv.paid_by_emp_id = paid_user.emp_id
                WHERE vv.id = ?";

        if (!$is_admin) {
            $sql .= " AND vv.issued_by_emp_id = ?";
        }

        $stmt = $conn->prepare($sql);
        if (!$is_admin) {
            $stmt->bind_param('is', $id, $user_emp_id);
        } else {
            $stmt->bind_param('i', $id);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $violation = $result->fetch_assoc();
            if (!empty($violation['payment_attachment'])) {
                $violation['attachment_url'] = '../../uploads/' . $violation['payment_attachment'];
                $violation['attachment_name'] = basename($violation['payment_attachment']);
            }

            echo json_encode([
                'success' => true,
                'data' => $violation
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'لم يتم العثور على المخالفة.'
            ], JSON_UNESCAPED_UNICODE);
        }

        $stmt->close();

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'حدث خطأ في جلب بيانات المخالفة',
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * جلب جميع المركبات للبحث الذكي
 */
function getAllVehiclesForSearch() {
    global $conn;

    try {
        $sql = "SELECT
                    v.id,
                    v.vehicle_code,
                    v.driver_name,
                    v.driver_phone,
                    d.name_ar as department_name,
                    s.name_ar as section_name,
                    dv.name_ar as division_name,
                    v.status
                FROM vehicles v
                LEFT JOIN Departments d ON v.department_id = d.department_id
                LEFT JOIN Sections s ON v.section_id = s.section_id
                LEFT JOIN Divisions dv ON v.division_id = dv.division_id
                WHERE v.status = 'operational'
                ORDER BY v.vehicle_code, v.driver_name";

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();

        $vehicles = [];
        while ($row = $result->fetch_assoc()) {
            $vehicles[] = $row;
        }

        $stmt->close();

        echo json_encode([
            'success' => true,
            'data' => $vehicles,
            'count' => count($vehicles)
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'حدث خطأ في جلب المركبات',
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * جلب المركبات للنموذج
 */
function getVehicles() {
    global $conn;

    try {
        $sql = "SELECT
                    id,
                    vehicle_code,
                    driver_name,
                    driver_phone,
                    type,
                    department_id,
                    section_id,
                    division_id
                FROM vehicles
                WHERE status = 'operational'
                ORDER BY vehicle_code";

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();

        $vehicles = [];
        while ($row = $result->fetch_assoc()) {
            $vehicles[] = $row;
        }

        $stmt->close();

        echo json_encode([
            'success' => true,
            'data' => $vehicles
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'حدث خطأ في جلب المركبات',
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * جلب الإدارات
 */
function getDepartments() {
    global $conn;

    try {
        $lang = $_GET['lang'] ?? 'ar';
        $name_field = $lang === 'ar' ? 'name_ar' : 'name_en';

        $sql = "SELECT department_id as id, $name_field as name, code
                FROM Departments
                WHERE status = 'active'
                ORDER BY $name_field";

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();

        $departments = [];
        while ($row = $result->fetch_assoc()) {
            $departments[] = $row;
        }

        $stmt->close();

        echo json_encode([
            'success' => true,
            'departments' => $departments
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'حدث خطأ في جلب الإدارات',
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * جلب الأقسام حسب الإدارة
 */
function getSections() {
    global $conn;

    try {
        $department_id = $_GET['parent_id'] ?? 0;
        $lang = $_GET['lang'] ?? 'ar';
        $name_field = $lang === 'ar' ? 'name_ar' : 'name_en';

        $sql = "SELECT section_id as id, $name_field as name, department_id
                FROM Sections
                WHERE status = 'active'";

        if ($department_id > 0) {
            $sql .= " AND department_id = ?";
        }

        $sql .= " ORDER BY $name_field";

        $stmt = $conn->prepare($sql);
        if ($department_id > 0) {
            $stmt->bind_param('i', $department_id);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $sections = [];
        while ($row = $result->fetch_assoc()) {
            $sections[] = $row;
        }

        $stmt->close();

        echo json_encode([
            'success' => true,
            'sections' => $sections
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'حدث خطأ في جلب الأقسام',
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * جلب الشعب حسب القسم
 */
function getDivisions() {
    global $conn;

    try {
        $section_id = $_GET['parent_id'] ?? 0;
        $lang = $_GET['lang'] ?? 'ar';
        $name_field = $lang === 'ar' ? 'name_ar' : 'name_en';

        $sql = "SELECT division_id as id, $name_field as name, section_id
                FROM Divisions
                WHERE status = 'active'";

        if ($section_id > 0) {
            $sql .= " AND section_id = ?";
        }

        $sql .= " ORDER BY $name_field";

        $stmt = $conn->prepare($sql);
        if ($section_id > 0) {
            $stmt->bind_param('i', $section_id);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $divisions = [];
        while ($row = $result->fetch_assoc()) {
            $divisions[] = $row;
        }

        $stmt->close();

        echo json_encode([
            'success' => true,
            'divisions' => $divisions
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'حدث خطأ في جلب الشعب',
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * جلب ملف المرفق
 */
function getAttachment() {
    if (!isset($_GET['filename'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'اسم الملف مطلوب.']);
        exit;
    }

    $filename = basename($_GET['filename']);
    $filepath = __DIR__ . '/../../../uploads/vehicle_violations/' . $filename;

    if (!file_exists($filepath)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'الملف غير موجود.']);
        exit;
    }

    $fileType = mime_content_type($filepath);

    echo json_encode([
        'success' => true,
        'file_url' => '../../uploads/vehicle_violations/' . $filename,
        'file_type' => $fileType
    ]);
}

// ================================================
// 7. معالجة طلبات POST (إضافة مخالفة جديدة)
// ================================================
function handlePostRequest() {
    global $conn, $user_emp_id, $current_datetime;

    if (isset($_POST['_method']) && $_POST['_method'] === 'PUT') {
        handlePutRequest();
        return;
    }

    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $vehicle_id = $_POST['vehicle_id'] ?? 0;
            $vehicle_code = $_POST['vehicle_code'] ?? '';
            $violation_datetime = $_POST['violation_datetime'] ?? '';
            $violation_amount = $_POST['violation_amount'] ?? 0;
            $notes = $_POST['notes'] ?? '';
        } else {
            $vehicle_id = $data['vehicle_id'] ?? 0;
            $vehicle_code = $data['vehicle_code'] ?? '';
            $violation_datetime = $data['violation_datetime'] ?? '';
            $violation_amount = $data['violation_amount'] ?? 0;
            $notes = $data['notes'] ?? '';
        }

        $vehicle_id = intval($vehicle_id);
        $violation_amount = floatval($violation_amount);

        if (!$vehicle_id) throw new Exception("معرف المركبة مطلوب.");
        if (!$vehicle_code) throw new Exception("رقم المركبة مطلوب.");
        if (!$violation_datetime) throw new Exception("تاريخ المخالفة مطلوب.");
        if ($violation_amount <= 0) throw new Exception("قيمة المخالفة يجب أن تكون أكبر من صفر.");

        $check_sql = "SELECT id, vehicle_code FROM vehicles WHERE id = ? AND status = 'operational'";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param('i', $vehicle_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows === 0) {
            throw new Exception("المركبة غير موجودة أو غير نشطة");
        }

        $vehicle_data = $check_result->fetch_assoc();
        if ($vehicle_data['vehicle_code'] !== $vehicle_code) {
            throw new Exception("رقم المركبة لا يتطابق مع المركبة المحددة.");
        }
        $check_stmt->close();

        $sql = "INSERT INTO vehicle_violations
                (vehicle_id, vehicle_code, violation_datetime, violation_amount, violation_status, issued_by_emp_id, notes, created_at)
                VALUES (?, ?, ?, ?, 'unpaid', ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('issdsss', $vehicle_id, $vehicle_code, $violation_datetime, $violation_amount, $user_emp_id, $notes, $current_datetime);

        if ($stmt->execute()) {
            $violation_id = $conn->insert_id;
            logActivity("إضافة مخالفة", "تم إضافة مخالفة جديدة رقم $violation_id للمركبة $vehicle_code");

            echo json_encode([
                'success' => true,
                'message' => 'تم إضافة المخالفة بنجاح.',
                'violation_id' => $violation_id
            ], JSON_UNESCAPED_UNICODE);
        } else {
            throw new Exception("فشل إضافة المخالفة: " . $stmt->error);
        }

        $stmt->close();

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}

// ================================================
// 8. معالجة طلبات PUT (تعديل المخالفة)
// ================================================
function handlePutRequest() {
    global $conn, $user_emp_id, $is_admin, $current_datetime;

    try {
        if (isset($_POST['id'])) {
            $id = intval($_POST['id']);
            $violation_amount = $_POST['violation_amount'] ?? null;
            $violation_datetime = $_POST['violation_datetime'] ?? null;
            $violation_status = $_POST['violation_status'] ?? null;
            $notes = $_POST['notes'] ?? null;

            $payment_attachment = null;
            if (isset($_FILES['payment_attachment']) && $_FILES['payment_attachment']['error'] === 0) {
                $payment_attachment = uploadViolationAttachment('payment_attachment');
            }
        } else {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            if (json_last_error() !== JSON_ERROR_NONE) throw new Exception("بيانات JSON غير صالحة");

            $id = $data['id'] ?? 0;
            $violation_amount = $data['violation_amount'] ?? null;
            $violation_datetime = $data['violation_datetime'] ?? null;
            $violation_status = $data['violation_status'] ?? null;
            $notes = $data['notes'] ?? null;
            $payment_attachment = $data['payment_attachment'] ?? null;
        }

        if (!$id) throw new Exception("معرف المخالفة مطلوب.");

        $check_sql = "SELECT * FROM vehicle_violations WHERE id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param('i', $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows === 0) throw new Exception("لم يتم العثور على المخالفة.");
        $current_violation = $check_result->fetch_assoc();
        $check_stmt->close();

        if ($current_violation['issued_by_emp_id'] !== $user_emp_id && !$is_admin) {
            throw new Exception("غير مصرح لك بتعديل هذه المخالفة.");
        }

        $updates = [];
        $params = [];
        $types = '';

        if ($violation_amount !== null && $violation_amount > 0) {
            $updates[] = "violation_amount = ?";
            $params[] = $violation_amount;
            $types .= 'd';
        }

        if ($violation_datetime !== null) {
            $updates[] = "violation_datetime = ?";
            $params[] = $violation_datetime;
            $types .= 's';
        }

        if ($notes !== null) {
            $updates[] = "notes = ?";
            $params[] = $notes;
            $types .= 's';
        }

        if ($violation_status !== null && in_array($violation_status, ['paid', 'unpaid'])) {
            $updates[] = "violation_status = ?";
            $params[] = $violation_status;
            $types .= 's';

            if ($violation_status === 'paid') {
                $updates[] = "paid_by_emp_id = ?";
                $params[] = $user_emp_id;
                $types .= 's';

                $updates[] = "payment_datetime = ?";
                $params[] = $current_datetime;
                $types .= 's';
            } elseif ($violation_status === 'unpaid') {
                $updates[] = "paid_by_emp_id = NULL";
                $updates[] = "payment_datetime = NULL";
            }
        }

        if ($payment_attachment !== null) {
            $updates[] = "payment_attachment = ?";
            $params[] = $payment_attachment;
            $types .= 's';
        }

        $updates[] = "updated_at = ?";
        $params[] = $current_datetime;
        $types .= 's';

        if (count($updates) > 1) { // أكثر من updated_at فقط
            $params[] = $id;
            $types .= 'i';

            $sql = "UPDATE vehicle_violations SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);

            if ($stmt->execute()) {
                logActivity("تعديل مخالفة", "تم تعديل المخالفة رقم $id");
                echo json_encode([
                    'success' => true,
                    'message' => 'تم تحديث المخالفة بنجاح.'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                throw new Exception("فشل التحديث: " . $stmt->error);
            }
            $stmt->close();
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'لا توجد بيانات للتحديث.'
            ], JSON_UNESCAPED_UNICODE);
        }

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}

// ================================================
// 9. معالجة طلبات DELETE
// ================================================
function handleDeleteRequest() {
    global $conn, $user_emp_id, $is_admin;

    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (!isset($data['id']) || !is_numeric($data['id'])) {
            throw new Exception("معرف المخالفة مطلوب.");
        }

        $id = intval($data['id']);

        $check_sql = "SELECT * FROM vehicle_violations WHERE id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param('i', $id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows === 0) throw new Exception("لم يتم العثور على المخالفة.");

        $violation = $result->fetch_assoc();
        $check_stmt->close();

        if (!$is_admin) {
            if ($violation['issued_by_emp_id'] !== $user_emp_id) {
                throw new Exception("غير مصرح لك بحذف هذه المخالفة.");
            }
            if ($violation['violation_status'] === 'paid') {
                throw new Exception("لا يمكن حذف مخالفة مدفوعة.");
            }
        }

        if (!empty($violation['payment_attachment'])) {
            $filepath = __DIR__ . '/../../../uploads/' . $violation['payment_attachment'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }

        $delete_sql = "DELETE FROM vehicle_violations WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param('i', $id);

        if ($delete_stmt->execute()) {
            logActivity("حذف مخالفة", "تم حذف المخالفة رقم $id للمركبة " . $violation['vehicle_code']);
            echo json_encode([
                'success' => true,
                'message' => 'تم حذف المخالفة بنجاح.'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            throw new Exception("فشل الحذف: " . $delete_stmt->error);
        }

        $delete_stmt->close();

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}

// ================================================
// نهاية الملف
// ================================================
?>
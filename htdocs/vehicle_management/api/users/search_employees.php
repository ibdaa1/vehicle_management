<?php
// vehicle_management/api/users/search_employees.php
header('Content-Type: application/json; charset=utf-8');
session_start();

// تحقق من الصلاحيات: فقط super_admin و admin (role_id = 1 أو 2)
$user_role_id = $_SESSION['user']['role_id'] ?? 0;
if (!isset($_SESSION['user']) || !in_array($user_role_id, [1, 2])) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'غير مصرح لك بهذا الإجراء'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../config/db.php';

$query = trim($_GET['q'] ?? '');
if ($query === '') {
    echo json_encode([
        'success' => false,
        'message' => 'يرجى إدخال نص للبحث'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

global $conn;
if (!$conn || $conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'فشل الاتصال بقاعدة البيانات'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // استعلام مُطوَّر ومُخصَّص تمامًا لجدولك الحالي
    $sql = "
        SELECT 
            u.emp_id,
            u.username AS full_name,
            COALESCE(d.name_ar, d.name_en, 'غير محدد') AS department_name,
            COALESCE(s.name_ar, s.name_en, '') AS section_name,
            COALESCE(dv.name_ar, dv.name_en, '') AS division_name,
            COALESCE(r.name_en, 'User') AS position
        FROM users u
        LEFT JOIN Departments d ON u.department_id = d.department_id
        LEFT JOIN Sections s ON u.section_id = s.section_id
        LEFT JOIN Divisions dv ON u.division_id = dv.division_id
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE u.is_active = 1
          AND (
            u.emp_id LIKE ?
            OR u.username LIKE ?
            OR u.email LIKE ?
          )
        ORDER BY 
            CASE WHEN u.emp_id = ? THEN 0 ELSE 1 END,
            u.username ASC
        LIMIT 20
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("فشل تحضير الاستعلام: " . $conn->error);
    }

    $like = "%{$query}%";
    $exact = $query;

    $stmt->bind_param("ssss", $like, $like, $like, $exact);
    $stmt->execute();
    $result = $stmt->get_result();

    $employees = [];
    while ($row = $result->fetch_assoc()) {
        $employees[] = [
            'emp_id'      => $row['emp_id'],
            'full_name'   => $row['full_name'] ?: 'غير محدد',
            'department'  => $row['department_name'],
            'section'     => $row['section_name'],
            'division'    => $row['division_name'],
            'position'    => $row['position']
        ];
    }

    $stmt->close();

    echo json_encode([
        'success'    => true,
        'count'      => count($employees),
        'employees'  => $employees,
        'searched_for' => $query
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("خطأ في search_employees.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ تقني أثناء البحث'
    ], JSON_UNESCAPED_UNICODE);
}
?>
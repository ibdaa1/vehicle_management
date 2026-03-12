<?php
// vehicle_management/api/vehicle/get_vehicle_management.php
header('Content-Type: application/json; charset=utf-8');
session_start();

// تضمين إعدادات قاعدة البيانات
$dbPaths = [
    __DIR__ . '/../config/db.php',
    __DIR__ . '/../../config/db.php',
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
    echo json_encode(['success' => false, 'message' => 'DB config missing']);
    exit;
}

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'غير مسجل الدخول', 'isLoggedIn' => false]);
    exit;
}

// تحديد اللغة
$lang = (isset($_GET['lang']) && $_GET['lang'] === 'en') ? 'en' : 'ar';

try {
    $query = "
        SELECT 
            vm.id,
            vm.vehicle_code,
            vm.operation_type,
            vm.performed_by,
            vm.movement_datetime,
            vm.notes,
            v.status AS vehicle_status,

            -- اسم الموظف من جدول users (نستخدم username لأنه الوحيد الموجود)
            u.username AS employee_name,

            -- الإدارة والقسم والشعبة
            d.name_ar  AS department_name_ar,
            d.name_en  AS department_name_en,
            s.name_ar  AS section_name_ar,
            s.name_en  AS section_name_en,
            dv.name_ar AS division_name_ar,
            dv.name_en AS division_name_en
        FROM vehicle_movements vm
        LEFT JOIN vehicles v ON vm.vehicle_code = v.vehicle_code
        LEFT JOIN users u ON vm.performed_by = u.emp_id
        LEFT JOIN Departments d ON u.department_id = d.department_id
        LEFT JOIN Sections s ON u.section_id = s.section_id
        LEFT JOIN Divisions dv ON u.division_id = dv.division_id
        WHERE 1=1
    ";

    $params = [];
    $types = "";

    // فلاتر
    if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
        $query .= " AND DATE(vm.movement_datetime) BETWEEN ? AND ?";
        $params[] = $_GET['start_date'];
        $params[] = $_GET['end_date'];
        $types .= "ss";
    }
    if (!empty($_GET['operation_type'])) {
        $query .= " AND vm.operation_type = ?";
        $params[] = $_GET['operation_type'];
        $types .= "s";
    }
    if (!empty($_GET['vehicle_code'])) {
        $query .= " AND vm.vehicle_code LIKE ?";
        $params[] = "%" . $_GET['vehicle_code'] . "%";
        $types .= "s";
    }
    if (!empty($_GET['employee_id'])) {
        $query .= " AND vm.performed_by = ?";
        $params[] = $_GET['employee_id'];
        $types .= "s";
    }
    if (!empty($_GET['department_id'])) {
        $query .= " AND u.department_id = ?";
        $params[] = $_GET['department_id'];
        $types .= "i";
    }
    if (!empty($_GET['section_id'])) {
        $query .= " AND u.section_id = ?";
        $params[] = $_GET['section_id'];
        $types .= "i";
    }
    if (!empty($_GET['division_id'])) {
        $query .= " AND u.division_id = ?";
        $params[] = $_GET['division_id'];
        $types .= "i";
    }
    if (!empty($_GET['status'])) {
        $query .= " AND v.status = ?";
        $params[] = $_GET['status'];
        $types .= "s";
    }

    $query .= " ORDER BY vm.movement_datetime DESC, vm.id DESC";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $managements = [];
    while ($row = $result->fetch_assoc()) {
        // اسم الموظف: نستخدم username (لأنه المتوفر)
        $row['employee_name'] = $row['employee_name'] ?? ($lang === 'ar' ? 'غير محدد' : 'Not specified');

        // تحويل أسماء الإدارة والقسم والشعبة حسب اللغة
        $row['department_name'] = $lang === 'en'
            ? ($row['department_name_en'] ?? $row['department_name_ar'] ?? ($lang === 'ar' ? 'غير محدد' : 'Not specified'))
            : ($row['department_name_ar'] ?? $row['department_name_en'] ?? ($lang === 'ar' ? 'غير محدد' : 'Not specified'));

        $row['section_name'] = $lang === 'en'
            ? ($row['section_name_en'] ?? $row['section_name_ar'] ?? ($lang === 'ar' ? 'غير محدد' : 'Not specified'))
            : ($row['section_name_ar'] ?? $row['section_name_en'] ?? ($lang === 'ar' ? 'غير محدد' : 'Not specified'));

        $row['division_name'] = $lang === 'en'
            ? ($row['division_name_en'] ?? $row['division_name_ar'] ?? ($lang === 'ar' ? 'غير محدد' : 'Not specified'))
            : ($row['division_name_ar'] ?? $row['division_name_en'] ?? ($lang === 'ar' ? 'غير محدد' : 'Not specified'));

        // توافق مع الـ JS
        $row['management_datetime'] = $row['movement_datetime'];

        // حذف الحقول المؤقتة
        unset(
            $row['department_name_ar'], $row['department_name_en'],
            $row['section_name_ar'], $row['section_name_en'],
            $row['division_name_ar'], $row['division_name_en']
        );

        $managements[] = $row;
    }
    $stmt->close();

    // === الإحصائيات ===
    $statsQuery = "
        SELECT 
            COUNT(*) AS total_managements,
            SUM(CASE WHEN vm.operation_type = 'pickup' THEN 1 ELSE 0 END) AS pickup_count,
            SUM(CASE WHEN vm.operation_type = 'return' THEN 1 ELSE 0 END) AS return_count
        FROM vehicle_movements vm
        LEFT JOIN users u ON vm.performed_by = u.emp_id
        WHERE 1=1
    ";

    $statsParams = [];
    $statsTypes = "";

    if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
        $statsQuery .= " AND DATE(vm.movement_datetime) BETWEEN ? AND ?";
        $statsParams[] = $_GET['start_date'];
        $statsParams[] = $_GET['end_date'];
        $statsTypes .= "ss";
    }
    if (!empty($_GET['operation_type'])) {
        $statsQuery .= " AND vm.operation_type = ?";
        $statsParams[] = $_GET['operation_type'];
        $statsTypes .= "s";
    }
    if (!empty($_GET['department_id'])) {
        $statsQuery .= " AND u.department_id = ?";
        $statsParams[] = $_GET['department_id'];
        $statsTypes .= "i";
    }
    if (!empty($_GET['section_id'])) {
        $statsQuery .= " AND u.section_id = ?";
        $statsParams[] = $_GET['section_id'];
        $statsTypes .= "i";
    }
    if (!empty($_GET['division_id'])) {
        $statsQuery .= " AND u.division_id = ?";
        $statsParams[] = $_GET['division_id'];
        $statsTypes .= "i";
    }

    $statsStmt = $conn->prepare($statsQuery);
    if (!empty($statsParams)) {
        $statsStmt->bind_param($statsTypes, ...$statsParams);
    }
    $statsStmt->execute();
    $statsResult = $statsStmt->get_result();
    $statistics = $statsResult->fetch_assoc() ?: ['total_managements'=>0, 'pickup_count'=>0, 'return_count'=>0];
    $statsStmt->close();

    // مركبات خارج الخدمة
    $pendingResult = $conn->query("SELECT COUNT(*) AS pending_vehicles FROM vehicles WHERE status = 'out_of_service'");
    $pendingRow = $pendingResult->fetch_assoc();
    $statistics['pending_vehicles'] = (int)($pendingRow['pending_vehicles'] ?? 0);

    // الرد
    echo json_encode([
        'success' => true,
        'managements' => $managements,
        'statistics' => $statistics,
        'total_count' => count($managements),
        'message' => $lang === 'ar' ? 'تم جلب البيانات بنجاح' : 'Data loaded successfully'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("get_vehicle_management.php Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $lang === 'ar' ? 'حدث خطأ في جلب البيانات' : 'Error loading data',
        'debug' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
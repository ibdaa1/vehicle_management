<?php
// vehicle_management/api/vehicle/get_vehicle_management.php
header('Content-Type: application/json; charset=utf-8');
session_start();

// تضمين إعدادات قاعدة البيانات
require_once __DIR__ . '/../config/db.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'غير مسجل الدخول', 'isLoggedIn' => false]);
    exit;
}

// تحديد اللغة
$lang = (isset($_GET['lang']) && $_GET['lang'] === 'en') ? 'en' : 'ar';

try {
    // ===== استعلام لجلب السيارات التي تحتاج تسليم =====
    $pendingVehiclesQuery = "
        SELECT DISTINCT
            v.vehicle_code,
            v.type,
            v.manufacture_year,
            v.driver_name,
            v.driver_phone,
            v.status,
            v.vehicle_mode,
            v.department_id AS vehicle_department_id,
            v.section_id AS vehicle_section_id,
            v.division_id AS vehicle_division_id,
            vm.performed_by AS last_employee_id,
            u.username AS last_employee_name,
            vm.movement_datetime AS last_pickup_date,
            d.name_ar AS department_name_ar,
            d.name_en AS department_name_en,
            s.name_ar AS section_name_ar,
            s.name_en AS section_name_en,
            dv.name_ar AS division_name_ar,
            dv.name_en AS division_name_en
        FROM vehicles v
        INNER JOIN vehicle_movements vm ON v.vehicle_code = vm.vehicle_code
        LEFT JOIN users u ON vm.performed_by = u.emp_id
        LEFT JOIN Departments d ON u.department_id = d.department_id
        LEFT JOIN Sections s ON u.section_id = s.section_id
        LEFT JOIN Divisions dv ON u.division_id = dv.division_id
        WHERE vm.operation_type = 'pickup'
        AND vm.movement_datetime = (
            SELECT MAX(movement_datetime) 
            FROM vehicle_movements 
            WHERE vehicle_code = v.vehicle_code 
            AND operation_type = 'pickup'
        )
        AND NOT EXISTS (
            SELECT 1 FROM vehicle_movements vm2 
            WHERE vm2.vehicle_code = v.vehicle_code 
            AND vm2.operation_type = 'return' 
            AND vm2.movement_datetime > vm.movement_datetime
        )
        AND 1=1
    ";

    $pendingParams = [];
    $pendingTypes = "";

    // تطبيق الفلاتر على السيارات المتعثرة
    if (!empty($_GET['vehicle_code'])) {
        $pendingVehiclesQuery .= " AND v.vehicle_code LIKE ?";
        $pendingParams[] = "%" . $_GET['vehicle_code'] . "%";
        $pendingTypes .= "s";
    }
    if (!empty($_GET['employee_id'])) {
        $pendingVehiclesQuery .= " AND vm.performed_by = ?";
        $pendingParams[] = $_GET['employee_id'];
        $pendingTypes .= "s";
    }
    if (!empty($_GET['department_id'])) {
        $pendingVehiclesQuery .= " AND (u.department_id = ? OR v.department_id = ?)";
        $pendingParams[] = $_GET['department_id'];
        $pendingParams[] = $_GET['department_id'];
        $pendingTypes .= "ii";
    }
    if (!empty($_GET['section_id'])) {
        $pendingVehiclesQuery .= " AND (u.section_id = ? OR v.section_id = ?)";
        $pendingParams[] = $_GET['section_id'];
        $pendingParams[] = $_GET['section_id'];
        $pendingTypes .= "ii";
    }
    if (!empty($_GET['division_id'])) {
        $pendingVehiclesQuery .= " AND (u.division_id = ? OR v.division_id = ?)";
        $pendingParams[] = $_GET['division_id'];
        $pendingParams[] = $_GET['division_id'];
        $pendingTypes .= "ii";
    }
    if (!empty($_GET['status'])) {
        $pendingVehiclesQuery .= " AND v.status = ?";
        $pendingParams[] = $_GET['status'];
        $pendingTypes .= "s";
    }
    if (!empty($_GET['vehicle_mode'])) {
        $pendingVehiclesQuery .= " AND v.vehicle_mode = ?";
        $pendingParams[] = $_GET['vehicle_mode'];
        $pendingTypes .= "s";
    }

    $pendingStmt = $conn->prepare($pendingVehiclesQuery);
    if (!empty($pendingParams)) {
        $pendingStmt->bind_param($pendingTypes, ...$pendingParams);
    }
    $pendingStmt->execute();
    $pendingResult = $pendingStmt->get_result();
    $pendingVehicles = [];
    while ($row = $pendingResult->fetch_assoc()) {
        // تحويل الأسماء حسب اللغة
        $row['department_name'] = $lang === 'en' 
            ? ($row['department_name_en'] ?? $row['department_name_ar'] ?? ($lang === 'ar' ? 'غير محدد' : 'Not specified'))
            : ($row['department_name_ar'] ?? $row['department_name_en'] ?? ($lang === 'ar' ? 'غير محدد' : 'Not specified'));
        
        $row['section_name'] = $lang === 'en'
            ? ($row['section_name_en'] ?? $row['section_name_ar'] ?? ($lang === 'ar' ? 'غير محدد' : 'Not specified'))
            : ($row['section_name_ar'] ?? $row['section_name_en'] ?? ($lang === 'ar' ? 'غير محدد' : 'Not specified'));
        
        $row['division_name'] = $lang === 'en'
            ? ($row['division_name_en'] ?? $row['division_name_ar'] ?? ($lang === 'ar' ? 'غير محدد' : 'Not specified'))
            : ($row['division_name_ar'] ?? $row['division_name_en'] ?? ($lang === 'ar' ? 'غير محدد' : 'Not specified'));
        
        $pendingVehicles[] = $row;
    }
    $pendingStmt->close();

    // ===== استعلام حركات المركبات الرئيسي =====
    $query = "
        SELECT 
            vm.id,
            vm.vehicle_code,
            vm.operation_type,
            vm.performed_by,
            vm.movement_datetime,
            vm.notes,
            vm.latitude,
            vm.longitude,
            v.type AS vehicle_type,
            v.manufacture_year,
            v.driver_name,
            v.driver_phone,
            v.status AS vehicle_status,
            v.vehicle_mode,
            v.department_id AS vehicle_department_id,
            v.section_id AS vehicle_section_id,
            v.division_id AS vehicle_division_id,

            -- اسم الموظف
            u.username AS employee_name,

            -- الإدارة والقسم والشعبة
            d.name_ar  AS department_name_ar,
            d.name_en  AS department_name_en,
            s.name_ar  AS section_name_ar,
            s.name_en  AS section_name_en,
            dv.name_ar AS division_name_ar,
            dv.name_en AS division_name_en,

            -- صور الحركة
            GROUP_CONCAT(DISTINCT vmp.photo_url SEPARATOR '||') as photos

        FROM vehicle_movements vm
        LEFT JOIN vehicles v ON vm.vehicle_code = v.vehicle_code
        LEFT JOIN users u ON vm.performed_by = u.emp_id
        LEFT JOIN Departments d ON u.department_id = d.department_id
        LEFT JOIN Sections s ON u.section_id = s.section_id
        LEFT JOIN Divisions dv ON u.division_id = dv.division_id
        LEFT JOIN vehicle_movement_photos vmp ON vm.id = vmp.movement_id
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
        $query .= " AND (u.department_id = ? OR v.department_id = ?)";
        $params[] = $_GET['department_id'];
        $params[] = $_GET['department_id'];
        $types .= "ii";
    }
    if (!empty($_GET['section_id'])) {
        $query .= " AND (u.section_id = ? OR v.section_id = ?)";
        $params[] = $_GET['section_id'];
        $params[] = $_GET['section_id'];
        $types .= "ii";
    }
    if (!empty($_GET['division_id'])) {
        $query .= " AND (u.division_id = ? OR v.division_id = ?)";
        $params[] = $_GET['division_id'];
        $params[] = $_GET['division_id'];
        $types .= "ii";
    }
    if (!empty($_GET['status'])) {
        $query .= " AND v.status = ?";
        $params[] = $_GET['status'];
        $types .= "s";
    }
    if (!empty($_GET['vehicle_mode'])) {
        $query .= " AND v.vehicle_mode = ?";
        $params[] = $_GET['vehicle_mode'];
        $types .= "s";
    }

    $query .= " GROUP BY vm.id ORDER BY vm.movement_datetime DESC, vm.id DESC";

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
        // معالجة الصور
        $row['photos_array'] = [];
        if (!empty($row['photos'])) {
            $row['photos_array'] = explode('||', $row['photos']);
        }
        unset($row['photos']);

        // اسم الموظف
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

        $managements[] = $row;
    }
    $stmt->close();

    // ===== الإحصائيات الرئيسية =====
    $statsQuery = "
        SELECT 
            COUNT(*) AS total_managements,
            SUM(CASE WHEN vm.operation_type = 'pickup' THEN 1 ELSE 0 END) AS pickup_count,
            SUM(CASE WHEN vm.operation_type = 'return' THEN 1 ELSE 0 END) AS return_count
        FROM vehicle_movements vm
        LEFT JOIN users u ON vm.performed_by = u.emp_id
        LEFT JOIN vehicles v ON vm.vehicle_code = v.vehicle_code
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
    if (!empty($_GET['vehicle_code'])) {
        $statsQuery .= " AND vm.vehicle_code LIKE ?";
        $statsParams[] = "%" . $_GET['vehicle_code'] . "%";
        $statsTypes .= "s";
    }
    if (!empty($_GET['employee_id'])) {
        $statsQuery .= " AND vm.performed_by = ?";
        $statsParams[] = $_GET['employee_id'];
        $statsTypes .= "s";
    }
    if (!empty($_GET['department_id'])) {
        $statsQuery .= " AND (u.department_id = ? OR v.department_id = ?)";
        $statsParams[] = $_GET['department_id'];
        $statsParams[] = $_GET['department_id'];
        $statsTypes .= "ii";
    }
    if (!empty($_GET['section_id'])) {
        $statsQuery .= " AND (u.section_id = ? OR v.section_id = ?)";
        $statsParams[] = $_GET['section_id'];
        $statsParams[] = $_GET['section_id'];
        $statsTypes .= "ii";
    }
    if (!empty($_GET['division_id'])) {
        $statsQuery .= " AND (u.division_id = ? OR v.division_id = ?)";
        $statsParams[] = $_GET['division_id'];
        $statsParams[] = $_GET['division_id'];
        $statsTypes .= "ii";
    }
    if (!empty($_GET['status'])) {
        $statsQuery .= " AND v.status = ?";
        $statsParams[] = $_GET['status'];
        $statsTypes .= "s";
    }
    if (!empty($_GET['vehicle_mode'])) {
        $statsQuery .= " AND v.vehicle_mode = ?";
        $statsParams[] = $_GET['vehicle_mode'];
        $statsTypes .= "s";
    }

    $statsStmt = $conn->prepare($statsQuery);
    if (!empty($statsParams)) {
        $statsStmt->bind_param($statsTypes, ...$statsParams);
    }
    $statsStmt->execute();
    $statsResult = $statsStmt->get_result();
    $statistics = $statsResult->fetch_assoc() ?: ['total_managements'=>0, 'pickup_count'=>0, 'return_count'=>0];
    $statsStmt->close();

    // ===== السيارات المستخدمة =====
    $usedVehiclesQuery = "
        SELECT COUNT(DISTINCT vm.vehicle_code) as used_count,
               SUM(CASE WHEN v.vehicle_mode = 'private' THEN 1 ELSE 0 END) as used_private_count,
               SUM(CASE WHEN v.vehicle_mode = 'shift' THEN 1 ELSE 0 END) as used_shift_count
        FROM vehicle_movements vm
        LEFT JOIN vehicles v ON vm.vehicle_code = v.vehicle_code
        LEFT JOIN users u ON vm.performed_by = u.emp_id
        WHERE 1=1
    ";
    
    $usedParams = [];
    $usedTypes = "";
    
    if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
        $usedVehiclesQuery .= " AND DATE(vm.movement_datetime) BETWEEN ? AND ?";
        $usedParams[] = $_GET['start_date'];
        $usedParams[] = $_GET['end_date'];
        $usedTypes .= "ss";
    }
    if (!empty($_GET['vehicle_code'])) {
        $usedVehiclesQuery .= " AND vm.vehicle_code LIKE ?";
        $usedParams[] = "%" . $_GET['vehicle_code'] . "%";
        $usedTypes .= "s";
    }
    if (!empty($_GET['employee_id'])) {
        $usedVehiclesQuery .= " AND vm.performed_by = ?";
        $usedParams[] = $_GET['employee_id'];
        $usedTypes .= "s";
    }
    if (!empty($_GET['department_id'])) {
        $usedVehiclesQuery .= " AND (u.department_id = ? OR v.department_id = ?)";
        $usedParams[] = $_GET['department_id'];
        $usedParams[] = $_GET['department_id'];
        $usedTypes .= "ii";
    }
    if (!empty($_GET['section_id'])) {
        $usedVehiclesQuery .= " AND (u.section_id = ? OR v.section_id = ?)";
        $usedParams[] = $_GET['section_id'];
        $usedParams[] = $_GET['section_id'];
        $usedTypes .= "ii";
    }
    if (!empty($_GET['division_id'])) {
        $usedVehiclesQuery .= " AND (u.division_id = ? OR v.division_id = ?)";
        $usedParams[] = $_GET['division_id'];
        $usedParams[] = $_GET['division_id'];
        $usedTypes .= "ii";
    }
    if (!empty($_GET['status'])) {
        $usedVehiclesQuery .= " AND v.status = ?";
        $usedParams[] = $_GET['status'];
        $usedTypes .= "s";
    }
    if (!empty($_GET['vehicle_mode'])) {
        $usedVehiclesQuery .= " AND v.vehicle_mode = ?";
        $usedParams[] = $_GET['vehicle_mode'];
        $usedTypes .= "s";
    }
    
    $usedStmt = $conn->prepare($usedVehiclesQuery);
    if (!empty($usedParams)) {
        $usedStmt->bind_param($usedTypes, ...$usedParams);
    }
    $usedStmt->execute();
    $usedResult = $usedStmt->get_result();
    $usedRow = $usedResult->fetch_assoc();
    $usedVehiclesCount = $usedRow['used_count'] ?? 0;
    $usedPrivateCount = $usedRow['used_private_count'] ?? 0;
    $usedShiftCount = $usedRow['used_shift_count'] ?? 0;
    $usedStmt->close();

    // ===== السيارات غير المستخدمة =====
    $unusedVehiclesQuery = "
        SELECT COUNT(DISTINCT v.vehicle_code) as unused_count,
               SUM(CASE WHEN v.vehicle_mode = 'private' THEN 1 ELSE 0 END) as unused_private_count,
               SUM(CASE WHEN v.vehicle_mode = 'shift' THEN 1 ELSE 0 END) as unused_shift_count
        FROM vehicles v
        WHERE NOT EXISTS (
            SELECT 1 FROM vehicle_movements vm 
            WHERE vm.vehicle_code = v.vehicle_code
            AND 1=1
    ";
    
    $unusedParams = [];
    $unusedTypes = "";
    
    if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
        $unusedVehiclesQuery .= " AND DATE(vm.movement_datetime) BETWEEN ? AND ?";
        $unusedParams[] = $_GET['start_date'];
        $unusedParams[] = $_GET['end_date'];
        $unusedTypes .= "ss";
    }
    
    $unusedVehiclesQuery .= ") AND 1=1";
    
    // شروط الفلاتر للسيارات
    if (!empty($_GET['vehicle_code'])) {
        $unusedVehiclesQuery .= " AND v.vehicle_code LIKE ?";
        $unusedParams[] = "%" . $_GET['vehicle_code'] . "%";
        $unusedTypes .= "s";
    }
    if (!empty($_GET['department_id'])) {
        $unusedVehiclesQuery .= " AND v.department_id = ?";
        $unusedParams[] = $_GET['department_id'];
        $unusedTypes .= "i";
    }
    if (!empty($_GET['section_id'])) {
        $unusedVehiclesQuery .= " AND v.section_id = ?";
        $unusedParams[] = $_GET['section_id'];
        $unusedTypes .= "i";
    }
    if (!empty($_GET['division_id'])) {
        $unusedVehiclesQuery .= " AND v.division_id = ?";
        $unusedParams[] = $_GET['division_id'];
        $unusedTypes .= "i";
    }
    if (!empty($_GET['status'])) {
        $unusedVehiclesQuery .= " AND v.status = ?";
        $unusedParams[] = $_GET['status'];
        $unusedTypes .= "s";
    }
    if (!empty($_GET['vehicle_mode'])) {
        $unusedVehiclesQuery .= " AND v.vehicle_mode = ?";
        $unusedParams[] = $_GET['vehicle_mode'];
        $unusedTypes .= "s";
    }
    
    $unusedStmt = $conn->prepare($unusedVehiclesQuery);
    if (!empty($unusedParams)) {
        $unusedStmt->bind_param($unusedTypes, ...$unusedParams);
    }
    $unusedStmt->execute();
    $unusedResult = $unusedStmt->get_result();
    $unusedRow = $unusedResult->fetch_assoc();
    $unusedVehiclesCount = $unusedRow['unused_count'] ?? 0;
    $unusedPrivateCount = $unusedRow['unused_private_count'] ?? 0;
    $unusedShiftCount = $unusedRow['unused_shift_count'] ?? 0;
    $unusedStmt->close();

    // ===== إحصائيات السيارات =====
    $allVehiclesQuery = "
        SELECT 
            COUNT(*) as total_vehicles,
            SUM(CASE WHEN vehicle_mode = 'private' THEN 1 ELSE 0 END) as total_private,
            SUM(CASE WHEN vehicle_mode = 'shift' THEN 1 ELSE 0 END) as total_shift,
            SUM(CASE WHEN status = 'operational' THEN 1 ELSE 0 END) as operational_vehicles,
            SUM(CASE WHEN status = 'out_of_service' THEN 1 ELSE 0 END) as out_of_service_vehicles,
            SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_vehicles
        FROM vehicles v
        WHERE 1=1
    ";
    
    $allVehiclesParams = [];
    $allVehiclesTypes = "";
    
    if (!empty($_GET['vehicle_code'])) {
        $allVehiclesQuery .= " AND v.vehicle_code LIKE ?";
        $allVehiclesParams[] = "%" . $_GET['vehicle_code'] . "%";
        $allVehiclesTypes .= "s";
    }
    if (!empty($_GET['department_id'])) {
        $allVehiclesQuery .= " AND v.department_id = ?";
        $allVehiclesParams[] = $_GET['department_id'];
        $allVehiclesTypes .= "i";
    }
    if (!empty($_GET['section_id'])) {
        $allVehiclesQuery .= " AND v.section_id = ?";
        $allVehiclesParams[] = $_GET['section_id'];
        $allVehiclesTypes .= "i";
    }
    if (!empty($_GET['division_id'])) {
        $allVehiclesQuery .= " AND v.division_id = ?";
        $allVehiclesParams[] = $_GET['division_id'];
        $allVehiclesTypes .= "i";
    }
    if (!empty($_GET['status'])) {
        $allVehiclesQuery .= " AND v.status = ?";
        $allVehiclesParams[] = $_GET['status'];
        $allVehiclesTypes .= "s";
    }
    if (!empty($_GET['vehicle_mode'])) {
        $allVehiclesQuery .= " AND v.vehicle_mode = ?";
        $allVehiclesParams[] = $_GET['vehicle_mode'];
        $allVehiclesTypes .= "s";
    }
    
    $allVehiclesStmt = $conn->prepare($allVehiclesQuery);
    if (!empty($allVehiclesParams)) {
        $allVehiclesStmt->bind_param($allVehiclesTypes, ...$allVehiclesParams);
    }
    $allVehiclesStmt->execute();
    $allVehiclesResult = $allVehiclesStmt->get_result();
    $allVehiclesRow = $allVehiclesResult->fetch_assoc();
    $allVehiclesStmt->close();

    // ===== جمع جميع الإحصائيات =====
    $statistics['pending_vehicles'] = count($pendingVehicles);
    $statistics['used_vehicles'] = $usedVehiclesCount;
    $statistics['unused_vehicles'] = $unusedVehiclesCount;
    
    // السيارات الخاصة والورديات
    $statistics['used_private_vehicles'] = $usedPrivateCount;
    $statistics['used_shift_vehicles'] = $usedShiftCount;
    $statistics['unused_private_vehicles'] = $unusedPrivateCount;
    $statistics['unused_shift_vehicles'] = $unusedShiftCount;
    
    // إجمالي السيارات
    $statistics['total_private_vehicles'] = ($allVehiclesRow['total_private'] ?? 0);
    $statistics['total_shift_vehicles'] = ($allVehiclesRow['total_shift'] ?? 0);
    $statistics['total_vehicles'] = ($allVehiclesRow['total_vehicles'] ?? 0);
    $statistics['operational_vehicles'] = ($allVehiclesRow['operational_vehicles'] ?? 0);
    $statistics['out_of_service_vehicles'] = ($allVehiclesRow['out_of_service_vehicles'] ?? 0);
    $statistics['maintenance_vehicles'] = ($allVehiclesRow['maintenance_vehicles'] ?? 0);

    // الرد
    echo json_encode([
        'success' => true,
        'managements' => $managements,
        'pending_vehicles_list' => $pendingVehicles,
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
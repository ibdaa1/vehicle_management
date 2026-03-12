<?php
// vehicle_management/api/vehicle/get_movement.php
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
    echo json_encode([
        'success' => false,
        'message' => 'غير مسجل الدخول',
        'isLoggedIn' => false
    ]);
    exit;
}

// تحديد اللغة
$lang = (isset($_GET['lang']) && $_GET['lang'] === 'en') ? 'en' : 'ar';

// التحقق من وجود ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => $lang === 'ar' ? 'معرف الحركة مطلوب' : 'Movement ID is required'
    ]);
    exit;
}

$movement_id = intval($_GET['id']);
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$photos_base_url = $base_url . '/vehicle_management/uploads/vehicle_movements/';

try {
    // اكتشاف أعمدة جدول users لتحديد اسم الموظف بذكاء
    $user_columns = [];
    $col_res = $conn->query("SHOW COLUMNS FROM users");
    while ($c = $col_res->fetch_assoc()) {
        $user_columns[] = $c['Field'];
    }

    $employee_name_sql = "u.username AS employee_display_name";
    if (in_array('display_name', $user_columns)) {
        $employee_name_sql = "COALESCE(u.display_name, u.username) AS employee_display_name";
    } elseif (in_array('full_name', $user_columns)) {
        $employee_name_sql = "COALESCE(u.full_name, u.username) AS employee_display_name";
    } elseif (in_array('first_name', $user_columns) && in_array('last_name', $user_columns)) {
        $employee_name_sql = "CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) AS employee_display_name";
    }

    $query = "
        SELECT
            vm.id,
            vm.vehicle_code,
            vm.operation_type,
            vm.performed_by,
            DATE_FORMAT(vm.movement_datetime, '%Y-%m-%d %H:%i:%s') AS movement_datetime,
            vm.notes,
            vm.latitude,
            vm.longitude,
            v.status AS vehicle_status,

            -- اسم الموظف (آمن 100%)
            $employee_name_sql,
            u.username AS employee_username,
            u.emp_id,

            -- الإدارة والقسم والشعبة
            d.department_id,
            COALESCE(d.name_ar, d.name_en) AS department_name_ar,
            COALESCE(d.name_en, d.name_ar) AS department_name_en,
            s.section_id,
            COALESCE(s.name_ar, s.name_en) AS section_name_ar,
            COALESCE(s.name_en, s.name_ar) AS section_name_en,
            dv.division_id,
            COALESCE(dv.name_ar, dv.name_en) AS division_name_ar,
            COALESCE(dv.name_en, dv.name_ar) AS division_name_en,

            -- الصور
            GROUP_CONCAT(vmp.photo_url SEPARATOR '|') AS raw_photos

        FROM vehicle_movements vm
        LEFT JOIN vehicles v ON vm.vehicle_code = v.vehicle_code
        LEFT JOIN users u ON vm.performed_by = u.emp_id
        LEFT JOIN Departments d ON u.department_id = d.department_id
        LEFT JOIN Sections s ON u.section_id = s.section_id
        LEFT JOIN Divisions dv ON u.division_id = dv.division_id
        LEFT JOIN vehicle_movement_photos vmp ON vm.id = vmp.movement_id
        WHERE vm.id = ?
        GROUP BY vm.id
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $movement_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => $lang === 'ar' ? 'لم يتم العثور على الحركة' : 'Movement not found'
        ]);
        exit;
    }

    $row = $result->fetch_assoc();
    $stmt->close();

    // تنظيف اسم الموظف
    if (empty(trim($row['employee_display_name'] ?? ''))) {
        $row['employee_display_name'] = $row['employee_username'] ?? ($lang === 'ar' ? 'غير محدد' : 'Not specified');
    }

    // تحديد أسماء الإدارات حسب اللغة
    $row['department_name'] = $lang === 'en' 
        ? ($row['department_name_en'] ?? $row['department_name_ar'] ?? 'Not specified')
        : ($row['department_name_ar'] ?? $row['department_name_en'] ?? 'غير محدد');

    $row['section_name'] = $lang === 'en'
        ? ($row['section_name_en'] ?? $row['section_name_ar'] ?? 'Not specified')
        : ($row['section_name_ar'] ?? $row['section_name_en'] ?? 'غير محدد');

    $row['division_name'] = $lang === 'en'
        ? ($row['division_name_en'] ?? $row['division_name_ar'] ?? 'Not specified')
        : ($row['division_name_ar'] ?? $row['division_name_en'] ?? 'غير محدد');

    // معالجة الصور
    $photos = [];
    if (!empty($row['raw_photos'])) {
        $urls = array_filter(explode('|', $row['raw_photos']));
        foreach ($urls as $url) {
            $filename = trim($url);
            if ($filename) {
                $photos[] = [
                    'url' => $photos_base_url . $filename,
                    'filename' => $filename,
                    'taken_at' => date('Y-m-d H:i') // يمكن تحسينه لاحقاً
                ];
            }
        }
    }
    $row['photos'] = $photos;
    $row['photos_count'] = count($photos);

    // نصوص مترجمة
    $row['status_text'] = $row['vehicle_status'] === 'operational'
        ? ($lang === 'ar' ? 'تشغيلية' : 'Operational')
        : ($lang === 'ar' ? 'خارج الخدمة' : 'Out of Service');

    $row['operation_type_text'] = $row['operation_type'] === 'pickup'
        ? ($lang === 'ar' ? 'استلام' : 'Pickup')
        : ($lang === 'ar' ? 'إرجاع' : 'Return');

    // حذف الحقول المؤقتة
    unset(
        $row['raw_photos'],
        $row['department_name_ar'], $row['department_name_en'],
        $row['section_name_ar'], $row['section_name_en'],
        $row['division_name_ar'], $row['division_name_en'],
        $row['employee_username']
    );

    // اقتراح العملية التالية
    $last_stmt = $conn->prepare("
        SELECT operation_type FROM vehicle_movements 
        WHERE vehicle_code = ? AND id != ? 
        ORDER BY movement_datetime DESC, id DESC LIMIT 1
    ");
    $last_stmt->bind_param("si", $row['vehicle_code'], $movement_id);
    $last_stmt->execute();
    $last_res = $last_stmt->get_result();
    $suggested = 'pickup';
    if ($last_res->num_rows > 0) {
        $last = $last_res->fetch_assoc();
        $suggested = ($last['operation_type'] === 'pickup') ? 'return' : 'pickup';
    }
    $last_stmt->close();

    echo json_encode([
        'success' => true,
        'movement' => $row,
        'suggested_operation' => $suggested,
        'photos_base_url' => $photos_base_url,
        'message' => $lang === 'ar' ? 'تم جلب بيانات الحركة بنجاح' : 'Movement loaded successfully'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("get_movement.php Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $lang === 'ar' ? 'حدث خطأ' : 'Error occurred',
        'debug' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
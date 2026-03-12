<?php
// /vehicle_management/api/vehicle/get_violation_responsible.php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

session_start();
require_once __DIR__ . '/../../../config/database.php';

// التحقق من الجلسة
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'غير مصرح. يرجى تسجيل الدخول'
    ]);
    exit;
}

try {
    $conn = getConnection();
    
    // الحصول على البيانات المرسلة
    $violation_id = $_GET['violation_id'] ?? null;
    $vehicle_code = $_GET['vehicle_code'] ?? null;
    $violation_datetime = $_GET['violation_datetime'] ?? null;
    
    // التحقق من البيانات المطلوبة
    if (!$violation_id && (!$vehicle_code || !$violation_datetime)) {
        echo json_encode([
            'success' => false,
            'message' => 'يرجى توفير إما معرف المخالفة أو كود المركبة ووقت المخالفة'
        ]);
        exit;
    }
    
    // إذا تم توفير معرف المخالفة، جلب بياناتها أولاً
    if ($violation_id) {
        $stmt = $conn->prepare("
            SELECT vehicle_code, violation_datetime 
            FROM vehicle_violations 
            WHERE id = ?
        ");
        $stmt->execute([$violation_id]);
        $violation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$violation) {
            echo json_encode([
                'success' => false,
                'message' => 'المخالفة غير موجودة'
            ]);
            exit;
        }
        
        $vehicle_code = $violation['vehicle_code'];
        $violation_datetime = $violation['violation_datetime'];
    }
    
    // 1. البحث عن آخر حركة استلام قبل وقت المخالفة
    $query = "
        SELECT vm.*, 
               u.emp_id, 
               u.username, 
               u.email, 
               u.phone,
               u.department_id,
               u.section_id,
               u.division_id
        FROM vehicle_movements vm
        LEFT JOIN users u ON vm.performed_by = u.emp_id
        WHERE vm.vehicle_code = ?
          AND vm.operation_type = 'pickup'
          AND vm.movement_datetime <= ?
        ORDER BY vm.movement_datetime DESC
        LIMIT 1
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$vehicle_code, $violation_datetime]);
    $last_pickup = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$last_pickup) {
        echo json_encode([
            'success' => true,
            'data' => null,
            'message' => 'لم يتم العثور على أي استلام للمركبة قبل وقت المخالفة',
            'vehicle_code' => $vehicle_code,
            'violation_datetime' => $violation_datetime
        ]);
        exit;
    }
    
    $pickup_datetime = $last_pickup['movement_datetime'];
    
    // 2. التحقق مما إذا كانت هناك حركة إرجاع بين وقت الاستلام ووقت المخالفة
    $query = "
        SELECT COUNT(*) as return_count
        FROM vehicle_movements
        WHERE vehicle_code = ?
          AND operation_type = 'return'
          AND movement_datetime > ?
          AND movement_datetime <= ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$vehicle_code, $pickup_datetime, $violation_datetime]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $has_return = $result['return_count'] > 0;
    
    if ($has_return) {
        // إذا كان هناك إرجاع، نبحث عن آخر استلام بعد هذا الإرجاع وقبل المخالفة
        $query = "
            SELECT vm.*, 
                   u.emp_id, 
                   u.username, 
                   u.email, 
                   u.phone,
                   u.department_id,
                   u.section_id,
                   u.division_id
            FROM vehicle_movements vm
            LEFT JOIN users u ON vm.performed_by = u.emp_id
            WHERE vm.vehicle_code = ?
              AND vm.operation_type = 'pickup'
              AND vm.movement_datetime > (
                  SELECT MAX(movement_datetime)
                  FROM vehicle_movements
                  WHERE vehicle_code = ?
                    AND operation_type = 'return'
                    AND movement_datetime <= ?
              )
              AND vm.movement_datetime <= ?
            ORDER BY vm.movement_datetime DESC
            LIMIT 1
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            $vehicle_code,
            $vehicle_code,
            $violation_datetime,
            $violation_datetime
        ]);
        $last_pickup = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$last_pickup) {
            echo json_encode([
                'success' => true,
                'data' => null,
                'message' => 'المركبة كانت معادة قبل وقت المخالفة ولا يوجد مستخدم مسؤول',
                'vehicle_code' => $vehicle_code,
                'violation_datetime' => $violation_datetime
            ]);
            exit;
        }
    }
    
    // 3. جلب معلومات إضافية عن المستخدم
    $user_info = [];
    if ($last_pickup['emp_id']) {
        $query = "
            SELECT 
                u.emp_id,
                u.username,
                u.email,
                u.phone,
                u.preferred_language,
                d.name_ar as department_name_ar,
                d.name_en as department_name_en,
                s.name_ar as section_name_ar,
                s.name_en as section_name_en,
                dv.name_ar as division_name_ar,
                dv.name_en as division_name_en
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.id
            LEFT JOIN sections s ON u.section_id = s.id
            LEFT JOIN divisions dv ON u.division_id = dv.id
            WHERE u.emp_id = ?
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$last_pickup['emp_id']]);
        $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // 4. جلب معلومات المركبة
    $vehicle_query = "
        SELECT 
            v.id,
            v.vehicle_code,
            v.type,
            v.manufacture_year,
            v.driver_name,
            v.driver_phone,
            v.status,
            v.vehicle_mode,
            d.name_ar as department_name_ar,
            d.name_en as department_name_en,
            s.name_ar as section_name_ar,
            s.name_en as section_name_en,
            dv.name_ar as division_name_ar,
            dv.name_en as division_name_en
        FROM vehicles v
        LEFT JOIN departments d ON v.department_id = d.id
        LEFT JOIN sections s ON v.section_id = s.id
        LEFT JOIN divisions dv ON v.division_id = dv.id
        WHERE v.vehicle_code = ?
    ";
    
    $stmt = $conn->prepare($vehicle_query);
    $stmt->execute([$vehicle_code]);
    $vehicle_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 5. جلب معلومات المخالفة إذا كان هناك violation_id
    $violation_info = [];
    if ($violation_id) {
        $query = "
            SELECT 
                vv.*,
                u1.username as issued_by_name,
                u2.username as paid_by_name
            FROM vehicle_violations vv
            LEFT JOIN users u1 ON vv.issued_by_emp_id = u1.emp_id
            LEFT JOIN users u2 ON vv.paid_by_emp_id = u2.emp_id
            WHERE vv.id = ?
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$violation_id]);
        $violation_info = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // 6. تجميع النتائج
    $response = [
        'success' => true,
        'data' => [
            'responsible_user' => [
                'movement_id' => $last_pickup['id'],
                'emp_id' => $last_pickup['emp_id'],
                'username' => $last_pickup['username'],
                'email' => $last_pickup['email'],
                'phone' => $last_pickup['phone'],
                'pickup_datetime' => $last_pickup['movement_datetime'],
                'notes' => $last_pickup['notes'],
                'latitude' => $last_pickup['latitude'],
                'longitude' => $last_pickup['longitude'],
                'additional_info' => $user_info
            ],
            'vehicle' => $vehicle_info,
            'violation' => $violation_info,
            'violation_datetime' => $violation_datetime,
            'time_analysis' => [
                'last_pickup_before_violation' => $pickup_datetime,
                'has_return_between' => $has_return,
                'time_difference_minutes' => $has_return ? 
                    round((strtotime($violation_datetime) - strtotime($last_pickup['movement_datetime'])) / 60, 2) : 
                    round((strtotime($violation_datetime) - strtotime($pickup_datetime)) / 60, 2)
            ]
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    error_log("Database error in get_violation_responsible.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error in get_violation_responsible.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ: ' . $e->getMessage()
    ]);
}
<?php
// vehicle_management/api/vehicle/get_vehicle_movements_by_vehicle.php
// جلب حركات المركبة + الصور المرتبطة بكل حركة
header('Content-Type: application/json; charset=utf-8');
session_start();

$paths = [__DIR__.'/../config/db.php', __DIR__.'/../../config/db.php', __DIR__.'/../../../config/db.php'];
foreach ($paths as $p) { if (file_exists($p)) { require_once $p; break; } }
if (!isset($conn)) { echo json_encode(['success'=>false,'message'=>'DB error']); exit; }

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user']) && !isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'message'=>'غير مسجل الدخول']); exit;
}

$lang = (isset($_GET['lang']) && $_GET['lang'] === 'en') ? 'en' : 'ar';
if (!isset($_GET['vehicle_code']) || empty(trim($_GET['vehicle_code']))) {
    echo json_encode(['success'=>false,'message'=>'رمز المركبة مطلوب']); exit;
}

$vehicle_code = trim($_GET['vehicle_code']);
$base_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$photos_base_path = $base_url . '/vehicle_management/uploads/vehicle_movements/';

try {
    // اكتشاف أعمدة users
    $userCols = [];
    $res = $conn->query("SHOW COLUMNS FROM users");
    while ($c = $res->fetch_assoc()) $userCols[] = $c['Field'];

    $displayNameExpr = "u.username";
    if (in_array('display_name', $userCols)) {
        $displayNameExpr = "COALESCE(u.display_name, u.username)";
    } elseif (in_array('full_name', $userCols)) {
        $displayNameExpr = "COALESCE(u.full_name, u.username)";
    } elseif (in_array('first_name', $userCols) && in_array('last_name', $userCols)) {
        $displayNameExpr = "CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))";
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
            $displayNameExpr AS employee_display_name
        FROM vehicle_movements vm
        LEFT JOIN users u ON vm.performed_by = u.emp_id
        WHERE vm.vehicle_code = ?
        ORDER BY vm.movement_datetime DESC, vm.id DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $vehicle_code);
    $stmt->execute();
    $result = $stmt->get_result();

    $movements = [];
    while ($row = $result->fetch_assoc()) {
        $movement_id = $row['id'];

        // جلب الصور لهذه الحركة
        $photoStmt = $conn->prepare("
            SELECT photo_url, taken_by, created_at 
            FROM vehicle_movement_photos 
            WHERE movement_id = ? 
            ORDER BY created_at ASC
        ");
        $photoStmt->bind_param("i", $movement_id);
        $photoStmt->execute();
        $photoRes = $photoStmt->get_result();

        $photos = [];
        while ($p = $photoRes->fetch_assoc()) {
            $photos[] = [
                'url' => $photos_base_path . $p['photo_url'],
                'filename' => $p['photo_url'],
                'taken_by' => $p['taken_by'] ?? 'غير محدد',
                'taken_at' => date('Y-m-d H:i', strtotime($p['created_at']))
            ];
        }
        $photoStmt->close();

        $row['photos'] = $photos;
        $row['photos_count'] = count($photos);

        // تنظيف اسم الموظف
        if (empty(trim($row['employee_display_name'] ?? ''))) {
            $row['employee_display_name'] = $lang === 'ar' ? 'غير محدد' : 'Not specified';
        }

        $movements[] = $row;
    }
    $stmt->close();

    echo json_encode([
        'success' => true,
        'movements' => $movements,
        'total_count' => count($movements),
        'vehicle_code' => $vehicle_code,
        'photos_base_url' => $photos_base_path,
        'message' => $lang === 'ar' ? 'تم جلب الحركات والصور بنجاح' : 'Movements and photos loaded'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("get_vehicle_movements_by_vehicle.php Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في جلب البيانات',
        'debug' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
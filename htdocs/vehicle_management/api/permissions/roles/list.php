<?php
// vehicle_management/api/permissions/roles/list.php
// نسخة نهائية 100% → تجلب كل حقل موجود في جدول roles تلقائيًا
header('Content-Type: application/json; charset=utf-8');

ini_set('session.cookie_path', '/');
session_start();

$userId = $_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? null);
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit;
}

// تضمين الاتصال
require_once __DIR__ . '/../../../config/db.php';
if (!isset($conn)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

// جلب كل الأعمدة من جدول roles
$colsResult = $conn->query("SHOW COLUMNS FROM `roles`");
$columns = [];
while ($col = $colsResult->fetch_assoc()) {
    $columns[] = $col['Field'];
}

// بناء SELECT يشمل كل الأعمدة
$select = implode(', ', array_map(fn($c) => "`$c`", $columns));
$sql = "SELECT $select FROM `roles` ORDER BY id ASC";

$result = $conn->query($sql);
$roles = [];
while ($row = $result->fetch_assoc()) {
    // تحويل كل القيم الرقمية إلى int (مهم للـ checkbox)
    foreach ($row as $k => $v) {
        if (in_array($k, ['can_create','can_edit','can_delete','can_view_all_vehicles','can_view_department_vehicles','can_assign_vehicle','can_receive_vehicle','can_override_department','can_self_assign_vehicle','allow_registration','is_active']) || str_ends_with($k, '_id')) {
            $row[$k] = $v === null ? null : (int)$v;
        }
    }
    $roles[] = $row;
}

echo json_encode([
    'success' => true,
    'roles' => $roles,
    'total' => count($roles),
    'debug_columns' => $columns  // احذف هذا السطر بعد التأكد
], JSON_UNESCAPED_UNICODE);
?>
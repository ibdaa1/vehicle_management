<?php
// vehicle_management/api/permissions/roles/update.php
header('Content-Type: application/json; charset=utf-8');
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'طريقة غير مدعومة']);
    exit;
}

require_once __DIR__ . '/../../config/db.php'; // عدل المسار حسب هيكلك

$userId = $_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? null);
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit;
}

$stmt = $conn->prepare("SELECT role_id FROM users WHERE id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!in_array($user['role_id'] ?? 0, [1, 2])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ممنوع']);
    exit;
}

$role_id = (int)($_POST['role_id'] ?? 0);
if ($role_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'معرف الدور مطلوب']);
    exit;
}

// كل الحقول المسموح بتحديثها
$allowed = ['name_en','name_ar','description','can_create','can_edit','can_delete',
    'can_view_all_vehicles','can_view_department_vehicles','can_assign_vehicle',
    'can_receive_vehicle','can_override_department','can_self_assign_vehicle','allow_registration'];

$updates = [];
$values = [];
$types = '';

foreach ($allowed as $field) {
    if (isset($_POST[$field])) {
        $value = $_POST[$field];
        if (in_array($field, ['can_create','can_edit','can_delete','can_view_all_vehicles',
            'can_view_department_vehicles','can_assign_vehicle','can_receive_vehicle',
            'can_override_department','can_self_assign_vehicle','allow_registration'])) {
            $value = $value ? 1 : 0;
            $types .= 'i';
        } else {
            $value = trim($value);
            $types .= 's';
        }
        $updates[] = "`$field` = ?";
        $values[] = $value === '' ? null : $value;
    }
}

if (empty($updates)) {
    echo json_encode(['success' => false, 'message' => 'لا توجد بيانات للتحديث']);
    exit;
}

$values[] = $role_id;
$types .= 'i';

$sql = "UPDATE `roles` SET " . implode(', ', $updates) . " WHERE id = ? LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$values);
$success = $stmt->execute();

echo json_encode([
    'success' => $success,
    'message' => $success ? 'تم حفظ التغييرات بنجاح' : 'فشل الحفظ',
    'affected' => $stmt->affected_rows
], JSON_UNESCAPED_UNICODE);
?>
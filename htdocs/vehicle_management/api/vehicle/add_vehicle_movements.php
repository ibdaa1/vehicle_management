<?php
// vehicle_management/api/vehicle/add_vehicle_movements.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

// 1. بدء الجلسة والتحقق من المستخدم
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

// تحديد إذا كان المستخدم مسؤولاً (role_id = 1 سوبر أدمن، 2 أدمن)
$is_admin = in_array($user_role_id, [1, 2]);

// 2. دعم GET للاختبار
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'طلب GET: استخدم POST لتسجيل الحركة.',
        'user_emp_id' => $user_emp_id,
        'user_role_id' => $user_role_id,
        'is_admin' => $is_admin,
        'isLoggedIn' => true
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 3. التحقق من POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'الطريقة غير مسموحة. استخدم POST.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 4. تضمين الإعدادات
$timezonePath = __DIR__ . '/../config/timezone.php';
$dbPath = __DIR__ . '/../config/db.php';
if (!file_exists($timezonePath) || !file_exists($dbPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'ملف الإعدادات مفقود.'], JSON_UNESCAPED_UNICODE);
    exit;
}
require_once $timezonePath;
require_once $dbPath;

// الحصول على الوقت الحالي
global $nowDt;
$movement_datetime = $nowDt->format('Y-m-d H:i:s');

// 5. استخراج البيانات
$vehicle_code = trim($_POST['vehicle_code'] ?? '');
$operation_type = trim($_POST['operation_type'] ?? '');
$performed_by = trim($_POST['performed_by'] ?? $user_emp_id);
$notes = trim($_POST['notes'] ?? '');
$latitude = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? floatval($_POST['latitude']) : null;
$longitude = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? floatval($_POST['longitude']) : null;
$created_by = $user_emp_id;
$updated_by = $user_emp_id;
$movement_id = isset($_POST['movement_id']) && !empty($_POST['movement_id']) ? intval($_POST['movement_id']) : null;

// Logging مفصل
error_log("=== " . ($movement_id ? "تحديث" : "تسجيل") . " حركة مركبة ===");
error_log("المستخدم: $user_emp_id | الدور: $user_role_id (مسؤول: " . ($is_admin ? 'نعم' : 'لا') . ")");
error_log("رمز: $vehicle_code | نوع: $operation_type | قام به: $performed_by");
if ($movement_id) {
    error_log("تحديث الحركة ID: $movement_id");
}

// التحقق من صحة الإحداثيات
if (($latitude !== null && ($latitude < -90 || $latitude > 90)) ||
    ($longitude !== null && ($longitude < -180 || $longitude > 180))) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'الإحداثيات غير صالحة. خط العرض: -90 إلى 90، خط الطول: -180 إلى 180.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($vehicle_code) || empty($operation_type)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'رمز المركبة ونوع العملية مطلوبان.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 6. الاتصال بقاعدة البيانات
global $conn;
if (!$conn || $conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'فشل الاتصال بقاعدة البيانات.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$conn->autocommit(false);

try {
    // 6.1. تحقق من وجود المركبة
    $check_sql = "SELECT id FROM vehicles WHERE vehicle_code = ?";
    $check_stmt = $conn->prepare($check_sql);
    if (!$check_stmt) {
        throw new Exception("فشل prepare check: " . $conn->error);
    }
    $check_stmt->bind_param("s", $vehicle_code);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        $check_stmt->close();
        throw new Exception("المركبة '$vehicle_code' غير موجودة.");
    }
    $check_stmt->close();
    error_log("تحقق المركبة: نجح - المركبة موجودة");

    // 6.1.1. إذا كان تحديثاً، تحقق من وجود الحركة
    $existing_movement = null;
    $previous_operation_type = null;
    if ($movement_id) {
        $check_movement_sql = "SELECT * FROM vehicle_movements WHERE id = ?";
        $check_movement_stmt = $conn->prepare($check_movement_sql);
        if (!$check_movement_stmt) {
            throw new Exception("فشل prepare check movement: " . $conn->error);
        }
        $check_movement_stmt->bind_param("i", $movement_id);
        $check_movement_stmt->execute();
        $movement_result = $check_movement_stmt->get_result();

        if ($movement_result->num_rows === 0) {
            $check_movement_stmt->close();
            throw new Exception("الحركة رقم $movement_id غير موجودة.");
        }

        $existing_movement = $movement_result->fetch_assoc();
        $check_movement_stmt->close();
        $previous_operation_type = $existing_movement['operation_type'];
        error_log("الحركة موجودة: ID = $movement_id | العملية السابقة: $previous_operation_type | العملية الجديدة: $operation_type");
    }

    // 6.1.2. التحقق من آخر حركة للمركبة (للإضافة الجديدة فقط)
    $last_movement = null;
    if (!$movement_id) {
        $last_movement_sql = "SELECT id, operation_type FROM vehicle_movements
                              WHERE vehicle_code = ?
                              ORDER BY movement_datetime DESC, id DESC
                              LIMIT 1";
        $last_movement_stmt = $conn->prepare($last_movement_sql);
        if (!$last_movement_stmt) {
            throw new Exception("فشل prepare last movement: " . $conn->error);
        }
        $last_movement_stmt->bind_param("s", $vehicle_code);
        $last_movement_stmt->execute();
        $last_movement_result = $last_movement_stmt->get_result();

        if ($last_movement_result->num_rows > 0) {
            $last_movement = $last_movement_result->fetch_assoc();
            error_log("آخر حركة للمركبة: ID = " . $last_movement['id'] . " | النوع: " . $last_movement['operation_type']);

            if ($last_movement['operation_type'] === $operation_type) {
                $error_message = "المركبة '$vehicle_code' لديها بالفعل حركة من نوع '$operation_type'.";
                $error_message .= " لا يمكن تسجيل نفس نوع الحركة مرتين متتاليتين.";
                $last_movement_stmt->close();
                throw new Exception($error_message);
            }
        }
        $last_movement_stmt->close();
    }

    // 6.1.3. إضافة ملاحظة التحديث إذا كان تحديثاً
    if ($movement_id) {
        $update_timestamp = date('Y-m-d H:i:s');
        if ($previous_operation_type !== $operation_type && $is_admin) {
            $update_note = "\n\n[تم تعديل نوع العملية من '$previous_operation_type' إلى '$operation_type' بواسطة $user_emp_id بتاريخ $update_timestamp]";
        } elseif ($is_admin) {
            $update_note = "\n\n[تم التحديث بواسطة $user_emp_id بتاريخ $update_timestamp]";
        } else {
            $update_note = "\n\n[تم التحديث بواسطة $user_emp_id بتاريخ $update_timestamp - بيانات فقط]";
        }
        $notes .= $update_note;
        error_log("تم إضافة ملاحظة تحديث: " . $update_note);
    }

    // 6.2. إدراج أو تحديث الحركة في جدول vehicle_movements
    if ($movement_id && $existing_movement) {
        // تحديث الحركة الحالية
        error_log("تحديث الحركة ID: $movement_id");

        $update_sql = "UPDATE vehicle_movements SET
                       operation_type = ?,
                       performed_by = ?,
                       notes = ?,
                       latitude = ?,
                       longitude = ?,
                       updated_by = ?,
                       updated_at = ?
                       WHERE id = ?";

        $update_stmt = $conn->prepare($update_sql);
        if (!$update_stmt) {
            throw new Exception("فشل prepare update: " . $conn->error);
        }

        $latitude_bind = $latitude;
        $longitude_bind = $longitude;

        if ($latitude !== null && $longitude !== null) {
            $update_stmt->bind_param("sssddssi",
                $operation_type, $performed_by, $notes,
                $latitude_bind, $longitude_bind, $updated_by, $movement_datetime,
                $movement_id
            );
        } else {
            $update_stmt->bind_param("sssssssi",
                $operation_type, $performed_by, $notes,
                $latitude_bind, $longitude_bind, $updated_by, $movement_datetime,
                $movement_id
            );
        }

        if (!$update_stmt->execute()) {
            $update_stmt->close();
            throw new Exception("فشل execute update: " . $conn->error);
        }

        $affected = $update_stmt->affected_rows;
        $update_stmt->close();

        error_log("تحديث ناجح: ID = $movement_id | الصفوف المتأثرة: $affected");
    } else {
        // إدراج حركة جديدة
        error_log("إدراج حركة جديدة");

        $insert_sql = "INSERT INTO vehicle_movements
                         (vehicle_code, operation_type, performed_by, movement_datetime,
                          notes, latitude, longitude, created_by, updated_by)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $insert_stmt = $conn->prepare($insert_sql);
        if (!$insert_stmt) {
            throw new Exception("فشل prepare insert: " . $conn->error);
        }

        $latitude_bind = $latitude;
        $longitude_bind = $longitude;

        if ($latitude !== null && $longitude !== null) {
            $insert_stmt->bind_param("sssssddss",
                $vehicle_code, $operation_type, $performed_by, $movement_datetime,
                $notes, $latitude_bind, $longitude_bind, $created_by, $updated_by
            );
        } else {
            $insert_stmt->bind_param("sssssssss",
                $vehicle_code, $operation_type, $performed_by, $movement_datetime,
                $notes, $latitude_bind, $longitude_bind, $created_by, $updated_by
            );
        }

        if (!$insert_stmt->execute()) {
            $insert_stmt->close();
            throw new Exception("فشل execute insert: " . $conn->error);
        }

        $movement_id = $insert_stmt->insert_id;
        $affected = $insert_stmt->affected_rows;
        $insert_stmt->close();

        if ($affected !== 1 || $movement_id <= 0) {
            throw new Exception("فشل في الإدراج (affected: $affected, ID: $movement_id).");
        }

        error_log("إدراج ناجح: ID = $movement_id");
    }

    // 6.3. معالجة الصور المحذوفة (حذف من قاعدة البيانات + حذف الملف الفعلي من السيرفر)
    $deleted_count = 0;
    $deleted_files = [];
    if ($movement_id && isset($_POST['deleted_filenames']) && !empty($_POST['deleted_filenames'])) {
        $deleted_filenames = explode(',', $_POST['deleted_filenames']);

        foreach ($deleted_filenames as $filename) {
            $filename = trim($filename);
            if (!empty($filename)) {
                // حذف السجل من قاعدة البيانات
                $delete_photo_sql = "DELETE FROM vehicle_movement_photos 
                                    WHERE movement_id = ? 
                                    AND photo_url LIKE ?";
                $delete_photo_stmt = $conn->prepare($delete_photo_sql);
                if ($delete_photo_stmt) {
                    $search_pattern = "%" . $filename;
                    $delete_photo_stmt->bind_param("is", $movement_id, $search_pattern);
                    if ($delete_photo_stmt->execute()) {
                        $deleted_count += $delete_photo_stmt->affected_rows;
                        $deleted_files[] = $filename;
                        error_log("تم حذف السجل للصورة: $filename");
                    }
                    $delete_photo_stmt->close();
                }
            }
        }

        // حذف الملفات الفعلية من السيرفر
        $upload_dir = __DIR__ . '/../../uploads/vehicle_movements/';
        foreach ($deleted_files as $filename) {
            $file_path = $upload_dir . $filename;
            if (file_exists($file_path)) {
                if (unlink($file_path)) {
                    error_log("تم حذف الملف الفعلي: $filename");
                } else {
                    error_log("فشل حذف الملف الفعلي: $filename");
                }
            }
        }

        error_log("إجمالي الصور المحذوفة من قاعدة البيانات: $deleted_count");
    }

    // 6.4. إضافة الصور الجديدة
    $photos_inserted = 0;
    $photo_sql = "INSERT INTO vehicle_movement_photos
                  (movement_id, photo_url, taken_by, created_at)
                  VALUES (?, ?, ?, ?)";

    $photo_stmt = $conn->prepare($photo_sql);
    if (!$photo_stmt) {
        throw new Exception("فشل prepare photos: " . $conn->error);
    }

    foreach ($_POST as $key => $value) {
        if (strpos($key, 'photo_url_') === 0 && !empty(trim($value))) {
            $photo_stmt->bind_param("isss",
                $movement_id, $value, $created_by, $movement_datetime
            );

            if ($photo_stmt->execute()) {
                $photos_inserted++;
                error_log("صورة مُدرجة: $value");
            } else {
                error_log("فشل صورة $value: " . $photo_stmt->error);
            }
        }
    }

    $photo_stmt->close();
    error_log("عدد الصور المدرجة: $photos_inserted");

    // 6.5. Commit
    if (!$conn->commit()) {
        throw new Exception("فشل commit: " . $conn->error);
    }

    error_log("Commit نجح");

    // 7. Response نجاح
    http_response_code($movement_id && $existing_movement ? 200 : 201);
    $response_message = ($movement_id && $existing_movement ? 'تم تحديث حركة المركبة بنجاح.' : 'تم تسجيل حركة المركبة بنجاح.');

    echo json_encode([
        'success' => true,
        'message' => $response_message,
        'movement_id' => (int)$movement_id,
        'photos_inserted' => $photos_inserted,
        'photos_deleted_count' => $deleted_count,
        'datetime_uae' => $movement_datetime,
        'has_coordinates' => ($latitude !== null && $longitude !== null),
        'coordinates' => [
            'latitude' => $latitude,
            'longitude' => $longitude
        ],
        'user_role' => $user_role_id,
        'is_admin' => $is_admin,
        'action' => ($movement_id && $existing_movement) ? 'update' : 'insert'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    http_response_code(400);

    $error_msg = $e->getMessage();
    error_log("خطأ تسجيل/تحديث حركة: " . $error_msg . " | رمز: $vehicle_code");

    echo json_encode([
        'success' => false,
        'message' => $error_msg,
        'error_type' => 'validation_error',
        'user_role' => $user_role_id,
        'is_admin' => $is_admin
    ], JSON_UNESCAPED_UNICODE);
}

// إعادة تفعيل autocommit
if (isset($conn)) {
    $conn->autocommit(true);
}
?>
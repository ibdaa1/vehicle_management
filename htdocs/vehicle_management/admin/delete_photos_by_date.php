<?php
// delete_photos_by_date.php - حذف الصور بين تاريخين (للمسؤولين فقط)
session_start();
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);

// التحقق من الجلسة
if (!isset($_SESSION['user']) || empty($_SESSION['user']['emp_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غير مسجل الدخول']);
    exit;
}

$user = $_SESSION['user'];
$user_role_id = $user['role_id'] ?? 0;
$is_admin = in_array($user_role_id, [1, 2]);

if (!$is_admin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك بهذا الإجراء']);
    exit;
}

// تضمين الإعدادات
$timezonePath = __DIR__ . '/../config/timezone.php';
$dbPath = __DIR__ . '/../config/db.php';
if (!file_exists($timezonePath) || !file_exists($dbPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'ملف الإعدادات مفقود']);
    exit;
}
require_once $timezonePath;
require_once $dbPath;

// الاتصال بقاعدة البيانات
global $conn;
if (!$conn || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'فشل الاتصال بقاعدة البيانات']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $from_date = trim($_POST['from_date'] ?? '');
    $to_date = trim($_POST['to_date'] ?? '');
    
    if (empty($from_date) || empty($to_date)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'يجب تحديد تاريخ البداية والنهاية']);
        exit;
    }
    
    // التحقق من صحة التواريخ
    if (!strtotime($from_date) || !strtotime($to_date)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'تواريخ غير صالحة']);
        exit;
    }
    
    if (strtotime($from_date) > strtotime($to_date)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'تاريخ البداية يجب أن يكون قبل تاريخ النهاية']);
        exit;
    }
    
    $conn->autocommit(false);
    
    try {
        // جلب الصور المراد حذفها
        $sql = "SELECT id, photo_url FROM vehicle_movement_photos 
                WHERE created_at BETWEEN ? AND ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $from_date . " 00:00:00", $to_date . " 23:59:59");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $photos_to_delete = [];
        while ($row = $result->fetch_assoc()) {
            $photos_to_delete[] = $row;
        }
        $stmt->close();
        
        if (empty($photos_to_delete)) {
            echo json_encode(['success' => true, 'message' => 'لا توجد صور في هذا النطاق الزمني']);
            exit;
        }
        
        // حذف الملفات من السيرفر
        $upload_dir = __DIR__ . '/../../uploads/vehicle_movements/';
        $files_deleted = 0;
        foreach ($photos_to_delete as $photo) {
            $filename = basename($photo['photo_url']);
            $file_path = $upload_dir . $filename;
            if (file_exists($file_path)) {
                if (unlink($file_path)) {
                    $files_deleted++;
                }
            }
        }
        
        // حذف من قاعدة البيانات
        $delete_sql = "DELETE FROM vehicle_movement_photos 
                      WHERE created_at BETWEEN ? AND ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("ss", $from_date . " 00:00:00", $to_date . " 23:59:59");
        $delete_stmt->execute();
        $rows_deleted = $delete_stmt->affected_rows;
        $delete_stmt->close();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => "تم حذف {$rows_deleted} صورة من قاعدة البيانات و {$files_deleted} ملف من السيرفر",
            'photos_deleted' => $rows_deleted,
            'files_deleted' => $files_deleted
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()]);
    }
    
    $conn->autocommit(true);
    exit;
}

// عرض النموذج
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حذف الصور بين تاريخين</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #dc3545; text-align: center; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="date"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; width: 100%; }
        button:hover { background: #c82333; }
        .alert { padding: 10px; margin-top: 10px; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-trash-alt"></i> حذف الصور بين تاريخين</h1>
        <p style="color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; border: 1px solid #ffeaa7;">
            <i class="fas fa-exclamation-triangle"></i> <strong>تحذير:</strong> هذا الإجراء نهائي ولا يمكن التراجع عنه. سيتم حذف الصور من قاعدة البيانات والسيرفر.
        </p>
        
        <form id="delete-form">
            <div class="form-group">
                <label for="from_date">من تاريخ:</label>
                <input type="date" id="from_date" name="from_date" required>
            </div>
            <div class="form-group">
                <label for="to_date">إلى تاريخ:</label>
                <input type="date" id="to_date" name="to_date" required>
            </div>
            <button type="submit"><i class="fas fa-trash"></i> حذف الصور</button>
        </form>
        
        <div id="result"></div>
    </div>

    <script>
        document.getElementById('delete-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const resultDiv = document.getElementById('result');
            
            try {
                const response = await fetch('delete_photos_by_date.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    resultDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                } else {
                    resultDiv.innerHTML = `<div class="alert alert-error">${data.message}</div>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<div class="alert alert-error">خطأ في الطلب: ${error.message}</div>`;
            }
        });
    </script>
</body>
</html>
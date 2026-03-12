<?php
ob_start();
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/delete_photos_error.log');
header('Content-Type: application/json; charset=utf-8');

// CORS (adjust origin in production)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Auth-Token');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// --- includes DB ---
$paths = [ __DIR__ . '/../../config/db.php', __DIR__ . '/../config/db.php', __DIR__ . '/config/db.php' ];
$dbFound = false;
foreach ($paths as $p) {
    if (file_exists($p)) {
        require_once $p;
        $dbFound = true;
        break;
    }
}
if (!$dbFound || !isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server misconfiguration: DB connection missing']);
    exit;
}

// get current user from session
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$currentUser = $_SESSION['user'] ?? null;
if (!$currentUser || empty($currentUser['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// تحقق من أن المستخدم مسؤول
$user_role_id = $currentUser['role_id'] ?? 0;
$is_admin = in_array($user_role_id, [1, 2]);

// عرض النموذج إذا كان GET ومسؤول
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!$is_admin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'غير مصرح لك بهذا الإجراء']);
        exit;
    }
    
    header('Content-Type: text/html; charset=utf-8');
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
                
                const response = await fetch('delete_photos_by_date.php', {
                    method: 'POST',
                    body: formData
                });
                
                const text = await response.text();
                console.log('Response text:', text);
                
                try {
                    const data = JSON.parse(text);
                    
                    if (data.success) {
                        resultDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                    } else {
                        resultDiv.innerHTML = `<div class="alert alert-error">${data.message}</div>`;
                    }
                } catch (error) {
                    console.log('JSON parse error:', error);
                    resultDiv.innerHTML = `<div class="alert alert-error">خطأ: ${text}</div>`;
                }
            });
        </script>
    </body>
    </html>
    <?php
    exit;
}

// للطلبات POST
header('Content-Type: application/json; charset=utf-8');
ob_end_clean(); // مسح أي إخراج سابق

// التحقق من POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'طريقة غير مسموحة']);
    exit;
}

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

try {
    // جلب الصور المراد حذفها
    $start_date = $from_date . " 00:00:00";
    $end_date = $to_date . " 23:59:59";
    $sql = "SELECT id, photo_url FROM vehicle_movement_photos 
            WHERE created_at >= ? AND created_at <= ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('فشل في تحضير الاستعلام: ' . $conn->error);
    }
    $stmt->bind_param("ss", $start_date, $end_date);
    if (!$stmt->execute()) {
        throw new Exception('فشل في تنفيذ الاستعلام: ' . $stmt->error);
    }
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
        if (file_exists($file_path) && is_file($file_path)) {
            if (unlink($file_path)) {
                $files_deleted++;
            }
        }
    }
    
    // حذف من قاعدة البيانات
    $delete_sql = "DELETE FROM vehicle_movement_photos 
                  WHERE created_at >= ? AND created_at <= ?";
    $delete_stmt = $conn->prepare($delete_sql);
    if (!$delete_stmt) {
        throw new Exception('فشل في تحضير حذف: ' . $conn->error);
    }
    $delete_stmt->bind_param("ss", $start_date, $end_date);
    if (!$delete_stmt->execute()) {
        throw new Exception('فشل في تنفيذ الحذف: ' . $delete_stmt->error);
    }
    $rows_deleted = $delete_stmt->affected_rows;
    $delete_stmt->close();
    
    echo json_encode([
        'success' => true,
        'message' => "تم حذف {$rows_deleted} صورة من قاعدة البيانات و {$files_deleted} ملف من السيرفر",
        'photos_deleted' => $rows_deleted,
        'files_deleted' => $files_deleted
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()]);
}
?>
<?php
// vehicle_management/api/vehicle/upload.php
header('Content-Type: application/json; charset=utf-8');
// المسار المُصحح للحفظ: /vehicle_management/uploads/vehicle_movements/
const BASE_UPLOAD_DIR = __DIR__ . '/../../uploads/';  // تصحيح: من /api/vehicle/ إلى /vehicle_management/uploads/
const TARGET_DIR = BASE_UPLOAD_DIR . 'vehicle_movements/';
const MAX_PHOTOS = 6;
const THUMB_MAX_WIDTH = 800; // عرض أقصى للصور المصغرة أو المعالجة

// دالة لتصغير أو تغيير حجم الصورة (تستخدم مكتبة GD) - مُصححة لتنظيف الذاكرة
function resizeAndSaveImage($file_path, $destination_path, $max_width) {
    $image = null;
    $image_new = null;
    $success = false;
    
    list($width, $height, $type) = getimagesize($file_path);
   
    // إذا كان العرض أقل من العرض الأقصى، لا تقم بتصغيرها
    if ($width <= $max_width) {
        $success = copy($file_path, $destination_path);
    } else {
        $ratio = $max_width / $width;
        $new_width = $max_width;
        $new_height = $height * $ratio;
        $image_new = imagecreatetruecolor($new_width, $new_height);
        
        switch ($type) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($file_path);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($file_path);
                // الحفاظ على الشفافية لصور PNG
                imagealphablending($image_new, false);
                imagesavealpha($image_new, true);
                break;
            default:
                $success = false; // نوع صورة غير مدعوم
                break;
        }
        
        if ($image && $image_new) {
            imagecopyresampled($image_new, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            // الحفظ
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $success = imagejpeg($image_new, $destination_path, 80); // جودة 80
                    break;
                case IMAGETYPE_PNG:
                    $success = imagepng($image_new, $destination_path, 7); // ضغط 7
                    break;
                default:
                    $success = false;
            }
        }
    }
    
    // تنظيف الذاكرة دائمًا (بعد return المحتمل)
    if ($image) imagedestroy($image);
    if ($image_new) imagedestroy($image_new);
    
    return $success;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['photos'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request or no files received.']);
    exit;
}

if (count($_FILES['photos']['name']) > MAX_PHOTOS) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Exceeded maximum number of photos (' . MAX_PHOTOS . ').']);
    exit;
}

// التأكد من وجود مجلد التحميل
if (!is_dir(TARGET_DIR)) {
    if (!mkdir(TARGET_DIR, 0777, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create upload directory.']);
        exit;
    }
}

$uploaded_files = [];
$errors = [];

for ($i = 0; $i < count($_FILES['photos']['name']); $i++) {
    if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['photos']['tmp_name'][$i];
        $file_extension = strtolower(pathinfo($_FILES['photos']['name'][$i], PATHINFO_EXTENSION));
       
        // التحقق من نوع الملف (أساسي)
        if (!in_array($file_extension, ['jpg', 'jpeg', 'png'])) {
            $errors[] = "File " . ($i+1) . " has an unsupported extension.";
            continue;
        }
        
        $new_file_name = uniqid('mv_') . '.' . $file_extension;
        $dest_path = TARGET_DIR . $new_file_name;
        $relative_url = '/vehicle_management/uploads/vehicle_movements/' . $new_file_name;  // 🚨 تصحيح المسار في الرد
        
        // معالجة وتصغير الصورة
        if (resizeAndSaveImage($file_tmp_path, $dest_path, THUMB_MAX_WIDTH)) {
            $uploaded_files[] = $relative_url;
        } else {
            $errors[] = "Failed to process and save file " . ($i+1) . ".";
        }
    } elseif ($_FILES['photos']['error'][$i] != UPLOAD_ERR_NO_FILE) {
        $errors[] = "Upload error for file " . ($i+1) . ": Code " . $_FILES['photos']['error'][$i];
    }
}

if (!empty($errors)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Some files failed to upload or process.', 'errors' => $errors]);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Files processed and uploaded successfully.', 'uploaded_files' => $uploaded_files]);
?>

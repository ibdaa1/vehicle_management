<?php
// vehicle_management/api/vehicle/upload.php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE); // إخفاء الـ Deprecated مؤقتاً (أفضل حل دائم هو التصحيح)
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

const BASE_UPLOAD_DIR = __DIR__ . '/../../uploads/';
const TARGET_DIR = BASE_UPLOAD_DIR . 'vehicle_movements/';
const MAX_PHOTOS = 6;
const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
const MAX_TARGET_SIZE = 1 * 1024 * 1024; // 1MB هدف الضغط
const MIN_WIDTH = 800;
const MIN_HEIGHT = 600;

// إنشاء المجلد إذا لم يكن موجوداً
if (!is_dir(TARGET_DIR)) {
    mkdir(TARGET_DIR, 0755, true);
}

/**
 * ضغط الصورة بذكاء مع تصحيح تحويل float → int
 */
function smartCompressImage($file_path, $destination_path, $original_extension) {
    $image_info = getimagesize($file_path);
    if ($image_info === false) {
        error_log("تعذر قراءة معلومات الصورة: $file_path");
        return false;
    }
    list($width, $height, $type) = $image_info;

    switch ($type) {
        case IMAGETYPE_JPEG:
            $image = @imagecreatefromjpeg($file_path);
            $output_function = 'imagejpeg';
            $output_extension = 'jpg';
            break;
        case IMAGETYPE_PNG:
            $image = @imagecreatefrompng($file_path);
            if ($image) imagesavealpha($image, true);
            $output_function = 'imagepng';
            $output_extension = 'png';
            break;
        case IMAGETYPE_GIF:
            $image = @imagecreatefromgif($file_path);
            $output_function = 'imagegif';
            $output_extension = 'gif';
            break;
        case IMAGETYPE_WEBP:
            $image = @imagecreatefromwebp($file_path);
            $output_function = 'imagewebp';
            $output_extension = 'webp';
            break;
        default:
            error_log("نوع الصورة غير مدعوم: $type");
            return false;
    }

    if (!$image) {
        error_log("فشل إنشاء صورة GD من: $file_path");
        return false;
    }

    $original_width = $width;
    $original_height = $height;
    $ratio = $width / $height;

    // حساب الأبعاد الجديدة مع الحفاظ على النسبة
    $new_width = min($width, 1920);
    $new_height = (int)round($new_width / $ratio); // تصحيح: round + int

    // التأكد من الحد الأدنى للأبعاد
    if ($new_width < MIN_WIDTH && $ratio <= 1) {
        $new_width = MIN_WIDTH;
        $new_height = (int)round($new_width / $ratio);
    } elseif ($new_height < MIN_HEIGHT && $ratio > 1) {
        $new_height = MIN_HEIGHT;
        $new_width = (int)round($new_height * $ratio);
    }

    $new_image = imagecreatetruecolor($new_width, $new_height);

    // الحفاظ على الشفافية لـ PNG/GIF/WEBP
    if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP])) {
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
        imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
    }

    imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

    $quality = 85;

    do {
        $temp_path = $destination_path . '_temp.' . $output_extension;

        if ($output_function === 'imagejpeg') {
            imagejpeg($new_image, $temp_path, $quality);
        } elseif ($output_function === 'imagepng') {
            $compression = (int)floor((100 - $quality) / 10); // تصحيح: floor + int
            imagepng($new_image, $temp_path, $compression);
        } elseif ($output_function === 'imagegif') {
            imagegif($new_image, $temp_path);
        } elseif ($output_function === 'imagewebp') {
            imagewebp($new_image, $temp_path, $quality);
        }

        if (file_exists($temp_path)) {
            $file_size = filesize($temp_path);

            if ($file_size <= MAX_TARGET_SIZE || $quality <= 30) {
                rename($temp_path, $destination_path);
                imagedestroy($image);
                imagedestroy($new_image);
                return true;
            }

            unlink($temp_path);
            $quality -= 15;

            if ($quality < 50 && $new_width > MIN_WIDTH) {
                $new_width = (int)max($new_width * 0.8, MIN_WIDTH); // تصحيح: max + int
                $new_height = (int)round($new_width / $ratio);

                imagedestroy($new_image);
                $new_image = imagecreatetruecolor($new_width, $new_height);

                if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP])) {
                    imagealphablending($new_image, false);
                    imagesavealpha($new_image, true);
                    $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
                    imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
                }

                imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height);
                $quality = 70;
            }
        } else {
            break;
        }
    } while ($quality > 30);

    imagedestroy($image);
    imagedestroy($new_image);

    // كخيار أخير: نسخ الملف الأصلي
    return copy($file_path, $destination_path);
}

function processUploadedPhotos($photos) {
    $results = [
        'success' => [],
        'errors' => [],
        'total_size_before' => 0,
        'total_size_after' => 0
    ];

    $file_count = count($photos['name']);

    for ($i = 0; $i < $file_count; $i++) {
        if ($photos['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }

        $file_name = $photos['name'][$i];
        $file_tmp = $photos['tmp_name'][$i];
        $file_size = $photos['size'][$i];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($file_ext, $allowed)) {
            $results['errors'][] = "نوع الملف غير مدعوم: $file_name";
            continue;
        }

        if ($file_size > MAX_FILE_SIZE) {
            $results['errors'][] = "الملف كبير جدًا: $file_name";
            continue;
        }

        $unique_name = time() . '_' . uniqid() . '_' . bin2hex(random_bytes(4)) . '.' . $file_ext;
        $dest_path = TARGET_DIR . $unique_name;

        $results['total_size_before'] += $file_size;

        if (smartCompressImage($file_tmp, $dest_path, $file_ext)) {
            $final_size = filesize($dest_path);
            $results['total_size_after'] += $final_size;

            $relative_url = '/vehicle_management/uploads/vehicle_movements/' . $unique_name;

            $results['success'][] = [
                'url' => $relative_url,
                'filename' => $unique_name,
                'original_name' => $file_name,
                'original_size' => $file_size,
                'compressed_size' => $final_size,
                'compression_ratio' => round(($file_size - $final_size) / $file_size * 100, 2)
            ];
        } else {
            if (move_uploaded_file($file_tmp, $dest_path)) {
                $results['success'][] = [
                    'url' => '/vehicle_management/uploads/vehicle_movements/' . $unique_name,
                    'filename' => $unique_name,
                    'original_name' => $file_name,
                    'compressed' => false
                ];
            } else {
                $results['errors'][] = "فشل حفظ الملف: $file_name";
            }
        }
    }

    return $results;
}

// التنفيذ الرئيسي
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'استخدم POST فقط.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_FILES['photos']) || empty($_FILES['photos']['name'][0])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'لم يتم رفع أي صور.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (count($_FILES['photos']['name']) > MAX_PHOTOS) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'الحد الأقصى 6 صور.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$result = processUploadedPhotos($_FILES['photos']);

if (empty($result['success'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'فشل رفع جميع الصور.',
        'errors' => $result['errors']
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$total_saved = $result['total_size_before'] - $result['total_size_after'];

echo json_encode([
    'success' => true,
    'message' => 'تم رفع ' . count($result['success']) . ' صور بنجاح.',
    'uploaded_files' => $result['success'],
    'count' => count($result['success']),
    'total_size_before' => $result['total_size_before'],
    'total_size_after' => $result['total_size_after'],
    'total_saved' => $total_saved,
    'errors' => $result['errors']
], JSON_UNESCAPED_UNICODE);
?>
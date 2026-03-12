<?php
// vehicle_management/api/vehicle/get_movement_photos.php
header('Content-Type: application/json; charset=utf-8');

// تضمين ملف الاتصال
$dbPaths = [
    __DIR__ . '/../config/db.php',
    __DIR__ . '/../../config/db.php',
    __DIR__ . '/../../../config/db.php'
];
foreach ($dbPaths as $p) {
    if (file_exists($p)) {
        require_once $p;
        break;
    }
}

if (!isset($conn)) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

if (!isset($_GET['movement_id']) || !is_numeric($_GET['movement_id'])) {
    echo json_encode(['success' => false, 'message' => 'movement_id required']);
    exit;
}

$movement_id = (int)$_GET['movement_id'];

// المسار الصحيح للصور
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$correct_path = $base_url . '/vehicle_management/uploads/vehicle_movements/';

$stmt = $conn->prepare("
    SELECT photo_url, taken_by, created_at 
    FROM vehicle_movement_photos 
    WHERE movement_id = ? 
    ORDER BY created_at ASC
");
$stmt->bind_param("i", $movement_id);
$stmt->execute();
$result = $stmt->get_result();

$photos = [];
while ($row = $result->fetch_assoc()) {
    $original = trim($row['photo_url']);

    // تنظيف المسار من أي تكرار أو مسارات قديمة
    $clean_filename = $original;

    // إذا كان فيه مسار كامل قديم (مثل /vehicle_management/uploads/...)
    if (str_contains($clean_filename, '/vehicle_management/uploads/vehicle_movements/')) {
        $clean_filename = str_replace('/vehicle_management/uploads/vehicle_movements/', '', $clean_filename);
    }

    // إزالة أي سلاش في البداية
    $clean_filename = ltrim($clean_filename, '/');

    $photos[] = [
        'url'       => $correct_path . $clean_filename,
        'filename'  => $clean_filename,
        'taken_by'  => $row['taken_by'] ?? 'غير محدد',
        'taken_at'  => $row['created_at']
    ];
}

echo json_encode([
    'success' => true,
    'photos'  => $photos,
    'count'   => count($photos),
    'base_url'=> $correct_path
], JSON_UNESCAPED_UNICODE);

$stmt->close();
?>
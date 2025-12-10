<?php
/**
 * vehicle_management/api/vehicle/upload.php
 * 
 * Handles photo uploads for vehicle movements.
 * Accepts multiple files in photos[] field.
 * Stores files in uploads/vehicle_movements/
 * Inserts records in vehicle_movement_photos table.
 * 
 * POST parameters:
 * - photos[] (required): Array of uploaded files
 * - vehicle_code (required): Vehicle code
 * - movement_id (optional): Movement ID if available
 * - notes (optional): Notes about the photos
 * 
 * Returns JSON with success status and uploaded file details
 */

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Include timezone config
$timezonePath = __DIR__ . '/../config/timezone.php';
if (file_exists($timezonePath)) {
    require_once $timezonePath;
} else {
    date_default_timezone_set('Asia/Dubai');
}

// CORS headers
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Use POST.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Include session and DB
$sessionPaths = [
    __DIR__ . '/../config/session.php',
    __DIR__ . '/../../config/session.php'
];
foreach ($sessionPaths as $p) {
    if (file_exists($p)) {
        require_once $p;
        break;
    }
}
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$dbPaths = [
    __DIR__ . '/../config/db.php',
    __DIR__ . '/../../config/db.php'
];
foreach ($dbPaths as $p) {
    if (file_exists($p)) {
        require_once $p;
        break;
    }
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection not available'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Authentication check
$currentUser = null;
if (!empty($_SESSION['user']) && is_array($_SESSION['user']) && !empty($_SESSION['user']['id'])) {
    $currentUser = $_SESSION['user'];
} elseif (!empty($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id, role_id, emp_id, username FROM users WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $currentUser = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($currentUser) {
            $_SESSION['user'] = $currentUser;
        }
    }
}

if (!$currentUser || empty($currentUser['emp_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$empId = $currentUser['emp_id'];
$roleId = intval($currentUser['role_id'] ?? 0);

// Get role permissions
$isAdmin = false;
if ($roleId > 0) {
    $stmt = $conn->prepare("SELECT can_view_all_vehicles FROM roles WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $roleId);
        $stmt->execute();
        $roleRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($roleRow) {
            $isAdmin = (bool)($roleRow['can_view_all_vehicles'] ?? false);
        }
    }
}

// Get POST data
$vehicleCode = trim($_POST['vehicle_code'] ?? '');
$movementId = !empty($_POST['movement_id']) ? intval($_POST['movement_id']) : null;
$notes = trim($_POST['notes'] ?? '');

// Validate vehicle code
if (empty($vehicleCode)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Vehicle code is required'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Check if files were uploaded
if (empty($_FILES['photos']) || !isset($_FILES['photos']['name'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'No photos uploaded'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Permission check: User must own the movement or be admin
// Check if vehicle belongs to user or user is admin
$hasPermission = $isAdmin;

if (!$hasPermission) {
    // Check if user has an active movement for this vehicle
    $checkStmt = $conn->prepare("
        SELECT id FROM vehicle_movements
        WHERE vehicle_code = ?
          AND performed_by = ?
          AND operation_type = 'pickup'
          AND NOT EXISTS (
              SELECT 1 FROM vehicle_movements vm2
              WHERE vm2.vehicle_code = vehicle_movements.vehicle_code
                AND vm2.operation_type = 'return'
                AND vm2.movement_datetime > vehicle_movements.movement_datetime
          )
        LIMIT 1
    ");
    if ($checkStmt) {
        $checkStmt->bind_param('ss', $vehicleCode, $empId);
        $checkStmt->execute();
        $hasPermission = $checkStmt->get_result()->num_rows > 0;
        $checkStmt->close();
    }
}

if (!$hasPermission) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'You do not have permission to upload photos for this vehicle'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Setup upload directory
$uploadDir = __DIR__ . '/../../uploads/vehicle_movements/';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create upload directory'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Allowed file types
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
// Maximum file size in bytes (5MB)
define('MAX_FILE_SIZE', 5 * 1024 * 1024);
$maxFileSize = MAX_FILE_SIZE;

// Process uploaded files
$uploadedFiles = [];
$errors = [];

// Handle both single and multiple file uploads
$files = $_FILES['photos'];
$fileCount = is_array($files['name']) ? count($files['name']) : 1;

for ($i = 0; $i < $fileCount; $i++) {
    // Get file details (handle both array and single file)
    $fileName = is_array($files['name']) ? $files['name'][$i] : $files['name'];
    $fileTmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
    $fileSize = is_array($files['size']) ? $files['size'][$i] : $files['size'];
    $fileError = is_array($files['error']) ? $files['error'][$i] : $files['error'];
    $fileType = is_array($files['type']) ? $files['type'][$i] : $files['type'];
    
    // Check for upload errors
    if ($fileError !== UPLOAD_ERR_OK) {
        $errors[] = [
            'file' => $fileName,
            'error' => 'Upload error code: ' . $fileError
        ];
        continue;
    }
    
    // Validate file size
    if ($fileSize > $maxFileSize) {
        $errors[] = [
            'file' => $fileName,
            'error' => 'File size exceeds ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB limit'
        ];
        continue;
    }
    
    // Validate file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $detectedType = finfo_file($finfo, $fileTmpName);
    finfo_close($finfo);
    
    if (!in_array($detectedType, $allowedTypes)) {
        $errors[] = [
            'file' => $fileName,
            'error' => 'Invalid file type. Only JPEG, PNG, and GIF allowed'
        ];
        continue;
    }
    
    // Generate unique filename with validated extension based on MIME type
    $extensionMap = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif'
    ];
    
    $extension = $extensionMap[$detectedType] ?? 'jpg'; // Default to jpg if unknown
    $uniqueName = uniqid('vm_' . $vehicleCode . '_', true) . '.' . $extension;
    $uploadPath = $uploadDir . $uniqueName;
    $relativeUrl = '/vehicle_management/uploads/vehicle_movements/' . $uniqueName;
    
    // Move uploaded file
    if (!move_uploaded_file($fileTmpName, $uploadPath)) {
        $errors[] = [
            'file' => $fileName,
            'error' => 'Failed to move uploaded file'
        ];
        continue;
    }
    
    // Set restrictive file permissions (read-only for owner, not readable by others)
    if (!chmod($uploadPath, 0640)) {
        @unlink($uploadPath);
        $errors[] = [
            'file' => $fileName,
            'error' => 'Failed to set file permissions'
        ];
        continue;
    }
    
    // Verify file exists and is readable
    if (!is_file($uploadPath) || !is_readable($uploadPath)) {
        @unlink($uploadPath);
        $errors[] = [
            'file' => $fileName,
            'error' => 'File verification failed after upload'
        ];
        continue;
    }
    
    // Insert into database
    $insertStmt = $conn->prepare("
        INSERT INTO vehicle_movement_photos 
        (movement_id, vehicle_code, photo_url, taken_by, notes, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    if ($insertStmt) {
        $insertStmt->bind_param('issss', $movementId, $vehicleCode, $relativeUrl, $empId, $notes);
        
        if ($insertStmt->execute()) {
            $uploadedFiles[] = [
                'id' => $insertStmt->insert_id,
                'file' => $fileName,
                'url' => $relativeUrl,
                'vehicle_code' => $vehicleCode,
                'taken_by' => $empId,
                'created_at' => date('Y-m-d H:i:s')
            ];
        } else {
            // Delete file if DB insert failed
            @unlink($uploadPath);
            $errors[] = [
                'file' => $fileName,
                'error' => 'Database insert failed: ' . $insertStmt->error
            ];
        }
        
        $insertStmt->close();
    } else {
        // Delete file if statement preparation failed
        @unlink($uploadPath);
        $errors[] = [
            'file' => $fileName,
            'error' => 'Failed to prepare database statement'
        ];
    }
}

// Build response
$response = [
    'success' => count($uploadedFiles) > 0,
    'message' => sprintf(
        '%d photo(s) uploaded successfully',
        count($uploadedFiles)
    ),
    'uploaded' => $uploadedFiles,
    'errors' => $errors,
    'total_uploaded' => count($uploadedFiles),
    'total_errors' => count($errors)
];

if (count($uploadedFiles) > 0 && count($errors) > 0) {
    $response['message'] = sprintf(
        '%d photo(s) uploaded, %d failed',
        count($uploadedFiles),
        count($errors)
    );
}

http_response_code($response['success'] ? 200 : 400);
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;

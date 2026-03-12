<?php
/**
 * PHP Built-in Server Router
 * 
 * Usage:
 *   DB_HOST=localhost DB_USER=vm_user DB_PASS=vm_pass_2024 DB_NAME=vehicle_management \
 *   APP_BASE_URL="" php -S localhost:8080 router.php
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// If the file exists, serve it directly
$filePath = __DIR__ . $uri;
if ($uri !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    return false;
}

// Route /api/v1/* to the MVC front controller
if (preg_match('#^/api/v1/#', $uri)) {
    try {
        include __DIR__ . '/api/v1/index.php';
    } catch (\Throwable $e) {
        // ResponseSentException is expected - output already sent
        // For any other error, log it
        if (!($e instanceof \App\Core\ResponseSentException)) {
            error_log("Router error: " . $e->getMessage());
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: application/json');
            }
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        }
    }
    return true;
}

// Default: 404
return false;

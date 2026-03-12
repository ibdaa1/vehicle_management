<?php
/**
 * MVC Front Controller (API v1 Entry Point)
 * 
 * This is the single entry point for the new MVC API (v1).
 * Existing API endpoints in /api/ continue to work independently.
 * 
 * URL Pattern: /vehicle_management/api/v1/*
 * 
 * Requires .htaccess URL rewriting to route requests to this file.
 */

// Load autoloader
require_once __DIR__ . '/../../config/autoload.php';

use App\Core\App;
use App\Core\Response;

try {
    // Bootstrap the application (creates new App instance each request)
    $app = new App(dirname(__DIR__, 2));
    $app->boot();

    // Load route definitions
    $router = $app->router();
    require dirname(__DIR__, 2) . '/config/routes.php';

    // Dispatch the request
    $app->run();
} catch (\Throwable $e) {
    // Catch any unhandled exception and return a proper JSON error response
    $msg = $e->getMessage();
    $cls = get_class($e);
    $file = $e->getFile() . ':' . $e->getLine();
    error_log("API v1 Error [{$cls}]: {$msg} in {$file}");

    // Also log to a local file for InfinityFree debugging (no access to PHP error log)
    $logDir = __DIR__ . '/../../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    @file_put_contents(
        $logDir . '/api_errors.log',
        date('[Y-m-d H:i:s]') . " [{$cls}] {$msg} in {$file}\n",
        FILE_APPEND | LOCK_EX
    );

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }

    $debug = (bool)(getenv('APP_DEBUG') ?: false);
    $response = [
        'success' => false,
        'message' => 'Internal server error',
    ];

    // Always include error type and a sanitized hint for debugging
    $response['error_type'] = $cls;

    // Always include a hint for connection errors to help diagnose
    if (stripos($msg, 'Database connection failed') !== false
        || stripos($msg, 'mysqli') !== false
        || stripos($msg, 'Access denied') !== false
        || stripos($msg, 'connect') !== false
    ) {
        $response['message'] = 'Database connection failed. Please check database host, username, password, and database name in config/database.php';
        $response['hint'] = $msg;
    }

    if ($debug) {
        $response['error'] = $msg;
        $response['file']  = $file;
        $response['trace'] = $e->getTraceAsString();
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

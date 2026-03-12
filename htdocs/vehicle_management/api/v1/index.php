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
    $file = $e->getFile() . ':' . $e->getLine();
    error_log("API v1 Error: {$msg} in {$file}");

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }

    $debug = (bool)(getenv('APP_DEBUG') ?: false);
    $response = [
        'success' => false,
        'message' => 'Internal server error',
    ];

    // Always include a hint for connection errors to help diagnose
    if (stripos($msg, 'Database connection failed') !== false) {
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

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

// Bootstrap the application (creates new App instance each request)
$app = new App(dirname(__DIR__, 2));
$app->boot();

// Load route definitions
$router = $app->router();
require dirname(__DIR__, 2) . '/config/routes.php';

// Dispatch the request
$app->run();

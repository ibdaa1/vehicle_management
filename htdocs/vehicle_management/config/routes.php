<?php
/**
 * Route Definitions
 * 
 * Defines all MVC API routes (v1 prefix).
 * Old API endpoints in /api/ remain functional for backward compatibility.
 * New endpoints use /api/v1/ prefix.
 * 
 * @param \App\Core\Router $router
 */

use App\Controllers\AuthController;
use App\Controllers\RoleController;
use App\Controllers\PermissionController;

// === Authentication Routes ===
$router->post('api/v1/auth/login', AuthController::class, 'login');
$router->get('api/v1/auth/check', AuthController::class, 'check');
$router->post('api/v1/auth/logout', AuthController::class, 'logout');

// === Roles Routes ===
$router->get('api/v1/roles', RoleController::class, 'index');
$router->get('api/v1/roles/public', RoleController::class, 'publicList');
$router->get('api/v1/roles/{id}', RoleController::class, 'show');
$router->post('api/v1/roles', RoleController::class, 'store');
$router->put('api/v1/roles/{id}', RoleController::class, 'update');
$router->delete('api/v1/roles/{id}', RoleController::class, 'destroy');
$router->put('api/v1/roles/{id}/permissions', RoleController::class, 'syncPermissions');
$router->put('api/v1/roles/{id}/resource-permissions', RoleController::class, 'setResourcePermissions');

// === Permissions Routes ===
$router->get('api/v1/permissions', PermissionController::class, 'index');
$router->get('api/v1/permissions/modules', PermissionController::class, 'modules');
$router->get('api/v1/permissions/check', PermissionController::class, 'check');
$router->get('api/v1/permissions/my', PermissionController::class, 'myPermissions');
$router->post('api/v1/permissions', PermissionController::class, 'store');
$router->put('api/v1/permissions/{id}', PermissionController::class, 'update');
$router->delete('api/v1/permissions/{id}', PermissionController::class, 'destroy');

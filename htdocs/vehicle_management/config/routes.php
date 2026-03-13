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
use App\Controllers\DashboardController;
use App\Controllers\SettingsController;
use App\Controllers\VehicleController;
use App\Controllers\ReferencesController;
use App\Controllers\UserController;
use App\Controllers\MovementController;
use App\Controllers\ViolationController;

// === Health Check Route (diagnostic) ===
$router->get('api/v1/health', \App\Controllers\HealthController::class, 'check');

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

// === Dashboard Routes ===
$router->get('api/v1/dashboard/stats', DashboardController::class, 'stats');

// === Settings & Theme Routes ===
$router->get('api/v1/settings/public', SettingsController::class, 'publicSettings');
$router->get('api/v1/settings/themes', SettingsController::class, 'themes');
$router->get('api/v1/settings/themes/{id}', SettingsController::class, 'themeDetail');
$router->get('api/v1/settings/theme', SettingsController::class, 'theme');
$router->put('api/v1/settings/theme/{slug}', SettingsController::class, 'switchTheme');
$router->put('api/v1/settings/themes/{id}/colors', SettingsController::class, 'updateColors');
$router->put('api/v1/settings/themes/{id}/design', SettingsController::class, 'updateDesign');
$router->get('api/v1/settings', SettingsController::class, 'index');
$router->put('api/v1/settings/{key}', SettingsController::class, 'update');

// Theme CRUD
$router->post('api/v1/settings/themes', SettingsController::class, 'storeTheme');
$router->put('api/v1/settings/themes/{id}', SettingsController::class, 'updateTheme');
$router->delete('api/v1/settings/themes/{id}', SettingsController::class, 'destroyTheme');

// Color Settings CRUD
$router->post('api/v1/settings/themes/{id}/colors', SettingsController::class, 'storeColor');
$router->put('api/v1/settings/colors/{colorId}', SettingsController::class, 'updateColor');
$router->delete('api/v1/settings/colors/{colorId}', SettingsController::class, 'destroyColor');

// Font Settings CRUD
$router->post('api/v1/settings/themes/{id}/fonts', SettingsController::class, 'storeFont');
$router->put('api/v1/settings/fonts/{fontId}', SettingsController::class, 'updateFont');
$router->delete('api/v1/settings/fonts/{fontId}', SettingsController::class, 'destroyFont');

// Button Styles CRUD
$router->post('api/v1/settings/themes/{id}/buttons', SettingsController::class, 'storeButton');
$router->put('api/v1/settings/buttons/{buttonId}', SettingsController::class, 'updateButton');
$router->delete('api/v1/settings/buttons/{buttonId}', SettingsController::class, 'destroyButton');

// Card Styles CRUD
$router->post('api/v1/settings/themes/{id}/cards', SettingsController::class, 'storeCard');
$router->put('api/v1/settings/cards/{cardId}', SettingsController::class, 'updateCard');
$router->delete('api/v1/settings/cards/{cardId}', SettingsController::class, 'destroyCard');

// Design Settings CRUD
$router->post('api/v1/settings/themes/{id}/design', SettingsController::class, 'storeDesignSetting');
$router->put('api/v1/settings/design/{designId}', SettingsController::class, 'updateDesignSetting');
$router->delete('api/v1/settings/design/{designId}', SettingsController::class, 'destroyDesignSetting');

// System Settings CRUD
$router->post('api/v1/settings', SettingsController::class, 'storeSetting');
$router->delete('api/v1/settings/{id}', SettingsController::class, 'destroySetting');

// === Vehicle Routes ===
$router->get('api/v1/vehicles/stats', VehicleController::class, 'stats');
$router->get('api/v1/vehicles/my-vehicles', VehicleController::class, 'myVehicles');
$router->get('api/v1/vehicles', VehicleController::class, 'index');
$router->get('api/v1/vehicles/{id}', VehicleController::class, 'show');
$router->post('api/v1/vehicles', VehicleController::class, 'store');
$router->put('api/v1/vehicles/{id}', VehicleController::class, 'update');
$router->delete('api/v1/vehicles/{id}', VehicleController::class, 'destroy');

// === References Routes (departments, sections, divisions) ===
$router->get('api/v1/references', ReferencesController::class, 'index');
$router->get('api/v1/references/departments', ReferencesController::class, 'departments');
$router->get('api/v1/references/sections/{departmentId}', ReferencesController::class, 'sections');
$router->get('api/v1/references/divisions/{sectionId}', ReferencesController::class, 'divisions');

// === Movement Routes ===
$router->get('api/v1/movements', MovementController::class, 'index');
$router->get('api/v1/movements/{id}', MovementController::class, 'show');
$router->post('api/v1/movements', MovementController::class, 'store');
$router->put('api/v1/movements/{id}', MovementController::class, 'update');
$router->delete('api/v1/movements/{id}', MovementController::class, 'destroy');
$router->get('api/v1/movements/{id}/photos', MovementController::class, 'photos');
$router->post('api/v1/movements/{id}/photos', MovementController::class, 'uploadPhotos');

// === User Routes ===
$router->get('api/v1/users', UserController::class, 'index');
$router->get('api/v1/users/{id}', UserController::class, 'show');
$router->post('api/v1/users', UserController::class, 'store');
$router->put('api/v1/users/{id}', UserController::class, 'update');
$router->delete('api/v1/users/{id}', UserController::class, 'destroy');

// === Violation Routes ===
$router->get('api/v1/violations/stats', ViolationController::class, 'stats');
$router->get('api/v1/violations', ViolationController::class, 'index');
$router->get('api/v1/violations/{id}', ViolationController::class, 'show');
$router->post('api/v1/violations', ViolationController::class, 'store');
$router->put('api/v1/violations/{id}', ViolationController::class, 'update');
$router->delete('api/v1/violations/{id}', ViolationController::class, 'destroy');

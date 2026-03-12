<?php
/**
 * Dashboard Configuration Helper
 * 
 * Detects environment (local dev vs InfinityFree) and loads DB config.
 * Returns a $dbConfig array for use by header.php.
 */

// Prevent double inclusion
if (defined('VM_CONFIG_LOADED')) return;
define('VM_CONFIG_LOADED', true);

// Detect base path
$basePath = dirname(dirname(__DIR__)); // htdocs/vehicle_management

// Load database config
$dbConfigFile = $basePath . '/config/database.php';
if (file_exists($dbConfigFile)) {
    $dbConfig = require $dbConfigFile;
} else {
    $dbConfig = [
        'host'     => getenv('DB_HOST') ?: 'localhost',
        'username' => getenv('DB_USER') ?: '',
        'password' => getenv('DB_PASS') ?: '',
        'database' => getenv('DB_NAME') ?: 'vehicle_management',
        'charset'  => 'utf8mb4',
    ];
}

// Helper: Get DB connection (lazy, cached)
function vm_get_db() {
    global $dbConfig;
    static $conn = null;
    if ($conn !== null) return $conn;

    mysqli_report(MYSQLI_REPORT_OFF);
    try {
        $conn = @new mysqli(
            $dbConfig['host'] ?? 'localhost',
            $dbConfig['username'] ?? '',
            $dbConfig['password'] ?? '',
            $dbConfig['database'] ?? 'vehicle_management'
        );
        if ($conn->connect_error) {
            error_log("Dashboard DB connection failed: " . $conn->connect_error);
            $conn = null;
            return null;
        }
        $conn->set_charset($dbConfig['charset'] ?? 'utf8mb4');
    } catch (\Throwable $e) {
        error_log("Dashboard DB exception: " . $e->getMessage());
        $conn = null;
    }
    return $conn;
}

// Helper: Fetch theme from DB
function vm_get_theme() {
    $db = vm_get_db();
    if (!$db) return null;

    try {
        $result = $db->query("SELECT * FROM themes WHERE is_active = 1 LIMIT 1");
        if (!$result) return null;
        $theme = $result->fetch_assoc();
        if (!$theme) return null;

        $themeId = (int)$theme['id'];

        // Load colors
        $theme['colors'] = [];
        $cr = $db->query("SELECT setting_key, color_value FROM color_settings WHERE theme_id = {$themeId} AND is_active = 1");
        if ($cr) {
            while ($row = $cr->fetch_assoc()) {
                $theme['colors'][$row['setting_key']] = $row['color_value'];
            }
        }

        // Load design settings
        $theme['design'] = [];
        $dr = $db->query("SELECT setting_key, setting_value FROM design_settings WHERE theme_id = {$themeId} AND is_active = 1");
        if ($dr) {
            while ($row = $dr->fetch_assoc()) {
                $theme['design'][$row['setting_key']] = $row['setting_value'];
            }
        }

        return $theme;
    } catch (\Throwable $e) {
        error_log("vm_get_theme error: " . $e->getMessage());
        return null;
    }
}

// Helper: Get public settings from DB
function vm_get_settings() {
    $db = vm_get_db();
    if (!$db) return [];

    try {
        $result = $db->query("SELECT setting_key, setting_value FROM system_settings WHERE is_public = 1");
        if (!$result) return [];
        $settings = [];
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    } catch (\Throwable $e) {
        error_log("vm_get_settings error: " . $e->getMessage());
        return [];
    }
}

// Helper: Get user permissions (by role_id)
function vm_get_permissions($roleId) {
    $db = vm_get_db();
    if (!$db) return ['*']; // Fallback: allow everything if DB unavailable
    if ((int)$roleId <= 2) return ['*']; // Superadmin/admin has all

    try {
        $roleId = (int)$roleId;
        $result = $db->query("SELECT p.key_name FROM permissions p 
            JOIN role_permissions rp ON rp.permission_id = p.id 
            WHERE rp.role_id = {$roleId} AND p.is_active = 1");
        if (!$result) return [];
        $perms = [];
        while ($row = $result->fetch_assoc()) {
            $perms[] = $row['key_name'];
        }
        return $perms;
    } catch (\Throwable $e) {
        error_log("vm_get_permissions error: " . $e->getMessage());
        return [];
    }
}

// Detect base URL for links
$appBaseUrl = getenv('APP_BASE_URL');
if ($appBaseUrl === false || $appBaseUrl === '') {
    // Try to detect from REQUEST_URI
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (preg_match('#(/vehicle_management)#', $uri, $m)) {
        $appBaseUrl = $m[1];
    } else {
        $appBaseUrl = '';
    }
}
$publicUrl = $appBaseUrl . '/public';

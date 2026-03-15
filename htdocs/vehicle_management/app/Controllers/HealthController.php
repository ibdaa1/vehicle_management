<?php
/**
 * Health Controller – diagnostic endpoint for production debugging.
 *
 * Returns PHP version, loaded extensions, database connection status,
 * and table existence checks. No authentication required.
 */

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;

class HealthController extends BaseController
{
    /**
     * GET /api/v1/health
     *
     * Returns system health information for debugging on shared hosting.
     */
    public function check(Request $request, array $params = []): void
    {
        $health = [
            'status'     => 'ok',
            'php'        => PHP_VERSION,
            'sapi'       => PHP_SAPI,
            'extensions' => [],
            'database'   => ['status' => 'unknown'],
            'tables'     => [],
            'config'     => [],
            'server'     => [],
        ];

        // Check required extensions
        $requiredExtensions = ['mysqli', 'json', 'mbstring', 'session'];
        foreach ($requiredExtensions as $ext) {
            $health['extensions'][$ext] = extension_loaded($ext);
            if (!extension_loaded($ext)) {
                $health['status'] = 'error';
            }
        }

        // Check config files
        $basePath = dirname(__DIR__, 2);
        $health['config']['app.php'] = file_exists($basePath . '/config/app.php');
        $health['config']['database.php'] = file_exists($basePath . '/config/database.php');
        $health['config']['routes.php'] = file_exists($basePath . '/config/routes.php');
        $health['config']['autoload.php'] = file_exists($basePath . '/config/autoload.php');

        // Server info
        $health['server']['document_root'] = $_SERVER['DOCUMENT_ROOT'] ?? 'unknown';
        $health['server']['request_uri'] = $_SERVER['REQUEST_URI'] ?? 'unknown';
        $health['server']['script_filename'] = $_SERVER['SCRIPT_FILENAME'] ?? 'unknown';

        // Test database connection
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection(); // Force connection
            $health['database'] = [
                'status'  => 'connected',
                'host'    => $conn->host_info ?? 'unknown',
                'version' => $conn->server_info ?? 'unknown',
                'charset' => $conn->character_set_name() ?? 'unknown',
            ];

            // Check expected tables
            $expectedTables = [
                'users', 'user_sessions', 'roles', 'permissions',
                'role_permissions', 'resource_permissions',
                'system_settings', 'themes', 'design_settings',
                'color_settings', 'font_settings', 'card_styles',
                'button_styles', 'activity_logs',
                'Departments', 'Sections', 'Divisions',
                'vehicles', 'vehicle_maintenance', 'vehicle_violations',
                'vehicle_movements', 'vehicle_movement_photos',
            ];
            foreach ($expectedTables as $table) {
                try {
                    $result = $db->fetchOne("SELECT COUNT(*) as cnt FROM `{$table}`");
                    $health['tables'][$table] = [
                        'exists' => true,
                        'rows'   => (int)($result['cnt'] ?? 0),
                    ];
                } catch (\Throwable $e) {
                    $health['tables'][$table] = [
                        'exists' => false,
                        'error'  => $e->getMessage(),
                    ];
                    $health['status'] = 'degraded';
                }
            }
        } catch (\Throwable $e) {
            $health['database'] = [
                'status' => 'error',
                'error'  => $e->getMessage(),
            ];
            $health['status'] = 'error';
        }

        // Check error log
        $logFile = $basePath . '/logs/api_errors.log';
        if (file_exists($logFile)) {
            $health['error_log'] = [
                'exists' => true,
                'size'   => filesize($logFile),
                'last_lines' => array_slice(
                    array_filter(explode("\n", file_get_contents($logFile))),
                    -5
                ),
            ];
        } else {
            $health['error_log'] = ['exists' => false];
        }

        $statusCode = $health['status'] === 'ok' ? 200 : ($health['status'] === 'degraded' ? 200 : 503);
        Response::json([
            'success' => $health['status'] !== 'error',
            'data'    => $health,
        ], $statusCode);
    }
}

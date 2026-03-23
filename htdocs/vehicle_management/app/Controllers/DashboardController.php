<?php
/**
 * Dashboard Controller – provides aggregated stats for the frontend dashboard.
 */

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;

class DashboardController extends BaseController
{
    /**
     * GET /api/v1/dashboard/stats
     * Returns counts and metrics for the main dashboard.
     */
    public function stats(Request $request, array $params = []): void
    {
        $user = $this->requireAuth($request);
        if (Response::isSent()) return;

        $db = Database::getInstance();

        // Helper to safely count from a table (returns 0 if table doesn't exist)
        $safeCount = function (string $sql) use ($db): int {
            try {
                $row = $db->fetchOne($sql);
                return (int)($row['cnt'] ?? 0);
            } catch (\Throwable $e) {
                error_log("DashboardController::stats query error: " . $e->getMessage());
                return 0;
            }
        };

        Response::json([
            'success' => true,
            'data' => [
                'vehicles_total'          => $safeCount("SELECT COUNT(*) as cnt FROM vehicles"),
                'vehicles_operational'    => $safeCount("SELECT COUNT(*) as cnt FROM vehicles WHERE status='operational'"),
                'vehicles_maintenance'    => $safeCount("SELECT COUNT(*) as cnt FROM vehicles WHERE status='maintenance'"),
                'vehicles_out_of_service' => $safeCount("SELECT COUNT(*) as cnt FROM vehicles WHERE status='out_of_service'"),
                'vehicles_private'        => $safeCount("SELECT COUNT(*) as cnt FROM vehicles WHERE vehicle_mode='private'"),
                'vehicles_shift'          => $safeCount("SELECT COUNT(*) as cnt FROM vehicles WHERE vehicle_mode='shift'"),
                'users_active'            => $safeCount("SELECT COUNT(*) as cnt FROM users WHERE is_active=1"),
                'violations_unpaid'       => $safeCount("SELECT COUNT(*) as cnt FROM vehicle_violations WHERE violation_status='unpaid'"),
                'maintenance_due'         => $safeCount("SELECT COUNT(*) as cnt FROM vehicle_maintenance WHERE next_visit_date <= CURDATE()"),
            ],
        ]);
        return;
    }
}
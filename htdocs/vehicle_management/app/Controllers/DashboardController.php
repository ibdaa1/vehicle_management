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

        $vehicles     = $db->fetchOne("SELECT COUNT(*) as cnt FROM vehicles") ?? ['cnt' => 0];
        $operational  = $db->fetchOne("SELECT COUNT(*) as cnt FROM vehicles WHERE status='operational'") ?? ['cnt' => 0];
        $maintenance  = $db->fetchOne("SELECT COUNT(*) as cnt FROM vehicles WHERE status='maintenance'") ?? ['cnt' => 0];
        $outOfService = $db->fetchOne("SELECT COUNT(*) as cnt FROM vehicles WHERE status='out_of_service'") ?? ['cnt' => 0];
        $privateCnt   = $db->fetchOne("SELECT COUNT(*) as cnt FROM vehicles WHERE vehicle_mode='private'") ?? ['cnt' => 0];
        $shiftCnt     = $db->fetchOne("SELECT COUNT(*) as cnt FROM vehicles WHERE vehicle_mode='shift'") ?? ['cnt' => 0];
        $users        = $db->fetchOne("SELECT COUNT(*) as cnt FROM users WHERE is_active=1") ?? ['cnt' => 0];
        $violations   = $db->fetchOne("SELECT COUNT(*) as cnt FROM vehicle_violations WHERE violation_status='unpaid'") ?? ['cnt' => 0];
        $maintenanceDue = $db->fetchOne("SELECT COUNT(*) as cnt FROM vehicle_maintenance WHERE next_visit_date <= CURDATE()") ?? ['cnt' => 0];

        Response::json([
            'success' => true,
            'data' => [
                'vehicles_total'     => (int)$vehicles['cnt'],
                'vehicles_operational' => (int)$operational['cnt'],
                'vehicles_maintenance' => (int)$maintenance['cnt'],
                'vehicles_out_of_service' => (int)$outOfService['cnt'],
                'vehicles_private'   => (int)$privateCnt['cnt'],
                'vehicles_shift'     => (int)$shiftCnt['cnt'],
                'users_active'       => (int)$users['cnt'],
                'violations_unpaid'  => (int)$violations['cnt'],
                'maintenance_due'    => (int)$maintenanceDue['cnt'],
            ],
        ]);
        return;
    }
}

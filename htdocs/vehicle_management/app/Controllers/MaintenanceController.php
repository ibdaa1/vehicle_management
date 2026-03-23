<?php
/**
 * Maintenance Controller
 *
 * Handles vehicle maintenance CRUD operations.
 */

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Models\VehicleMaintenance;

class MaintenanceController extends BaseController
{
    private VehicleMaintenance $maintenanceModel;

    public function __construct()
    {
        $this->maintenanceModel = new VehicleMaintenance();
    }

    /**
     * GET /api/v1/maintenance
     */
    public function index(Request $request, array $params = []): void
    {
        $this->requireAuth($request);
        if (Response::isSent()) return;

        $filters = $request->only(['vehicle_code', 'maintenance_type']);

        try {
            $records = $this->maintenanceModel->allFiltered($filters);
        } catch (\Throwable $e) {
            error_log("MaintenanceController::index error: " . $e->getMessage());
            $records = [];
        }

        Response::json(['success' => true, 'data' => $records]);
    }

    /**
     * GET /api/v1/maintenance/stats
     */
    public function stats(Request $request, array $params = []): void
    {
        $this->requireAuth($request);
        if (Response::isSent()) return;

        try {
            $stats = $this->maintenanceModel->stats();
        } catch (\Throwable $e) {
            error_log("MaintenanceController::stats error: " . $e->getMessage());
            $stats = ['total' => 0, 'routine' => 0, 'emergency' => 0,
                       'technical_check' => 0, 'upcoming' => 0, 'overdue' => 0];
        }

        Response::json(['success' => true, 'data' => $stats]);
    }

    /**
     * GET /api/v1/maintenance/{id}
     */
    public function show(Request $request, array $params = []): void
    {
        $this->requireAuth($request);
        if (Response::isSent()) return;

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            Response::error('Invalid maintenance record ID', 400);
            return;
        }

        try {
            $record = $this->maintenanceModel->findWithVehicle($id);
            if (!$record) {
                Response::error('Maintenance record not found', 404);
                return;
            }
        } catch (\Throwable $e) {
            error_log("MaintenanceController::show error: " . $e->getMessage());
            Response::error('Failed to load maintenance record', 500);
            return;
        }

        Response::json(['success' => true, 'data' => $record]);
    }

    /**
     * POST /api/v1/maintenance
     */
    public function store(Request $request, array $params = []): void
    {
        $user = $this->requirePermission($request, 'manage_maintenance');
        if (Response::isSent()) return;

        $data = $request->only([
            'vehicle_code', 'visit_date', 'next_visit_date',
            'maintenance_type', 'location', 'notes',
        ]);

        $missing = $this->validateRequired($data, ['vehicle_code', 'visit_date']);
        if (!empty($missing)) {
            Response::error('Missing required fields: ' . implode(', ', $missing), 400);
            return;
        }

        $data['created_by'] = $user['emp_id'] ?? (string)$user['id'];

        try {
            $recordId = $this->maintenanceModel->create($data);

            $this->logActivity($user, 'vehicle_maintenance', 'vehicle_maintenance', $recordId,
                "Created maintenance record for vehicle {$data['vehicle_code']}");

            $record = $this->maintenanceModel->findWithVehicle($recordId);
            Response::json(['success' => true, 'message' => 'Maintenance record created', 'data' => $record], 201);
        } catch (\Throwable $e) {
            error_log("MaintenanceController::store error: " . $e->getMessage());
            Response::error('Failed to create maintenance record: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/v1/maintenance/{id}
     */
    public function update(Request $request, array $params = []): void
    {
        $user = $this->requirePermission($request, 'manage_maintenance');
        if (Response::isSent()) return;

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            Response::error('Invalid maintenance record ID', 400);
            return;
        }

        try {
            $existing = $this->maintenanceModel->find($id);
            if (!$existing) {
                Response::error('Maintenance record not found', 404);
                return;
            }
        } catch (\Throwable $e) {
            Response::error('Failed to find maintenance record', 500);
            return;
        }

        $data = $request->only([
            'vehicle_code', 'visit_date', 'next_visit_date',
            'maintenance_type', 'location', 'notes',
        ]);

        $data['updated_by'] = $user['emp_id'] ?? (string)$user['id'];
        $data = array_filter($data, fn($v) => $v !== null && $v !== '');

        try {
            $ok = $this->maintenanceModel->update($id, $data);
            if (!$ok) {
                Response::error('Failed to update maintenance record', 500);
                return;
            }

            $this->logActivity($user, 'vehicle_maintenance', 'vehicle_maintenance', $id,
                "Updated maintenance record #{$id}");

            $record = $this->maintenanceModel->findWithVehicle($id);
            Response::json(['success' => true, 'message' => 'Maintenance record updated', 'data' => $record]);
        } catch (\Throwable $e) {
            error_log("MaintenanceController::update error: " . $e->getMessage());
            Response::error('Failed to update maintenance record', 500);
        }
    }

    /**
     * DELETE /api/v1/maintenance/{id}
     */
    public function destroy(Request $request, array $params = []): void
    {
        $user = $this->requirePermission($request, 'manage_maintenance');
        if (Response::isSent()) return;

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            Response::error('Invalid maintenance record ID', 400);
            return;
        }

        try {
            $existing = $this->maintenanceModel->find($id);
            if (!$existing) {
                Response::error('Maintenance record not found', 404);
                return;
            }

            $ok = $this->maintenanceModel->delete($id);
            if (!$ok) {
                Response::error('Failed to delete maintenance record', 500);
                return;
            }

            $this->logActivity($user, 'vehicle_maintenance', 'vehicle_maintenance', $id,
                "Deleted maintenance record #{$id} for vehicle {$existing['vehicle_code']}");

            Response::json(['success' => true, 'message' => 'Maintenance record deleted']);
        } catch (\Throwable $e) {
            error_log("MaintenanceController::destroy error: " . $e->getMessage());
            Response::error('Failed to delete maintenance record', 500);
        }
    }

    /**
     * Log an activity to activity_logs table.
     */
    private function logActivity(array $user, string $type, string $tableName, int $recordId, string $description): void
    {
        try {
            $db = Database::getInstance();
            $db->execute(
                "INSERT INTO activity_logs (user_id, emp_id, activity_type, description, table_name, record_id, ip_address, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                'issssis' . 's',
                [
                    $user['id'],
                    $user['emp_id'] ?? '',
                    $type,
                    $description,
                    $tableName,
                    $recordId,
                    $_SERVER['REMOTE_ADDR'] ?? '',
                    substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                ]
            );
        } catch (\Throwable $e) {
            error_log("Activity log error: " . $e->getMessage());
        }
    }
}

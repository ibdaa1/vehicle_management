<?php
/**
 * Violation Controller
 *
 * Handles vehicle violation CRUD operations with vehicle-holder lookup.
 */

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Models\VehicleViolation;

class ViolationController extends BaseController
{
    private VehicleViolation $violationModel;

    public function __construct()
    {
        $this->violationModel = new VehicleViolation();
    }

    /**
     * GET /api/v1/violations
     */
    public function index(Request $request, array $params = []): void
    {
        $this->requireAuth($request);
        if (Response::isSent()) return;

        $filters = $request->only(['vehicle_code', 'violation_status']);

        try {
            $violations = $this->violationModel->allWithHolder($filters);
        } catch (\Throwable $e) {
            error_log("ViolationController::index error: " . $e->getMessage());
            $violations = [];
        }

        Response::json(['success' => true, 'data' => $violations]);
    }

    /**
     * GET /api/v1/violations/stats
     */
    public function stats(Request $request, array $params = []): void
    {
        $this->requireAuth($request);
        if (Response::isSent()) return;

        try {
            $stats = $this->violationModel->stats();
        } catch (\Throwable $e) {
            error_log("ViolationController::stats error: " . $e->getMessage());
            $stats = ['total' => 0, 'paid' => 0, 'unpaid' => 0,
                       'total_amount' => 0, 'paid_amount' => 0, 'unpaid_amount' => 0];
        }

        Response::json(['success' => true, 'data' => $stats]);
    }

    /**
     * GET /api/v1/violations/{id}
     */
    public function show(Request $request, array $params = []): void
    {
        $this->requireAuth($request);
        if (Response::isSent()) return;

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            Response::error('Invalid violation ID', 400);
            return;
        }

        try {
            $violation = $this->violationModel->findWithHolder($id);
            if (!$violation) {
                Response::error('Violation not found', 404);
                return;
            }
        } catch (\Throwable $e) {
            error_log("ViolationController::show error: " . $e->getMessage());
            Response::error('Failed to load violation', 500);
            return;
        }

        Response::json(['success' => true, 'data' => $violation]);
    }

    /**
     * POST /api/v1/violations
     */
    public function store(Request $request, array $params = []): void
    {
        $user = $this->requireAuth($request);
        if (Response::isSent()) return;

        $data = $request->only([
            'vehicle_code', 'violation_datetime', 'violation_amount',
            'violation_status', 'notes',
        ]);

        $missing = $this->validateRequired($data, ['vehicle_code', 'violation_datetime', 'violation_amount']);
        if (!empty($missing)) {
            Response::error('Missing required fields: ' . implode(', ', $missing), 400);
            return;
        }

        // Look up vehicle_id from vehicle_code
        try {
            $db = Database::getInstance();
            $vehicle = $db->fetchOne(
                "SELECT id FROM vehicles WHERE vehicle_code = ? LIMIT 1",
                's', [$data['vehicle_code']]
            );
            $data['vehicle_id'] = $vehicle ? (int)$vehicle['id'] : 0;
        } catch (\Throwable $e) {
            $data['vehicle_id'] = 0;
        }

        $data['issued_by_emp_id'] = $user['emp_id'] ?? (string)$user['id'];
        $data['violation_amount'] = (float)$data['violation_amount'];
        if (empty($data['violation_status'])) {
            $data['violation_status'] = 'unpaid';
        }

        try {
            $recordId = $this->violationModel->create($data);
            if ($recordId === false) {
                Response::error('Failed to create violation', 500);
                return;
            }

            $this->logActivity($user, 'vehicle_violation', 'vehicle_violations', $recordId,
                "Created violation for vehicle {$data['vehicle_code']}");

            $record = $this->violationModel->findWithHolder($recordId);
            Response::json(['success' => true, 'message' => 'Violation created', 'data' => $record], 201);
        } catch (\Throwable $e) {
            error_log("ViolationController::store error: " . $e->getMessage());
            Response::error('Failed to create violation: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/v1/violations/{id}
     */
    public function update(Request $request, array $params = []): void
    {
        $user = $this->requireAuth($request);
        if (Response::isSent()) return;

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            Response::error('Invalid violation ID', 400);
            return;
        }

        try {
            $existing = $this->violationModel->find($id);
            if (!$existing) {
                Response::error('Violation not found', 404);
                return;
            }
        } catch (\Throwable $e) {
            Response::error('Failed to find violation', 500);
            return;
        }

        $data = $request->only([
            'vehicle_code', 'violation_datetime', 'violation_amount',
            'violation_status', 'paid_by_emp_id', 'payment_datetime', 'notes',
        ]);

        if (isset($data['violation_amount'])) {
            $data['violation_amount'] = (float)$data['violation_amount'];
        }

        $data['updated_at'] = date('Y-m-d H:i:s');
        $data = array_filter($data, fn($v) => $v !== null && $v !== '');

        try {
            $ok = $this->violationModel->update($id, $data);
            if (!$ok) {
                Response::error('Failed to update violation', 500);
                return;
            }

            $this->logActivity($user, 'vehicle_violation', 'vehicle_violations', $id,
                "Updated violation #{$id}");

            $record = $this->violationModel->findWithHolder($id);
            Response::json(['success' => true, 'message' => 'Violation updated', 'data' => $record]);
        } catch (\Throwable $e) {
            error_log("ViolationController::update error: " . $e->getMessage());
            Response::error('Failed to update violation', 500);
        }
    }

    /**
     * DELETE /api/v1/violations/{id}
     */
    public function destroy(Request $request, array $params = []): void
    {
        $user = $this->requireAdmin($request);
        if (Response::isSent()) return;

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            Response::error('Invalid violation ID', 400);
            return;
        }

        try {
            $existing = $this->violationModel->find($id);
            if (!$existing) {
                Response::error('Violation not found', 404);
                return;
            }

            $ok = $this->violationModel->delete($id);
            if (!$ok) {
                Response::error('Failed to delete violation', 500);
                return;
            }

            $this->logActivity($user, 'vehicle_violation', 'vehicle_violations', $id,
                "Deleted violation #{$id} for vehicle {$existing['vehicle_code']}");

            Response::json(['success' => true, 'message' => 'Violation deleted']);
        } catch (\Throwable $e) {
            error_log("ViolationController::destroy error: " . $e->getMessage());
            Response::error('Failed to delete violation', 500);
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

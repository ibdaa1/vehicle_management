<?php
/**
 * Vehicle Controller – CRUD for vehicles.
 */

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Models\Vehicle;
use App\Models\VehicleMovement;

class VehicleController extends BaseController
{
    private Vehicle $vehicleModel;
    private VehicleMovement $movementModel;

    public function __construct()
    {
        $this->vehicleModel  = new Vehicle();
        $this->movementModel = new VehicleMovement();
    }

    /**
     * GET /api/v1/vehicles
     */
    public function index(Request $request, array $params = []): void
    {
        $this->requirePermission($request, 'manage_vehicles');
        if (Response::isSent()) return;

        $filters = $request->only(['status', 'vehicle_mode', 'department_id', 'gender']);
        try {
            $vehicles        = $this->vehicleModel->allWithRelations($filters);
            $latestMovements = $this->movementModel->getLatestByVehicle();
            foreach ($vehicles as &$v) {
                $code = $v['vehicle_code'] ?? '';
                if (isset($latestMovements[$code])) {
                    $v['last_operation'] = $latestMovements[$code]['operation_type'];
                    $v['last_holder']    = $latestMovements[$code]['performed_by'] ?? null;
                } else {
                    $v['last_operation'] = null;
                    $v['last_holder']    = null;
                }
                $v['available'] = ($v['last_operation'] === null || $v['last_operation'] === 'return');
            }
            unset($v);
        } catch (\Throwable $e) {
            error_log("VehicleController::index error: " . $e->getMessage());
            $vehicles = [];
        }

        Response::json(['success' => true, 'data' => $vehicles]);
    }

    /**
     * GET /api/v1/vehicles/{id}
     */
    public function show(Request $request, array $params = []): void
    {
        $this->requirePermission($request, 'manage_vehicles');
        if (Response::isSent()) return;

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) { Response::error('Invalid vehicle ID', 400); return; }

        $vehicle = $this->vehicleModel->find($id);
        if (!$vehicle) { Response::error('Vehicle not found', 404); return; }

        Response::json(['success' => true, 'data' => $vehicle]);
    }

    /**
     * POST /api/v1/vehicles
     */
    public function store(Request $request, array $params = []): void
    {
        $user = $this->requirePermission($request, 'manage_vehicles');
        if (Response::isSent()) return;

        $data = $request->only([
            'vehicle_code', 'type', 'vehicle_category', 'manufacture_year', 'emp_id',
            'driver_name', 'driver_phone', 'status', 'department_id',
            'section_id', 'division_id', 'vehicle_mode', 'gender', 'notes',
        ]);

        $missing = $this->validateRequired($data, ['vehicle_code', 'type', 'manufacture_year']);
        if (!empty($missing)) {
            Response::error('Missing required fields: ' . implode(', ', $missing), 400);
            return;
        }

        $data['created_by'] = $user['id'];
        try {
            $id      = $this->vehicleModel->create($data);
            $vehicle = $this->vehicleModel->find($id);
            Response::json(['success' => true, 'message' => 'Vehicle created', 'data' => $vehicle], 201);
        } catch (\Throwable $e) {
            error_log("VehicleController::store error: " . $e->getMessage());
            Response::error('Failed to create vehicle: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/v1/vehicles/{id}
     */
    public function update(Request $request, array $params = []): void
    {
        $user = $this->requirePermission($request, 'manage_vehicles');
        if (Response::isSent()) return;

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) { Response::error('Invalid vehicle ID', 400); return; }

        $vehicle = $this->vehicleModel->find($id);
        if (!$vehicle) { Response::error('Vehicle not found', 404); return; }

        $data = $request->only([
            'vehicle_code', 'type', 'vehicle_category', 'manufacture_year', 'emp_id',
            'driver_name', 'driver_phone', 'status', 'department_id',
            'section_id', 'division_id', 'vehicle_mode', 'gender', 'notes',
        ]);
        $data = array_filter($data, fn($v) => $v !== null && $v !== '');
        $data['updated_by'] = $user['id'];

        if (empty($data)) { Response::error('No fields to update', 400); return; }

        try {
            $this->vehicleModel->update($id, $data);
            $vehicle = $this->vehicleModel->find($id);
            Response::json(['success' => true, 'message' => 'Vehicle updated', 'data' => $vehicle]);
        } catch (\Throwable $e) {
            error_log("VehicleController::update error: " . $e->getMessage());
            Response::error('Failed to update vehicle: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/v1/vehicles/{id}
     */
    public function destroy(Request $request, array $params = []): void
    {
        $this->requirePermission($request, 'manage_vehicles');
        if (Response::isSent()) return;

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) { Response::error('Invalid vehicle ID', 400); return; }

        $vehicle = $this->vehicleModel->find($id);
        if (!$vehicle) { Response::error('Vehicle not found', 404); return; }

        $success = $this->vehicleModel->delete($id);
        if (!$success) { Response::error('Failed to delete vehicle', 500); return; }

        Response::success(null, 'Vehicle deleted successfully');
    }

    /**
     * GET /api/v1/vehicles/my-vehicles
     * - Private : vehicles where emp_id + gender match the logged-in user
     * - Shift   : ONE vehicle only (next in round-robin for user's gender)
     *             If the user already holds a shift vehicle → show that for
     *             return only, do NOT show next vehicle for pickup.
     */
    public function myVehicles(Request $request, array $params = []): void
    {
        $user = $this->requireAuth($request);
        if (Response::isSent()) return;

        $userEmpId  = $user['emp_id'] ?? '';
        $userGender = $user['gender'] ?? null;

        try {
            $allVehicles     = $this->vehicleModel->allWithRelations([]);
            $latestMovements = $this->movementModel->getLatestByVehicle();

            foreach ($allVehicles as &$v) {
                $code = $v['vehicle_code'] ?? '';
                if (isset($latestMovements[$code])) {
                    $v['last_operation'] = $latestMovements[$code]['operation_type'];
                    $v['last_holder']    = $latestMovements[$code]['performed_by'] ?? null;
                } else {
                    $v['last_operation'] = null;
                    $v['last_holder']    = null;
                }
                $v['available'] = ($v['last_operation'] === null || $v['last_operation'] === 'return');
            }
            unset($v);

            // Private: emp_id + gender match
            $privateVehicles = array_values(array_filter($allVehicles, function ($v) use ($userEmpId, $userGender) {
                return ($v['vehicle_mode'] ?? '') === 'private'
                    && trim($v['emp_id'] ?? '') !== ''
                    && trim($userEmpId)           !== ''
                    && trim($v['emp_id'] ?? '') === trim($userEmpId)
                    && ($v['status']     ?? '') === 'operational'
                    && (!$userGender || empty($v['gender']) || $v['gender'] === $userGender);
            }));

            // Shift: operational + gender match
            $shiftVehicles = array_values(array_filter($allVehicles, function ($v) use ($userGender) {
                return ($v['vehicle_mode'] ?? '') === 'shift'
                    && ($v['status']       ?? '') === 'operational'
                    && (!$userGender || empty($v['gender']) || $v['gender'] === $userGender);
            }));

            usort($shiftVehicles, fn($a, $b) => strcmp($a['vehicle_code'] ?? '', $b['vehicle_code'] ?? ''));

            $nextShiftVehicle  = null;
            $myCheckedOutShift = null;

            if (!empty($shiftVehicles)) {
                // FIX: compare with emp_id (FK → users.emp_id)
                foreach ($shiftVehicles as $v) {
                    if (!$v['available'] && ($v['last_holder'] ?? '') === $userEmpId) {
                        $myCheckedOutShift = $v;
                        break;
                    }
                }

                // FIX: only calculate next vehicle if user has no checked-out shift vehicle
                if ($myCheckedOutShift === null) {
                    $shiftCodes     = array_column($shiftVehicles, 'vehicle_code');
                    $lastPickupCode = $this->movementModel->getLastPickupForShiftVehicles($shiftCodes);

                    if ($lastPickupCode) {
                        $lastIdx = -1;
                        foreach ($shiftVehicles as $i => $v) {
                            if ($v['vehicle_code'] === $lastPickupCode) {
                                $lastIdx = $i;
                                break;
                            }
                        }
                        $count = count($shiftVehicles);
                        for ($j = 1; $j <= $count; $j++) {
                            $idx = ($lastIdx + $j) % $count;
                            if ($shiftVehicles[$idx]['available']) {
                                $nextShiftVehicle = $shiftVehicles[$idx];
                                $nextShiftVehicle['turn_order'] = $idx + 1;
                                break;
                            }
                        }
                    }

                    // No history yet → first available
                    if (!$nextShiftVehicle) {
                        foreach ($shiftVehicles as $i => $v) {
                            if ($v['available']) {
                                $nextShiftVehicle = $v;
                                $nextShiftVehicle['turn_order'] = $i + 1;
                                break;
                            }
                        }
                    }
                }
                // If $myCheckedOutShift !== null → $nextShiftVehicle stays null (no pickup shown)
            }

            Response::json([
                'success' => true,
                'data'    => [
                    'private'          => $privateVehicles,
                    'shift_next'       => $nextShiftVehicle,
                    'shift_my_current' => $myCheckedOutShift,
                    'shift_total'      => count($shiftVehicles),
                ],
            ]);

        } catch (\Throwable $e) {
            error_log("VehicleController::myVehicles error: " . $e->getMessage());
            Response::json([
                'success' => true,
                'data'    => [
                    'private'          => [],
                    'shift_next'       => null,
                    'shift_my_current' => null,
                    'shift_total'      => 0,
                ],
            ]);
        }
    }

    /**
     * GET /api/v1/vehicles/list
     * Lightweight list for dropdowns and cross-filtering.
     * FIX: queries DB directly to guarantee department_id, section_id, division_id
     * are always present — allWithRelations() may not return them in all setups.
     */
    public function list(Request $request, array $params = []): void
    {
        $this->requireAuth($request);
        if (Response::isSent()) return;

        try {
            $db   = Database::getInstance();
            $rows = $db->fetchAll(
                "SELECT
                    v.id,
                    v.vehicle_code,
                    v.type,
                    v.type              AS vehicle_type,
                    v.vehicle_category,
                    v.status,
                    v.vehicle_mode,
                    v.gender,
                    v.department_id,
                    v.section_id,
                    v.division_id,
                    COALESCE(d.name_ar,  '') AS department_name,
                    COALESCE(s.name_ar,  '') AS section_name,
                    COALESCE(dv.name_ar, '') AS division_name
                FROM vehicles v
                LEFT JOIN Departments d  ON d.department_id  = v.department_id
                LEFT JOIN Sections    s  ON s.section_id     = v.section_id
                LEFT JOIN Divisions   dv ON dv.division_id   = v.division_id
                ORDER BY v.vehicle_code ASC"
            );
        } catch (\Throwable $e) {
            error_log("VehicleController::list error: " . $e->getMessage());
            $rows = [];
        }

        Response::json(['success' => true, 'data' => $rows]);
    }

    /**
     * GET /api/v1/vehicles/stats
     */
    public function stats(Request $request, array $params = []): void
    {
        $this->requirePermission($request, 'manage_vehicles');
        if (Response::isSent()) return;

        try {
            $stats = $this->vehicleModel->getStats();
        } catch (\Throwable $e) {
            error_log("VehicleController::stats error: " . $e->getMessage());
            $stats = ['total' => 0, 'operational' => 0, 'maintenance' => 0, 'out_of_service' => 0, 'private' => 0, 'shift' => 0];
        }
        Response::json(['success' => true, 'data' => $stats]);
    }
}

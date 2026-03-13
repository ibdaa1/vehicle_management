<?php
/**
 * Vehicle Controller – CRUD for vehicles.
 */

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Vehicle;
use App\Models\VehicleMovement;

class VehicleController extends BaseController
{
    private Vehicle $vehicleModel;
    private VehicleMovement $movementModel;

    public function __construct()
    {
        $this->vehicleModel = new Vehicle();
        $this->movementModel = new VehicleMovement();
    }

    /**
     * GET /api/v1/vehicles
     */
    public function index(Request $request, array $params = []): void
    {
        $this->requireAuth($request);
        if (Response::isSent()) return;

        $filters = $request->only(['status', 'vehicle_mode', 'department_id', 'gender']);
        try {
            $vehicles = $this->vehicleModel->allWithRelations($filters);
            // Add availability info based on latest movement
            $latestMovements = $this->movementModel->getLatestByVehicle();
            foreach ($vehicles as &$v) {
                $code = $v['vehicle_code'] ?? '';
                if (isset($latestMovements[$code])) {
                    $v['last_operation'] = $latestMovements[$code]['operation_type'];
                    $v['last_holder'] = $latestMovements[$code]['performed_by'] ?? null;
                } else {
                    $v['last_operation'] = null;
                    $v['last_holder'] = null;
                }
                // available = no movement or last was return
                $v['available'] = ($v['last_operation'] === null || $v['last_operation'] === 'return');
            }
            unset($v);
        } catch (\Throwable $e) {
            error_log("VehicleController::index error: " . $e->getMessage());
            $vehicles = [];
        }

        Response::json([
            'success' => true,
            'data' => $vehicles,
        ]);
        return;
    }

    /**
     * GET /api/v1/vehicles/{id}
     */
    public function show(Request $request, array $params = []): void
    {
        $this->requireAuth($request);
        if (Response::isSent()) return;

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            Response::error('Invalid vehicle ID', 400);
            return;
        }

        $vehicle = $this->vehicleModel->find($id);
        if (!$vehicle) {
            Response::error('Vehicle not found', 404);
            return;
        }

        Response::json(['success' => true, 'data' => $vehicle]);
        return;
    }

    /**
     * POST /api/v1/vehicles
     */
    public function store(Request $request, array $params = []): void
    {
        $user = $this->requireAuth($request);
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
        $id = $this->vehicleModel->create($data);
        if ($id === false) {
            Response::error('Failed to create vehicle', 500);
            return;
        }

        $vehicle = $this->vehicleModel->find($id);
        Response::json(['success' => true, 'message' => 'Vehicle created', 'data' => $vehicle], 201);
        return;
    }

    /**
     * PUT /api/v1/vehicles/{id}
     */
    public function update(Request $request, array $params = []): void
    {
        $user = $this->requireAuth($request);
        if (Response::isSent()) return;

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            Response::error('Invalid vehicle ID', 400);
            return;
        }

        $vehicle = $this->vehicleModel->find($id);
        if (!$vehicle) {
            Response::error('Vehicle not found', 404);
            return;
        }

        $data = $request->only([
            'vehicle_code', 'type', 'vehicle_category', 'manufacture_year', 'emp_id',
            'driver_name', 'driver_phone', 'status', 'department_id',
            'section_id', 'division_id', 'vehicle_mode', 'gender', 'notes',
        ]);
        $data = array_filter($data, fn($v) => $v !== null && $v !== '');
        $data['updated_by'] = $user['id'];

        if (empty($data)) {
            Response::error('No fields to update', 400);
            return;
        }

        $success = $this->vehicleModel->update($id, $data);
        if (!$success) {
            Response::error('Failed to update vehicle', 500);
            return;
        }

        $vehicle = $this->vehicleModel->find($id);
        Response::json(['success' => true, 'message' => 'Vehicle updated', 'data' => $vehicle]);
        return;
    }

    /**
     * DELETE /api/v1/vehicles/{id}
     */
    public function destroy(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);
        if (Response::isSent()) return;

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            Response::error('Invalid vehicle ID', 400);
            return;
        }

        $vehicle = $this->vehicleModel->find($id);
        if (!$vehicle) {
            Response::error('Vehicle not found', 404);
            return;
        }

        $success = $this->vehicleModel->delete($id);
        if (!$success) {
            Response::error('Failed to delete vehicle', 500);
            return;
        }

        Response::success(null, 'Vehicle deleted successfully');
        return;
    }

    /**
     * GET /api/v1/vehicles/my-vehicles
     * Returns vehicles the current employee is allowed to see/pickup:
     * - Private: only their own vehicle (emp_id + gender match)
     * - Shift: only the next-in-turn vehicle based on round-robin rotation for their gender
     */
    public function myVehicles(Request $request, array $params = []): void
    {
        $user = $this->requireAuth($request);
        if (Response::isSent()) return;

        $userEmpId = $user['emp_id'] ?? '';
        $userGender = $user['gender'] ?? null;
        $username = $user['username'] ?? '';

        try {
            $allVehicles = $this->vehicleModel->allWithRelations([]);
            $latestMovements = $this->movementModel->getLatestByVehicle();

            foreach ($allVehicles as &$v) {
                $code = $v['vehicle_code'] ?? '';
                if (isset($latestMovements[$code])) {
                    $v['last_operation'] = $latestMovements[$code]['operation_type'];
                    $v['last_holder'] = $latestMovements[$code]['performed_by'] ?? null;
                } else {
                    $v['last_operation'] = null;
                    $v['last_holder'] = null;
                }
                $v['available'] = ($v['last_operation'] === null || $v['last_operation'] === 'return');
            }
            unset($v);

            // --- Private vehicles: emp_id match AND gender match ---
            $privateVehicles = array_values(array_filter($allVehicles, function ($v) use ($userEmpId, $userGender) {
                return ($v['vehicle_mode'] ?? '') === 'private'
                    && trim($v['emp_id'] ?? '') != '' && trim($userEmpId) != ''
                    && trim($v['emp_id'] ?? '') == trim($userEmpId)
                    && ($v['status'] ?? '') === 'operational'
                    && (!$userGender || empty($v['gender']) || $v['gender'] === $userGender);
            }));

            // --- Shift vehicles for this gender ---
            $shiftVehicles = array_values(array_filter($allVehicles, function ($v) use ($userGender) {
                return ($v['vehicle_mode'] ?? '') === 'shift'
                    && ($v['status'] ?? '') === 'operational'
                    && (!$userGender || empty($v['gender']) || $v['gender'] === $userGender);
            }));

            // Sort by vehicle_code for consistent round-robin order
            usort($shiftVehicles, function ($a, $b) {
                return strcmp($a['vehicle_code'] ?? '', $b['vehicle_code'] ?? '');
            });

            // Determine next-in-turn vehicle using round-robin
            $nextShiftVehicle = null;
            $myCheckedOutShift = null;

            if (!empty($shiftVehicles)) {
                // Check if user currently holds a shift vehicle
                foreach ($shiftVehicles as $v) {
                    if (!$v['available'] && ($v['last_holder'] ?? '') === $username) {
                        $myCheckedOutShift = $v;
                        break;
                    }
                }

                // Find the last pickup for a shift vehicle of this gender (round-robin pivot)
                $shiftCodes = array_column($shiftVehicles, 'vehicle_code');
                $lastPickupCode = $this->movementModel->getLastPickupForShiftVehicles($shiftCodes);

                if ($lastPickupCode) {
                    // Find position of last picked-up vehicle in sorted list
                    $lastIdx = -1;
                    foreach ($shiftVehicles as $i => $v) {
                        if ($v['vehicle_code'] === $lastPickupCode) {
                            $lastIdx = $i;
                            break;
                        }
                    }

                    // Find next available vehicle starting after lastIdx (circular)
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

                // If no next found (all checked out or no history), pick first available
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

            Response::json([
                'success' => true,
                'data' => [
                    'private' => $privateVehicles,
                    'shift_next' => $nextShiftVehicle,
                    'shift_my_current' => $myCheckedOutShift,
                    'shift_total' => count($shiftVehicles),
                ],
            ]);
        } catch (\Throwable $e) {
            error_log("VehicleController::myVehicles error: " . $e->getMessage());
            Response::json([
                'success' => true,
                'data' => [
                    'private' => [],
                    'shift_next' => null,
                    'shift_my_current' => null,
                    'shift_total' => 0,
                ],
            ]);
        }
    }

    /**
     * GET /api/v1/vehicles/stats
     */
    public function stats(Request $request, array $params = []): void
    {
        $this->requireAuth($request);
        if (Response::isSent()) return;

        try {
            $stats = $this->vehicleModel->getStats();
        } catch (\Throwable $e) {
            error_log("VehicleController::stats error: " . $e->getMessage());
            $stats = ['total' => 0, 'operational' => 0, 'maintenance' => 0, 'out_of_service' => 0, 'private' => 0, 'shift' => 0];
        }
        Response::json(['success' => true, 'data' => $stats]);
        return;
    }
}

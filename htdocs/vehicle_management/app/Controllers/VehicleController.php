<?php
/**
 * Vehicle Controller – CRUD for vehicles.
 */

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Vehicle;
use App\Models\VehicleHandover;

class VehicleController extends BaseController
{
    private Vehicle $vehicleModel;
    private VehicleHandover $handoverModel;

    public function __construct()
    {
        $this->vehicleModel = new Vehicle();
        $this->handoverModel = new VehicleHandover();
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
            'vehicle_code', 'type', 'manufacture_year', 'emp_id',
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
            'vehicle_code', 'type', 'manufacture_year', 'emp_id',
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
     * POST /api/v1/vehicles/{id}/handover
     */
    public function handover(Request $request, array $params = []): void
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
            'to_emp_id', 'to_emp_name', 'from_emp_id', 'from_emp_name',
            'handover_date', 'odometer_reading', 'fuel_level',
            'vehicle_condition', 'notes',
        ]);

        $missing = $this->validateRequired($data, ['to_emp_id', 'handover_date']);
        if (!empty($missing)) {
            Response::error('Missing required fields: ' . implode(', ', $missing), 400);
            return;
        }

        $data['vehicle_id'] = $id;
        $data['handover_type'] = 'deliver';
        $data['created_by'] = $user['id'];

        try {
            $recordId = $this->handoverModel->create($data);
            if ($recordId === false) {
                Response::error('Failed to create handover record', 500);
                return;
            }
            $record = $this->handoverModel->find($recordId);
            Response::json(['success' => true, 'message' => 'Handover created', 'data' => $record], 201);
        } catch (\Throwable $e) {
            error_log("VehicleController::handover error: " . $e->getMessage());
            Response::error('Failed to create handover record', 500);
        }
        return;
    }

    /**
     * POST /api/v1/vehicles/{id}/receive
     */
    public function receive(Request $request, array $params = []): void
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
            'from_emp_id', 'from_emp_name', 'to_emp_id', 'to_emp_name',
            'handover_date', 'odometer_reading', 'fuel_level',
            'vehicle_condition', 'notes',
        ]);

        $missing = $this->validateRequired($data, ['from_emp_id', 'handover_date']);
        if (!empty($missing)) {
            Response::error('Missing required fields: ' . implode(', ', $missing), 400);
            return;
        }

        $data['vehicle_id'] = $id;
        $data['handover_type'] = 'receive';
        $data['created_by'] = $user['id'];

        try {
            $recordId = $this->handoverModel->create($data);
            if ($recordId === false) {
                Response::error('Failed to create receive record', 500);
                return;
            }
            $record = $this->handoverModel->find($recordId);
            Response::json(['success' => true, 'message' => 'Receive record created', 'data' => $record], 201);
        } catch (\Throwable $e) {
            error_log("VehicleController::receive error: " . $e->getMessage());
            Response::error('Failed to create receive record', 500);
        }
        return;
    }

    /**
     * GET /api/v1/vehicles/{id}/handovers
     */
    public function handovers(Request $request, array $params = []): void
    {
        $this->requireAuth($request);
        if (Response::isSent()) return;

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            Response::error('Invalid vehicle ID', 400);
            return;
        }

        try {
            $records = $this->handoverModel->getByVehicle($id);
        } catch (\Throwable $e) {
            error_log("VehicleController::handovers error: " . $e->getMessage());
            $records = [];
        }

        Response::json(['success' => true, 'data' => $records]);
        return;
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

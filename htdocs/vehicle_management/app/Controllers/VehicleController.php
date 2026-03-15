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
     * - Dept    : ONE vehicle only (next in round-robin for user's
     *             department/section/division + gender).
     *             If the user already holds a dept vehicle → show that for
     *             return only, do NOT show next vehicle for pickup.
     */
    public function myVehicles(Request $request, array $params = []): void
    {
        $user = $this->requireAuth($request);
        if (Response::isSent()) return;

        $userEmpId      = $user['emp_id'] ?? '';
        $userGender     = $user['gender'] ?? null;
        $userDeptId     = $user['department_id'] ?? null;
        $userSectionId  = $user['section_id'] ?? null;
        $userDivisionId = $user['division_id'] ?? null;

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
                foreach ($shiftVehicles as $v) {
                    if (!$v['available'] && ($v['last_holder'] ?? '') === $userEmpId) {
                        $myCheckedOutShift = $v;
                        break;
                    }
                }

                if ($myCheckedOutShift === null) {
                    $nextShiftVehicle = $this->findNextInRotation($shiftVehicles);
                }
            }

            // Department vehicles: filter by dept/section/division + gender,
            // exclude private vehicles already shown, then pick ONE in round-robin
            $departmentVehicles = [];
            $nextDeptVehicle    = null;
            $myCheckedOutDept   = null;
            $deptTotal          = 0;

            if ($userDeptId) {
                $departmentVehicles = array_values(array_filter($allVehicles, function ($v) use ($userDeptId, $userSectionId, $userDivisionId, $userGender, $userEmpId) {
                    $deptMatch = ((int)($v['department_id'] ?? 0)) === (int)$userDeptId;
                    // Also match section/division when available
                    if ($userSectionId && !empty($v['section_id'])) {
                        $deptMatch = $deptMatch && ((int)($v['section_id'] ?? 0)) === (int)$userSectionId;
                    }
                    if ($userDivisionId && !empty($v['division_id'])) {
                        $deptMatch = $deptMatch && ((int)($v['division_id'] ?? 0)) === (int)$userDivisionId;
                    }
                    $statusOk  = ($v['status'] ?? '') === 'operational';
                    $genderOk  = (!$userGender || empty($v['gender']) || $v['gender'] === $userGender);
                    // Exclude vehicles already shown in private section
                    $isMyPrivate = ($v['vehicle_mode'] ?? '') === 'private'
                        && trim($v['emp_id'] ?? '') !== ''
                        && trim($v['emp_id'] ?? '') === trim($userEmpId);
                    // Exclude shift vehicles (shown in shift section)
                    $isShift = ($v['vehicle_mode'] ?? '') === 'shift';
                    return $deptMatch && $statusOk && $genderOk && !$isMyPrivate && !$isShift;
                }));

                usort($departmentVehicles, fn($a, $b) => strcmp($a['vehicle_code'] ?? '', $b['vehicle_code'] ?? ''));
                $deptTotal = count($departmentVehicles);

                // Check if user currently holds a department vehicle
                foreach ($departmentVehicles as $v) {
                    if (!$v['available'] && ($v['last_holder'] ?? '') === $userEmpId) {
                        $myCheckedOutDept = $v;
                        break;
                    }
                }

                // If user doesn't hold one, find next in round-robin
                if ($myCheckedOutDept === null && !empty($departmentVehicles)) {
                    $nextDeptVehicle = $this->findNextInRotation($departmentVehicles);
                }
            }

            Response::json([
                'success' => true,
                'data'    => [
                    'private'             => $privateVehicles,
                    'shift_next'          => $nextShiftVehicle,
                    'shift_my_current'    => $myCheckedOutShift,
                    'shift_total'         => count($shiftVehicles),
                    'shift_vehicles'      => [],
                    'dept_next'           => $nextDeptVehicle,
                    'dept_my_current'     => $myCheckedOutDept,
                    'dept_total'          => $deptTotal,
                    'department_vehicles' => [],
                ],
            ]);

        } catch (\Throwable $e) {
            error_log("VehicleController::myVehicles error: " . $e->getMessage());
            Response::json([
                'success' => false,
                'message' => 'Failed to load vehicles',
                'data'    => [
                    'private'             => [],
                    'shift_next'          => null,
                    'shift_my_current'    => null,
                    'shift_total'         => 0,
                    'shift_vehicles'      => [],
                    'dept_next'           => null,
                    'dept_my_current'     => null,
                    'dept_total'          => 0,
                    'department_vehicles' => [],
                ],
            ], 500);
        }
    }

    /**
     * Find the next available vehicle in round-robin rotation.
     * Looks at the last pickup among the given vehicles and returns the next
     * available one in sorted order (wrapping around like a circular list).
     */
    private function findNextInRotation(array $vehicles): ?array
    {
        if (empty($vehicles)) return null;

        $codes          = array_column($vehicles, 'vehicle_code');
        $lastPickupCode = $this->movementModel->getLastPickupForShiftVehicles($codes);

        if ($lastPickupCode) {
            $lastIdx = -1;
            foreach ($vehicles as $i => $v) {
                if ($v['vehicle_code'] === $lastPickupCode) {
                    $lastIdx = $i;
                    break;
                }
            }
            $count = count($vehicles);
            for ($j = 1; $j <= $count; $j++) {
                $idx = ($lastIdx + $j) % $count;
                if ($vehicles[$idx]['available']) {
                    $next = $vehicles[$idx];
                    $next['turn_order'] = $idx + 1;
                    return $next;
                }
            }
        }

        // Fallback: first available vehicle
        foreach ($vehicles as $i => $v) {
            if ($v['available']) {
                $next = $v;
                $next['turn_order'] = $i + 1;
                return $next;
            }
        }

        return null;
    }

    /**
     * POST /api/v1/vehicles/self-service
     * Self-service vehicle pickup/return for any authenticated user.
     * Unlike POST /movements (requires manage_movements), this only needs authentication.
     * The user can only act for themselves (performed_by = their own emp_id).
     */
    public function selfServiceMovement(Request $request, array $params = []): void
    {
        $user = $this->requireAuth($request);
        if (Response::isSent()) return;

        $userEmpId = trim($user['emp_id'] ?? '');
        if ($userEmpId === '') {
            Response::error('User has no emp_id configured', 400);
            return;
        }

        $data = $request->only(['vehicle_code', 'operation_type']);

        $missing = $this->validateRequired($data, ['vehicle_code', 'operation_type']);
        if (!empty($missing)) {
            Response::error('Missing required fields: ' . implode(', ', $missing), 400);
            return;
        }

        $operationType = $data['operation_type'];
        if (!in_array($operationType, ['pickup', 'return'], true)) {
            Response::error('Invalid operation_type: must be pickup or return', 400);
            return;
        }

        $vehicleCode = trim($data['vehicle_code']);

        // Verify the vehicle exists and is operational
        $vehicle = $this->vehicleModel->findByCode($vehicleCode);
        if (!$vehicle) {
            Response::error('Vehicle not found: ' . $vehicleCode, 404);
            return;
        }
        if (($vehicle['status'] ?? '') !== 'operational') {
            Response::error('Vehicle is not operational', 400);
            return;
        }

        // Get latest movement to check availability
        $latestMovements = $this->movementModel->getLatestByVehicle();
        $lastMovement = $latestMovements[$vehicleCode] ?? null;
        $isAvailable = ($lastMovement === null || ($lastMovement['operation_type'] ?? '') === 'return');

        if ($operationType === 'pickup') {
            if (!$isAvailable) {
                Response::error('Vehicle is not available for pickup', 400);
                return;
            }
        } else {
            // Return: verify the user is the one who checked it out, or is admin/superadmin
            if ($isAvailable) {
                Response::error('Vehicle is not checked out', 400);
                return;
            }
            $lastHolder = trim($lastMovement['performed_by'] ?? '');
            $isAdmin = in_array((int)($user['role_id'] ?? 0), [1, 2], true);
            if ($lastHolder !== $userEmpId && !$isAdmin) {
                Response::error('You can only return vehicles you have checked out', 403);
                return;
            }
        }

        try {
            $recordId = $this->movementModel->create([
                'vehicle_code'      => $vehicleCode,
                'operation_type'    => $operationType,
                'performed_by'      => $userEmpId,
                'created_by'        => $userEmpId,
                'movement_datetime' => date('Y-m-d H:i:s'),
            ]);

            $this->logActivity($user, 'vehicle_self_service', 'vehicle_movements', $recordId,
                "Self-service {$operationType} for vehicle {$vehicleCode}");

            $record = $this->movementModel->find($recordId);
            Response::json(['success' => true, 'message' => ucfirst($operationType) . ' successful', 'data' => $record], 201);
        } catch (\Throwable $e) {
            error_log("VehicleController::selfServiceMovement error: " . $e->getMessage());
            Response::error('Failed to process vehicle ' . $operationType, 500);
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

    /**
     * Log an activity to the activity_logs table.
     */
    private function logActivity(array $user, string $type, string $tableName, int $recordId, string $description): void
    {
        try {
            $db = Database::getInstance();
            $db->execute(
                "INSERT INTO activity_logs (user_id, emp_id, activity_type, description, table_name, record_id, ip_address, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                'issssiss',
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

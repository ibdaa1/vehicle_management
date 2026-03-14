<?php
/**
 * Movement Controller
 *
 * Handles vehicle movement CRUD operations, photo uploads, and activity logging.
 */

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Models\VehicleMovement;
use App\Models\VehicleMovementPhoto;

class MovementController extends BaseController
{
    private VehicleMovement $movementModel;
    private VehicleMovementPhoto $photoModel;

    public function __construct()
    {
        $this->movementModel = new VehicleMovement();
        $this->photoModel    = new VehicleMovementPhoto();
    }

    /**
     * Ensure photo URLs include the base_url prefix.
     * Fixes legacy records stored without the prefix (e.g. /public/uploads/... → /vehicle_management/public/uploads/...).
     */
    private function fixPhotoUrls(array $photos): array
    {
        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $baseUrl = rtrim($config['base_url'] ?? '', '/');
        if ($baseUrl === '') {
            return $photos;
        }
        foreach ($photos as &$photo) {
            $url = $photo['photo_url'] ?? '';
            if ($url !== '' && strpos($url, '/public/uploads/') === 0) {
                $photo['photo_url'] = $baseUrl . $url;
            }
        }
        unset($photo);
        return $photos;
    }

    /**
     * GET /api/v1/movements/stats
     * Returns comprehensive vehicle and movement statistics.
     */
    public function stats(Request $request, array $params = []): void
    {
        $this->requirePermission($request, 'manage_movements');
        if (Response::isSent()) return;

        $filters = $request->only(['department_id', 'section_id', 'division_id', 'date_from', 'date_to', 'gender', 'vehicle_mode']);

        try {
            $db = Database::getInstance();

            // Build vehicle filter conditions
            $vWhere = " WHERE 1=1";
            $vTypes = '';
            $vParams = [];

            if (!empty($filters['department_id'])) {
                $vWhere .= " AND v.department_id = ?";
                $vTypes .= 'i';
                $vParams[] = (int)$filters['department_id'];
            }
            if (!empty($filters['section_id'])) {
                $vWhere .= " AND v.section_id = ?";
                $vTypes .= 'i';
                $vParams[] = (int)$filters['section_id'];
            }
            if (!empty($filters['division_id'])) {
                $vWhere .= " AND v.division_id = ?";
                $vTypes .= 'i';
                $vParams[] = (int)$filters['division_id'];
            }
            if (!empty($filters['gender'])) {
                $vWhere .= " AND v.gender = ?";
                $vTypes .= 's';
                $vParams[] = $filters['gender'];
            }
            if (!empty($filters['vehicle_mode'])) {
                $vWhere .= " AND v.vehicle_mode = ?";
                $vTypes .= 's';
                $vParams[] = $filters['vehicle_mode'];
            }

            // Total vehicles
            $totalVehicles = $db->fetchOne(
                "SELECT COUNT(*) as cnt FROM vehicles v" . $vWhere, $vTypes, $vParams
            );

            // Vehicles by mode (private vs shift)
            $byMode = $db->fetchAll(
                "SELECT v.vehicle_mode, COUNT(*) as cnt FROM vehicles v" . $vWhere . " GROUP BY v.vehicle_mode",
                $vTypes, $vParams
            );
            $privateCount = 0;
            $shiftCount = 0;
            foreach ($byMode as $row) {
                if ($row['vehicle_mode'] === 'private') $privateCount = (int)$row['cnt'];
                elseif ($row['vehicle_mode'] === 'shift') $shiftCount = (int)$row['cnt'];
            }

            // Vehicles by category (sedan, pickup, bus)
            $byCategory = $db->fetchAll(
                "SELECT v.vehicle_category, COUNT(*) as cnt FROM vehicles v" . $vWhere . " GROUP BY v.vehicle_category",
                $vTypes, $vParams
            );
            $categories = [];
            foreach ($byCategory as $row) {
                $categories[$row['vehicle_category'] ?? 'other'] = (int)$row['cnt'];
            }

            // Vehicles by status (operational, maintenance, out_of_service)
            $byStatus = $db->fetchAll(
                "SELECT v.status, COUNT(*) as cnt FROM vehicles v" . $vWhere . " GROUP BY v.status",
                $vTypes, $vParams
            );
            $statuses = [];
            foreach ($byStatus as $row) {
                $statuses[$row['status'] ?? 'unknown'] = (int)$row['cnt'];
            }

            // Vehicles by gender
            $byGender = $db->fetchAll(
                "SELECT v.gender, COUNT(*) as cnt FROM vehicles v" . $vWhere . " GROUP BY v.gender",
                $vTypes, $vParams
            );
            $genders = [];
            foreach ($byGender as $row) {
                $genders[$row['gender'] ?? 'unspecified'] = (int)$row['cnt'];
            }

            // Date filter for movements
            $today = date('Y-m-d');
            $dateFrom = !empty($filters['date_from']) ? $filters['date_from'] : $today;
            $dateTo = !empty($filters['date_to']) ? $filters['date_to'] : $today;

            // Vehicles used in the date range (have movements)
            $usedSql = "SELECT COUNT(DISTINCT m.vehicle_code) as cnt
                        FROM vehicle_movements m
                        INNER JOIN vehicles v ON v.vehicle_code = m.vehicle_code"
                        . $vWhere
                        . " AND DATE(m.movement_datetime) BETWEEN ? AND ?";
            $usedVehicles = $db->fetchOne(
                $usedSql, $vTypes . 'ss', array_merge($vParams, [$dateFrom, $dateTo])
            );

            $totalCount = (int)($totalVehicles['cnt'] ?? 0);
            $usedCount = (int)($usedVehicles['cnt'] ?? 0);
            $unusedCount = $totalCount - $usedCount;

            // Currently checked out vehicles (latest movement is pickup)
            // This tells us how many vehicles are currently handed over
            $checkedOutSql = "SELECT COUNT(*) as cnt FROM (
                SELECT m.vehicle_code, m.operation_type
                FROM vehicle_movements m
                INNER JOIN vehicles v ON v.vehicle_code = m.vehicle_code
                INNER JOIN (
                    SELECT vehicle_code, MAX(movement_datetime) as max_dt
                    FROM vehicle_movements
                    GROUP BY vehicle_code
                ) latest ON m.vehicle_code = latest.vehicle_code AND m.movement_datetime = latest.max_dt"
                . $vWhere
                . " AND m.operation_type = 'pickup'
            ) as checked_out";
            $checkedOut = $db->fetchOne($checkedOutSql, $vTypes, $vParams);
            $checkedOutCount = (int)($checkedOut['cnt'] ?? 0);
            $availableCount = $totalCount - $checkedOutCount;

            // Private vehicles currently checked out (key not returned)
            $privateCheckedOutSql = "SELECT COUNT(*) as cnt FROM (
                SELECT m.vehicle_code
                FROM vehicle_movements m
                INNER JOIN vehicles v ON v.vehicle_code = m.vehicle_code
                INNER JOIN (
                    SELECT vehicle_code, MAX(movement_datetime) as max_dt
                    FROM vehicle_movements
                    GROUP BY vehicle_code
                ) latest ON m.vehicle_code = latest.vehicle_code AND m.movement_datetime = latest.max_dt"
                . $vWhere
                . " AND v.vehicle_mode = 'private' AND m.operation_type = 'pickup'
            ) as priv_out";
            $privateCheckedOut = $db->fetchOne($privateCheckedOutSql, $vTypes, $vParams);
            $privateNotReturnedCount = (int)($privateCheckedOut['cnt'] ?? 0);

            // Movement counts for the date range
            $movementCountsSql = "SELECT
                COUNT(*) as total_movements,
                SUM(CASE WHEN m.operation_type = 'pickup' THEN 1 ELSE 0 END) as pickups,
                SUM(CASE WHEN m.operation_type = 'return' THEN 1 ELSE 0 END) as returns
                FROM vehicle_movements m
                INNER JOIN vehicles v ON v.vehicle_code = m.vehicle_code"
                . $vWhere
                . " AND DATE(m.movement_datetime) BETWEEN ? AND ?";
            $movementCounts = $db->fetchOne(
                $movementCountsSql, $vTypes . 'ss', array_merge($vParams, [$dateFrom, $dateTo])
            );

            // Employee count in the filtered department/section
            $empWhere = " WHERE 1=1";
            $empTypes = '';
            $empParams = [];
            if (!empty($filters['department_id'])) {
                $empWhere .= " AND department_id = ?";
                $empTypes .= 'i';
                $empParams[] = (int)$filters['department_id'];
            }
            if (!empty($filters['section_id'])) {
                $empWhere .= " AND section_id = ?";
                $empTypes .= 'i';
                $empParams[] = (int)$filters['section_id'];
            }
            $empCount = $db->fetchOne(
                "SELECT COUNT(*) as cnt FROM users" . $empWhere, $empTypes, $empParams
            );

            $stats = [
                'total_vehicles'       => $totalCount,
                'private_vehicles'     => $privateCount,
                'shift_vehicles'       => $shiftCount,
                'categories'           => $categories,
                'statuses'             => $statuses,
                'genders'              => $genders,
                'used_in_period'       => $usedCount,
                'unused_in_period'     => $unusedCount,
                'checked_out'          => $checkedOutCount,
                'available'            => $availableCount,
                'private_not_returned' => $privateNotReturnedCount,
                'total_movements'      => (int)($movementCounts['total_movements'] ?? 0),
                'pickups'              => (int)($movementCounts['pickups'] ?? 0),
                'returns'              => (int)($movementCounts['returns'] ?? 0),
                'employee_count'       => (int)($empCount['cnt'] ?? 0),
                'date_from'            => $dateFrom,
                'date_to'              => $dateTo,
            ];

            Response::json(['success' => true, 'data' => $stats]);
        } catch (\Throwable $e) {
            error_log("MovementController::stats error: " . $e->getMessage());
            Response::error('Failed to load statistics', 500);
        }
    }

    /**
     * GET /api/v1/movements
     */
    public function index(Request $request, array $params = []): void
    {
        $this->requirePermission($request, 'manage_movements');
        if (Response::isSent()) return;

        $filters = $request->only(['vehicle_code', 'operation_type', 'performed_by']);

        try {
            $movements = $this->movementModel->allFiltered($filters);
        } catch (\Throwable $e) {
            error_log("MovementController::index error: " . $e->getMessage());
            $movements = [];
        }

        Response::json(['success' => true, 'data' => $movements]);
    }

    /**
     * GET /api/v1/movements/{id}
     */
    public function show(Request $request, array $params = []): void
    {
        $this->requirePermission($request, 'manage_movements');
        if (Response::isSent()) return;

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            Response::error('Invalid movement ID', 400);
            return;
        }

        try {
            $movement = $this->movementModel->find($id);
            if (!$movement) {
                Response::error('Movement not found', 404);
                return;
            }
            $movement['photos'] = $this->fixPhotoUrls($this->photoModel->getByMovement($id));
        } catch (\Throwable $e) {
            error_log("MovementController::show error: " . $e->getMessage());
            Response::error('Failed to load movement', 500);
            return;
        }

        Response::json(['success' => true, 'data' => $movement]);
    }

    /**
     * POST /api/v1/movements
     */
    public function store(Request $request, array $params = []): void
    {
        $user = $this->requirePermission($request, 'manage_movements');
        if (Response::isSent()) return;

        $data = $request->only([
            'vehicle_code', 'operation_type', 'performed_by',
            'movement_datetime', 'notes', 'vehicle_condition', 'fuel_level',
            'latitude', 'longitude',
        ]);

        $missing = $this->validateRequired($data, ['vehicle_code', 'operation_type', 'performed_by']);
        if (!empty($missing)) {
            Response::error('Missing required fields: ' . implode(', ', $missing), 400);
            return;
        }

        // Validate performed_by exists in users table (FK: users.emp_id)
        $performedBy = trim($data['performed_by']);
        $data['performed_by'] = $performedBy;
        $db = Database::getInstance();
        $existingUser = $db->fetchOne(
            "SELECT emp_id FROM users WHERE emp_id = ? LIMIT 1",
            's',
            [$performedBy]
        );
        if (!$existingUser) {
            Response::error('Invalid performed_by: employee ID "' . $performedBy . '" does not exist', 400);
            return;
        }

        $data['created_by'] = $user['emp_id'] ?? (string)$user['id'];
        if (empty($data['movement_datetime'])) {
            $data['movement_datetime'] = date('Y-m-d H:i:s');
        }

        if (isset($data['latitude']) && $data['latitude'] !== '') {
            $data['latitude'] = (float)$data['latitude'];
        } else {
            unset($data['latitude']);
        }
        if (isset($data['longitude']) && $data['longitude'] !== '') {
            $data['longitude'] = (float)$data['longitude'];
        } else {
            unset($data['longitude']);
        }

        try {
            $recordId = $this->movementModel->create($data);

            $this->logActivity($user, 'vehicle_movement', 'vehicle_movements', $recordId,
                "Created {$data['operation_type']} movement for vehicle {$data['vehicle_code']}");

            $record = $this->movementModel->find($recordId);
            Response::json(['success' => true, 'message' => 'Movement created', 'data' => $record], 201);
        } catch (\Throwable $e) {
            error_log("MovementController::store error: " . $e->getMessage());
            Response::error('Failed to create movement: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/v1/movements/{id}
     */
    public function update(Request $request, array $params = []): void
    {
        $user = $this->requirePermission($request, 'manage_movements');
        if (Response::isSent()) return;

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            Response::error('Invalid movement ID', 400);
            return;
        }

        try {
            $existing = $this->movementModel->find($id);
            if (!$existing) {
                Response::error('Movement not found', 404);
                return;
            }
        } catch (\Throwable $e) {
            Response::error('Failed to find movement', 500);
            return;
        }

        $data = $request->only([
            'vehicle_code', 'operation_type', 'performed_by',
            'movement_datetime', 'notes', 'vehicle_condition', 'fuel_level',
            'latitude', 'longitude',
        ]);

        $data['updated_by'] = $user['emp_id'] ?? (string)$user['id'];

        if (isset($data['latitude']) && $data['latitude'] !== '') {
            $data['latitude'] = (float)$data['latitude'];
        } else {
            unset($data['latitude']);
        }
        if (isset($data['longitude']) && $data['longitude'] !== '') {
            $data['longitude'] = (float)$data['longitude'];
        } else {
            unset($data['longitude']);
        }

        $data = array_filter($data, fn($v) => $v !== null && $v !== '');

        try {
            $ok = $this->movementModel->update($id, $data);
            if (!$ok) {
                Response::error('Failed to update movement', 500);
                return;
            }

            $this->logActivity($user, 'vehicle_movement', 'vehicle_movements', $id,
                "Updated movement #{$id}");

            $record = $this->movementModel->find($id);
            Response::json(['success' => true, 'message' => 'Movement updated', 'data' => $record]);
        } catch (\Throwable $e) {
            error_log("MovementController::update error: " . $e->getMessage());
            Response::error('Failed to update movement', 500);
        }
    }

    /**
     * DELETE /api/v1/movements/{id}
     */
    public function destroy(Request $request, array $params = []): void
    {
        $user = $this->requirePermission($request, 'manage_movements');
        if (Response::isSent()) return;
        // Additionally require admin role for delete operations
        if (!in_array((int)$user['role_id'], [1, 2], true)) {
            Response::error('Forbidden: admin access required', 403);
            return;
        }

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            Response::error('Invalid movement ID', 400);
            return;
        }

        try {
            $existing = $this->movementModel->find($id);
            if (!$existing) {
                Response::error('Movement not found', 404);
                return;
            }

            $ok = $this->movementModel->delete($id);
            if (!$ok) {
                Response::error('Failed to delete movement', 500);
                return;
            }

            $this->logActivity($user, 'vehicle_movement', 'vehicle_movements', $id,
                "Deleted movement #{$id} for vehicle {$existing['vehicle_code']}");

            Response::json(['success' => true, 'message' => 'Movement deleted']);
        } catch (\Throwable $e) {
            error_log("MovementController::destroy error: " . $e->getMessage());
            Response::error('Failed to delete movement', 500);
        }
    }

    /**
     * GET /api/v1/movements/{id}/photos
     */
    public function photos(Request $request, array $params = []): void
    {
        $this->requirePermission($request, 'manage_movements');
        if (Response::isSent()) return;

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            Response::error('Invalid movement ID', 400);
            return;
        }

        try {
            $photos = $this->photoModel->getByMovement($id);
        } catch (\Throwable $e) {
            error_log("MovementController::photos error: " . $e->getMessage());
            $photos = [];
        }

        Response::json(['success' => true, 'data' => $this->fixPhotoUrls($photos)]);
    }

    /**
     * POST /api/v1/movements/{id}/photos
     * Accepts JSON array of base64-encoded images.
     */
    public function uploadPhotos(Request $request, array $params = []): void
    {
        $user = $this->requirePermission($request, 'manage_movements');
        if (Response::isSent()) return;

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            Response::error('Invalid movement ID', 400);
            return;
        }

        try {
            $movement = $this->movementModel->find($id);
            if (!$movement) {
                Response::error('Movement not found', 404);
                return;
            }
        } catch (\Throwable $e) {
            Response::error('Failed to find movement', 500);
            return;
        }

        $body = $request->body();
        $photos = $body['photos'] ?? [];
        if (empty($photos) || !is_array($photos)) {
            Response::error('No photos provided', 400);
            return;
        }

        $photos = array_slice($photos, 0, 6);
        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/vehicle_movements/' . $id;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $saved = [];
        $empId = $user['emp_id'] ?? (string)$user['id'];
        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $baseUrl = $config['base_url'] ?? '';

        foreach ($photos as $i => $photoData) {
            if (!is_string($photoData) || strpos($photoData, 'base64,') === false) {
                continue;
            }

            $parts = explode('base64,', $photoData, 2);

            // Validate mime type - only jpg, jpeg, png allowed
            $mimeHeader = strtolower($parts[0]);
            if (strpos($mimeHeader, 'image/jpeg') === false && strpos($mimeHeader, 'image/jpg') === false && strpos($mimeHeader, 'image/png') === false) {
                continue;
            }

            $decoded = base64_decode($parts[1] ?? '', true);
            if ($decoded === false || strlen($decoded) < 10) {
                continue;
            }

            $ext = 'jpg';
            if (strpos($mimeHeader, 'png') !== false) $ext = 'png';

            $filename = 'photo_' . time() . '_' . $i . '.' . $ext;
            $filepath = $uploadDir . '/' . $filename;
            file_put_contents($filepath, $decoded);

            $photoUrl = $baseUrl . '/public/uploads/vehicle_movements/' . $id . '/' . $filename;
            $photoId = $this->photoModel->create([
                'movement_id' => $id,
                'photo_url'   => $photoUrl,
                'taken_by'    => $empId,
            ]);

            if ($photoId !== false) {
                $saved[] = $this->photoModel->find($photoId);
            }
        }

        $this->logActivity($user, 'vehicle_movement', 'vehicle_movement_photos', $id,
            "Uploaded " . count($saved) . " photos for movement #{$id}");

        Response::json(['success' => true, 'message' => count($saved) . ' photos uploaded', 'data' => $saved], 201);
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

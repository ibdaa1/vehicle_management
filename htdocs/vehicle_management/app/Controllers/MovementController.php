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
     * GET /api/v1/movements
     */
    public function index(Request $request, array $params = []): void
    {
        $this->requireAuth($request);
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
        $this->requireAuth($request);
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
            $movement['photos'] = $this->photoModel->getByMovement($id);
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
        $user = $this->requireAuth($request);
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
            if ($recordId === false) {
                Response::error('Failed to create movement record', 500);
                return;
            }

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
        $user = $this->requireAuth($request);
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
        $user = $this->requireAdmin($request);
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
        $this->requireAuth($request);
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

        Response::json(['success' => true, 'data' => $photos]);
    }

    /**
     * POST /api/v1/movements/{id}/photos
     * Accepts JSON array of base64-encoded images.
     */
    public function uploadPhotos(Request $request, array $params = []): void
    {
        $user = $this->requireAuth($request);
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
            if ($decoded === false || strlen($decoded) < 100) {
                continue;
            }

            $ext = 'jpg';
            if (strpos($mimeHeader, 'png') !== false) $ext = 'png';

            $filename = 'photo_' . time() . '_' . $i . '.' . $ext;
            $filepath = $uploadDir . '/' . $filename;
            file_put_contents($filepath, $decoded);

            $photoUrl = '/public/uploads/vehicle_movements/' . $id . '/' . $filename;
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

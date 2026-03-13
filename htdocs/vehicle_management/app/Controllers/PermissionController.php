<?php
/**
 * Permission Controller
 * 
 * Manages permission definitions and provides permission data for the frontend.
 * MVC replacement for api/permissions/get_permissions.php
 */

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Permission;
use App\Middleware\PermissionMiddleware;

class PermissionController extends BaseController
{
    private Permission $permissionModel;

    public function __construct()
    {
        $this->permissionModel = new Permission();
    }

    /**
     * GET /api/v1/permissions
     * 
     * List all permissions, optionally grouped by module.
     * Requires admin access.
     */
    public function index(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);

        if (Response::isSent()) {
            return;
        }

        $grouped = $request->query('grouped') === '1';

        if ($grouped) {
            $data = $this->permissionModel->groupedByModule();
        } else {
            $data = $this->permissionModel->allActive();
        }

        Response::json([
            'success' => true,
            'data'    => $data,
        ]);
        return;
    }

    /**
     * GET /api/v1/permissions/modules
     * 
     * List all permission modules.
     */
    public function modules(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);

        if (Response::isSent()) {
            return;
        }

        $modules = $this->permissionModel->getModules();

        Response::json([
            'success' => true,
            'data'    => $modules,
        ]);
        return;
    }

    /**
     * GET /api/v1/permissions/check
     * 
     * Check if the current user has a specific permission.
     * Query params: ?permission=some_permission_key
     */
    public function check(Request $request, array $params = []): void
    {
        $user = $this->requireAuth($request);

        if (Response::isSent()) {
            return;
        }

        $permKey = $request->query('permission', '');

        if ($permKey === '') {
            Response::error('permission query parameter required', 400);
            return;
        }

        $hasPermission = PermissionMiddleware::hasPermission(
            (int)$user['role_id'],
            $permKey
        );

        Response::json([
            'success'        => true,
            'permission'     => $permKey,
            'has_permission' => $hasPermission,
        ]);
        return;
    }

    /**
     * GET /api/v1/permissions/my
     * 
     * Get all permissions for the current user's role.
     * Used by the frontend to show/hide UI elements.
     */
    public function myPermissions(Request $request, array $params = []): void
    {
        $user = $this->requireAuth($request);

        if (Response::isSent()) {
            return;
        }

        $roleId = (int)$user['role_id'];

        $permissions = PermissionMiddleware::getRolePermissions($roleId);

        Response::json([
            'success' => true,
            'role_id' => $roleId,
            'data'    => $permissions,
        ]);
        return;
    }

    /**
     * POST /api/v1/permissions
     * 
     * Create a new permission definition. Requires admin access.
     */
    public function store(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);

        if (Response::isSent()) {
            return;
        }

        $data = $request->only(['key_name', 'display_name', 'description', 'module']);
        $missing = $this->validateRequired($data, ['key_name', 'display_name']);
        if (!empty($missing)) {
            Response::error('Missing required fields: ' . implode(', ', $missing), 400);
            return;
        }

        // Check for duplicate key_name
        $existing = $this->permissionModel->findByKey($data['key_name']);
        if ($existing) {
            Response::error('Permission key_name already exists', 409);
            return;
        }

        // Default is_active to 1
        $data['is_active'] = 1;

        try {
            $id = $this->permissionModel->create($data);

            $permission = $this->permissionModel->find($id);
            Response::json([
                'success' => true,
                'message' => 'Permission created successfully',
                'data'    => $permission,
            ], 201);
        } catch (\Throwable $e) {
            error_log("PermissionController::store error: " . $e->getMessage());
            Response::error('Failed to create permission: ' . $e->getMessage(), 500);
        }
        return;
    }

    /**
     * PUT /api/v1/permissions/{id}
     * 
     * Update a permission. Requires admin access.
     */
    public function update(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);

        if (Response::isSent()) {
            return;
        }

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            Response::error('Invalid permission ID', 400);
            return;
        }

        $permission = $this->permissionModel->find($id);
        if (!$permission) {
            Response::error('Permission not found', 404);
            return;
        }

        $data = $request->only(['key_name', 'display_name', 'description', 'module', 'is_active']);
        $data = array_filter($data, fn($v) => $v !== null);

        if (empty($data)) {
            Response::error('No fields to update', 400);
            return;
        }

        if (isset($data['is_active'])) {
            $data['is_active'] = (int)(bool)$data['is_active'];
        }

        try {
            $this->permissionModel->update($id, $data);

            $updated = $this->permissionModel->find($id);
            Response::json([
                'success' => true,
                'message' => 'Permission updated successfully',
                'data'    => $updated,
            ]);
        } catch (\Throwable $e) {
            error_log("PermissionController::update error: " . $e->getMessage());
            Response::error('Failed to update permission: ' . $e->getMessage(), 500);
        }
        return;
    }

    /**
     * DELETE /api/v1/permissions/{id}
     * 
     * Delete a permission. Requires admin access.
     */
    public function destroy(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);

        if (Response::isSent()) {
            return;
        }

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            Response::error('Invalid permission ID', 400);
            return;
        }

        $permission = $this->permissionModel->find($id);
        if (!$permission) {
            Response::error('Permission not found', 404);
            return;
        }

        $success = $this->permissionModel->delete($id);
        if (!$success) {
            Response::error('Failed to delete permission', 500);
            return;
        }

        Response::success(null, 'Permission deleted successfully');
        return;
    }
}

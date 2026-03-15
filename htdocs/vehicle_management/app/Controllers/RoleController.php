<?php
/**
 * Role Controller
 * 
 * Manages roles and their permission assignments.
 * MVC replacement for api/permissions/roles/*.php
 */

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Role;
use App\Models\Permission;

class RoleController extends BaseController
{
    private Role $roleModel;
    private Permission $permissionModel;

    public function __construct()
    {
        $this->roleModel = new Role();
        $this->permissionModel = new Permission();
    }

    /**
     * GET /api/v1/roles
     * 
     * List all roles with permission count. Requires admin access.
     */
    public function index(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);

        if (Response::isSent()) {
            return;
        }

        $roles = $this->roleModel->allWithPermissionCount();

        Response::json([
            'success' => true,
            'data'    => $roles,
        ]);
        return;
    }

    /**
     * GET /api/v1/roles/{id}
     * 
     * Get a single role with all its permissions. Requires admin access.
     */
    public function show(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);

        if (Response::isSent()) {
            return;
        }

        $roleId = (int)($params['id'] ?? 0);
        if ($roleId <= 0) {
            Response::error('Invalid role ID', 400);
            return;
        }

        $role = $this->roleModel->getWithPermissions($roleId);
        if (!$role) {
            Response::error('Role not found', 404);
            return;
        }

        Response::json([
            'success' => true,
            'data'    => $role,
        ]);
        return;
    }

    /**
     * POST /api/v1/roles
     * 
     * Create a new role. Requires admin access.
     */
    public function store(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);

        if (Response::isSent()) {
            return;
        }

        $data = $request->only(['key_name', 'display_name']);
        $missing = $this->validateRequired($data, ['key_name', 'display_name']);
        if (!empty($missing)) {
            Response::error('Missing required fields: ' . implode(', ', $missing), 400);
            return;
        }

        // Check for duplicate key_name
        $existing = $this->roleModel->findByKey($data['key_name']);
        if ($existing) {
            Response::error('Role key_name already exists', 409);
            return;
        }

        try {
            $roleId = $this->roleModel->create($data);

            $role = $this->roleModel->find($roleId);
            Response::json([
                'success' => true,
                'message' => 'Role created successfully',
                'data'    => $role,
            ], 201);
        } catch (\Throwable $e) {
            error_log("RoleController::store error: " . $e->getMessage());
            Response::error('Failed to create role: ' . $e->getMessage(), 500);
        }
        return;
    }

    /**
     * PUT /api/v1/roles/{id}
     * 
     * Update a role. Requires admin access.
     */
    public function update(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);

        if (Response::isSent()) {
            return;
        }

        $roleId = (int)($params['id'] ?? 0);
        if ($roleId <= 0) {
            Response::error('Invalid role ID', 400);
            return;
        }

        $role = $this->roleModel->find($roleId);
        if (!$role) {
            Response::error('Role not found', 404);
            return;
        }

        $data = $request->only(['key_name', 'display_name']);
        // Filter out empty values
        $data = array_filter($data, fn($v) => $v !== null && $v !== '');

        if (empty($data)) {
            Response::error('No fields to update', 400);
            return;
        }

        try {
            $this->roleModel->update($roleId, $data);

            $role = $this->roleModel->find($roleId);
            Response::json([
                'success' => true,
                'message' => 'Role updated successfully',
                'data'    => $role,
            ]);
        } catch (\Throwable $e) {
            error_log("RoleController::update error: " . $e->getMessage());
            Response::error('Failed to update role: ' . $e->getMessage(), 500);
        }
        return;
    }

    /**
     * DELETE /api/v1/roles/{id}
     * 
     * Delete a role. Requires admin access. Cannot delete superadmin/admin roles.
     */
    public function destroy(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);

        if (Response::isSent()) {
            return;
        }

        $roleId = (int)($params['id'] ?? 0);
        if ($roleId <= 0) {
            Response::error('Invalid role ID', 400);
            return;
        }

        // Prevent deleting built-in roles
        if (in_array($roleId, [1, 2], true)) {
            Response::error('Cannot delete built-in roles', 403);
            return;
        }

        $role = $this->roleModel->find($roleId);
        if (!$role) {
            Response::error('Role not found', 404);
            return;
        }

        $success = $this->roleModel->delete($roleId);
        if (!$success) {
            Response::error('Failed to delete role', 500);
            return;
        }

        Response::success(null, 'Role deleted successfully');
        return;
    }

    /**
     * PUT /api/v1/roles/{id}/permissions
     * 
     * Sync permissions for a role. Requires admin access.
     * Expects: { "permission_ids": [1, 2, 3] }
     */
    public function syncPermissions(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);

        if (Response::isSent()) {
            return;
        }

        $roleId = (int)($params['id'] ?? 0);
        if ($roleId <= 0) {
            Response::error('Invalid role ID', 400);
            return;
        }

        $role = $this->roleModel->find($roleId);
        if (!$role) {
            Response::error('Role not found', 404);
            return;
        }

        $permissionIds = $request->input('permission_ids', []);
        if (!is_array($permissionIds)) {
            Response::error('permission_ids must be an array', 400);
            return;
        }

        $success = $this->roleModel->syncPermissions($roleId, $permissionIds);
        if (!$success) {
            Response::error('Failed to sync permissions', 500);
            return;
        }

        $updated = $this->roleModel->getWithPermissions($roleId);
        Response::json([
            'success' => true,
            'message' => 'Permissions synced successfully',
            'data'    => $updated,
        ]);
        return;
    }

    /**
     * PUT /api/v1/roles/{id}/resource-permissions
     * 
     * Set resource-level permissions for a role. Requires admin access.
     * Expects: {
     *   "permission_id": 5,
     *   "resource_type": "vehicles",
     *   "can_view_all": true,
     *   "can_create": true,
     *   ...
     * }
     */
    public function setResourcePermissions(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);

        if (Response::isSent()) {
            return;
        }

        $roleId = (int)($params['id'] ?? 0);
        if ($roleId <= 0) {
            Response::error('Invalid role ID', 400);
            return;
        }

        $permissionId = (int)$request->input('permission_id', 0);
        $resourceType = (string)$request->input('resource_type', '');

        if ($permissionId <= 0 || $resourceType === '') {
            Response::error('permission_id and resource_type are required', 400);
            return;
        }

        $flags = $request->only([
            'can_view_all', 'can_view_own', 'can_view_tenant',
            'can_create', 'can_edit_all', 'can_edit_own',
            'can_delete_all', 'can_delete_own',
        ]);

        $success = $this->roleModel->setResourcePermission($roleId, $permissionId, $resourceType, $flags);
        if (!$success) {
            Response::error('Failed to set resource permissions', 500);
            return;
        }

        Response::success(null, 'Resource permissions updated successfully');
        return;
    }

    /**
     * GET /api/v1/roles/public
     * 
     * Get roles available for registration (no auth required).
     */
    public function publicList(Request $request, array $params = []): void
    {
        try {
            $roles = $this->roleModel->all();
        } catch (\Throwable $e) {
            error_log("RoleController::publicList error: " . $e->getMessage());
            $roles = [];
        }

        // Filter to basic info only
        $publicRoles = array_map(function ($role) {
            return [
                'id'           => (int)$role['id'],
                'key_name'     => $role['key_name'],
                'display_name' => $role['display_name'],
            ];
        }, $roles);

        Response::json([
            'success' => true,
            'data'    => array_values($publicRoles),
        ]);
        return;
    }
}

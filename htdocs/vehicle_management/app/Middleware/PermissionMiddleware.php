<?php
/**
 * Permission Middleware
 * 
 * Checks user permissions using the new permission tables:
 * - roles: Basic role info (id, key_name, display_name)
 * - permissions: Permission definitions (key_name, module, is_active)
 * - role_permissions: Maps roles to permissions
 * - resource_permissions: Fine-grained resource-level permissions per role
 * 
 * This replaces the old approach of permission columns directly on the roles table.
 */

namespace App\Middleware;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

class PermissionMiddleware
{
    /**
     * Check if a user's role has a specific permission key.
     *
     * @param int    $roleId        The user's role ID
     * @param string $permissionKey The permission key_name to check
     * @return bool
     */
    public static function hasPermission(int $roleId, string $permissionKey): bool
    {
        // Superadmin (role 1) has all permissions
        if ($roleId === 1) {
            return true;
        }

        $db = Database::getInstance();
        $row = $db->fetchOne(
            "SELECT rp.id 
             FROM role_permissions rp
             JOIN permissions p ON p.id = rp.permission_id
             WHERE rp.role_id = ? AND p.key_name = ? AND p.is_active = 1
             LIMIT 1",
            'is',
            [$roleId, $permissionKey]
        );

        return $row !== null;
    }

    /**
     * Check if a user's role has specific resource-level permission.
     *
     * @param int    $roleId       The user's role ID
     * @param string $resourceType Resource type (e.g., 'vehicles', 'users')
     * @param string $action       Action to check (e.g., 'can_view_all', 'can_create', 'can_edit_own')
     * @return bool
     */
    public static function hasResourcePermission(int $roleId, string $resourceType, string $action): bool
    {
        // Superadmin has all permissions
        if ($roleId === 1) {
            return true;
        }

        // Whitelist mapping: only these exact column names are allowed
        $allowedActions = [
            'can_view_all'    => 'can_view_all',
            'can_view_own'    => 'can_view_own',
            'can_view_tenant' => 'can_view_tenant',
            'can_create'      => 'can_create',
            'can_edit_all'    => 'can_edit_all',
            'can_edit_own'    => 'can_edit_own',
            'can_delete_all'  => 'can_delete_all',
            'can_delete_own'  => 'can_delete_own',
        ];

        if (!isset($allowedActions[$action])) {
            return false;
        }

        $column = $allowedActions[$action];
        $db = Database::getInstance();
        $row = $db->fetchOne(
            "SELECT `{$column}` as allowed
             FROM resource_permissions
             WHERE role_id = ? AND resource_type = ?
             LIMIT 1",
            'is',
            [$roleId, $resourceType]
        );

        return $row !== null && (bool)$row['allowed'];
    }

    /**
     * Get all permissions for a role as a structured array.
     *
     * @param int $roleId
     * @return array ['permissions' => [...], 'resources' => [...]]
     */
    public static function getRolePermissions(int $roleId): array
    {
        $db = Database::getInstance();

        // Get all active permissions for the role
        $permissions = $db->fetchAll(
            "SELECT p.id, p.key_name, p.display_name, p.description, p.module
             FROM role_permissions rp
             JOIN permissions p ON p.id = rp.permission_id
             WHERE rp.role_id = ? AND p.is_active = 1
             ORDER BY p.module, p.key_name",
            'i',
            [$roleId]
        );

        // Get resource-level permissions for the role
        $resources = $db->fetchAll(
            "SELECT rp.resource_type, rp.can_view_all, rp.can_view_own, rp.can_view_tenant,
                    rp.can_create, rp.can_edit_all, rp.can_edit_own,
                    rp.can_delete_all, rp.can_delete_own,
                    p.key_name as permission_key, p.display_name as permission_name
             FROM resource_permissions rp
             JOIN permissions p ON p.id = rp.permission_id
             WHERE rp.role_id = ?
             ORDER BY rp.resource_type",
            'i',
            [$roleId]
        );

        // Convert boolean fields
        foreach ($resources as &$res) {
            foreach ($res as $key => $value) {
                if (str_starts_with($key, 'can_')) {
                    $res[$key] = (bool)$value;
                }
            }
        }

        return [
            'permissions' => $permissions,
            'resources'   => $resources,
        ];
    }

    /**
     * Require a specific permission. Sends 403 and exits if not allowed.
     */
    public static function requirePermission(Request $request, string $permissionKey): array
    {
        $user = AuthMiddleware::requireAuth($request);
        if (!self::hasPermission((int)$user['role_id'], $permissionKey)) {
            Response::error('Forbidden: missing permission ' . $permissionKey, 403);
        }
        return $user;
    }

    /**
     * Require a resource-level permission. Sends 403 and exits if not allowed.
     */
    public static function requireResourcePermission(Request $request, string $resourceType, string $action): array
    {
        $user = AuthMiddleware::requireAuth($request);
        if (!self::hasResourcePermission((int)$user['role_id'], $resourceType, $action)) {
            Response::error("Forbidden: missing {$action} on {$resourceType}", 403);
        }
        return $user;
    }
}

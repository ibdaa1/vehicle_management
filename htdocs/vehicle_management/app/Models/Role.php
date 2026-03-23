<?php
/**
 * Role Model
 * 
 * Represents the roles table.
 * Manages role data and role-permission relationships.
 * 
 * Table: roles (id, key_name, display_name, created_at)
 */

namespace App\Models;

class Role extends BaseModel
{
    protected string $table = 'roles';

    /**
     * Find a role by its key_name.
     */
    public function findByKey(string $keyName): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM roles WHERE key_name = ? LIMIT 1",
            's',
            [$keyName]
        );
    }

    /**
     * Get all roles with their permission counts.
     */
    public function allWithPermissionCount(): array
    {
        return $this->db->fetchAll(
            "SELECT r.*, 
                    (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id) as permission_count
             FROM roles r
             ORDER BY r.id"
        );
    }

    /**
     * Get a role with all its assigned permissions.
     */
    public function getWithPermissions(int $roleId): ?array
    {
        $role = $this->find($roleId);
        if (!$role) {
            return null;
        }

        // Get assigned permission IDs and details
        $role['permissions'] = $this->db->fetchAll(
            "SELECT p.id, p.key_name, p.display_name, p.description, p.module
             FROM role_permissions rp
             JOIN permissions p ON p.id = rp.permission_id
             WHERE rp.role_id = ? AND p.is_active = 1
             ORDER BY p.module, p.key_name",
            'i',
            [$roleId]
        );

        // Get resource-level permissions
        $resources = $this->db->fetchAll(
            "SELECT rp.*, p.key_name as permission_key, p.display_name as permission_name
             FROM resource_permissions rp
             JOIN permissions p ON p.id = rp.permission_id
             WHERE rp.role_id = ?
             ORDER BY rp.resource_type",
            'i',
            [$roleId]
        );

        // Convert boolean fields in resources
        foreach ($resources as &$res) {
            foreach ($res as $key => $value) {
                if (str_starts_with($key, 'can_')) {
                    $res[$key] = (bool)$value;
                }
            }
        }
        $role['resource_permissions'] = $resources;

        return $role;
    }

    /**
     * Assign a permission to a role.
     */
    public function assignPermission(int $roleId, int $permissionId): bool
    {
        // Check if already assigned
        $existing = $this->db->fetchOne(
            "SELECT id FROM role_permissions WHERE role_id = ? AND permission_id = ?",
            'ii',
            [$roleId, $permissionId]
        );
        if ($existing) {
            return true; // Already assigned
        }

        $result = $this->db->execute(
            "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)",
            'ii',
            [$roleId, $permissionId]
        );
        return $result->success;
    }

    /**
     * Remove a permission from a role.
     */
    public function removePermission(int $roleId, int $permissionId): bool
    {
        $result = $this->db->execute(
            "DELETE FROM role_permissions WHERE role_id = ? AND permission_id = ?",
            'ii',
            [$roleId, $permissionId]
        );
        return $result->success;
    }

    /**
     * Sync permissions for a role (replace all with new set).
     *
     * @param int   $roleId
     * @param int[] $permissionIds Array of permission IDs to assign
     */
    public function syncPermissions(int $roleId, array $permissionIds): bool
    {
        $conn = $this->connection();

        $conn->begin_transaction();
        try {
            // Remove existing permissions
            $stmt = $conn->prepare("DELETE FROM role_permissions WHERE role_id = ?");
            $stmt->bind_param('i', $roleId);
            $stmt->execute();
            $stmt->close();

            // Insert new permissions
            if (!empty($permissionIds)) {
                $stmt = $conn->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
                foreach ($permissionIds as $pid) {
                    $pid = (int)$pid;
                    $stmt->bind_param('ii', $roleId, $pid);
                    $stmt->execute();
                }
                $stmt->close();
            }

            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            error_log("Role::syncPermissions error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Set resource-level permissions for a role.
     */
    public function setResourcePermission(int $roleId, int $permissionId, string $resourceType, array $flags): bool
    {
        $validFlags = [
            'can_view_all', 'can_view_own', 'can_view_tenant',
            'can_create', 'can_edit_all', 'can_edit_own',
            'can_delete_all', 'can_delete_own'
        ];

        // Check if record exists
        $existing = $this->db->fetchOne(
            "SELECT id FROM resource_permissions WHERE role_id = ? AND permission_id = ? AND resource_type = ?",
            'iis',
            [$roleId, $permissionId, $resourceType]
        );

        $data = [];
        foreach ($validFlags as $flag) {
            $data[$flag] = isset($flags[$flag]) ? (int)(bool)$flags[$flag] : 0;
        }

        if ($existing) {
            // Update
            $setClauses = [];
            $types = '';
            $params = [];
            foreach ($data as $col => $val) {
                $setClauses[] = "`{$col}` = ?";
                $types .= 'i';
                $params[] = $val;
            }
            $types .= 'i';
            $params[] = (int)$existing['id'];

            $result = $this->db->execute(
                "UPDATE resource_permissions SET " . implode(', ', $setClauses) . " WHERE id = ?",
                $types,
                $params
            );
            return $result->success;
        }

        // Insert new
        $data['role_id'] = $roleId;
        $data['permission_id'] = $permissionId;
        $data['resource_type'] = $resourceType;

        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        $types = '';
        $params = [];
        foreach ($data as $val) {
            if (is_int($val)) {
                $types .= 'i';
            } else {
                $types .= 's';
            }
            $params[] = $val;
        }

        $sql = "INSERT INTO resource_permissions (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
        $result = $this->db->execute($sql, $types, $params);
        return $result->success;
    }
}

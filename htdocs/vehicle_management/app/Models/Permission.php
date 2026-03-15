<?php
/**
 * Permission Model
 * 
 * Represents the permissions table.
 * Manages permission definitions and module groupings.
 * 
 * Table: permissions (id, key_name, display_name, description, is_active, module, created_at, updated_at)
 */

namespace App\Models;

class Permission extends BaseModel
{
    protected string $table = 'permissions';

    /**
     * Find a permission by its key_name.
     */
    public function findByKey(string $keyName): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM permissions WHERE key_name = ? LIMIT 1",
            's',
            [$keyName]
        );
    }

    /**
     * Get all active permissions.
     */
    public function allActive(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM permissions WHERE is_active = 1 ORDER BY module, key_name"
        );
    }

    /**
     * Get permissions grouped by module.
     */
    public function groupedByModule(): array
    {
        $permissions = $this->allActive();
        $grouped = [];

        foreach ($permissions as $perm) {
            $module = $perm['module'] ?? 'general';
            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }
            $grouped[$module][] = $perm;
        }

        return $grouped;
    }

    /**
     * Get permissions for a specific module.
     */
    public function byModule(string $module): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM permissions WHERE module = ? AND is_active = 1 ORDER BY key_name",
            's',
            [$module]
        );
    }

    /**
     * Get all distinct modules.
     */
    public function getModules(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT DISTINCT module FROM permissions WHERE is_active = 1 AND module IS NOT NULL ORDER BY module"
        );
        return array_column($rows, 'module');
    }

    /**
     * Check if a permission key exists and is active.
     */
    public function isActive(string $keyName): bool
    {
        $row = $this->db->fetchOne(
            "SELECT is_active FROM permissions WHERE key_name = ? LIMIT 1",
            's',
            [$keyName]
        );
        return $row !== null && (bool)$row['is_active'];
    }
}

<?php
/**
 * Vehicle Movement Model – manages vehicle movement (pickup/return) records.
 */

namespace App\Models;

use App\Core\Database;

class VehicleMovement extends BaseModel
{
    protected string $table = 'vehicle_movements';

    /**
     * Get all movements with optional filters.
     */
    public function allFiltered(array $filters = []): array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE 1=1";
        $types = '';
        $params = [];

        if (!empty($filters['vehicle_code'])) {
            $sql .= " AND vehicle_code = ?";
            $types .= 's';
            $params[] = $filters['vehicle_code'];
        }
        if (!empty($filters['operation_type'])) {
            $sql .= " AND operation_type = ?";
            $types .= 's';
            $params[] = $filters['operation_type'];
        }
        if (!empty($filters['performed_by'])) {
            $sql .= " AND performed_by = ?";
            $types .= 's';
            $params[] = $filters['performed_by'];
        }

        $sql .= " ORDER BY movement_datetime DESC";
        return $this->db->fetchAll($sql, $types, $params);
    }

    /**
     * Get all movements for a specific vehicle code.
     */
    public function getByVehicleCode(string $vehicleCode): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `{$this->table}` WHERE vehicle_code = ? ORDER BY movement_datetime DESC",
            's', [$vehicleCode]
        );
    }

    /**
     * Get recent movements.
     */
    public function getRecent(int $limit = 50): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `{$this->table}` ORDER BY movement_datetime DESC LIMIT ?",
            'i', [$limit]
        );
    }
}

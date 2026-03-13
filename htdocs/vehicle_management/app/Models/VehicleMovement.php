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

    /**
     * Get the latest movement for each vehicle code.
     * Returns an associative array keyed by vehicle_code with the latest operation_type.
     */
    public function getLatestByVehicle(): array
    {
        $sql = "SELECT m.vehicle_code, m.operation_type, m.performed_by, m.movement_datetime
                FROM `{$this->table}` m
                INNER JOIN (
                    SELECT vehicle_code, MAX(movement_datetime) as max_dt
                    FROM `{$this->table}`
                    GROUP BY vehicle_code
                ) latest ON m.vehicle_code = latest.vehicle_code AND m.movement_datetime = latest.max_dt";
        $rows = $this->db->fetchAll($sql);
        $result = [];
        foreach ($rows as $row) {
            $result[$row['vehicle_code']] = $row;
        }
        return $result;
    }
}

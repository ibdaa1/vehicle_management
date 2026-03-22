<?php
/**
 * Vehicle Maintenance Model – manages vehicle maintenance records.
 */

namespace App\Models;

use App\Core\Database;

class VehicleMaintenance extends BaseModel
{
    protected string $table = 'vehicle_maintenance';

    /**
     * Get all maintenance records with optional filters, ordered by visit_date DESC.
     */
    public function allFiltered(array $filters = []): array
    {
        $sql = "SELECT m.*, v.type AS vehicle_type, v.vehicle_category
                FROM `{$this->table}` m
                LEFT JOIN vehicles v ON v.vehicle_code = m.vehicle_code
                WHERE 1=1";
        $types = '';
        $params = [];

        if (!empty($filters['vehicle_code'])) {
            $sql .= " AND m.vehicle_code = ?";
            $types .= 's';
            $params[] = $filters['vehicle_code'];
        }
        if (!empty($filters['maintenance_type'])) {
            $sql .= " AND m.maintenance_type = ?";
            $types .= 's';
            $params[] = $filters['maintenance_type'];
        }

        $sql .= " ORDER BY m.visit_date DESC, m.id DESC";
        return $this->db->fetchAll($sql, $types, $params);
    }

    /**
     * Find a single record with vehicle info.
     */
    public function findWithVehicle(int $id): ?array
    {
        $sql = "SELECT m.*, v.type AS vehicle_type, v.vehicle_category
                FROM `{$this->table}` m
                LEFT JOIN vehicles v ON v.vehicle_code = m.vehicle_code
                WHERE m.id = ?
                LIMIT 1";
        return $this->db->fetchOne($sql, 'i', [$id]);
    }

    /**
     * Get statistics summary.
     */
    public function stats(): array
    {
        $row = $this->db->fetchOne("
            SELECT
                COUNT(*)                                                                            AS total,
                SUM(CASE WHEN maintenance_type = 'Routine'          THEN 1 ELSE 0 END) AS routine,
                SUM(CASE WHEN maintenance_type = 'Emergency'        THEN 1 ELSE 0 END) AS emergency,
                SUM(CASE WHEN maintenance_type = 'Technical Check'  THEN 1 ELSE 0 END) AS technical_check,
                SUM(CASE WHEN next_visit_date >= CURDATE()          THEN 1 ELSE 0 END) AS upcoming,
                SUM(CASE WHEN next_visit_date < CURDATE()           THEN 1 ELSE 0 END) AS overdue
            FROM `{$this->table}`
        ");
        return $row ?: [
            'total' => 0, 'routine' => 0, 'emergency' => 0,
            'technical_check' => 0, 'upcoming' => 0, 'overdue' => 0,
        ];
    }
}

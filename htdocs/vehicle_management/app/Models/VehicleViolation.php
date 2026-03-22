<?php
/**
 * Vehicle Violation Model – manages vehicle violation records.
 */

namespace App\Models;

use App\Core\Database;

class VehicleViolation extends BaseModel
{
    protected string $table = 'vehicle_violations';

    /**
     * Get all violations with optional filters, ordered by violation_datetime DESC.
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
        if (!empty($filters['violation_status'])) {
            $sql .= " AND violation_status = ?";
            $types .= 's';
            $params[] = $filters['violation_status'];
        }

        $sql .= " ORDER BY violation_datetime DESC";
        return $this->db->fetchAll($sql, $types, $params);
    }

    /**
     * Get violations with the person who had the vehicle at violation time.
     * Joins vehicle_movements to find who performed the pickup during the violation window.
     */
    public function allWithHolder(array $filters = []): array
    {
        $sql = "
        SELECT
          vv.*,
          vm.performed_by AS holder_emp_id,
          u.username       AS holder_name,
          vm.movement_datetime AS pickup_datetime
        FROM vehicle_violations vv
        LEFT JOIN vehicle_movements vm
          ON vm.vehicle_code = vv.vehicle_code
         AND vm.operation_type = 'pickup'
         AND vm.movement_datetime = (
            SELECT MAX(vm2.movement_datetime)
            FROM vehicle_movements vm2
            WHERE vm2.vehicle_code = vv.vehicle_code
              AND vm2.operation_type = 'pickup'
              AND vm2.movement_datetime <= vv.violation_datetime
              AND vm2.movement_datetime >= IFNULL((
                  SELECT MAX(vm3.movement_datetime)
                  FROM vehicle_movements vm3
                  WHERE vm3.vehicle_code = vv.vehicle_code
                    AND vm3.operation_type = 'return'
                    AND vm3.movement_datetime <= vv.violation_datetime
              ), '1970-01-01 00:00:00')
         )
        LEFT JOIN users u ON u.emp_id = vm.performed_by
        WHERE 1=1
        ";
        $types = '';
        $params = [];

        if (!empty($filters['vehicle_code'])) {
            $sql .= " AND vv.vehicle_code = ?";
            $types .= 's';
            $params[] = $filters['vehicle_code'];
        }
        if (!empty($filters['violation_status'])) {
            $sql .= " AND vv.violation_status = ?";
            $types .= 's';
            $params[] = $filters['violation_status'];
        }

        $sql .= " ORDER BY vv.violation_datetime DESC, vv.id DESC";
        return $this->db->fetchAll($sql, $types, $params);
    }

    /**
     * Get a single violation with holder info.
     */
    public function findWithHolder(int $id): ?array
    {
        $sql = "
        SELECT
          vv.*,
          vm.performed_by AS holder_emp_id,
          u.username       AS holder_name,
          vm.movement_datetime AS pickup_datetime
        FROM vehicle_violations vv
        LEFT JOIN vehicle_movements vm
          ON vm.vehicle_code = vv.vehicle_code
         AND vm.operation_type = 'pickup'
         AND vm.movement_datetime = (
            SELECT MAX(vm2.movement_datetime)
            FROM vehicle_movements vm2
            WHERE vm2.vehicle_code = vv.vehicle_code
              AND vm2.operation_type = 'pickup'
              AND vm2.movement_datetime <= vv.violation_datetime
              AND vm2.movement_datetime >= IFNULL((
                  SELECT MAX(vm3.movement_datetime)
                  FROM vehicle_movements vm3
                  WHERE vm3.vehicle_code = vv.vehicle_code
                    AND vm3.operation_type = 'return'
                    AND vm3.movement_datetime <= vv.violation_datetime
              ), '1970-01-01 00:00:00')
         )
        LEFT JOIN users u ON u.emp_id = vm.performed_by
        WHERE vv.id = ?
        LIMIT 1
        ";
        return $this->db->fetchOne($sql, 'i', [$id]);
    }

    /**
     * Get statistics summary.
     */
    public function stats(): array
    {
        $row = $this->db->fetchOne("
            SELECT
                COUNT(*)                                                AS total,
                SUM(CASE WHEN violation_status = 'paid'   THEN 1 ELSE 0 END) AS paid,
                SUM(CASE WHEN violation_status = 'unpaid' THEN 1 ELSE 0 END) AS unpaid,
                COALESCE(SUM(violation_amount), 0)                       AS total_amount,
                COALESCE(SUM(CASE WHEN violation_status = 'paid' THEN violation_amount ELSE 0 END), 0) AS paid_amount,
                COALESCE(SUM(CASE WHEN violation_status = 'unpaid' THEN violation_amount ELSE 0 END), 0) AS unpaid_amount
            FROM `{$this->table}`
        ");
        return $row ?: [
            'total' => 0, 'paid' => 0, 'unpaid' => 0,
            'total_amount' => 0, 'paid_amount' => 0, 'unpaid_amount' => 0,
        ];
    }
}

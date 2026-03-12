<?php
/**
 * Vehicle Model
 */

namespace App\Models;

use App\Core\Database;

class Vehicle extends BaseModel
{
    protected string $table = 'vehicles';

    /**
     * Get vehicles with department/section/division names.
     */
    public function allWithRelations(array $filters = []): array
    {
        $db = Database::getInstance();
        $sql = "SELECT v.*, 
                       dept.name_ar AS department_name_ar, dept.name_en AS department_name_en,
                       sec.name_ar AS section_name_ar, sec.name_en AS section_name_en,
                       dv.name_ar AS division_name_ar, dv.name_en AS division_name_en
                FROM vehicles v
                LEFT JOIN Departments dept ON v.department_id = dept.department_id
                LEFT JOIN Sections sec ON v.section_id = sec.section_id
                LEFT JOIN Divisions dv ON v.division_id = dv.division_id
                WHERE 1=1";

        $types = '';
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND v.status = ?";
            $types .= 's';
            $params[] = $filters['status'];
        }
        if (!empty($filters['vehicle_mode'])) {
            $sql .= " AND v.vehicle_mode = ?";
            $types .= 's';
            $params[] = $filters['vehicle_mode'];
        }
        if (!empty($filters['department_id'])) {
            $sql .= " AND v.department_id = ?";
            $types .= 'i';
            $params[] = (int)$filters['department_id'];
        }
        if (!empty($filters['gender'])) {
            $sql .= " AND v.gender = ?";
            $types .= 's';
            $params[] = $filters['gender'];
        }

        $sql .= " ORDER BY v.id DESC";
        return $db->fetchAll($sql, $types, $params);
    }

    /**
     * Get vehicle by code.
     */
    public function findByCode(string $code): ?array
    {
        $db = Database::getInstance();
        return $db->fetchOne(
            "SELECT * FROM vehicles WHERE vehicle_code = ?",
            's',
            [$code]
        );
    }

    /**
     * Get dashboard statistics.
     */
    public function getStats(): array
    {
        $db = Database::getInstance();

        $total = $db->fetchOne("SELECT COUNT(*) as cnt FROM vehicles");
        $operational = $db->fetchOne("SELECT COUNT(*) as cnt FROM vehicles WHERE status='operational'");
        $maintenance = $db->fetchOne("SELECT COUNT(*) as cnt FROM vehicles WHERE status='maintenance'");
        $outOfService = $db->fetchOne("SELECT COUNT(*) as cnt FROM vehicles WHERE status='out_of_service'");
        $private = $db->fetchOne("SELECT COUNT(*) as cnt FROM vehicles WHERE vehicle_mode='private'");
        $shift = $db->fetchOne("SELECT COUNT(*) as cnt FROM vehicles WHERE vehicle_mode='shift'");

        return [
            'total' => (int)($total['cnt'] ?? 0),
            'operational' => (int)($operational['cnt'] ?? 0),
            'maintenance' => (int)($maintenance['cnt'] ?? 0),
            'out_of_service' => (int)($outOfService['cnt'] ?? 0),
            'private' => (int)($private['cnt'] ?? 0),
            'shift' => (int)($shift['cnt'] ?? 0),
        ];
    }
}

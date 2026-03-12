<?php
/**
 * Vehicle Handover Model – manages vehicle handover/receive records.
 */

namespace App\Models;

use App\Core\Database;

class VehicleHandover extends BaseModel
{
    protected string $table = 'vehicle_handovers';

    public function getByVehicle(int $vehicleId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM vehicle_handovers WHERE vehicle_id = ? ORDER BY handover_date DESC",
            'i', [$vehicleId]
        );
    }

    public function getRecent(int $limit = 20): array
    {
        return $this->db->fetchAll(
            "SELECT vh.*, v.vehicle_code FROM vehicle_handovers vh LEFT JOIN vehicles v ON vh.vehicle_id = v.id ORDER BY vh.handover_date DESC LIMIT ?",
            'i', [$limit]
        );
    }
}

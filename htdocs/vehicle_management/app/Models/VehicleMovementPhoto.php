<?php
/**
 * Vehicle Movement Photo Model – manages photos attached to movements.
 */

namespace App\Models;

use App\Core\Database;

class VehicleMovementPhoto extends BaseModel
{
    protected string $table = 'vehicle_movement_photos';

    /**
     * Get all photos for a specific movement.
     */
    public function getByMovement(int $movementId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `{$this->table}` WHERE movement_id = ? ORDER BY id ASC",
            'i', [$movementId]
        );
    }
}

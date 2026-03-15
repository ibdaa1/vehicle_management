<?php
/**
 * Sector Model
 */

namespace App\Models;

use App\Core\Database;

class Sector extends BaseModel
{
    protected string $table = 'sectors';

    /**
     * Get all active sectors.
     */
    public function allSectors(): array
    {
        $db = Database::getInstance();
        return $db->fetchAll("SELECT * FROM sectors WHERE is_active = 1 ORDER BY id");
    }

    /**
     * Get a single sector by ID.
     */
    public function findById(int $id): ?array
    {
        $db = Database::getInstance();
        return $db->fetchOne(
            "SELECT * FROM sectors WHERE id = ? LIMIT 1",
            'i',
            [$id]
        );
    }
}

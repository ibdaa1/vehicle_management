<?php
/**
 * Department Model
 */

namespace App\Models;

use App\Core\Database;

class Department extends BaseModel
{
    protected string $table = 'Departments';
    protected string $primaryKey = 'department_id';

    /**
     * Get all departments.
     */
    public function allDepartments(): array
    {
        $db = Database::getInstance();
        return $db->fetchAll("SELECT * FROM Departments ORDER BY department_id");
    }

    /**
     * Get sections for a department.
     */
    public function getSections(int $departmentId): array
    {
        $db = Database::getInstance();
        return $db->fetchAll(
            "SELECT * FROM Sections WHERE department_id = ? ORDER BY section_id",
            'i', [$departmentId]
        );
    }

    /**
     * Get divisions for a section.
     */
    public function getDivisions(int $sectionId): array
    {
        $db = Database::getInstance();
        return $db->fetchAll(
            "SELECT * FROM Divisions WHERE section_id = ? ORDER BY division_id",
            'i', [$sectionId]
        );
    }

    /**
     * Get all references (sectors + departments + sections + divisions).
     */
    public function getAllReferences(): array
    {
        $db = Database::getInstance();
        return [
            'sectors' => $db->fetchAll("SELECT * FROM sectors WHERE is_active = 1 ORDER BY id"),
            'departments' => $db->fetchAll("SELECT * FROM Departments ORDER BY department_id"),
            'sections' => $db->fetchAll("SELECT * FROM Sections ORDER BY section_id"),
            'divisions' => $db->fetchAll("SELECT * FROM Divisions ORDER BY division_id"),
        ];
    }
}
<?php
/**
 * SystemSetting Model
 */

namespace App\Models;

use App\Core\Database;

class SystemSetting extends BaseModel
{
    protected string $table = 'system_settings';

    /**
     * Get setting value by key.
     */
    public function getValue(string $key, $default = null)
    {
        $db = Database::getInstance();
        $row = $db->fetchOne(
            "SELECT setting_value FROM system_settings WHERE setting_key = ?",
            's',
            [$key]
        );
        return $row ? $row['setting_value'] : $default;
    }

    /**
     * Get all public settings as key-value map.
     */
    public function getPublicSettings(): array
    {
        $db = Database::getInstance();
        $rows = $db->fetchAll(
            "SELECT setting_key, setting_value FROM system_settings WHERE is_public = 1"
        );
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    /**
     * Get all settings grouped by category.
     */
    public function getByCategory(string $category): array
    {
        $db = Database::getInstance();
        return $db->fetchAll(
            "SELECT * FROM system_settings WHERE category = ? ORDER BY id",
            's',
            [$category]
        );
    }

    /**
     * Update or create a setting.
     */
    public function setValue(string $key, string $value): bool
    {
        $db = Database::getInstance();
        $existing = $db->fetchOne(
            "SELECT id FROM system_settings WHERE setting_key = ?",
            's',
            [$key]
        );
        if ($existing) {
            $result = $db->execute(
                "UPDATE system_settings SET setting_value = ? WHERE setting_key = ?",
                'ss',
                [$value, $key]
            );
            return $result->success;
        }
        return false;
    }
}

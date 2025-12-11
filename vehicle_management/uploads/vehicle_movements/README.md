# Vehicle Movements Photos Directory

This directory stores uploaded photos for vehicle movements.

## Database Table Required

```sql
CREATE TABLE IF NOT EXISTS vehicle_movement_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    movement_id INT NULL COMMENT 'Reference to vehicle_movements.id if available',
    vehicle_code VARCHAR(50) NOT NULL COMMENT 'Vehicle code for the movement',
    photo_url VARCHAR(255) NOT NULL COMMENT 'Relative path to the uploaded photo',
    taken_by VARCHAR(50) NOT NULL COMMENT 'emp_id of the user who uploaded',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT NULL COMMENT 'Optional notes about the photo',
    INDEX idx_vehicle_code (vehicle_code),
    INDEX idx_taken_by (taken_by),
    INDEX idx_movement_id (movement_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add coordinate columns to vehicle_movements table (if not already present)
-- Use IF NOT EXISTS to prevent errors on repeated deployments
-- Note: MySQL 5.7+ doesn't support IF NOT EXISTS for ALTER TABLE ADD COLUMN
-- For safety, you may need to check if columns exist first or ignore errors

ALTER TABLE vehicle_movements 
ADD COLUMN latitude DECIMAL(10, 8) NULL COMMENT 'GPS latitude coordinate';

ALTER TABLE vehicle_movements 
ADD COLUMN longitude DECIMAL(11, 8) NULL COMMENT 'GPS longitude coordinate';

-- If columns already exist, the above will error. That's expected and safe.
-- You can also use this alternative approach:

-- Check and add latitude if not exists (for MySQL 8.0+)
-- SET @s = (SELECT IF(
--     (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
--      WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='vehicle_movements' AND COLUMN_NAME='latitude') > 0,
--     "SELECT 'latitude exists'",
--     "ALTER TABLE vehicle_movements ADD COLUMN latitude DECIMAL(10, 8) NULL COMMENT 'GPS latitude coordinate'"
-- ));
-- PREPARE stmt FROM @s;
-- EXECUTE stmt;
-- DEALLOCATE PREPARE stmt;
```

## Permissions

This directory must be writable by the web server user (www-data, apache, nginx, etc.)

```bash
chmod 755 /path/to/vehicle_management/uploads/vehicle_movements
```

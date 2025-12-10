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

-- Also add coordinate fields to vehicle_movements table if not present:
ALTER TABLE vehicle_movements 
ADD COLUMN latitude DECIMAL(10, 8) NULL COMMENT 'GPS latitude coordinate',
ADD COLUMN longitude DECIMAL(11, 8) NULL COMMENT 'GPS longitude coordinate';
```

## Permissions

This directory must be writable by the web server user (www-data, apache, nginx, etc.)

```bash
chmod 755 /path/to/vehicle_management/uploads/vehicle_movements
```

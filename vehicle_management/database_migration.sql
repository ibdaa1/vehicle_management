-- =====================================================
-- Vehicle Movements Enhancement - Database Migration
-- =====================================================
-- 
-- This script adds the necessary database tables and columns
-- for the vehicle movements enhancement features:
-- - GPS coordinates storage
-- - Photo uploads tracking
--
-- Run this script against your database before deploying
-- the application changes.
--
-- IMPORTANT: Review and test in a development environment first!
-- =====================================================

-- =====================================================
-- 1. Add GPS Coordinate Columns to vehicle_movements
-- =====================================================

-- Use a transaction for atomicity
START TRANSACTION;

-- Check and add latitude column
SET @dbname = DATABASE();
SET @tablename = 'vehicle_movements';
SET @columnname = 'latitude';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE 
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  "SELECT 'Column latitude already exists' AS message;",
  "ALTER TABLE vehicle_movements ADD COLUMN latitude DECIMAL(10, 8) NULL COMMENT 'GPS latitude coordinate (-90 to 90)';"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Check and add longitude column
SET @columnname = 'longitude';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE 
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  "SELECT 'Column longitude already exists' AS message;",
  "ALTER TABLE vehicle_movements ADD COLUMN longitude DECIMAL(11, 8) NULL COMMENT 'GPS longitude coordinate (-180 to 180)';"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

COMMIT;

SELECT 'Step 1 completed: Coordinate columns added or already exist' AS status;

-- =====================================================
-- 2. Create vehicle_movement_photos Table
-- =====================================================

-- This uses IF NOT EXISTS so it's safe to run multiple times
CREATE TABLE IF NOT EXISTS vehicle_movement_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Reference to the movement (optional, can be NULL if movement_id not available)
    movement_id INT NULL 
        COMMENT 'Reference to vehicle_movements.id if available',
    
    -- Vehicle code for the movement (required)
    vehicle_code VARCHAR(50) NOT NULL 
        COMMENT 'Vehicle code for the movement',
    
    -- Photo file path
    photo_url VARCHAR(255) NOT NULL 
        COMMENT 'Relative path to the uploaded photo',
    
    -- Who uploaded the photo
    taken_by VARCHAR(50) NOT NULL 
        COMMENT 'emp_id of the user who uploaded',
    
    -- Timestamp
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        COMMENT 'When the photo was uploaded',
    
    -- Optional notes
    notes TEXT NULL 
        COMMENT 'Optional notes about the photo',
    
    -- Indexes for better performance
    INDEX idx_vehicle_code (vehicle_code),
    INDEX idx_taken_by (taken_by),
    INDEX idx_movement_id (movement_id),
    INDEX idx_created_at (created_at)
    
) ENGINE=InnoDB 
  DEFAULT CHARSET=utf8mb4 
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Stores photos uploaded for vehicle movements';

SELECT 'Step 2 completed: vehicle_movement_photos table created or already exists' AS status;

-- =====================================================
-- 3. Verification Queries
-- =====================================================

-- Uncomment these to verify the changes:

-- Check if latitude and longitude columns exist
-- SELECT 
--     COLUMN_NAME, 
--     DATA_TYPE, 
--     COLUMN_TYPE,
--     IS_NULLABLE,
--     COLUMN_COMMENT
-- FROM INFORMATION_SCHEMA.COLUMNS
-- WHERE TABLE_SCHEMA = DATABASE()
--   AND TABLE_NAME = 'vehicle_movements'
--   AND COLUMN_NAME IN ('latitude', 'longitude');

-- Check vehicle_movement_photos table structure
-- DESCRIBE vehicle_movement_photos;

-- Check if table was created successfully
-- SELECT 
--     TABLE_NAME,
--     ENGINE,
--     TABLE_COLLATION,
--     TABLE_COMMENT
-- FROM INFORMATION_SCHEMA.TABLES
-- WHERE TABLE_SCHEMA = DATABASE()
--   AND TABLE_NAME = 'vehicle_movement_photos';

-- =====================================================
-- 4. Sample Data (Optional - for testing)
-- =====================================================

-- Uncomment to insert sample coordinate data:
-- UPDATE vehicle_movements 
-- SET latitude = 25.2048, longitude = 55.2708 
-- WHERE vehicle_code = 'ABC123' 
-- LIMIT 1;

-- =====================================================
-- Migration Complete
-- =====================================================

SELECT 'Migration script completed. Please verify the changes.' AS status;

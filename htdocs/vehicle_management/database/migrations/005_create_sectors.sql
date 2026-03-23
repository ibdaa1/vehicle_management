-- Migration: Add sectors table and sector_id to users and vehicles
-- This creates the sectors organizational unit and links it to users and vehicles.

CREATE TABLE IF NOT EXISTS sectors (
    id INT(11) NOT NULL AUTO_INCREMENT,
    sector_code VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    name_en VARCHAR(255) DEFAULT NULL,
    description MEDIUMTEXT DEFAULT NULL,
    manager_user_id INT(11) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_sector_code (sector_code),
    KEY idx_manager (manager_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add sector_id to users table (if not already present)
ALTER TABLE users ADD COLUMN IF NOT EXISTS sector_id INT(11) DEFAULT NULL AFTER role_id;
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_users_sector (sector_id);

-- Add sector_id to vehicles table (if not already present)
ALTER TABLE vehicles ADD COLUMN IF NOT EXISTS sector_id INT(11) DEFAULT NULL AFTER vehicle_code;
ALTER TABLE vehicles ADD INDEX IF NOT EXISTS idx_vehicles_sector (sector_id);

-- Migration 004: Create vehicle_movements table
-- This replaces the vehicle_handovers system with a more comprehensive movement tracking table

CREATE TABLE IF NOT EXISTS `vehicle_movements` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `vehicle_code` VARCHAR(50) NOT NULL,
    `operation_type` ENUM('pickup','return') NOT NULL,
    `performed_by` VARCHAR(50) NOT NULL,
    `movement_datetime` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `notes` TEXT DEFAULT NULL,
    `vehicle_condition` ENUM('clean','acceptable','damaged') DEFAULT NULL,
    `fuel_level` ENUM('full','three_quarter','half','quarter','empty') DEFAULT NULL,
    `latitude` DECIMAL(10,8) DEFAULT NULL,
    `longitude` DECIMAL(11,8) DEFAULT NULL,
    `created_by` VARCHAR(50) DEFAULT NULL,
    `updated_by` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_vehicle_code` (`vehicle_code`),
    KEY `idx_performed_by` (`performed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- Database Setup Script for Vehicle Management System
-- ================================================================
-- Run this script to set up a fresh local database for testing.
-- 
-- Usage:
--   mysql -u root -e "SOURCE /path/to/setup_database.sql"
--   OR
--   mysql -u root < setup_database.sql
-- ================================================================

-- Create database
CREATE DATABASE IF NOT EXISTS vehicle_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vehicle_management;

-- -------------------------------------------
-- Users table
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `emp_id` VARCHAR(50) NOT NULL,
    `username` VARCHAR(50) NOT NULL,
    `email` VARCHAR(191) NOT NULL,
    `password_hash` VARCHAR(255) DEFAULT NULL,
    `preferred_language` VARCHAR(8) DEFAULT NULL,
    `phone` VARCHAR(45) DEFAULT NULL,
    `gender` ENUM('men','women') DEFAULT NULL,
    `role_id` INT(11) DEFAULT NULL,
    `profile_image` VARCHAR(255) DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `department_id` INT(11) DEFAULT NULL,
    `section_id` INT(11) DEFAULT NULL,
    `division_id` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_emp_id` (`emp_id`),
    UNIQUE KEY `uk_username` (`username`),
    UNIQUE KEY `uk_email` (`email`),
    KEY `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------
-- User sessions table
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `user_sessions` (
    `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `token` CHAR(64) NOT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `ip` VARCHAR(45) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `expires_at` DATETIME DEFAULT NULL,
    `revoked` TINYINT(1) DEFAULT 0,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_token` (`token`),
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------
-- Roles table
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `key_name` VARCHAR(100) NOT NULL,
    `display_name` VARCHAR(150) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `roles` (`id`, `key_name`, `display_name`) VALUES
    (1, 'superadmin', 'مدير النظام / Super Admin'),
    (2, 'admin', 'مدير / Admin'),
    (3, 'manager', 'مشرف / Manager'),
    (4, 'employee', 'موظف / Employee'),
    (5, 'viewer', 'مشاهد / Viewer');

-- -------------------------------------------
-- Permissions table
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `permissions` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `key_name` VARCHAR(100) NOT NULL,
    `display_name` VARCHAR(150) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `is_active` TINYINT(1) DEFAULT 1,
    `module` VARCHAR(100) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_permissions_key` (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `permissions` (`key_name`, `display_name`, `description`, `module`) VALUES
    ('users_read',    'View Users',       'View user list and profiles',    'users'),
    ('users_create',  'Create User',      'Create new users',               'users'),
    ('users_edit',    'Edit User',        'Edit user information',          'users'),
    ('users_delete',  'Delete User',      'Delete users',                   'users'),
    ('users_activate','Activate User',    'Activate/deactivate users',      'users'),
    ('vehicles_read',   'View Vehicles',   'View vehicle list',             'vehicles'),
    ('vehicles_create', 'Create Vehicle',  'Add new vehicles',              'vehicles'),
    ('vehicles_edit',   'Edit Vehicle',    'Edit vehicle information',      'vehicles'),
    ('vehicles_delete', 'Delete Vehicle',  'Delete vehicles',               'vehicles'),
    ('vehicles_assign', 'Assign Vehicle',  'Assign vehicles to employees',  'vehicles'),
    ('movements_read',   'View Movements',  'View vehicle movements',       'movements'),
    ('movements_create', 'Create Movement', 'Record vehicle movements',     'movements'),
    ('movements_edit',   'Edit Movement',   'Edit movement records',        'movements'),
    ('violations_read',   'View Violations',  'View violations',            'violations'),
    ('violations_create', 'Create Violation', 'Record violations',          'violations'),
    ('violations_edit',   'Edit Violation',   'Edit violation records',     'violations'),
    ('violations_delete', 'Delete Violation', 'Delete violations',          'violations'),
    ('maintenance_read',   'View Maintenance',   'View maintenance records',   'maintenance'),
    ('maintenance_create', 'Create Maintenance', 'Add maintenance records',    'maintenance'),
    ('maintenance_edit',   'Edit Maintenance',   'Edit maintenance records',   'maintenance'),
    ('maintenance_delete', 'Delete Maintenance', 'Delete maintenance records', 'maintenance'),
    ('reports_view',   'View Reports',     'View reports',                   'reports'),
    ('reports_export', 'Export Reports',   'Export/print reports',           'reports'),
    ('roles_manage',       'Manage Roles',       'Create/edit/delete roles',       'admin'),
    ('permissions_manage', 'Manage Permissions', 'Manage permission assignments',  'admin'),
    ('references_read',   'View References',   'View departments/sections/divisions', 'references'),
    ('references_manage', 'Manage References', 'Create/edit/delete references',      'references'),
    ('settings_view',   'View Settings',    'View system settings',           'settings'),
    ('settings_manage', 'Manage Settings', 'Edit system settings',           'settings');

-- -------------------------------------------
-- Role-Permission mapping
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `role_permissions` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `role_id` BIGINT(20) UNSIGNED NOT NULL,
    `permission_id` BIGINT(20) UNSIGNED NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_role_permission` (`role_id`, `permission_id`),
    KEY `idx_role_id` (`role_id`),
    KEY `idx_permission_id` (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Superadmin gets all permissions
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `permissions`;

-- Admin gets all except role/perm management
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, id FROM `permissions` WHERE `key_name` NOT IN ('roles_manage', 'permissions_manage');

-- -------------------------------------------
-- Resource-level permissions
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `resource_permissions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `permission_id` BIGINT(20) UNSIGNED NOT NULL,
    `role_id` BIGINT(20) UNSIGNED NOT NULL,
    `resource_type` VARCHAR(50) NOT NULL,
    `can_view_all` TINYINT(1) NOT NULL DEFAULT 0,
    `can_view_own` TINYINT(1) NOT NULL DEFAULT 0,
    `can_view_tenant` TINYINT(1) NOT NULL DEFAULT 0,
    `can_create` TINYINT(1) NOT NULL DEFAULT 0,
    `can_edit_all` TINYINT(1) NOT NULL DEFAULT 0,
    `can_edit_own` TINYINT(1) NOT NULL DEFAULT 0,
    `can_delete_all` TINYINT(1) NOT NULL DEFAULT 0,
    `can_delete_own` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_rp_role` (`role_id`),
    KEY `idx_rp_resource` (`resource_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

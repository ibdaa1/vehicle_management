-- ================================================================
-- Migration 001: Permission System Tables
-- ================================================================
-- Tables: roles, permissions, role_permissions, resource_permissions
--
-- permissions table: ONE row per module (manage_users, manage_vehicles, etc.)
-- resource_permissions table: granular CRUD flags per role per resource
--
-- NOTE: Uses DELETE + INSERT (not INSERT IGNORE) to ensure data is
-- always correct when re-running the migration.
-- ================================================================

-- -------------------------------------------
-- 1. Roles Table
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `key_name` VARCHAR(100) NOT NULL,
    `display_name` VARCHAR(150) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `roles` (`id`, `key_name`, `display_name`) VALUES
    (1, 'superadmin', 'مدير النظام / Super Admin'),
    (2, 'admin', 'مدير / Admin'),
    (3, 'manager', 'مشرف / Manager'),
    (4, 'employee', 'موظف / Employee'),
    (5, 'viewer', 'مشاهد / Viewer')
ON DUPLICATE KEY UPDATE `key_name`=VALUES(`key_name`), `display_name`=VALUES(`display_name`);

-- -------------------------------------------
-- 2. Permissions Table — ONE row per module
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

-- Clear old permission data and re-insert to ensure correctness
DELETE FROM `permissions` WHERE `key_name` NOT IN ('manage_users','manage_vehicles','manage_movements','manage_violations','manage_maintenance','manage_reports','manage_roles','manage_references','manage_settings');

INSERT INTO `permissions` (`key_name`, `display_name`, `description`, `module`) VALUES
    ('manage_users',       'manage_users',       'Manage users (CRUD)',                   'users'),
    ('manage_vehicles',    'manage_vehicles',    'Manage vehicles (CRUD)',                'vehicles'),
    ('manage_movements',   'manage_movements',   'Manage vehicle movements',              'movements'),
    ('manage_violations',  'manage_violations',  'Manage vehicle violations',             'violations'),
    ('manage_maintenance', 'manage_maintenance', 'Manage maintenance records',            'maintenance'),
    ('manage_reports',     'manage_reports',     'View and export reports',               'reports'),
    ('manage_roles',       'manage_roles',       'Manage roles and permissions',          'admin'),
    ('manage_references',  'manage_references',  'Manage departments/sections/divisions', 'references'),
    ('manage_settings',    'manage_settings',    'Manage system settings',                'settings')
ON DUPLICATE KEY UPDATE
    `display_name`=VALUES(`display_name`),
    `description`=VALUES(`description`),
    `module`=VALUES(`module`),
    `is_active`=1;

-- -------------------------------------------
-- 3. Role-Permission Mapping Table
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

-- Clear and re-seed role_permissions for all roles to ensure correctness
DELETE FROM `role_permissions` WHERE `role_id` IN (1,2,3,4,5);

-- Superadmin (role 1): all permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `permissions`;

-- Admin (role 2): all except manage_roles
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, id FROM `permissions` WHERE `key_name` NOT IN ('manage_roles');

-- Manager (role 3): most modules (not roles/settings)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 3, id FROM `permissions` WHERE `key_name` IN ('manage_users','manage_vehicles','manage_movements','manage_violations','manage_maintenance','manage_reports','manage_references');

-- Employee (role 4): vehicles, movements, reports
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 4, id FROM `permissions` WHERE `key_name` IN ('manage_vehicles','manage_movements','manage_reports');

-- Viewer (role 5): vehicles, movements, reports (read-only via resource_permissions flags)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 5, id FROM `permissions` WHERE `key_name` IN ('manage_vehicles','manage_movements','manage_reports');

-- -------------------------------------------
-- 4. Resource Permissions Table — Granular CRUD per role per resource
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

-- Clear and re-seed resource_permissions for all roles
DELETE FROM `resource_permissions` WHERE `role_id` IN (1,2,3,4,5);

-- Superadmin (role 1): full CRUD on all resources
INSERT INTO `resource_permissions` (`permission_id`, `role_id`, `resource_type`, `can_view_all`, `can_view_own`, `can_view_tenant`, `can_create`, `can_edit_all`, `can_edit_own`, `can_delete_all`, `can_delete_own`)
SELECT p.id, 1, 'users', 1,1,1,1,1,1,1,1 FROM permissions p WHERE p.key_name='manage_users';
INSERT INTO `resource_permissions` (`permission_id`, `role_id`, `resource_type`, `can_view_all`, `can_view_own`, `can_view_tenant`, `can_create`, `can_edit_all`, `can_edit_own`, `can_delete_all`, `can_delete_own`)
SELECT p.id, 1, 'vehicles', 1,1,1,1,1,1,1,1 FROM permissions p WHERE p.key_name='manage_vehicles';
INSERT INTO `resource_permissions` (`permission_id`, `role_id`, `resource_type`, `can_view_all`, `can_view_own`, `can_view_tenant`, `can_create`, `can_edit_all`, `can_edit_own`, `can_delete_all`, `can_delete_own`)
SELECT p.id, 1, 'movements', 1,1,1,1,1,1,1,1 FROM permissions p WHERE p.key_name='manage_movements';
INSERT INTO `resource_permissions` (`permission_id`, `role_id`, `resource_type`, `can_view_all`, `can_view_own`, `can_view_tenant`, `can_create`, `can_edit_all`, `can_edit_own`, `can_delete_all`, `can_delete_own`)
SELECT p.id, 1, 'violations', 1,1,1,1,1,1,1,1 FROM permissions p WHERE p.key_name='manage_violations';
INSERT INTO `resource_permissions` (`permission_id`, `role_id`, `resource_type`, `can_view_all`, `can_view_own`, `can_view_tenant`, `can_create`, `can_edit_all`, `can_edit_own`, `can_delete_all`, `can_delete_own`)
SELECT p.id, 1, 'maintenance', 1,1,1,1,1,1,1,1 FROM permissions p WHERE p.key_name='manage_maintenance';

-- Admin (role 2): full CRUD on all resources
INSERT INTO `resource_permissions` (`permission_id`, `role_id`, `resource_type`, `can_view_all`, `can_view_own`, `can_view_tenant`, `can_create`, `can_edit_all`, `can_edit_own`, `can_delete_all`, `can_delete_own`)
SELECT p.id, 2, 'users', 1,1,1,1,1,1,1,1 FROM permissions p WHERE p.key_name='manage_users';
INSERT INTO `resource_permissions` (`permission_id`, `role_id`, `resource_type`, `can_view_all`, `can_view_own`, `can_view_tenant`, `can_create`, `can_edit_all`, `can_edit_own`, `can_delete_all`, `can_delete_own`)
SELECT p.id, 2, 'vehicles', 1,1,1,1,1,1,1,1 FROM permissions p WHERE p.key_name='manage_vehicles';
INSERT INTO `resource_permissions` (`permission_id`, `role_id`, `resource_type`, `can_view_all`, `can_view_own`, `can_view_tenant`, `can_create`, `can_edit_all`, `can_edit_own`, `can_delete_all`, `can_delete_own`)
SELECT p.id, 2, 'movements', 1,1,1,1,1,1,1,1 FROM permissions p WHERE p.key_name='manage_movements';
INSERT INTO `resource_permissions` (`permission_id`, `role_id`, `resource_type`, `can_view_all`, `can_view_own`, `can_view_tenant`, `can_create`, `can_edit_all`, `can_edit_own`, `can_delete_all`, `can_delete_own`)
SELECT p.id, 2, 'violations', 1,1,1,1,1,1,1,1 FROM permissions p WHERE p.key_name='manage_violations';
INSERT INTO `resource_permissions` (`permission_id`, `role_id`, `resource_type`, `can_view_all`, `can_view_own`, `can_view_tenant`, `can_create`, `can_edit_all`, `can_edit_own`, `can_delete_all`, `can_delete_own`)
SELECT p.id, 2, 'maintenance', 1,1,1,1,1,1,1,1 FROM permissions p WHERE p.key_name='manage_maintenance';

-- Manager (role 3): view all + create + edit, no delete
INSERT INTO `resource_permissions` (`permission_id`, `role_id`, `resource_type`, `can_view_all`, `can_view_own`, `can_view_tenant`, `can_create`, `can_edit_all`, `can_edit_own`, `can_delete_all`, `can_delete_own`)
SELECT p.id, 3, 'users', 1,1,1,1,1,1,0,0 FROM permissions p WHERE p.key_name='manage_users';
INSERT INTO `resource_permissions` (`permission_id`, `role_id`, `resource_type`, `can_view_all`, `can_view_own`, `can_view_tenant`, `can_create`, `can_edit_all`, `can_edit_own`, `can_delete_all`, `can_delete_own`)
SELECT p.id, 3, 'vehicles', 1,1,1,1,1,1,0,0 FROM permissions p WHERE p.key_name='manage_vehicles';
INSERT INTO `resource_permissions` (`permission_id`, `role_id`, `resource_type`, `can_view_all`, `can_view_own`, `can_view_tenant`, `can_create`, `can_edit_all`, `can_edit_own`, `can_delete_all`, `can_delete_own`)
SELECT p.id, 3, 'movements', 1,1,1,1,1,1,0,0 FROM permissions p WHERE p.key_name='manage_movements';
INSERT INTO `resource_permissions` (`permission_id`, `role_id`, `resource_type`, `can_view_all`, `can_view_own`, `can_view_tenant`, `can_create`, `can_edit_all`, `can_edit_own`, `can_delete_all`, `can_delete_own`)
SELECT p.id, 3, 'violations', 1,1,1,1,1,1,0,0 FROM permissions p WHERE p.key_name='manage_violations';
INSERT INTO `resource_permissions` (`permission_id`, `role_id`, `resource_type`, `can_view_all`, `can_view_own`, `can_view_tenant`, `can_create`, `can_edit_all`, `can_edit_own`, `can_delete_all`, `can_delete_own`)
SELECT p.id, 3, 'maintenance', 1,1,1,1,1,1,0,0 FROM permissions p WHERE p.key_name='manage_maintenance';

-- Employee (role 4): view own + create movements
INSERT INTO `resource_permissions` (`permission_id`, `role_id`, `resource_type`, `can_view_all`, `can_view_own`, `can_view_tenant`, `can_create`, `can_edit_all`, `can_edit_own`, `can_delete_all`, `can_delete_own`)
SELECT p.id, 4, 'vehicles', 0,1,1,0,0,0,0,0 FROM permissions p WHERE p.key_name='manage_vehicles';
INSERT INTO `resource_permissions` (`permission_id`, `role_id`, `resource_type`, `can_view_all`, `can_view_own`, `can_view_tenant`, `can_create`, `can_edit_all`, `can_edit_own`, `can_delete_all`, `can_delete_own`)
SELECT p.id, 4, 'movements', 0,1,1,1,0,0,0,0 FROM permissions p WHERE p.key_name='manage_movements';

-- Viewer (role 5): view only
INSERT INTO `resource_permissions` (`permission_id`, `role_id`, `resource_type`, `can_view_all`, `can_view_own`, `can_view_tenant`, `can_create`, `can_edit_all`, `can_edit_own`, `can_delete_all`, `can_delete_own`)
SELECT p.id, 5, 'vehicles', 1,1,1,0,0,0,0,0 FROM permissions p WHERE p.key_name='manage_vehicles';
INSERT INTO `resource_permissions` (`permission_id`, `role_id`, `resource_type`, `can_view_all`, `can_view_own`, `can_view_tenant`, `can_create`, `can_edit_all`, `can_edit_own`, `can_delete_all`, `can_delete_own`)
SELECT p.id, 5, 'movements', 1,1,1,0,0,0,0,0 FROM permissions p WHERE p.key_name='manage_movements';

-- ================================================================
-- Migration 001: Permission System Tables
-- ================================================================
-- This migration creates/ensures the permission system tables exist
-- with the correct structure based on the new schema design.
--
-- Tables: roles, permissions, role_permissions, resource_permissions
--
-- Run this migration on the database to set up the new permission system.
-- The new roles table uses a clean structure (id, key_name, display_name)
-- instead of embedding permission flags directly in the roles table.
-- ================================================================

-- -------------------------------------------
-- 1. Roles Table (clean structure)
-- -------------------------------------------
-- Note: If the old roles table exists with permission columns (can_*),
-- this does not drop it. The new MVC code reads from the new normalized tables.
-- Ensure the roles table at minimum has: id, key_name, display_name, created_at

CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `key_name` VARCHAR(100) NOT NULL,
    `display_name` VARCHAR(150) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default roles if empty
INSERT IGNORE INTO `roles` (`id`, `key_name`, `display_name`) VALUES
    (1, 'superadmin', 'مدير النظام / Super Admin'),
    (2, 'admin', 'مدير / Admin'),
    (3, 'manager', 'مشرف / Manager'),
    (4, 'employee', 'موظف / Employee'),
    (5, 'viewer', 'مشاهد / Viewer');

-- -------------------------------------------
-- 2. Permissions Table
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

-- Insert default permissions for each module
INSERT IGNORE INTO `permissions` (`key_name`, `display_name`, `description`, `module`) VALUES
    -- User Management Module
    ('users_read',    'عرض المستخدمين / View Users',       'View user list and profiles',    'users'),
    ('users_create',  'إضافة مستخدم / Create User',        'Create new users',               'users'),
    ('users_edit',    'تعديل مستخدم / Edit User',          'Edit user information',          'users'),
    ('users_delete',  'حذف مستخدم / Delete User',          'Delete users',                   'users'),
    ('users_activate','تفعيل مستخدم / Activate User',      'Activate/deactivate users',      'users'),

    -- Vehicle Management Module
    ('vehicles_read',   'عرض المركبات / View Vehicles',     'View vehicle list',              'vehicles'),
    ('vehicles_create', 'إضافة مركبة / Create Vehicle',     'Add new vehicles',               'vehicles'),
    ('vehicles_edit',   'تعديل مركبة / Edit Vehicle',       'Edit vehicle information',       'vehicles'),
    ('vehicles_delete', 'حذف مركبة / Delete Vehicle',       'Delete vehicles',                'vehicles'),
    ('vehicles_assign', 'تعيين مركبة / Assign Vehicle',     'Assign vehicles to employees',   'vehicles'),

    -- Vehicle Movements Module
    ('movements_read',   'عرض الحركات / View Movements',    'View vehicle movements',         'movements'),
    ('movements_create', 'إضافة حركة / Create Movement',    'Record vehicle movements',       'movements'),
    ('movements_edit',   'تعديل حركة / Edit Movement',      'Edit movement records',          'movements'),

    -- Vehicle Violations Module
    ('violations_read',   'عرض المخالفات / View Violations',  'View violations',              'violations'),
    ('violations_create', 'إضافة مخالفة / Create Violation',  'Record violations',            'violations'),
    ('violations_edit',   'تعديل مخالفة / Edit Violation',    'Edit violation records',       'violations'),
    ('violations_delete', 'حذف مخالفة / Delete Violation',    'Delete violations',            'violations'),

    -- Maintenance Module
    ('maintenance_read',   'عرض الصيانة / View Maintenance',   'View maintenance records',   'maintenance'),
    ('maintenance_create', 'إضافة صيانة / Create Maintenance', 'Add maintenance records',    'maintenance'),
    ('maintenance_edit',   'تعديل صيانة / Edit Maintenance',   'Edit maintenance records',   'maintenance'),
    ('maintenance_delete', 'حذف صيانة / Delete Maintenance',   'Delete maintenance records', 'maintenance'),

    -- Reports Module
    ('reports_view',   'عرض التقارير / View Reports',       'View reports',                   'reports'),
    ('reports_export', 'تصدير التقارير / Export Reports',   'Export/print reports',           'reports'),

    -- Roles & Permissions Module
    ('roles_manage',       'إدارة الأدوار / Manage Roles',           'Create/edit/delete roles',         'admin'),
    ('permissions_manage', 'إدارة الصلاحيات / Manage Permissions',   'Manage permission assignments',    'admin'),

    -- References Module (Departments/Sections/Divisions)
    ('references_read',   'عرض المراجع / View References',   'View departments/sections/divisions',   'references'),
    ('references_manage', 'إدارة المراجع / Manage References', 'Create/edit/delete references',       'references'),

    -- Settings Module
    ('settings_view',   'عرض الإعدادات / View Settings',    'View system settings',           'settings'),
    ('settings_manage', 'إدارة الإعدادات / Manage Settings', 'Edit system settings',          'settings');

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

-- Assign all permissions to superadmin (role_id=1)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `permissions`;

-- Assign most permissions to admin (role_id=2)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, id FROM `permissions` WHERE `key_name` NOT IN ('roles_manage', 'permissions_manage');

-- Assign read + create permissions to manager (role_id=3)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 3, id FROM `permissions` WHERE `key_name` LIKE '%_read' OR `key_name` LIKE '%_create' OR `key_name` LIKE '%_edit' OR `key_name` IN ('reports_view', 'reports_export', 'vehicles_assign');

-- Assign read + limited create permissions to employee (role_id=4)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 4, id FROM `permissions` WHERE `key_name` LIKE '%_read' OR `key_name` IN ('movements_create', 'reports_view');

-- Assign only read permissions to viewer (role_id=5)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 5, id FROM `permissions` WHERE `key_name` LIKE '%_read' OR `key_name` = 'reports_view';

-- -------------------------------------------
-- 4. Resource-Level Permissions Table
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

-- Default resource permissions for superadmin (full access to all resources)
INSERT IGNORE INTO `resource_permissions` (`permission_id`, `role_id`, `resource_type`, `can_view_all`, `can_view_own`, `can_view_tenant`, `can_create`, `can_edit_all`, `can_edit_own`, `can_delete_all`, `can_delete_own`)
SELECT p.id, 1, 'vehicles', 1, 1, 1, 1, 1, 1, 1, 1 FROM permissions p WHERE p.key_name = 'vehicles_read' LIMIT 1;

INSERT IGNORE INTO `resource_permissions` (`permission_id`, `role_id`, `resource_type`, `can_view_all`, `can_view_own`, `can_view_tenant`, `can_create`, `can_edit_all`, `can_edit_own`, `can_delete_all`, `can_delete_own`)
SELECT p.id, 1, 'users', 1, 1, 1, 1, 1, 1, 1, 1 FROM permissions p WHERE p.key_name = 'users_read' LIMIT 1;

-- Default resource permissions for employee (view own, create)
INSERT IGNORE INTO `resource_permissions` (`permission_id`, `role_id`, `resource_type`, `can_view_all`, `can_view_own`, `can_view_tenant`, `can_create`, `can_edit_all`, `can_edit_own`, `can_delete_all`, `can_delete_own`)
SELECT p.id, 4, 'vehicles', 0, 1, 1, 0, 0, 0, 0, 0 FROM permissions p WHERE p.key_name = 'vehicles_read' LIMIT 1;

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

-- -------------------------------------------
-- Themes table
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `themes` (
    `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `thumbnail_url` VARCHAR(500) DEFAULT NULL,
    `preview_url` VARCHAR(500) DEFAULT NULL,
    `version` VARCHAR(50) DEFAULT '1.0.0',
    `author` VARCHAR(255) DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 0,
    `is_default` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_themes_slug` (`slug`),
    KEY `idx_themes_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------
-- Color settings table
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `color_settings` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `theme_id` BIGINT(20) DEFAULT NULL,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_name` VARCHAR(255) NOT NULL,
    `color_value` VARCHAR(7) NOT NULL,
    `category` ENUM('primary','secondary','accent','background','text','border','status','other') DEFAULT 'other',
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT(11) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_color_theme` (`theme_id`),
    KEY `idx_color_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------
-- Card styles table
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `card_styles` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `theme_id` BIGINT(20) DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `card_type` VARCHAR(50) NOT NULL,
    `background_color` VARCHAR(7) DEFAULT '#FFFFFF',
    `border_color` VARCHAR(7) DEFAULT '#E0E0E0',
    `border_width` INT(11) DEFAULT 1,
    `border_radius` INT(11) DEFAULT 8,
    `shadow_style` VARCHAR(100) DEFAULT 'none',
    `padding` VARCHAR(50) DEFAULT '16px',
    `hover_effect` ENUM('none','lift','zoom','shadow','border','brightness') DEFAULT 'none',
    `text_align` ENUM('left','center','right') DEFAULT 'left',
    `image_aspect_ratio` VARCHAR(50) DEFAULT '1:1',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_card_theme` (`theme_id`),
    KEY `idx_card_type` (`card_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------
-- Font settings table
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `font_settings` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `theme_id` BIGINT(20) DEFAULT NULL,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_name` VARCHAR(255) NOT NULL,
    `font_family` VARCHAR(255) NOT NULL,
    `font_size` VARCHAR(50) DEFAULT NULL,
    `font_weight` VARCHAR(50) DEFAULT NULL,
    `line_height` VARCHAR(50) DEFAULT NULL,
    `category` ENUM('heading','body','button','navigation','other') DEFAULT 'other',
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT(11) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_font_theme` (`theme_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------
-- Button styles table
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `button_styles` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `theme_id` BIGINT(20) DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `button_type` ENUM('primary','secondary','success','danger','warning','info','outline','ghost') NOT NULL,
    `background_color` VARCHAR(7) NOT NULL,
    `text_color` VARCHAR(7) NOT NULL,
    `border_color` VARCHAR(7) DEFAULT NULL,
    `border_width` INT(11) DEFAULT 0,
    `border_radius` INT(11) DEFAULT 4,
    `padding` VARCHAR(50) DEFAULT '10px 20px',
    `font_size` VARCHAR(50) DEFAULT '14px',
    `font_weight` VARCHAR(50) DEFAULT 'normal',
    `hover_background_color` VARCHAR(7) DEFAULT NULL,
    `hover_text_color` VARCHAR(7) DEFAULT NULL,
    `hover_border_color` VARCHAR(7) DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_button_theme` (`theme_id`),
    KEY `idx_button_type` (`button_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------
-- Design settings table
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `design_settings` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `theme_id` BIGINT(20) DEFAULT NULL,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_name` VARCHAR(255) NOT NULL,
    `setting_value` TEXT DEFAULT NULL,
    `setting_type` ENUM('text','number','color','image','boolean','select','json') DEFAULT 'text',
    `category` ENUM('layout','header','footer','sidebar','homepage','other') DEFAULT 'other',
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT(11) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_design_theme` (`theme_id`),
    KEY `idx_design_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------
-- System settings table
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `system_settings` (
    `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(255) NOT NULL,
    `setting_value` TEXT DEFAULT NULL,
    `setting_type` ENUM('text','number','boolean','json','file','email','url','color') DEFAULT 'text',
    `category` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `is_public` TINYINT(1) DEFAULT 0,
    `is_editable` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_settings_key` (`setting_key`),
    KEY `idx_settings_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------
-- Default theme and settings data
-- -------------------------------------------
INSERT IGNORE INTO `themes` (`id`, `name`, `slug`, `description`, `is_active`, `is_default`) VALUES
    (1, 'المظهر الافتراضي', 'default', 'المظهر الرسمي الافتراضي للنظام', 1, 1),
    (2, 'المظهر الداكن', 'dark', 'مظهر داكن مريح للعين', 0, 0);

INSERT IGNORE INTO `color_settings` (`theme_id`, `setting_key`, `setting_name`, `color_value`, `category`, `sort_order`) VALUES
    (1, 'primary_main', 'اللون الرئيسي', '#1a5276', 'primary', 1),
    (1, 'primary_dark', 'اللون الرئيسي الداكن', '#0d3b5e', 'primary', 2),
    (1, 'primary_light', 'اللون الرئيسي الفاتح', '#2980b9', 'primary', 3),
    (1, 'accent_gold', 'اللون الذهبي', '#c69c3f', 'accent', 4),
    (1, 'bg_main', 'خلفية الصفحة', '#f4f6f9', 'background', 5),
    (1, 'bg_card', 'خلفية البطاقات', '#ffffff', 'background', 6),
    (1, 'text_primary', 'النص الرئيسي', '#2c3e50', 'text', 7),
    (1, 'text_secondary', 'النص الثانوي', '#7f8c8d', 'text', 8),
    (1, 'border_default', 'لون الحدود', '#e0e0e0', 'border', 9),
    (1, 'status_success', 'نجاح', '#27ae60', 'status', 10),
    (1, 'status_danger', 'خطر', '#e74c3c', 'status', 11),
    (1, 'status_warning', 'تحذير', '#f39c12', 'status', 12),
    (1, 'status_info', 'معلومات', '#3498db', 'status', 13),
    (2, 'primary_main', 'اللون الرئيسي', '#2196F3', 'primary', 1),
    (2, 'primary_dark', 'اللون الرئيسي الداكن', '#1565C0', 'primary', 2),
    (2, 'primary_light', 'اللون الرئيسي الفاتح', '#64B5F6', 'primary', 3),
    (2, 'accent_gold', 'اللون الذهبي', '#FFB74D', 'accent', 4),
    (2, 'bg_main', 'خلفية الصفحة', '#121212', 'background', 5),
    (2, 'bg_card', 'خلفية البطاقات', '#1e1e1e', 'background', 6),
    (2, 'text_primary', 'النص الرئيسي', '#e0e0e0', 'text', 7),
    (2, 'text_secondary', 'النص الثانوي', '#9e9e9e', 'text', 8),
    (2, 'border_default', 'لون الحدود', '#333333', 'border', 9),
    (2, 'status_success', 'نجاح', '#66BB6A', 'status', 10),
    (2, 'status_danger', 'خطر', '#EF5350', 'status', 11),
    (2, 'status_warning', 'تحذير', '#FFA726', 'status', 12),
    (2, 'status_info', 'معلومات', '#42A5F5', 'status', 13);

INSERT IGNORE INTO `font_settings` (`theme_id`, `setting_key`, `setting_name`, `font_family`, `font_size`, `font_weight`, `line_height`, `category`, `sort_order`) VALUES
    (1, 'heading_font', 'خط العناوين', 'Tajawal', '1.5rem', '700', '1.4', 'heading', 1),
    (1, 'body_font', 'خط النصوص', 'Tajawal', '0.95rem', '400', '1.6', 'body', 2),
    (1, 'button_font', 'خط الأزرار', 'Tajawal', '0.9rem', '600', '1.2', 'button', 3),
    (1, 'nav_font', 'خط القوائم', 'Tajawal', '0.85rem', '500', '1.4', 'navigation', 4);

INSERT IGNORE INTO `button_styles` (`theme_id`, `name`, `slug`, `button_type`, `background_color`, `text_color`, `border_color`, `border_radius`, `padding`, `font_size`, `font_weight`, `hover_background_color`, `hover_text_color`) VALUES
    (1, 'زر أساسي', 'btn-primary', 'primary', '#1a5276', '#ffffff', NULL, 8, '10px 22px', '0.9rem', '600', '#0d3b5e', '#ffffff'),
    (1, 'زر ثانوي', 'btn-secondary', 'secondary', '#e0e0e0', '#2c3e50', '#cccccc', 8, '10px 22px', '0.9rem', '500', '#d0d0d0', '#2c3e50'),
    (1, 'زر نجاح', 'btn-success', 'success', '#27ae60', '#ffffff', NULL, 8, '10px 22px', '0.9rem', '600', '#219a52', '#ffffff'),
    (1, 'زر خطر', 'btn-danger', 'danger', '#e74c3c', '#ffffff', NULL, 8, '10px 22px', '0.9rem', '600', '#c0392b', '#ffffff');

INSERT IGNORE INTO `card_styles` (`theme_id`, `name`, `slug`, `card_type`, `background_color`, `border_color`, `border_radius`, `shadow_style`, `padding`, `hover_effect`) VALUES
    (1, 'بطاقة افتراضية', 'card-default', 'default', '#ffffff', '#e0e0e0', 12, '0 2px 8px rgba(0,0,0,.06)', '16px', 'lift'),
    (1, 'بطاقة إحصائيات', 'card-stat', 'stat', '#ffffff', '#e0e0e0', 12, '0 2px 8px rgba(0,0,0,.06)', '20px', 'shadow');

INSERT IGNORE INTO `design_settings` (`theme_id`, `setting_key`, `setting_name`, `setting_value`, `setting_type`, `category`, `sort_order`) VALUES
    (1, 'sidebar_width', 'عرض الشريط الجانبي', '260px', 'text', 'sidebar', 1),
    (1, 'header_height', 'ارتفاع الرأس', '64px', 'text', 'header', 2),
    (1, 'border_radius', 'زوايا العناصر', '12px', 'text', 'layout', 3),
    (1, 'card_shadow', 'ظل البطاقات', '0 2px 8px rgba(0,0,0,.06)', 'text', 'layout', 4),
    (1, 'transition_speed', 'سرعة الانتقال', '0.3s', 'text', 'layout', 5);

INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `category`, `description`, `is_public`, `is_editable`) VALUES
    ('site_name', 'نظام إدارة المركبات', 'text', 'general', 'اسم النظام', 1, 1),
    ('site_name_en', 'Vehicle Management System', 'text', 'general', 'System name in English', 1, 1),
    ('organization_name', 'بلدية مدينة الشارقة', 'text', 'branding', 'اسم الجهة', 1, 1),
    ('default_language', 'ar', 'text', 'general', 'اللغة الافتراضية (ar/en)', 1, 1),
    ('session_timeout', '3600', 'number', 'security', 'مهلة الجلسة بالثواني', 0, 1),
    ('max_login_attempts', '5', 'number', 'security', 'أقصى عدد محاولات تسجيل الدخول', 0, 1),
    ('enable_notifications', '1', 'boolean', 'system', 'تفعيل الإشعارات', 0, 1),
    ('maintenance_mode', '0', 'boolean', 'system', 'وضع الصيانة', 1, 1),
    ('contact_email', 'info@shjmun.gov.ae', 'email', 'contact', 'البريد الإلكتروني للتواصل', 1, 1),
    ('contact_phone', '+971-6-5614000', 'text', 'contact', 'رقم الهاتف للتواصل', 1, 1);

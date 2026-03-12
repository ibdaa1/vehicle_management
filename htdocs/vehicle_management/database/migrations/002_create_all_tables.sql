-- ================================================================
-- Migration 002: Create all remaining tables from table.md schema
-- ================================================================
-- Tables: system_settings, themes, design_settings, color_settings,
--         font_settings, card_styles, button_styles, activity_logs,
--         Departments, Sections, Divisions, vehicles,
--         vehicle_maintenance, vehicle_violations, vehicle_movement_photos
-- ================================================================

-- -------------------------------------------
-- 1. Organization tables
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `Departments` (
    `department_id` INT(11) NOT NULL AUTO_INCREMENT,
    `name_en` VARCHAR(255) NOT NULL,
    `name_ar` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `Sections` (
    `section_id` INT(11) NOT NULL AUTO_INCREMENT,
    `name_en` VARCHAR(255) NOT NULL,
    `name_ar` VARCHAR(255) NOT NULL,
    `department_id` INT(11) DEFAULT NULL,
    PRIMARY KEY (`section_id`),
    KEY `idx_department` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `Divisions` (
    `division_id` INT(11) NOT NULL AUTO_INCREMENT,
    `name_en` VARCHAR(255) NOT NULL,
    `name_ar` VARCHAR(255) NOT NULL,
    `section_id` INT(11) DEFAULT NULL,
    PRIMARY KEY (`division_id`),
    KEY `idx_section` (`section_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------
-- 2. Vehicles table
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `vehicles` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `vehicle_code` VARCHAR(50) NOT NULL,
    `type` VARCHAR(200) NOT NULL,
    `manufacture_year` INT(11) NOT NULL,
    `emp_id` VARCHAR(50) DEFAULT NULL,
    `driver_name` VARCHAR(150) DEFAULT NULL,
    `driver_phone` VARCHAR(20) DEFAULT NULL,
    `status` ENUM('operational','maintenance','out_of_service') DEFAULT 'operational',
    `department_id` INT(11) DEFAULT NULL,
    `section_id` INT(11) DEFAULT NULL,
    `division_id` INT(11) DEFAULT NULL,
    `vehicle_mode` ENUM('private','shift') DEFAULT 'shift',
    `gender` ENUM('men','women') DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `created_by` INT(11) DEFAULT NULL,
    `updated_by` INT(11) DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_vehicle_code` (`vehicle_code`),
    KEY `idx_department` (`department_id`),
    KEY `idx_section` (`section_id`),
    KEY `idx_division` (`division_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------
-- 3. Vehicle maintenance table
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `vehicle_maintenance` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `vehicle_code` VARCHAR(50) NOT NULL,
    `visit_date` DATE DEFAULT NULL,
    `next_visit_date` DATE DEFAULT NULL,
    `maintenance_type` ENUM('Routine','Emergency','Technical Check','Mechanical','Electrical','Body Work','Other') DEFAULT NULL,
    `location` VARCHAR(255) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_by` VARCHAR(50) DEFAULT NULL,
    `updated_by` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_vehicle_code` (`vehicle_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------
-- 4. Vehicle violations table
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `vehicle_violations` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `vehicle_id` INT(11) NOT NULL,
    `vehicle_code` VARCHAR(50) NOT NULL,
    `violation_datetime` DATETIME NOT NULL,
    `violation_amount` DECIMAL(10,2) NOT NULL,
    `violation_status` ENUM('unpaid','paid') DEFAULT 'unpaid',
    `issued_by_emp_id` VARCHAR(50) NOT NULL,
    `paid_by_emp_id` VARCHAR(50) DEFAULT NULL,
    `payment_datetime` DATETIME DEFAULT NULL,
    `payment_attachment` VARCHAR(255) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_vehicle_id` (`vehicle_id`),
    KEY `idx_issued_by` (`issued_by_emp_id`),
    KEY `idx_paid_by` (`paid_by_emp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------
-- 5. Vehicle movement photos table
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `vehicle_movement_photos` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `movement_id` INT(11) NOT NULL,
    `photo_url` VARCHAR(255) NOT NULL,
    `taken_by` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_movement_id` (`movement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------
-- 6. System settings table
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
    KEY `idx_setting_key` (`setting_key`),
    KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default system settings
INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `category`, `description`, `is_public`) VALUES
    ('site_name_ar', 'بلدية مدينة الشارقة', 'text', 'general', 'Site name in Arabic', 1),
    ('site_name_en', 'Sharjah City Municipality', 'text', 'general', 'Site name in English', 1),
    ('system_title_ar', 'نظام متابعة وإدارة السيارات', 'text', 'general', 'System title in Arabic', 1),
    ('system_title_en', 'Vehicle Management System', 'text', 'general', 'System title in English', 1),
    ('default_language', 'ar', 'text', 'general', 'Default language (ar/en)', 1),
    ('logo_url', '/vehicle_management/assets/images/logo.png', 'file', 'branding', 'Logo URL', 1),
    ('favicon_url', '/vehicle_management/assets/images/favicon.ico', 'file', 'branding', 'Favicon URL', 1),
    ('footer_text_ar', '© 2025 بلدية مدينة الشارقة - جميع الحقوق محفوظة', 'text', 'general', 'Footer text Arabic', 1),
    ('footer_text_en', '© 2025 Sharjah City Municipality - All Rights Reserved', 'text', 'general', 'Footer text English', 1),
    ('session_timeout', '28800', 'number', 'security', 'Session timeout in seconds', 0),
    ('max_login_attempts', '5', 'number', 'security', 'Maximum login attempts', 0),
    ('password_min_length', '8', 'number', 'security', 'Minimum password length', 0);

-- -------------------------------------------
-- 7. Themes table
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
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default theme
INSERT IGNORE INTO `themes` (`id`, `name`, `slug`, `description`, `version`, `author`, `is_active`, `is_default`) VALUES
    (1, 'Municipality Green', 'municipality-green', 'Default Sharjah Municipality theme with green and gold colors', '1.0.0', 'System', 1, 1);

-- -------------------------------------------
-- 8. Design settings table
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `design_settings` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `theme_id` BIGINT(20) DEFAULT NULL,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_name` VARCHAR(255) NOT NULL,
    `setting_value` TEXT DEFAULT NULL,
    `setting_type` ENUM('text','number','color','image','boolean','select','json') DEFAULT 'text',
    `category` ENUM('layout','header','footer','sidebar','homepage','cards','navigation','other') DEFAULT 'other',
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT(11) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_theme` (`theme_id`),
    KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default design settings
INSERT IGNORE INTO `design_settings` (`theme_id`, `setting_key`, `setting_name`, `setting_value`, `setting_type`, `category`) VALUES
    (1, 'header_height', 'Header Height', '64px', 'text', 'header'),
    (1, 'header_position', 'Header Position', 'fixed', 'select', 'header'),
    (1, 'sidebar_width', 'Sidebar Width', '260px', 'text', 'sidebar'),
    (1, 'sidebar_collapsed_width', 'Sidebar Collapsed Width', '64px', 'text', 'sidebar'),
    (1, 'footer_height', 'Footer Height', '48px', 'text', 'footer'),
    (1, 'card_border_radius', 'Card Border Radius', '12px', 'text', 'cards'),
    (1, 'card_shadow', 'Card Shadow', '0 2px 8px rgba(0,0,0,0.08)', 'text', 'cards'),
    (1, 'layout_max_width', 'Layout Max Width', '1400px', 'text', 'layout'),
    (1, 'layout_padding', 'Layout Padding', '24px', 'text', 'layout');

-- -------------------------------------------
-- 9. Color settings table
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
    KEY `idx_theme` (`theme_id`),
    KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default color settings (Municipality Green theme)
INSERT IGNORE INTO `color_settings` (`theme_id`, `setting_key`, `setting_name`, `color_value`, `category`) VALUES
    (1, 'primary_dark', 'Primary Dark', '#2c3e2c', 'primary'),
    (1, 'primary_main', 'Primary Main', '#3a513a', 'primary'),
    (1, 'primary_light', 'Primary Light', '#4a6b4a', 'primary'),
    (1, 'accent_gold', 'Accent Gold', '#d4af37', 'accent'),
    (1, 'accent_beige', 'Accent Beige', '#b8a174', 'accent'),
    (1, 'bg_main', 'Background Main', '#f5f6f8', 'background'),
    (1, 'bg_card', 'Card Background', '#ffffff', 'background'),
    (1, 'bg_sidebar', 'Sidebar Background', '#1a2e1a', 'background'),
    (1, 'text_primary', 'Text Primary', '#1a1a2e', 'text'),
    (1, 'text_secondary', 'Text Secondary', '#6b7280', 'text'),
    (1, 'text_light', 'Text Light', '#ffffff', 'text'),
    (1, 'border_default', 'Border Default', '#e5e7eb', 'border'),
    (1, 'status_success', 'Status Success', '#28a745', 'status'),
    (1, 'status_warning', 'Status Warning', '#ffc107', 'status'),
    (1, 'status_danger', 'Status Danger', '#dc3545', 'status'),
    (1, 'status_info', 'Status Info', '#17a2b8', 'status');

-- -------------------------------------------
-- 10. Font settings table
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
    KEY `idx_theme` (`theme_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default font settings
INSERT IGNORE INTO `font_settings` (`theme_id`, `setting_key`, `setting_name`, `font_family`, `font_size`, `font_weight`, `line_height`, `category`) VALUES
    (1, 'font_ar', 'Arabic Font', 'Noto Sans Arabic, Tahoma, sans-serif', '14px', '400', '1.6', 'body'),
    (1, 'font_en', 'English Font', 'Inter, Segoe UI, sans-serif', '14px', '400', '1.6', 'body'),
    (1, 'font_heading', 'Heading Font', 'Noto Sans Arabic, Inter, sans-serif', '18px', '700', '1.4', 'heading'),
    (1, 'font_button', 'Button Font', 'inherit', '14px', '600', '1', 'button'),
    (1, 'font_nav', 'Navigation Font', 'inherit', '13px', '500', '1.4', 'navigation');

-- -------------------------------------------
-- 11. Card styles table
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
    KEY `idx_theme` (`theme_id`),
    KEY `idx_card_type` (`card_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default card styles
INSERT IGNORE INTO `card_styles` (`theme_id`, `name`, `slug`, `card_type`, `background_color`, `border_color`, `border_radius`, `shadow_style`, `padding`, `hover_effect`) VALUES
    (1, 'Vehicle Card', 'vehicle-card', 'vehicle', '#FFFFFF', '#e5e7eb', 12, '0 2px 8px rgba(0,0,0,0.06)', '16px', 'lift'),
    (1, 'Dashboard Stat', 'dashboard-stat', 'stat', '#FFFFFF', '#e5e7eb', 12, '0 2px 8px rgba(0,0,0,0.06)', '20px', 'shadow'),
    (1, 'Menu Item', 'menu-item', 'menu', 'transparent', 'transparent', 8, 'none', '12px 16px', 'brightness'),
    (1, 'Info Card', 'info-card', 'info', '#f8faf8', '#d4e8d4', 10, 'none', '16px', 'border');

-- -------------------------------------------
-- 12. Button styles table
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `button_styles` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `theme_id` BIGINT(20) DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `button_type` ENUM('primary','secondary','success','danger','warning','info','outline','ghost','link') NOT NULL,
    `background_color` VARCHAR(20) DEFAULT '#2c3e2c',
    `text_color` VARCHAR(7) DEFAULT '#ffffff',
    `border_color` VARCHAR(20) DEFAULT 'transparent',
    `border_width` INT(11) DEFAULT 0,
    `border_radius` INT(11) DEFAULT 8,
    `padding` VARCHAR(50) DEFAULT '10px 20px',
    `font_size` VARCHAR(50) DEFAULT '14px',
    `font_weight` VARCHAR(50) DEFAULT '600',
    `hover_background_color` VARCHAR(7) DEFAULT '#3a513a',
    `hover_text_color` VARCHAR(7) DEFAULT '#ffffff',
    `hover_border_color` VARCHAR(20) DEFAULT 'transparent',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_theme` (`theme_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default button styles
INSERT IGNORE INTO `button_styles` (`theme_id`, `name`, `slug`, `button_type`, `background_color`, `text_color`, `hover_background_color`) VALUES
    (1, 'Primary Button', 'btn-primary', 'primary', '#2c3e2c', '#ffffff', '#3a513a'),
    (1, 'Secondary Button', 'btn-secondary', 'secondary', '#6b7280', '#ffffff', '#4b5563'),
    (1, 'Success Button', 'btn-success', 'success', '#28a745', '#ffffff', '#218838'),
    (1, 'Danger Button', 'btn-danger', 'danger', '#dc3545', '#ffffff', '#c82333'),
    (1, 'Warning Button', 'btn-warning', 'warning', '#ffc107', '#1a1a2e', '#e0a800'),
    (1, 'Gold Button', 'btn-gold', 'info', '#d4af37', '#1a1a2e', '#b8962e'),
    (1, 'Outline Button', 'btn-outline', 'outline', 'transparent', '#2c3e2c', '#f0f4f0'),
    (1, 'Ghost Button', 'btn-ghost', 'ghost', 'transparent', '#6b7280', '#f3f4f6');

-- -------------------------------------------
-- 13. Activity logs table
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) DEFAULT NULL,
    `emp_id` VARCHAR(50) DEFAULT NULL,
    `activity_type` VARCHAR(50) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `table_name` VARCHAR(50) DEFAULT NULL,
    `record_id` INT(11) DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_emp_id` (`emp_id`),
    KEY `idx_activity_type` (`activity_type`),
    KEY `idx_table_name` (`table_name`),
    KEY `idx_record_id` (`record_id`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------
-- Sample departments/sections/divisions
-- -------------------------------------------
INSERT IGNORE INTO `Departments` (`department_id`, `name_en`, `name_ar`) VALUES
    (1, 'Transportation Department', 'إدارة النقل'),
    (2, 'Maintenance Department', 'إدارة الصيانة'),
    (3, 'Administrative Department', 'الإدارة العامة');

INSERT IGNORE INTO `Sections` (`section_id`, `name_en`, `name_ar`, `department_id`) VALUES
    (1, 'Fleet Management', 'إدارة الأسطول', 1),
    (2, 'Logistics', 'اللوجستيات', 1),
    (3, 'Mechanical Workshop', 'الورشة الميكانيكية', 2),
    (4, 'HR Section', 'قسم الموارد البشرية', 3);

INSERT IGNORE INTO `Divisions` (`division_id`, `name_en`, `name_ar`, `section_id`) VALUES
    (1, 'Light Vehicles', 'المركبات الخفيفة', 1),
    (2, 'Heavy Vehicles', 'المركبات الثقيلة', 1),
    (3, 'Dispatch Division', 'شعبة الإرسال', 2);

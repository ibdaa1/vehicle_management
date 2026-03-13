-- ================================================================
-- Migration 003: Dark theme + vehicle handovers table
-- ================================================================

-- -------------------------------------------
-- 1. Dark Mode theme
-- -------------------------------------------
INSERT IGNORE INTO `themes` (`id`, `name`, `slug`, `description`, `version`, `author`, `is_active`, `is_default`) VALUES
    (2, 'Dark Mode', 'dark-mode', 'Dark theme for comfortable night viewing', '1.0.0', 'System', 0, 0);

-- Dark color settings
INSERT IGNORE INTO `color_settings` (`theme_id`, `setting_key`, `setting_name`, `color_value`, `category`) VALUES
    (2, 'primary_dark', 'Primary Dark', '#1a472a', 'primary'),
    (2, 'primary_main', 'Primary Main', '#2d6a4f', 'primary'),
    (2, 'primary_light', 'Primary Light', '#40916c', 'primary'),
    (2, 'accent_gold', 'Accent Gold', '#f0c040', 'accent'),
    (2, 'accent_beige', 'Accent Beige', '#c9a65a', 'accent'),
    (2, 'bg_main', 'Background Main', '#121212', 'background'),
    (2, 'bg_card', 'Card Background', '#1e1e1e', 'background'),
    (2, 'bg_sidebar', 'Sidebar Background', '#0a1f0a', 'background'),
    (2, 'text_primary', 'Text Primary', '#e0e0e0', 'text'),
    (2, 'text_secondary', 'Text Secondary', '#9ca3af', 'text'),
    (2, 'text_light', 'Text Light', '#ffffff', 'text'),
    (2, 'border_default', 'Border Default', '#333333', 'border'),
    (2, 'status_success', 'Status Success', '#28a745', 'status'),
    (2, 'status_warning', 'Status Warning', '#ffc107', 'status'),
    (2, 'status_danger', 'Status Danger', '#dc3545', 'status'),
    (2, 'status_info', 'Status Info', '#17a2b8', 'status');

-- Dark font settings (same fonts as light theme)
INSERT IGNORE INTO `font_settings` (`theme_id`, `setting_key`, `setting_name`, `font_family`, `font_size`, `font_weight`, `line_height`, `category`) VALUES
    (2, 'font_ar', 'Arabic Font', 'Noto Sans Arabic, Tahoma, sans-serif', '14px', '400', '1.6', 'body'),
    (2, 'font_en', 'English Font', 'Inter, Segoe UI, sans-serif', '14px', '400', '1.6', 'body'),
    (2, 'font_heading', 'Heading Font', 'Noto Sans Arabic, Inter, sans-serif', '18px', '700', '1.4', 'heading'),
    (2, 'font_button', 'Button Font', 'inherit', '14px', '600', '1', 'button'),
    (2, 'font_nav', 'Navigation Font', 'inherit', '13px', '500', '1.4', 'navigation');

-- Dark button styles
INSERT IGNORE INTO `button_styles` (`theme_id`, `name`, `slug`, `button_type`, `background_color`, `text_color`, `hover_background_color`) VALUES
    (2, 'Primary Button', 'btn-primary', 'primary', '#2d6a4f', '#ffffff', '#40916c'),
    (2, 'Secondary Button', 'btn-secondary', 'secondary', '#4b5563', '#e0e0e0', '#6b7280'),
    (2, 'Success Button', 'btn-success', 'success', '#28a745', '#ffffff', '#218838'),
    (2, 'Danger Button', 'btn-danger', 'danger', '#dc3545', '#ffffff', '#c82333'),
    (2, 'Warning Button', 'btn-warning', 'warning', '#ffc107', '#1a1a2e', '#e0a800'),
    (2, 'Gold Button', 'btn-gold', 'info', '#f0c040', '#1a1a2e', '#d4af37'),
    (2, 'Outline Button', 'btn-outline', 'outline', 'transparent', '#e0e0e0', '#2d6a4f'),
    (2, 'Ghost Button', 'btn-ghost', 'ghost', 'transparent', '#9ca3af', '#1e1e1e');

-- Dark card styles
INSERT IGNORE INTO `card_styles` (`theme_id`, `name`, `slug`, `card_type`, `background_color`, `border_color`, `border_radius`, `shadow_style`, `padding`, `hover_effect`) VALUES
    (2, 'Vehicle Card', 'vehicle-card', 'vehicle', '#1e1e1e', '#333333', 12, '0 2px 8px rgba(0,0,0,0.3)', '16px', 'lift'),
    (2, 'Dashboard Stat', 'dashboard-stat', 'stat', '#1e1e1e', '#333333', 12, '0 2px 8px rgba(0,0,0,0.3)', '20px', 'shadow'),
    (2, 'Menu Item', 'menu-item', 'menu', 'transparent', 'transparent', 8, 'none', '12px 16px', 'brightness'),
    (2, 'Info Card', 'info-card', 'info', '#252525', '#2d6a4f', 10, 'none', '16px', 'border');

-- Dark design settings (same layout values as light theme)
INSERT IGNORE INTO `design_settings` (`theme_id`, `setting_key`, `setting_name`, `setting_value`, `setting_type`, `category`) VALUES
    (2, 'header_height', 'Header Height', '64px', 'text', 'header'),
    (2, 'header_position', 'Header Position', 'fixed', 'select', 'header'),
    (2, 'sidebar_width', 'Sidebar Width', '260px', 'text', 'sidebar'),
    (2, 'sidebar_collapsed_width', 'Sidebar Collapsed Width', '64px', 'text', 'sidebar'),
    (2, 'footer_height', 'Footer Height', '48px', 'text', 'footer'),
    (2, 'card_border_radius', 'Card Border Radius', '12px', 'text', 'cards'),
    (2, 'card_shadow', 'Card Shadow', '0 2px 8px rgba(0,0,0,0.3)', 'text', 'cards'),
    (2, 'layout_max_width', 'Layout Max Width', '1400px', 'text', 'layout'),
    (2, 'layout_padding', 'Layout Padding', '24px', 'text', 'layout');

-- -------------------------------------------
-- 2. Vehicle handovers table (DEPRECATED: replaced by vehicle_movements in migration 004)
-- -------------------------------------------
-- NOTE: This table is no longer used. Use vehicle_movements instead.
CREATE TABLE IF NOT EXISTS `vehicle_handovers` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `vehicle_id` INT(11) NOT NULL,
    `handover_type` ENUM('deliver','receive') NOT NULL,
    `from_emp_id` VARCHAR(50) DEFAULT NULL,
    `from_emp_name` VARCHAR(150) DEFAULT NULL,
    `to_emp_id` VARCHAR(50) DEFAULT NULL,
    `to_emp_name` VARCHAR(150) DEFAULT NULL,
    `handover_date` DATETIME NOT NULL,
    `odometer_reading` INT(11) DEFAULT NULL,
    `fuel_level` VARCHAR(20) DEFAULT NULL,
    `vehicle_condition` TEXT DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_vehicle` (`vehicle_id`),
    KEY `idx_handover_date` (`handover_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

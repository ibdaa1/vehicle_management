-- ================================================================
-- Migration 009: Header, Footer & Sidebar Color Settings
-- ================================================================
-- Adds dedicated color settings for header, footer, and sidebar
-- so they can be independently customized via the theme system.
-- Only super admin can manage themes (enforced by migration 008).
-- ================================================================

-- -------------------------------------------
-- 1. Add header/footer/sidebar colors for Municipality Green theme (theme_id=1)
-- -------------------------------------------
INSERT IGNORE INTO `color_settings`
    (`theme_id`, `setting_key`, `setting_name`, `color_value`, `category`, `sort_order`)
VALUES
    (1, 'header_bg',        'Header Background',            '#2c3e2c', 'other', 20),
    (1, 'header_text',      'Header Text',                  '#ffffff', 'other', 21),
    (1, 'footer_bg',        'Footer Background',            '#ffffff', 'other', 22),
    (1, 'footer_text',      'Footer Text',                  '#6b7280', 'other', 23),
    (1, 'sidebar_text',     'Sidebar Text',                 '#ffffff', 'other', 24),
    (1, 'sidebar_active_bg','Sidebar Active Item Background','#3a513a', 'other', 25);

-- -------------------------------------------
-- 2. Add header/footer/sidebar colors for Dark Mode theme (theme_id=2)
-- -------------------------------------------
INSERT IGNORE INTO `color_settings`
    (`theme_id`, `setting_key`, `setting_name`, `color_value`, `category`, `sort_order`)
VALUES
    (2, 'header_bg',        'Header Background',            '#1a1a2e', 'other', 20),
    (2, 'header_text',      'Header Text',                  '#e0e0e0', 'other', 21),
    (2, 'footer_bg',        'Footer Background',            '#16213e', 'other', 22),
    (2, 'footer_text',      'Footer Text',                  '#a0a0a0', 'other', 23),
    (2, 'sidebar_text',     'Sidebar Text',                 '#e0e0e0', 'other', 24),
    (2, 'sidebar_active_bg','Sidebar Active Item Background','#0f3460', 'other', 25);

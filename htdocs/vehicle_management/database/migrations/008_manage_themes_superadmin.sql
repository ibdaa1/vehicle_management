-- ================================================================
-- Migration 008: Super Admin-Only Theme Management
-- ================================================================
-- Adds manage_themes permission and assigns it exclusively to
-- the Super Admin role (role_id=1).
-- Adds audit columns (created_by, updated_by) to themes table.
-- ================================================================

-- -------------------------------------------
-- 1. Add manage_themes permission
-- -------------------------------------------
INSERT INTO `permissions` (`key_name`, `display_name`, `description`, `module`) VALUES
    ('manage_themes', 'manage_themes', 'Create, edit, and delete themes — Super Admin only', 'admin')
ON DUPLICATE KEY UPDATE `display_name`=VALUES(`display_name`), `description`=VALUES(`description`);

-- -------------------------------------------
-- 2. Assign manage_themes to Super Admin (role_id=1) only
-- -------------------------------------------
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `permissions` WHERE `key_name` = 'manage_themes'
ON DUPLICATE KEY UPDATE `role_id` = 1;

-- -------------------------------------------
-- 3. Add audit columns to themes table
-- -------------------------------------------
ALTER TABLE `themes`
    ADD COLUMN IF NOT EXISTS `created_by` INT(11) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `updated_by` INT(11) DEFAULT NULL;

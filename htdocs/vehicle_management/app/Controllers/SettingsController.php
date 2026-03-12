<?php
/**
 * Settings Controller – serves system settings and theme data for the frontend.
 */

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\SystemSetting;
use App\Models\Theme;

class SettingsController extends BaseController
{
    private SystemSetting $settingsModel;
    private Theme $themeModel;

    public function __construct()
    {
        $this->settingsModel = new SystemSetting();
        $this->themeModel = new Theme();
    }

    /**
     * GET /api/v1/settings/public
     * Returns all public settings – no auth required (for login page, etc.).
     */
    public function publicSettings(Request $request, array $params = []): void
    {
        try {
            $settings = $this->settingsModel->getPublicSettings();
        } catch (\Throwable $e) {
            error_log("SettingsController::publicSettings error: " . $e->getMessage());
            $settings = [];
        }
        Response::json([
            'success' => true,
            'data' => $settings,
        ]);
        return;
    }

    /**
     * GET /api/v1/settings/theme
     * Returns active theme with all colors, fonts, buttons, cards – no auth required.
     */
    public function theme(Request $request, array $params = []): void
    {
        try {
            $theme = $this->themeModel->getActiveTheme();
        } catch (\Throwable $e) {
            error_log("SettingsController::theme error: " . $e->getMessage());
            $theme = null;
        }

        if (!$theme) {
            Response::json([
                'success' => true,
                'data' => null,
                'message' => 'No active theme',
            ]);
            return;
        }

        Response::json([
            'success' => true,
            'data' => $theme,
        ]);
        return;
    }

    /**
     * GET /api/v1/settings
     * Returns all settings – requires admin.
     */
    public function index(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);
        if (Response::isSent()) return;

        try {
            $settings = $this->settingsModel->all();
        } catch (\Throwable $e) {
            error_log("SettingsController::index error: " . $e->getMessage());
            Response::error('Failed to load settings: ' . $e->getMessage(), 500);
            return;
        }
        Response::json([
            'success' => true,
            'data' => $settings,
        ]);
        return;
    }

    /**
     * GET /api/v1/settings/themes
     * Returns all available themes – no auth required.
     */
    public function themes(Request $request, array $params = []): void
    {
        try {
            $themes = $this->themeModel->where([]);
            $data = array_map(fn($t) => [
                'id'          => (int)$t['id'],
                'name'        => $t['name'],
                'slug'        => $t['slug'],
                'description' => $t['description'],
                'is_active'   => (int)$t['is_active'],
            ], $themes);
        } catch (\Throwable $e) {
            error_log("SettingsController::themes error: " . $e->getMessage());
            $data = [];
        }

        Response::json([
            'success' => true,
            'data' => $data,
        ]);
        return;
    }

    /**
     * PUT /api/v1/settings/theme/{slug}
     * Activate a theme by slug – requires admin.
     */
    public function switchTheme(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);
        if (Response::isSent()) return;

        $slug = $params['slug'] ?? '';
        if (empty($slug)) {
            Response::error('Theme slug is required', 400);
            return;
        }

        try {
            $db = \App\Core\Database::getInstance();

            // Find the theme by slug
            $theme = $db->fetchOne(
                "SELECT id FROM themes WHERE slug = ? LIMIT 1",
                's', [$slug]
            );

            if (!$theme) {
                Response::error('Theme not found', 404);
                return;
            }

            // Deactivate all themes
            $db->execute("UPDATE themes SET is_active = 0");

            // Activate the requested theme
            $db->execute(
                "UPDATE themes SET is_active = 1 WHERE id = ?",
                'i', [(int)$theme['id']]
            );

            Response::success(null, 'Theme switched successfully');
        } catch (\Throwable $e) {
            error_log("SettingsController::switchTheme error: " . $e->getMessage());
            Response::error('Failed to switch theme: ' . $e->getMessage(), 500);
        }
        return;
    }

    /**
     * PUT /api/v1/settings/{key}
     * Update a setting value – requires admin.
     */
    public function update(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);
        if (Response::isSent()) return;

        $key = $params['key'] ?? '';
        $value = $request->input('value', '');

        if (empty($key)) {
            Response::error('Setting key is required', 400);
            return;
        }

        try {
            $success = $this->settingsModel->setValue($key, $value);
        } catch (\Throwable $e) {
            error_log("SettingsController::update error: " . $e->getMessage());
            Response::error('Failed to update setting: ' . $e->getMessage(), 500);
            return;
        }

        if (!$success) {
            Response::error('Setting not found or not updatable', 404);
            return;
        }

        Response::success(null, 'Setting updated successfully');
        return;
    }

    /**
     * GET /api/v1/settings/themes/{id}
     * Returns a specific theme with all its style data – requires admin.
     */
    public function themeDetail(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);
        if (Response::isSent()) return;

        $id = (int)($params['id'] ?? 0);
        if (!$id) {
            Response::error('Theme ID is required', 400);
            return;
        }

        try {
            $db = \App\Core\Database::getInstance();
            $theme = $db->fetchOne("SELECT * FROM themes WHERE id = ? LIMIT 1", 'i', [$id]);
            if (!$theme) {
                Response::error('Theme not found', 404);
                return;
            }

            $theme['colors'] = $db->fetchAll(
                "SELECT id, setting_key, setting_name, color_value, category FROM color_settings WHERE theme_id = ? ORDER BY sort_order",
                'i', [$id]
            );
            $theme['fonts'] = $db->fetchAll(
                "SELECT id, setting_key, setting_name, font_family, font_size, font_weight, line_height, category FROM font_settings WHERE theme_id = ? ORDER BY sort_order",
                'i', [$id]
            );
            $theme['buttons'] = $db->fetchAll(
                "SELECT id, slug, name, button_type, background_color, text_color, border_color, border_radius, padding, font_size, font_weight, hover_background_color, hover_text_color FROM button_styles WHERE theme_id = ?",
                'i', [$id]
            );
            $theme['cards'] = $db->fetchAll(
                "SELECT id, slug, name, card_type, background_color, border_color, border_width, border_radius, shadow_style, padding, hover_effect, text_align FROM card_styles WHERE theme_id = ?",
                'i', [$id]
            );
            $theme['design'] = $db->fetchAll(
                "SELECT id, setting_key, setting_name, setting_value, setting_type, category FROM design_settings WHERE theme_id = ? ORDER BY sort_order",
                'i', [$id]
            );

            Response::json(['success' => true, 'data' => $theme]);
        } catch (\Throwable $e) {
            error_log("SettingsController::themeDetail error: " . $e->getMessage());
            Response::error('Failed to load theme: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/v1/settings/themes/{id}/colors
     * Update color settings for a specific theme – requires admin.
     * Expects JSON body: { colors: [{id, color_value}, ...] }
     */
    public function updateColors(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);
        if (Response::isSent()) return;

        $themeId = (int)($params['id'] ?? 0);
        if (!$themeId) {
            Response::error('Theme ID is required', 400);
            return;
        }

        $colors = $request->input('colors', []);
        if (!is_array($colors) || empty($colors)) {
            Response::error('Colors array is required', 400);
            return;
        }

        try {
            $db = \App\Core\Database::getInstance();
            $updated = 0;
            foreach ($colors as $c) {
                $colorId = (int)($c['id'] ?? 0);
                $colorValue = $c['color_value'] ?? '';
                if ($colorId && preg_match('/^#[0-9A-Fa-f]{6}$/', $colorValue)) {
                    $db->execute(
                        "UPDATE color_settings SET color_value = ? WHERE id = ? AND theme_id = ?",
                        'sii', [$colorValue, $colorId, $themeId]
                    );
                    $updated++;
                }
            }

            Response::success(['updated' => $updated], "Updated {$updated} color(s)");
        } catch (\Throwable $e) {
            error_log("SettingsController::updateColors error: " . $e->getMessage());
            Response::error('Failed to update colors: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/v1/settings/themes/{id}/design
     * Update design settings for a specific theme – requires admin.
     * Expects JSON body: { settings: [{id, setting_value}, ...] }
     */
    public function updateDesign(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);
        if (Response::isSent()) return;

        $themeId = (int)($params['id'] ?? 0);
        if (!$themeId) {
            Response::error('Theme ID is required', 400);
            return;
        }

        $settings = $request->input('settings', []);
        if (!is_array($settings) || empty($settings)) {
            Response::error('Settings array is required', 400);
            return;
        }

        try {
            $db = \App\Core\Database::getInstance();
            $updated = 0;
            foreach ($settings as $s) {
                $settingId = (int)($s['id'] ?? 0);
                $settingValue = $s['setting_value'] ?? '';
                if ($settingId && is_string($settingValue)) {
                    $db->execute(
                        "UPDATE design_settings SET setting_value = ? WHERE id = ? AND theme_id = ?",
                        'sii', [$settingValue, $settingId, $themeId]
                    );
                    $updated++;
                }
            }

            Response::success(['updated' => $updated], "Updated {$updated} design setting(s)");
        } catch (\Throwable $e) {
            error_log("SettingsController::updateDesign error: " . $e->getMessage());
            Response::error('Failed to update design settings: ' . $e->getMessage(), 500);
        }
    }

    // =========================================================================
    // Theme CRUD
    // =========================================================================

    /**
     * POST /api/v1/settings/themes
     * Create a new theme – requires admin.
     */
    public function storeTheme(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);
        if (Response::isSent()) return;

        $name = trim($request->input('name', ''));
        if (empty($name)) {
            Response::error('Theme name is required', 400);
            return;
        }

        $slug = trim($request->input('slug', ''));
        if (empty($slug)) {
            $slug = strtolower(str_replace(' ', '-', $name));
        }

        try {
            $db = \App\Core\Database::getInstance();

            $result = $db->execute(
                "INSERT INTO themes (name, slug, description, thumbnail_url, preview_url, version, author, is_active, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                'sssssssii',
                [
                    $name,
                    $slug,
                    $request->input('description', ''),
                    $request->input('thumbnail_url', ''),
                    $request->input('preview_url', ''),
                    $request->input('version', '1.0'),
                    $request->input('author', ''),
                    (int)$request->input('is_active', 0),
                    (int)$request->input('is_default', 0),
                ]
            );

            $newId = $result->insert_id;
            $theme = $db->fetchOne("SELECT * FROM themes WHERE id = ? LIMIT 1", 'i', [$newId]);

            Response::success($theme, 'Theme created successfully');
        } catch (\Throwable $e) {
            error_log("SettingsController::storeTheme error: " . $e->getMessage());
            Response::error('Failed to create theme: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/v1/settings/themes/{id}
     * Update theme by id – requires admin.
     */
    public function updateTheme(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);
        if (Response::isSent()) return;

        $id = (int)($params['id'] ?? 0);
        if (!$id) {
            Response::error('Theme ID is required', 400);
            return;
        }

        try {
            $db = \App\Core\Database::getInstance();

            $theme = $db->fetchOne("SELECT * FROM themes WHERE id = ? LIMIT 1", 'i', [$id]);
            if (!$theme) {
                Response::error('Theme not found', 404);
                return;
            }

            $db->execute(
                "UPDATE themes SET name = ?, slug = ?, description = ?, thumbnail_url = ?, preview_url = ?, version = ?, author = ?, is_active = ?, is_default = ? WHERE id = ?",
                'sssssssiii',
                [
                    $request->input('name', $theme['name']),
                    $request->input('slug', $theme['slug']),
                    $request->input('description', $theme['description']),
                    $request->input('thumbnail_url', $theme['thumbnail_url']),
                    $request->input('preview_url', $theme['preview_url']),
                    $request->input('version', $theme['version']),
                    $request->input('author', $theme['author']),
                    (int)$request->input('is_active', $theme['is_active']),
                    (int)$request->input('is_default', $theme['is_default']),
                    $id,
                ]
            );

            Response::success(null, 'Theme updated successfully');
        } catch (\Throwable $e) {
            error_log("SettingsController::updateTheme error: " . $e->getMessage());
            Response::error('Failed to update theme: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/v1/settings/themes/{id}
     * Delete theme and all related settings – requires admin.
     */
    public function destroyTheme(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);
        if (Response::isSent()) return;

        $id = (int)($params['id'] ?? 0);
        if (!$id) {
            Response::error('Theme ID is required', 400);
            return;
        }

        try {
            $db = \App\Core\Database::getInstance();

            $theme = $db->fetchOne("SELECT id FROM themes WHERE id = ? LIMIT 1", 'i', [$id]);
            if (!$theme) {
                Response::error('Theme not found', 404);
                return;
            }

            // Cascade delete related settings
            $db->execute("DELETE FROM color_settings WHERE theme_id = ?", 'i', [$id]);
            $db->execute("DELETE FROM font_settings WHERE theme_id = ?", 'i', [$id]);
            $db->execute("DELETE FROM button_styles WHERE theme_id = ?", 'i', [$id]);
            $db->execute("DELETE FROM card_styles WHERE theme_id = ?", 'i', [$id]);
            $db->execute("DELETE FROM design_settings WHERE theme_id = ?", 'i', [$id]);
            $db->execute("DELETE FROM themes WHERE id = ?", 'i', [$id]);

            Response::success(null, 'Theme deleted successfully');
        } catch (\Throwable $e) {
            error_log("SettingsController::destroyTheme error: " . $e->getMessage());
            Response::error('Failed to delete theme: ' . $e->getMessage(), 500);
        }
    }

    // =========================================================================
    // Color Settings CRUD
    // =========================================================================

    /**
     * POST /api/v1/settings/themes/{id}/colors
     * Create a new color setting for a theme – requires admin.
     */
    public function storeColor(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);
        if (Response::isSent()) return;

        $themeId = (int)($params['id'] ?? 0);
        $settingKey = trim($request->input('setting_key', ''));
        $settingName = trim($request->input('setting_name', ''));
        $colorValue = trim($request->input('color_value', ''));

        if (empty($settingKey) || empty($settingName) || empty($colorValue)) {
            Response::error('setting_key, setting_name, and color_value are required', 400);
            return;
        }

        try {
            $db = \App\Core\Database::getInstance();

            $result = $db->execute(
                "INSERT INTO color_settings (theme_id, setting_key, setting_name, color_value, category, sort_order) VALUES (?, ?, ?, ?, ?, ?)",
                'issssi',
                [
                    $themeId,
                    $settingKey,
                    $settingName,
                    $colorValue,
                    $request->input('category', ''),
                    (int)$request->input('sort_order', 0),
                ]
            );

            $newId = $result->insert_id;
            $color = $db->fetchOne("SELECT * FROM color_settings WHERE id = ? LIMIT 1", 'i', [$newId]);

            Response::success($color, 'Color setting created successfully');
        } catch (\Throwable $e) {
            error_log("SettingsController::storeColor error: " . $e->getMessage());
            Response::error('Failed to create color setting: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/v1/settings/colors/{colorId}
     * Update a color setting – requires admin.
     */
    public function updateColor(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);
        if (Response::isSent()) return;

        $colorId = (int)($params['colorId'] ?? 0);
        if (!$colorId) {
            Response::error('Color ID is required', 400);
            return;
        }

        try {
            $db = \App\Core\Database::getInstance();

            $color = $db->fetchOne("SELECT * FROM color_settings WHERE id = ? LIMIT 1", 'i', [$colorId]);
            if (!$color) {
                Response::error('Color setting not found', 404);
                return;
            }

            $db->execute(
                "UPDATE color_settings SET setting_key = ?, setting_name = ?, color_value = ?, category = ? WHERE id = ?",
                'ssssi',
                [
                    $request->input('setting_key', $color['setting_key']),
                    $request->input('setting_name', $color['setting_name']),
                    $request->input('color_value', $color['color_value']),
                    $request->input('category', $color['category']),
                    $colorId,
                ]
            );

            Response::success(null, 'Color setting updated successfully');
        } catch (\Throwable $e) {
            error_log("SettingsController::updateColor error: " . $e->getMessage());
            Response::error('Failed to update color setting: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/v1/settings/colors/{colorId}
     * Delete a color setting – requires admin.
     */
    public function destroyColor(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);
        if (Response::isSent()) return;

        $colorId = (int)($params['colorId'] ?? 0);
        if (!$colorId) {
            Response::error('Color ID is required', 400);
            return;
        }

        try {
            $db = \App\Core\Database::getInstance();

            $color = $db->fetchOne("SELECT id FROM color_settings WHERE id = ? LIMIT 1", 'i', [$colorId]);
            if (!$color) {
                Response::error('Color setting not found', 404);
                return;
            }

            $db->execute("DELETE FROM color_settings WHERE id = ?", 'i', [$colorId]);

            Response::success(null, 'Color setting deleted successfully');
        } catch (\Throwable $e) {
            error_log("SettingsController::destroyColor error: " . $e->getMessage());
            Response::error('Failed to delete color setting: ' . $e->getMessage(), 500);
        }
    }

    // =========================================================================
    // Font Settings CRUD
    // =========================================================================

    /**
     * POST /api/v1/settings/themes/{id}/fonts
     * Create a new font setting for a theme – requires admin.
     */
    public function storeFont(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);
        if (Response::isSent()) return;

        $themeId = (int)($params['id'] ?? 0);
        $settingKey = trim($request->input('setting_key', ''));
        $settingName = trim($request->input('setting_name', ''));
        $fontFamily = trim($request->input('font_family', ''));

        if (empty($settingKey) || empty($settingName) || empty($fontFamily)) {
            Response::error('setting_key, setting_name, and font_family are required', 400);
            return;
        }

        try {
            $db = \App\Core\Database::getInstance();

            $result = $db->execute(
                "INSERT INTO font_settings (theme_id, setting_key, setting_name, font_family, font_size, font_weight, line_height, category, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                'isssssssi',
                [
                    $themeId,
                    $settingKey,
                    $settingName,
                    $fontFamily,
                    $request->input('font_size', ''),
                    $request->input('font_weight', ''),
                    $request->input('line_height', ''),
                    $request->input('category', ''),
                    (int)$request->input('sort_order', 0),
                ]
            );

            $newId = $result->insert_id;
            $font = $db->fetchOne("SELECT * FROM font_settings WHERE id = ? LIMIT 1", 'i', [$newId]);

            Response::success($font, 'Font setting created successfully');
        } catch (\Throwable $e) {
            error_log("SettingsController::storeFont error: " . $e->getMessage());
            Response::error('Failed to create font setting: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/v1/settings/fonts/{fontId}
     * Update a font setting – requires admin.
     */
    public function updateFont(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);
        if (Response::isSent()) return;

        $fontId = (int)($params['fontId'] ?? 0);
        if (!$fontId) {
            Response::error('Font ID is required', 400);
            return;
        }

        try {
            $db = \App\Core\Database::getInstance();

            $font = $db->fetchOne("SELECT * FROM font_settings WHERE id = ? LIMIT 1", 'i', [$fontId]);
            if (!$font) {
                Response::error('Font setting not found', 404);
                return;
            }

            $db->execute(
                "UPDATE font_settings SET setting_key = ?, setting_name = ?, font_family = ?, font_size = ?, font_weight = ?, line_height = ?, category = ? WHERE id = ?",
                'sssssssi',
                [
                    $request->input('setting_key', $font['setting_key']),
                    $request->input('setting_name', $font['setting_name']),
                    $request->input('font_family', $font['font_family']),
                    $request->input('font_size', $font['font_size']),
                    $request->input('font_weight', $font['font_weight']),
                    $request->input('line_height', $font['line_height']),
                    $request->input('category', $font['category']),
                    $fontId,
                ]
            );

            Response::success(null, 'Font setting updated successfully');
        } catch (\Throwable $e) {
            error_log("SettingsController::updateFont error: " . $e->getMessage());
            Response::error('Failed to update font setting: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/v1/settings/fonts/{fontId}
     * Delete a font setting – requires admin.
     */
    public function destroyFont(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);
        if (Response::isSent()) return;

        $fontId = (int)($params['fontId'] ?? 0);
        if (!$fontId) {
            Response::error('Font ID is required', 400);
            return;
        }

        try {
            $db = \App\Core\Database::getInstance();

            $font = $db->fetchOne("SELECT id FROM font_settings WHERE id = ? LIMIT 1", 'i', [$fontId]);
            if (!$font) {
                Response::error('Font setting not found', 404);
                return;
            }

            $db->execute("DELETE FROM font_settings WHERE id = ?", 'i', [$fontId]);

            Response::success(null, 'Font setting deleted successfully');
        } catch (\Throwable $e) {
            error_log("SettingsController::destroyFont error: " . $e->getMessage());
            Response::error('Failed to delete font setting: ' . $e->getMessage(), 500);
        }
    }

    // =========================================================================
    // Button Styles CRUD
    // =========================================================================

    /**
     * POST /api/v1/settings/themes/{id}/buttons
     * Create a new button style for a theme – requires admin.
     */
    public function storeButton(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);
        if (Response::isSent()) return;

        $themeId = (int)($params['id'] ?? 0);
        $name = trim($request->input('name', ''));
        $slug = trim($request->input('slug', ''));
        $buttonType = trim($request->input('button_type', ''));
        $bgColor = trim($request->input('background_color', ''));
        $textColor = trim($request->input('text_color', ''));

        if (empty($name) || empty($slug) || empty($buttonType) || empty($bgColor) || empty($textColor)) {
            Response::error('name, slug, button_type, background_color, and text_color are required', 400);
            return;
        }

        try {
            $db = \App\Core\Database::getInstance();

            $result = $db->execute(
                "INSERT INTO button_styles (theme_id, name, slug, button_type, background_color, text_color, border_color, border_width, border_radius, padding, font_size, font_weight, hover_background_color, hover_text_color) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                'isssssssssssss',
                [
                    $themeId,
                    $name,
                    $slug,
                    $buttonType,
                    $bgColor,
                    $textColor,
                    $request->input('border_color', ''),
                    $request->input('border_width', ''),
                    $request->input('border_radius', ''),
                    $request->input('padding', ''),
                    $request->input('font_size', ''),
                    $request->input('font_weight', ''),
                    $request->input('hover_background_color', ''),
                    $request->input('hover_text_color', ''),
                ]
            );

            $newId = $result->insert_id;
            $button = $db->fetchOne("SELECT * FROM button_styles WHERE id = ? LIMIT 1", 'i', [$newId]);

            Response::success($button, 'Button style created successfully');
        } catch (\Throwable $e) {
            error_log("SettingsController::storeButton error: " . $e->getMessage());
            Response::error('Failed to create button style: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/v1/settings/buttons/{buttonId}
     * Update a button style – requires admin.
     */
    public function updateButton(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);
        if (Response::isSent()) return;

        $buttonId = (int)($params['buttonId'] ?? 0);
        if (!$buttonId) {
            Response::error('Button ID is required', 400);
            return;
        }

        try {
            $db = \App\Core\Database::getInstance();

            $button = $db->fetchOne("SELECT * FROM button_styles WHERE id = ? LIMIT 1", 'i', [$buttonId]);
            if (!$button) {
                Response::error('Button style not found', 404);
                return;
            }

            $db->execute(
                "UPDATE button_styles SET name = ?, slug = ?, button_type = ?, background_color = ?, text_color = ?, border_color = ?, border_width = ?, border_radius = ?, padding = ?, font_size = ?, font_weight = ?, hover_background_color = ?, hover_text_color = ? WHERE id = ?",
                'sssssssssssssi',
                [
                    $request->input('name', $button['name']),
                    $request->input('slug', $button['slug']),
                    $request->input('button_type', $button['button_type']),
                    $request->input('background_color', $button['background_color']),
                    $request->input('text_color', $button['text_color']),
                    $request->input('border_color', $button['border_color']),
                    $request->input('border_width', $button['border_width']),
                    $request->input('border_radius', $button['border_radius']),
                    $request->input('padding', $button['padding']),
                    $request->input('font_size', $button['font_size']),
                    $request->input('font_weight', $button['font_weight']),
                    $request->input('hover_background_color', $button['hover_background_color']),
                    $request->input('hover_text_color', $button['hover_text_color']),
                    $buttonId,
                ]
            );

            Response::success(null, 'Button style updated successfully');
        } catch (\Throwable $e) {
            error_log("SettingsController::updateButton error: " . $e->getMessage());
            Response::error('Failed to update button style: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/v1/settings/buttons/{buttonId}
     * Delete a button style – requires admin.
     */
    public function destroyButton(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);
        if (Response::isSent()) return;

        $buttonId = (int)($params['buttonId'] ?? 0);
        if (!$buttonId) {
            Response::error('Button ID is required', 400);
            return;
        }

        try {
            $db = \App\Core\Database::getInstance();

            $button = $db->fetchOne("SELECT id FROM button_styles WHERE id = ? LIMIT 1", 'i', [$buttonId]);
            if (!$button) {
                Response::error('Button style not found', 404);
                return;
            }

            $db->execute("DELETE FROM button_styles WHERE id = ?", 'i', [$buttonId]);

            Response::success(null, 'Button style deleted successfully');
        } catch (\Throwable $e) {
            error_log("SettingsController::destroyButton error: " . $e->getMessage());
            Response::error('Failed to delete button style: ' . $e->getMessage(), 500);
        }
    }

    // =========================================================================
    // Card Styles CRUD
    // =========================================================================

    /**
     * POST /api/v1/settings/themes/{id}/cards
     * Create a new card style for a theme – requires admin.
     */
    public function storeCard(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);
        if (Response::isSent()) return;

        $themeId = (int)($params['id'] ?? 0);
        $name = trim($request->input('name', ''));
        $slug = trim($request->input('slug', ''));
        $cardType = trim($request->input('card_type', ''));

        if (empty($name) || empty($slug) || empty($cardType)) {
            Response::error('name, slug, and card_type are required', 400);
            return;
        }

        try {
            $db = \App\Core\Database::getInstance();

            $result = $db->execute(
                "INSERT INTO card_styles (theme_id, name, slug, card_type, background_color, border_color, border_width, border_radius, shadow_style, padding, hover_effect, text_align, image_aspect_ratio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                'issssssssssss',
                [
                    $themeId,
                    $name,
                    $slug,
                    $cardType,
                    $request->input('background_color', ''),
                    $request->input('border_color', ''),
                    $request->input('border_width', ''),
                    $request->input('border_radius', ''),
                    $request->input('shadow_style', ''),
                    $request->input('padding', ''),
                    $request->input('hover_effect', ''),
                    $request->input('text_align', ''),
                    $request->input('image_aspect_ratio', ''),
                ]
            );

            $newId = $result->insert_id;
            $card = $db->fetchOne("SELECT * FROM card_styles WHERE id = ? LIMIT 1", 'i', [$newId]);

            Response::success($card, 'Card style created successfully');
        } catch (\Throwable $e) {
            error_log("SettingsController::storeCard error: " . $e->getMessage());
            Response::error('Failed to create card style: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/v1/settings/cards/{cardId}
     * Update a card style – requires admin.
     */
    public function updateCard(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);
        if (Response::isSent()) return;

        $cardId = (int)($params['cardId'] ?? 0);
        if (!$cardId) {
            Response::error('Card ID is required', 400);
            return;
        }

        try {
            $db = \App\Core\Database::getInstance();

            $card = $db->fetchOne("SELECT * FROM card_styles WHERE id = ? LIMIT 1", 'i', [$cardId]);
            if (!$card) {
                Response::error('Card style not found', 404);
                return;
            }

            $db->execute(
                "UPDATE card_styles SET name = ?, slug = ?, card_type = ?, background_color = ?, border_color = ?, border_width = ?, border_radius = ?, shadow_style = ?, padding = ?, hover_effect = ?, text_align = ?, image_aspect_ratio = ? WHERE id = ?",
                'ssssssssssssi',
                [
                    $request->input('name', $card['name']),
                    $request->input('slug', $card['slug']),
                    $request->input('card_type', $card['card_type']),
                    $request->input('background_color', $card['background_color']),
                    $request->input('border_color', $card['border_color']),
                    $request->input('border_width', $card['border_width']),
                    $request->input('border_radius', $card['border_radius']),
                    $request->input('shadow_style', $card['shadow_style']),
                    $request->input('padding', $card['padding']),
                    $request->input('hover_effect', $card['hover_effect']),
                    $request->input('text_align', $card['text_align']),
                    $request->input('image_aspect_ratio', $card['image_aspect_ratio']),
                    $cardId,
                ]
            );

            Response::success(null, 'Card style updated successfully');
        } catch (\Throwable $e) {
            error_log("SettingsController::updateCard error: " . $e->getMessage());
            Response::error('Failed to update card style: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/v1/settings/cards/{cardId}
     * Delete a card style – requires admin.
     */
    public function destroyCard(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);
        if (Response::isSent()) return;

        $cardId = (int)($params['cardId'] ?? 0);
        if (!$cardId) {
            Response::error('Card ID is required', 400);
            return;
        }

        try {
            $db = \App\Core\Database::getInstance();

            $card = $db->fetchOne("SELECT id FROM card_styles WHERE id = ? LIMIT 1", 'i', [$cardId]);
            if (!$card) {
                Response::error('Card style not found', 404);
                return;
            }

            $db->execute("DELETE FROM card_styles WHERE id = ?", 'i', [$cardId]);

            Response::success(null, 'Card style deleted successfully');
        } catch (\Throwable $e) {
            error_log("SettingsController::destroyCard error: " . $e->getMessage());
            Response::error('Failed to delete card style: ' . $e->getMessage(), 500);
        }
    }

    // =========================================================================
    // Design Settings CRUD
    // =========================================================================

    /**
     * POST /api/v1/settings/themes/{id}/design
     * Create a new design setting for a theme – requires admin.
     */
    public function storeDesignSetting(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);
        if (Response::isSent()) return;

        $themeId = (int)($params['id'] ?? 0);
        $settingKey = trim($request->input('setting_key', ''));
        $settingName = trim($request->input('setting_name', ''));

        if (empty($settingKey) || empty($settingName)) {
            Response::error('setting_key and setting_name are required', 400);
            return;
        }

        try {
            $db = \App\Core\Database::getInstance();

            $result = $db->execute(
                "INSERT INTO design_settings (theme_id, setting_key, setting_name, setting_value, setting_type, category, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)",
                'isssssi',
                [
                    $themeId,
                    $settingKey,
                    $settingName,
                    $request->input('setting_value', ''),
                    $request->input('setting_type', ''),
                    $request->input('category', ''),
                    (int)$request->input('sort_order', 0),
                ]
            );

            $newId = $result->insert_id;
            $setting = $db->fetchOne("SELECT * FROM design_settings WHERE id = ? LIMIT 1", 'i', [$newId]);

            Response::success($setting, 'Design setting created successfully');
        } catch (\Throwable $e) {
            error_log("SettingsController::storeDesignSetting error: " . $e->getMessage());
            Response::error('Failed to create design setting: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/v1/settings/design/{designId}
     * Update a design setting – requires admin.
     */
    public function updateDesignSetting(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);
        if (Response::isSent()) return;

        $designId = (int)($params['designId'] ?? 0);
        if (!$designId) {
            Response::error('Design setting ID is required', 400);
            return;
        }

        try {
            $db = \App\Core\Database::getInstance();

            $setting = $db->fetchOne("SELECT * FROM design_settings WHERE id = ? LIMIT 1", 'i', [$designId]);
            if (!$setting) {
                Response::error('Design setting not found', 404);
                return;
            }

            $db->execute(
                "UPDATE design_settings SET setting_key = ?, setting_name = ?, setting_value = ?, setting_type = ?, category = ? WHERE id = ?",
                'sssssi',
                [
                    $request->input('setting_key', $setting['setting_key']),
                    $request->input('setting_name', $setting['setting_name']),
                    $request->input('setting_value', $setting['setting_value']),
                    $request->input('setting_type', $setting['setting_type']),
                    $request->input('category', $setting['category']),
                    $designId,
                ]
            );

            Response::success(null, 'Design setting updated successfully');
        } catch (\Throwable $e) {
            error_log("SettingsController::updateDesignSetting error: " . $e->getMessage());
            Response::error('Failed to update design setting: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/v1/settings/design/{designId}
     * Delete a design setting – requires admin.
     */
    public function destroyDesignSetting(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);
        if (Response::isSent()) return;

        $designId = (int)($params['designId'] ?? 0);
        if (!$designId) {
            Response::error('Design setting ID is required', 400);
            return;
        }

        try {
            $db = \App\Core\Database::getInstance();

            $setting = $db->fetchOne("SELECT id FROM design_settings WHERE id = ? LIMIT 1", 'i', [$designId]);
            if (!$setting) {
                Response::error('Design setting not found', 404);
                return;
            }

            $db->execute("DELETE FROM design_settings WHERE id = ?", 'i', [$designId]);

            Response::success(null, 'Design setting deleted successfully');
        } catch (\Throwable $e) {
            error_log("SettingsController::destroyDesignSetting error: " . $e->getMessage());
            Response::error('Failed to delete design setting: ' . $e->getMessage(), 500);
        }
    }

    // =========================================================================
    // System Settings CRUD
    // =========================================================================

    /**
     * POST /api/v1/settings
     * Create a new system setting – requires admin.
     */
    public function storeSetting(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);
        if (Response::isSent()) return;

        $settingKey = trim($request->input('setting_key', ''));
        $category = trim($request->input('category', ''));

        if (empty($settingKey) || empty($category)) {
            Response::error('setting_key and category are required', 400);
            return;
        }

        try {
            $db = \App\Core\Database::getInstance();

            $result = $db->execute(
                "INSERT INTO system_settings (setting_key, setting_value, setting_type, category, description, is_public, is_editable) VALUES (?, ?, ?, ?, ?, ?, ?)",
                'sssssii',
                [
                    $settingKey,
                    $request->input('setting_value', ''),
                    $request->input('setting_type', 'string'),
                    $category,
                    $request->input('description', ''),
                    (int)$request->input('is_public', 0),
                    (int)$request->input('is_editable', 1),
                ]
            );

            $newId = $result->insert_id;
            $setting = $db->fetchOne("SELECT * FROM system_settings WHERE id = ? LIMIT 1", 'i', [$newId]);

            Response::success($setting, 'System setting created successfully');
        } catch (\Throwable $e) {
            error_log("SettingsController::storeSetting error: " . $e->getMessage());
            Response::error('Failed to create system setting: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/v1/settings/{id}
     * Delete a system setting – requires admin.
     */
    public function destroySetting(Request $request, array $params = []): void
    {
        $this->requireAdmin($request);
        if (Response::isSent()) return;

        $id = (int)($params['id'] ?? 0);
        if (!$id) {
            Response::error('Setting ID is required', 400);
            return;
        }

        try {
            $db = \App\Core\Database::getInstance();

            $setting = $db->fetchOne("SELECT id FROM system_settings WHERE id = ? LIMIT 1", 'i', [$id]);
            if (!$setting) {
                Response::error('System setting not found', 404);
                return;
            }

            $db->execute("DELETE FROM system_settings WHERE id = ?", 'i', [$id]);

            Response::success(null, 'System setting deleted successfully');
        } catch (\Throwable $e) {
            error_log("SettingsController::destroySetting error: " . $e->getMessage());
            Response::error('Failed to delete system setting: ' . $e->getMessage(), 500);
        }
    }
}

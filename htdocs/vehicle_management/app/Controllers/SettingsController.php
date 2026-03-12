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
}

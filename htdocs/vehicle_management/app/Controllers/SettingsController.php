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
}

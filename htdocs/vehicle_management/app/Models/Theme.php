<?php
/**
 * Theme Model – loads active theme with colors, fonts, buttons, cards, design settings
 */

namespace App\Models;

use App\Core\Database;

class Theme extends BaseModel
{
    protected string $table = 'themes';

    /**
     * Get the active theme with all related style settings.
     */
    public function getActiveTheme(): ?array
    {
        $db = Database::getInstance();
        $theme = $db->fetchOne("SELECT * FROM themes WHERE is_active = 1 LIMIT 1");
        if (!$theme) {
            return null;
        }

        $themeId = (int)$theme['id'];

        $theme['colors'] = $db->fetchAll(
            "SELECT setting_key, setting_name, color_value, category FROM color_settings WHERE theme_id = ? AND is_active = 1 ORDER BY sort_order",
            'i', [$themeId]
        );

        $theme['fonts'] = $db->fetchAll(
            "SELECT setting_key, setting_name, font_family, font_size, font_weight, line_height, category FROM font_settings WHERE theme_id = ? AND is_active = 1 ORDER BY sort_order",
            'i', [$themeId]
        );

        $theme['buttons'] = $db->fetchAll(
            "SELECT slug, name, button_type, background_color, text_color, border_color, border_radius, padding, font_size, font_weight, hover_background_color, hover_text_color FROM button_styles WHERE theme_id = ? AND is_active = 1",
            'i', [$themeId]
        );

        $theme['cards'] = $db->fetchAll(
            "SELECT slug, name, card_type, background_color, border_color, border_width, border_radius, shadow_style, padding, hover_effect, text_align FROM card_styles WHERE theme_id = ? AND is_active = 1",
            'i', [$themeId]
        );

        $theme['design'] = $db->fetchAll(
            "SELECT setting_key, setting_name, setting_value, category FROM design_settings WHERE theme_id = ? AND is_active = 1 ORDER BY sort_order",
            'i', [$themeId]
        );

        return $theme;
    }

    /**
     * Generate CSS custom properties from theme.
     */
    public function generateCssVars(array $theme): string
    {
        $vars = [];
        foreach ($theme['colors'] ?? [] as $c) {
            $vars[] = "--{$c['setting_key']}: {$c['color_value']};";
        }
        foreach ($theme['design'] ?? [] as $d) {
            $vars[] = "--{$d['setting_key']}: {$d['setting_value']};";
        }
        foreach ($theme['fonts'] ?? [] as $f) {
            $vars[] = "--{$f['setting_key']}: {$f['font_family']};";
            if ($f['font_size']) {
                $vars[] = "--{$f['setting_key']}_size: {$f['font_size']};";
            }
        }
        return ":root {\n  " . implode("\n  ", $vars) . "\n}";
    }
}

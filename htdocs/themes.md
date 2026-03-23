<?php
declare(strict_types=1);

/**
 * /admin/fragments/themes.php
 * Theme Management - Products-pattern: List view → Form with tabs
 */

// ════════════════════════════════════════════════════════════
// DETECT REQUEST TYPE
// ════════════════════════════════════════════════════════════
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$isEmbedded = isset($_GET['embedded']) || isset($_POST['embedded']);
$isFragment = $isAjax || $isEmbedded;

if ($isFragment) {
    require_once __DIR__ . '/../includes/admin_context.php';
} else {
    require_once __DIR__ . '/../includes/header.php';
}

if (!is_admin_logged_in()) {
    if ($isFragment) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    header('Location: /admin/login.php');
    exit;
}

$user     = admin_user();
$lang     = admin_lang();
$dir      = admin_dir();
$csrf     = admin_csrf();
$tenantId = admin_tenant_id();

$isSuperAdmin = is_super_admin();
$canManage    = $isSuperAdmin || can('manage_themes');

if (!$canManage) {
    if ($isFragment) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
    http_response_code(403);
    die('Access denied');
}

// ════════════════════════════════════════════════════════════
// DB-DRIVEN CSS VARS HELPER (Themes)
// ════════════════════════════════════════════════════════════
if (!function_exists('renderFragmentThemeVars')) {
    function renderFragmentThemeVars(array $theme): void {
        $emitted = [];
        $lines   = [':root {'];

        $emit = function(string $k, string $v) use (&$emitted, &$lines): void {
            $ke = htmlspecialchars($k, ENT_QUOTES);
            $ve = htmlspecialchars($v, ENT_QUOTES);
            $lines[]           = "    --{$ke}: {$ve};";
            $emitted["--{$k}"] = $v;
        };

        foreach ($theme['color_settings'] ?? [] as $c) {
            if (empty($c['setting_key']) || !isset($c['color_value'])) continue;
            $k = $c['setting_key'];
            $h = str_replace('_', '-', $k);
            $v = $c['color_value'];
            $emit($k, $v);
            if ($h !== $k) $emit($h, $v);
        }
        foreach ($theme['font_settings'] ?? [] as $f) {
            if (empty($f['setting_key'])) continue;
            $sk = $f['setting_key'];
            $sh = str_replace('_', '-', $sk);
            if (!empty($f['font_family'])) {
                $emit("{$sk}-family", $f['font_family']);
                if ($sh !== $sk) $emit("{$sh}-family", $f['font_family']);
            }
            if (!empty($f['font_size'])) {
                $emit("{$sk}-size", $f['font_size']);
                if ($sh !== $sk) $emit("{$sh}-size", $f['font_size']);
            }
            if (!empty($f['font_weight'])) {
                $emit("{$sk}-weight", $f['font_weight']);
                if ($sh !== $sk) $emit("{$sh}-weight", $f['font_weight']);
            }
        }
        foreach ($theme['design_settings'] ?? [] as $d) {
            if (empty($d['setting_key']) || !isset($d['setting_value'])) continue;
            $dk = $d['setting_key'];
            $dh = str_replace('_', '-', $dk);
            $emit($dk, $d['setting_value']);
            if ($dh !== $dk) $emit($dh, $d['setting_value']);
        }
        foreach ($theme['button_styles'] ?? [] as $b) {
            if (empty($b['slug'])) continue;
            $slug = preg_replace('/[^a-z0-9_-]/', '-', strtolower((string)$b['slug']));
            if (!empty($b['background_color'])) $emit("btn-{$slug}-bg",     $b['background_color']);
            if (!empty($b['text_color']))        $emit("btn-{$slug}-color",  $b['text_color']);
            if (!empty($b['border_color']))      $emit("btn-{$slug}-border", $b['border_color']);
            if (!empty($b['border_radius']))     $emit("btn-{$slug}-radius", $b['border_radius'] . 'px');
        }
        foreach ($theme['card_styles'] ?? [] as $cs) {
            if (empty($cs['slug'])) continue;
            $slug = preg_replace('/[^a-z0-9_-]/', '-', strtolower((string)$cs['slug']));
            if (!empty($cs['background_color'])) $emit("card-{$slug}-bg",     $cs['background_color']);
            if (!empty($cs['border_color']))      $emit("card-{$slug}-border", $cs['border_color']);
            if (!empty($cs['border_radius']))     $emit("card-{$slug}-radius", $cs['border_radius'] . 'px');
            if (!empty($cs['shadow_style']))      $emit("card-{$slug}-shadow", $cs['shadow_style']);
        }

        // Emit card_type-based aliases so that CSS selectors like --card-product-bg work
        foreach ($theme['card_styles'] ?? [] as $cs) {
            if (empty($cs['card_type'])) continue;
            $type = preg_replace('/[^a-z0-9_-]/', '-', strtolower((string)$cs['card_type']));
            if (!empty($cs['background_color'])) $emit("card-{$type}-bg",     $cs['background_color']);
            if (!empty($cs['border_color']))      $emit("card-{$type}-border", $cs['border_color']);
            if (!empty($cs['border_radius']))     $emit("card-{$type}-radius", $cs['border_radius'] . 'px');
            if (!empty($cs['shadow_style']))      $emit("card-{$type}-shadow", $cs['shadow_style']);
        }

        // Common alias defaults
        $bgSec = $emitted['--background-secondary'] ?? $emitted['--background_secondary'] ?? null;
        $aliasDefaults = [
            '--card-bg'       => $emitted['--card-bg']      ?? $bgSec ?? '#081127',
            '--input-bg'      => $emitted['--input-bg']     ?? $bgSec ?? '#0b1220',
            '--thead-bg'      => $emitted['--thead-bg']     ?? $bgSec ?? '#061021',
            '--danger-color'  => $emitted['--danger-color'] ?? $emitted['--error-color'] ?? '#ef4444',
            '--success-color' => $emitted['--success-color'] ?? '#22c55e',
        ];
        foreach ($aliasDefaults as $cssVar => $val) {
            if (!isset($emitted[$cssVar])) {
                $lines[]           = '    ' . htmlspecialchars($cssVar, ENT_QUOTES) . ': ' . htmlspecialchars($val, ENT_QUOTES) . ';';
                $emitted[$cssVar]  = $val;
            }
        }

        $lines[] = '}';
        echo implode(PHP_EOL, $lines) . PHP_EOL;
    }
}
?>
<!-- DB-driven CSS vars (all settings, colors, fonts from database) -->
<style id="db-theme-vars-themes">
<?php renderFragmentThemeVars($GLOBALS['ADMIN_UI']['theme'] ?? []); ?>
<?php if (!empty($GLOBALS['ADMIN_UI']['theme']['generated_css'])): ?>
<?= $GLOBALS['ADMIN_UI']['theme']['generated_css'] ?>
<?php endif; ?>
</style>

<?php if ($isFragment): ?>
<link rel="stylesheet" href="/admin/assets/css/themes-system.css">
<?php endif; ?>

<!-- Page Meta -->
<meta data-page="themes"
      data-assets-css="/admin/assets/css/themes-system.css"
      data-i18n-files="/languages/AdminUiTheme/<?= htmlspecialchars($lang) ?>.json">

<script>
window.THEMES_CONFIG = {
    TENANT_ID: <?= (int)$tenantId ?>,
    LANG: '<?= htmlspecialchars($lang) ?>',
    DIR: '<?= htmlspecialchars($dir) ?>',
    CSRF: '<?= htmlspecialchars($csrf) ?>',
    API: {
        themes: '/api/themes',
        designSettings: '/api/design_settings',
        colorSettings: '/api/color_settings',
        fontSettings: '/api/font_settings',
        buttonStyles: '/api/button_styles',
        cardStyles: '/api/card_styles',
        homepageSections: '/api/homepage_sections',
        systemSettings: '/api/system_settings',
        languages: '/api/languages',
        themeTranslations: '/api/theme_translations'
    }
};
</script>

<div class="themes-page" id="themesPageContainer" dir="<?= htmlspecialchars($dir) ?>">

    <!-- Alerts -->
    <div id="alertsContainer"></div>

    <!-- ═══════════ LIST VIEW ═══════════ -->
    <div id="themesListView">
        <div class="page-header">
            <div>
                <h1 data-i18n="theme_manager.title">Theme Management</h1>
                <p data-i18n="theme_manager.subtitle">Manage themes and styling</p>
            </div>
            <div class="page-header-actions">
                <button class="btn btn-primary" id="btnAddTheme">
                    <i class="fas fa-plus"></i>
                    <span data-i18n="theme_manager.add_new">Add New Theme</span>
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-bar">
            <input type="text" id="themeSearch" class="form-control"
                   data-i18n-placeholder="theme_manager.filters.search_placeholder"
                   placeholder="Search themes...">
            <select id="themeStatusFilter" class="form-control">
                <option value="" data-i18n="theme_manager.filters.status_options.all">All Status</option>
                <option value="1" data-i18n="theme_manager.status.active">Active</option>
                <option value="0" data-i18n="theme_manager.status.inactive">Inactive</option>
            </select>
        </div>

        <!-- Themes Table -->
        <div class="card table-card">
            <div class="card-body" style="padding:0">
                <!-- Loading — visible by default; JS hides it when data loads -->
                <div id="themesLoading" class="loading-state" style="display:flex">
                    <div class="spinner"></div>
                    <p data-i18n="theme_manager.loading">Loading themes...</p>
                </div>

                <div id="themesTableContainer" style="display:none">
                    <table class="data-table" id="themesTable">
                        <thead>
                            <tr>
                                <th data-i18n="theme_manager.table.id">ID</th>
                                <th data-i18n="theme_manager.form.fields.name.label">Name</th>
                                <th data-i18n="theme_manager.form.fields.slug.label">Slug</th>
                                <th data-i18n="theme_manager.form.fields.version.label">Version</th>
                                <th data-i18n="theme_manager.form.fields.status.label">Status</th>
                                <th data-i18n="theme_manager.form.fields.is_default">Default</th>
                                <th data-i18n="theme_manager.table.actions_label">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="themesTableBody"></tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div id="themesEmpty" class="empty-state" style="display:none">
                    <i class="fas fa-palette fa-3x"></i>
                    <h3 data-i18n="theme_manager.table.empty.title">No Themes Found</h3>
                    <p data-i18n="theme_manager.table.empty.message">Start by creating your first theme</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════ FORM VIEW (hidden by default) ═══════════ -->
    <div id="themeFormView" style="display:none">
        <div class="page-header">
            <div>
                <h1 id="formTitle" data-i18n="theme_manager.form.add_title">Add Theme</h1>
            </div>
            <div class="page-header-actions">
                <button class="btn btn-secondary" id="btnCancelForm">
                    <i class="fas fa-arrow-left"></i>
                    <span data-i18n="theme_manager.form.buttons.cancel">Cancel</span>
                </button>
                <button class="btn btn-primary" id="btnSaveTheme">
                    <i class="fas fa-save"></i>
                    <span data-i18n="theme_manager.form.buttons.save">Save</span>
                </button>
                <button class="btn btn-danger" id="btnDeleteTheme" style="display:none">
                    <i class="fas fa-trash"></i>
                    <span data-i18n="theme_manager.table.actions.delete">Delete</span>
                </button>
            </div>
        </div>

        <!-- Form Tabs -->
        <div class="form-tabs">
            <button class="form-tab active" data-tab="info">
                <i class="fas fa-info-circle"></i> <span data-i18n="tabs.info">Theme Info</span>
            </button>
            <button class="form-tab" data-tab="design">
                <i class="fas fa-cog"></i> <span data-i18n="tabs.design">Design</span>
            </button>
            <button class="form-tab" data-tab="colors">
                <i class="fas fa-paint-brush"></i> <span data-i18n="tabs.colors">Colors</span>
            </button>
            <button class="form-tab" data-tab="fonts">
                <i class="fas fa-font"></i> <span data-i18n="tabs.fonts">Fonts</span>
            </button>
            <button class="form-tab" data-tab="buttons">
                <i class="fas fa-mouse-pointer"></i> <span data-i18n="tabs.buttons">Buttons</span>
            </button>
            <button class="form-tab" data-tab="cards">
                <i class="fas fa-square"></i> <span data-i18n="tabs.cards">Cards</span>
            </button>
            <button class="form-tab" data-tab="homepage">
                <i class="fas fa-home"></i> <span data-i18n="tabs.homepage">Homepage</span>
            </button>
            <button class="form-tab" data-tab="system">
                <i class="fas fa-cogs"></i> <span data-i18n="tabs.system">System Settings</span>
            </button>
            <button class="form-tab" data-tab="translations">
                <i class="fas fa-language"></i> <span data-i18n="tabs.translations">Translations</span>
            </button>
        </div>

        <!-- TAB: Theme Info -->
        <div class="tab-content active" id="tab-info">
            <form id="themeForm">
                <input type="hidden" id="themeId" value="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="themeName" data-i18n="theme_manager.form.fields.name.label">Theme Name</label>
                        <input type="text" id="themeName" class="form-control" required
                               data-i18n-placeholder="theme_manager.form.fields.name.placeholder">
                    </div>
                    <div class="form-group">
                        <label for="themeSlug" data-i18n="theme_manager.form.fields.slug.label">Slug</label>
                        <input type="text" id="themeSlug" class="form-control" required
                               data-i18n-placeholder="theme_manager.form.fields.slug.placeholder">
                    </div>
                </div>
                <div class="form-group">
                    <label for="themeDescription" data-i18n="theme_manager.form.fields.description.label">Description</label>
                    <textarea id="themeDescription" class="form-control" rows="3"
                              data-i18n-placeholder="theme_manager.form.fields.description.placeholder"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="themeVersion" data-i18n="theme_manager.form.fields.version.label">Version</label>
                        <input type="text" id="themeVersion" class="form-control" value="1.0.0"
                               data-i18n-placeholder="theme_manager.form.fields.version.placeholder">
                    </div>
                    <div class="form-group">
                        <label for="themeAuthor" data-i18n="theme_manager.form.fields.author.label">Author</label>
                        <input type="text" id="themeAuthor" class="form-control"
                               data-i18n-placeholder="theme_manager.form.fields.author.placeholder">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="themeThumbnailUrl" data-i18n="theme_manager.form.fields.thumbnail_url.label">Thumbnail URL</label>
                        <div style="display:flex;gap:8px;align-items:flex-end;">
                            <input type="text" id="themeThumbnailUrl" class="form-control"
                                   data-i18n-placeholder="theme_manager.form.fields.thumbnail_url.placeholder">
                            <button type="button" class="btn btn-secondary btn-sm" id="btnSelectThumbnail" style="white-space:nowrap;">
                                <i class="fas fa-images"></i> <span data-i18n="theme_manager.form.fields.select_image">Select Image</span>
                            </button>
                        </div>
                        <div id="thumbnailPreviewWrap" style="margin-top:6px;display:none;">
                            <img id="thumbnailPreviewImg" src="" alt="" style="max-width:120px;max-height:80px;border-radius:6px;border:1px solid var(--border-color,#334155);">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="themePreviewUrl" data-i18n="theme_manager.form.fields.preview_url.label">Preview URL</label>
                        <div style="display:flex;gap:8px;align-items:flex-end;">
                            <input type="text" id="themePreviewUrl" class="form-control"
                                   data-i18n-placeholder="theme_manager.form.fields.preview_url.placeholder">
                            <button type="button" class="btn btn-secondary btn-sm" id="btnSelectPreview" style="white-space:nowrap;">
                                <i class="fas fa-images"></i> <span data-i18n="theme_manager.form.fields.select_image">Select Image</span>
                            </button>
                        </div>
                        <div id="previewUrlPreviewWrap" style="margin-top:6px;display:none;">
                            <img id="previewUrlPreviewImg" src="" alt="" style="max-width:120px;max-height:80px;border-radius:6px;border:1px solid var(--border-color,#334155);">
                        </div>
                    </div>
                </div>

                <!-- Inline Media Studio Panel -->
                <div id="themeMediaStudioPanel" style="display:none;margin-top:16px;border:1px solid var(--border-color,#334155);border-radius:8px;overflow:hidden;">
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:var(--card-bg,#0d1b2e);border-bottom:1px solid var(--border-color,#334155);">
                        <strong data-i18n="theme_manager.form.media_studio.title"><i class="fas fa-images"></i> Media Studio</strong>
                        <button type="button" id="btnCloseThemeMediaStudio" class="btn btn-sm btn-secondary">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <iframe id="themeMediaStudioFrame"
                            src=""
                            style="width:100%;height:520px;border:none;display:block;"
                            allow="same-origin"></iframe>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label data-i18n="theme_manager.form.fields.status.label">Status</label>
                        <select id="themeIsActive" class="form-control">
                            <option value="1" data-i18n="theme_manager.status.active">Active</option>
                            <option value="0" data-i18n="theme_manager.status.inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="themeIsDefault">
                            <span data-i18n="theme_manager.form.fields.is_default">Set as default theme</span>
                        </label>
                    </div>
                </div>
            </form>
        </div>

        <!-- TAB: Design Settings -->
        <div class="tab-content" id="tab-design" style="display:none">
            <div class="settings-header">
                <h3 data-i18n="theme_manager_settings.design.title">Design Settings</h3>
                <button class="btn btn-sm btn-primary" id="btnAddDesign">
                    <i class="fas fa-plus"></i> <span data-i18n="theme_manager_settings.design.add">Add Setting</span>
                </button>
            </div>
            <div id="designSettingsList" class="settings-list"></div>
            <div id="designForm" class="inline-form" style="display:none">
                <input type="hidden" id="designId">
                <div class="form-row">
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.key">Key</label><input type="text" id="designKey" class="form-control"></div>
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.name">Name</label><input type="text" id="designName" class="form-control"></div>
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.value">Value</label><input type="text" id="designValue" class="form-control"></div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label data-i18n="theme_manager_settings.form.type">Type</label>
                        <select id="designType" class="form-control">
                            <option value="text"    data-i18n="theme_manager_settings.types.text">Text</option>
                            <option value="number"  data-i18n="theme_manager_settings.types.number">Number</option>
                            <option value="color"   data-i18n="theme_manager_settings.types.color">Color</option>
                            <option value="image"   data-i18n="theme_manager_settings.types.image">Image</option>
                            <option value="boolean" data-i18n="theme_manager_settings.types.boolean">Boolean</option>
                            <option value="select"  data-i18n="theme_manager_settings.types.select">Select</option>
                            <option value="json"    data-i18n="theme_manager_settings.types.json">JSON</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label data-i18n="theme_manager_settings.form.category">Category</label>
                        <select id="designCategory" class="form-control">
                            <option value="layout"   data-i18n="theme_manager_settings.categories.layout">Layout</option>
                            <option value="header"   data-i18n="theme_manager_settings.categories.header">Header</option>
                            <option value="footer"   data-i18n="theme_manager_settings.categories.footer">Footer</option>
                            <option value="sidebar"  data-i18n="theme_manager_settings.categories.sidebar">Sidebar</option>
                            <option value="homepage" data-i18n="theme_manager_settings.categories.homepage">Homepage</option>
                            <option value="product"  data-i18n="theme_manager_settings.categories.product">Product</option>
                            <option value="cart"     data-i18n="theme_manager_settings.categories.cart">Cart</option>
                            <option value="checkout" data-i18n="theme_manager_settings.categories.checkout">Checkout</option>
                            <option value="other"    data-i18n="theme_manager_settings.categories.other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label data-i18n="theme_manager_settings.form.active">Active</label>
                        <select id="designIsActive" class="form-control">
                            <option value="1" data-i18n="theme_manager_settings.form.yes">Yes</option>
                            <option value="0" data-i18n="theme_manager_settings.form.no">No</option>
                        </select>
                    </div>
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.sort_order">Sort Order</label><input type="number" id="designSortOrder" class="form-control" value="0"></div>
                </div>
                <div class="inline-form-actions">
                    <button class="btn btn-primary btn-sm" id="btnSaveDesign" data-i18n="theme_manager_settings.form.save">Save</button>
                    <button class="btn btn-secondary btn-sm" id="btnCancelDesign" data-i18n="theme_manager_settings.form.cancel">Cancel</button>
                </div>
            </div>
        </div>

        <!-- TAB: Color Settings -->
        <div class="tab-content" id="tab-colors" style="display:none">
            <div class="settings-header">
                <h3 data-i18n="theme_manager_settings.colors.title">Color Settings</h3>
                <button class="btn btn-sm btn-primary" id="btnAddColor">
                    <i class="fas fa-plus"></i> <span data-i18n="theme_manager_settings.colors.add">Add Color</span>
                </button>
            </div>
            <div id="colorSettingsList" class="settings-list"></div>
            <div id="colorForm" class="inline-form" style="display:none">
                <input type="hidden" id="colorId">
                <div class="form-row">
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.key">Key</label><input type="text" id="colorKey" class="form-control"></div>
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.name">Name</label><input type="text" id="colorName" class="form-control"></div>
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.color">Color</label><input type="color" id="colorValue" class="form-control"></div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label data-i18n="theme_manager_settings.form.category">Category</label>
                        <select id="colorCategory" class="form-control">
                            <option value="primary"    data-i18n="theme_manager_settings.categories.primary">Primary</option>
                            <option value="secondary"  data-i18n="theme_manager_settings.categories.secondary">Secondary</option>
                            <option value="accent"     data-i18n="theme_manager_settings.categories.accent">Accent</option>
                            <option value="background" data-i18n="theme_manager_settings.categories.background">Background</option>
                            <option value="text"       data-i18n="theme_manager_settings.categories.text">Text</option>
                            <option value="border"     data-i18n="theme_manager_settings.categories.border">Border</option>
                            <option value="status"     data-i18n="theme_manager_settings.categories.status">Status</option>
                            <option value="header"     data-i18n="theme_manager_settings.categories.header">Header</option>
                            <option value="footer"     data-i18n="theme_manager_settings.categories.footer">Footer</option>
                            <option value="other"      data-i18n="theme_manager_settings.categories.other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label data-i18n="theme_manager_settings.form.active">Active</label>
                        <select id="colorIsActive" class="form-control">
                            <option value="1" data-i18n="theme_manager_settings.form.yes">Yes</option>
                            <option value="0" data-i18n="theme_manager_settings.form.no">No</option>
                        </select>
                    </div>
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.sort_order">Sort Order</label><input type="number" id="colorSortOrder" class="form-control" value="0"></div>
                </div>
                <div class="inline-form-actions">
                    <button class="btn btn-primary btn-sm" id="btnSaveColor" data-i18n="theme_manager_settings.form.save">Save</button>
                    <button class="btn btn-secondary btn-sm" id="btnCancelColor" data-i18n="theme_manager_settings.form.cancel">Cancel</button>
                </div>
            </div>
        </div>

        <!-- TAB: Font Settings -->
        <div class="tab-content" id="tab-fonts" style="display:none">
            <div class="settings-header">
                <h3 data-i18n="theme_manager_settings.fonts.title">Font Settings</h3>
                <button class="btn btn-sm btn-primary" id="btnAddFont">
                    <i class="fas fa-plus"></i> <span data-i18n="theme_manager_settings.fonts.add">Add Font</span>
                </button>
            </div>
            <div id="fontSettingsList" class="settings-list"></div>
            <div id="fontForm" class="inline-form" style="display:none">
                <input type="hidden" id="fontId">
                <div class="form-row">
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.key">Key</label><input type="text" id="fontKey" class="form-control"></div>
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.name">Name</label><input type="text" id="fontName" class="form-control"></div>
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.font_family">Font Family</label><input type="text" id="fontFamily" class="form-control"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.size">Size</label><input type="text" id="fontSize" class="form-control" placeholder="16px"></div>
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.weight">Weight</label><input type="text" id="fontWeight" class="form-control" placeholder="400"></div>
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.line_height">Line Height</label><input type="text" id="fontLineHeight" class="form-control" placeholder="1.5"></div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label data-i18n="theme_manager_settings.form.category">Category</label>
                        <select id="fontCategory" class="form-control">
                            <option value="heading"    data-i18n="theme_manager_settings.categories.heading">Heading</option>
                            <option value="body"       data-i18n="theme_manager_settings.categories.body">Body</option>
                            <option value="button"     data-i18n="theme_manager_settings.categories.button">Button</option>
                            <option value="navigation" data-i18n="theme_manager_settings.categories.navigation">Navigation</option>
                            <option value="other"      data-i18n="theme_manager_settings.categories.other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label data-i18n="theme_manager_settings.form.active">Active</label>
                        <select id="fontIsActive" class="form-control">
                            <option value="1" data-i18n="theme_manager_settings.form.yes">Yes</option>
                            <option value="0" data-i18n="theme_manager_settings.form.no">No</option>
                        </select>
                    </div>
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.sort_order">Sort Order</label><input type="number" id="fontSortOrder" class="form-control" value="0"></div>
                </div>
                <div class="inline-form-actions">
                    <button class="btn btn-primary btn-sm" id="btnSaveFont" data-i18n="theme_manager_settings.form.save">Save</button>
                    <button class="btn btn-secondary btn-sm" id="btnCancelFont" data-i18n="theme_manager_settings.form.cancel">Cancel</button>
                </div>
            </div>
        </div>

        <!-- TAB: Button Styles -->
        <div class="tab-content" id="tab-buttons" style="display:none">
            <div class="settings-header">
                <h3 data-i18n="theme_manager_settings.buttons.title">Button Styles</h3>
                <button class="btn btn-sm btn-primary" id="btnAddButton">
                    <i class="fas fa-plus"></i> <span data-i18n="theme_manager_settings.buttons.add">Add Button Style</span>
                </button>
            </div>
            <div id="buttonStylesList" class="settings-list"></div>
            <div id="buttonForm" class="inline-form" style="display:none">
                <input type="hidden" id="buttonId">
                <div class="form-row">
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.name">Name</label><input type="text" id="buttonName" class="form-control"></div>
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.slug">Slug</label><input type="text" id="buttonSlug" class="form-control"></div>
                    <div class="form-group">
                        <label data-i18n="theme_manager_settings.form.type">Type</label>
                        <select id="buttonType" class="form-control">
                            <option value="primary"   data-i18n="theme_manager_settings.button_types.primary">Primary</option>
                            <option value="secondary" data-i18n="theme_manager_settings.button_types.secondary">Secondary</option>
                            <option value="success"   data-i18n="theme_manager_settings.button_types.success">Success</option>
                            <option value="danger"    data-i18n="theme_manager_settings.button_types.danger">Danger</option>
                            <option value="warning"   data-i18n="theme_manager_settings.button_types.warning">Warning</option>
                            <option value="info"      data-i18n="theme_manager_settings.button_types.info">Info</option>
                            <option value="outline"   data-i18n="theme_manager_settings.button_types.outline">Outline</option>
                            <option value="link"      data-i18n="theme_manager_settings.button_types.link">Link</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.bg_color">BG Color</label><input type="color" id="buttonBgColor" class="form-control" value="#007bff"></div>
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.text_color">Text Color</label><input type="color" id="buttonTextColor" class="form-control" value="#ffffff"></div>
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.border_color">Border Color</label><input type="color" id="buttonBorderColor" class="form-control"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.border_width">Border Width</label><input type="number" id="buttonBorderWidth" class="form-control" value="0"></div>
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.border_radius">Border Radius</label><input type="number" id="buttonBorderRadius" class="form-control" value="4"></div>
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.padding">Padding</label><input type="text" id="buttonPadding" class="form-control" value="10px 20px"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.font_size">Font Size</label><input type="text" id="buttonFontSize" class="form-control" value="14px"></div>
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.font_weight">Font Weight</label><input type="text" id="buttonFontWeight" class="form-control" value="normal"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.hover_bg">Hover BG</label><input type="color" id="buttonHoverBg" class="form-control"></div>
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.hover_text">Hover Text</label><input type="color" id="buttonHoverText" class="form-control"></div>
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.hover_border">Hover Border</label><input type="color" id="buttonHoverBorder" class="form-control"></div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label data-i18n="theme_manager_settings.form.active">Active</label>
                        <select id="buttonIsActive" class="form-control">
                            <option value="1" data-i18n="theme_manager_settings.form.yes">Yes</option>
                            <option value="0" data-i18n="theme_manager_settings.form.no">No</option>
                        </select>
                    </div>
                </div>
                <div class="inline-form-actions">
                    <button class="btn btn-primary btn-sm" id="btnSaveButton" data-i18n="theme_manager_settings.form.save">Save</button>
                    <button class="btn btn-secondary btn-sm" id="btnCancelButton" data-i18n="theme_manager_settings.form.cancel">Cancel</button>
                </div>
            </div>
        </div>

        <!-- TAB: Card Styles -->
        <div class="tab-content" id="tab-cards" style="display:none">
            <div class="settings-header">
                <h3 data-i18n="theme_manager_settings.cards.title">Card Styles</h3>
                <button class="btn btn-sm btn-primary" id="btnAddCard">
                    <i class="fas fa-plus"></i> <span data-i18n="theme_manager_settings.cards.add">Add Card Style</span>
                </button>
            </div>
            <div id="cardStylesList" class="settings-list"></div>
            <div id="cardForm" class="inline-form" style="display:none">
                <input type="hidden" id="cardId">
                <div class="form-row">
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.name">Name</label><input type="text" id="cardName" class="form-control"></div>
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.slug">Slug</label><input type="text" id="cardSlug" class="form-control"></div>
                    <div class="form-group">
                        <label data-i18n="theme_manager_settings.form.type">Type</label>
                        <select id="cardType" class="form-control">
                            <option value="product"      data-i18n="theme_manager_settings.card_types.product">Product</option>
                            <option value="category"     data-i18n="theme_manager_settings.card_types.category">Category</option>
                            <option value="entity"       data-i18n="theme_manager_settings.card_types.entity">Entity</option>
                            <option value="entities"     data-i18n="theme_manager_settings.card_types.entities">Entities</option>
                            <option value="tenant"       data-i18n="theme_manager_settings.card_types.tenant">Tenant</option>
                            <option value="tenants"      data-i18n="theme_manager_settings.card_types.tenants">Tenants</option>
                            <option value="vendor"       data-i18n="theme_manager_settings.card_types.vendor">Vendor</option>
                            <option value="blog"         data-i18n="theme_manager_settings.card_types.blog">Blog</option>
                            <option value="feature"      data-i18n="theme_manager_settings.card_types.feature">Feature</option>
                            <option value="testimonial"  data-i18n="theme_manager_settings.card_types.testimonial">Testimonial</option>
                            <option value="auction"      data-i18n="theme_manager_settings.card_types.auction">Auction</option>
                            <option value="notification" data-i18n="theme_manager_settings.card_types.notification">Notification</option>
                            <option value="discount"     data-i18n="theme_manager_settings.card_types.discount">Discount</option>
                            <option value="jobs"         data-i18n="theme_manager_settings.card_types.jobs">Jobs</option>
                            <option value="plan"         data-i18n="theme_manager_settings.card_types.plan">Plan</option>
                            <option value="other"        data-i18n="theme_manager_settings.card_types.other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.bg_color">BG Color</label><input type="color" id="cardBgColor" class="form-control" value="#FFFFFF"></div>
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.border_color">Border Color</label><input type="color" id="cardBorderColor" class="form-control" value="#E0E0E0"></div>
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.border_width">Border Width</label><input type="number" id="cardBorderWidth" class="form-control" value="1"></div>
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.border_radius">Border Radius</label><input type="number" id="cardBorderRadius" class="form-control" value="8"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.shadow">Shadow</label><input type="text" id="cardShadow" class="form-control" value="none" placeholder="0 2px 4px rgba(0,0,0,.1)"></div>
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.padding">Padding</label><input type="text" id="cardPadding" class="form-control" value="16px"></div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label data-i18n="theme_manager_settings.form.hover_effect">Hover Effect</label>
                        <select id="cardHoverEffect" class="form-control">
                            <option value="none"       data-i18n="theme_manager_settings.hover_effects.none">None</option>
                            <option value="lift"       data-i18n="theme_manager_settings.hover_effects.lift">Lift</option>
                            <option value="zoom"       data-i18n="theme_manager_settings.hover_effects.zoom">Zoom</option>
                            <option value="shadow"     data-i18n="theme_manager_settings.hover_effects.shadow">Shadow</option>
                            <option value="border"     data-i18n="theme_manager_settings.hover_effects.border">Border</option>
                            <option value="brightness" data-i18n="theme_manager_settings.hover_effects.brightness">Brightness</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label data-i18n="theme_manager_settings.form.text_align">Text Align</label>
                        <select id="cardTextAlign" class="form-control">
                            <option value="left"   data-i18n="theme_manager_settings.text_aligns.left">Left</option>
                            <option value="center" data-i18n="theme_manager_settings.text_aligns.center">Center</option>
                            <option value="right"  data-i18n="theme_manager_settings.text_aligns.right">Right</option>
                        </select>
                    </div>
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.image_ratio">Image Ratio</label><input type="text" id="cardImageRatio" class="form-control" value="1:1"></div>
                    <div class="form-group">
                        <label data-i18n="theme_manager_settings.form.active">Active</label>
                        <select id="cardIsActive" class="form-control">
                            <option value="1" data-i18n="theme_manager_settings.form.yes">Yes</option>
                            <option value="0" data-i18n="theme_manager_settings.form.no">No</option>
                        </select>
                    </div>
                </div>
                <div class="inline-form-actions">
                    <button class="btn btn-primary btn-sm" id="btnSaveCard" data-i18n="theme_manager_settings.form.save">Save</button>
                    <button class="btn btn-secondary btn-sm" id="btnCancelCard" data-i18n="theme_manager_settings.form.cancel">Cancel</button>
                </div>
            </div>
        </div>

        <!-- TAB: Homepage Sections -->
        <div class="tab-content" id="tab-homepage" style="display:none">
            <div class="settings-header">
                <h3 data-i18n="theme_manager_settings.homepage.title">Homepage Sections</h3>
                <button class="btn btn-sm btn-primary" id="btnAddSection">
                    <i class="fas fa-plus"></i> <span data-i18n="theme_manager_settings.homepage.add">Add Section</span>
                </button>
            </div>
            <div id="homepageSectionsList" class="settings-list"></div>
            <div id="sectionForm" class="inline-form" style="display:none">
                <input type="hidden" id="sectionId">
                <div class="form-row">
                    <div class="form-group">
                        <label data-i18n="theme_manager_settings.form.section_type">Section Type</label>
                        <select id="sectionType" class="form-control">
                            <option value="slider"            data-i18n="theme_manager_settings.section_types.slider">Slider</option>
                            <option value="categories"        data-i18n="theme_manager_settings.section_types.categories">Categories</option>
                            <option value="featured_products" data-i18n="theme_manager_settings.section_types.featured_products">Featured Products</option>
                            <option value="new_products"      data-i18n="theme_manager_settings.section_types.new_products">New Products</option>
                            <option value="deals"             data-i18n="theme_manager_settings.section_types.deals">Deals</option>
                            <option value="brands"            data-i18n="theme_manager_settings.section_types.brands">Brands</option>
                            <option value="vendors"           data-i18n="theme_manager_settings.section_types.vendors">Vendors</option>
                            <option value="banners"           data-i18n="theme_manager_settings.section_types.banners">Banners</option>
                            <option value="testimonials"      data-i18n="theme_manager_settings.section_types.testimonials">Testimonials</option>
                            <option value="custom_html"       data-i18n="theme_manager_settings.section_types.custom_html">Custom HTML</option>
                            <option value="other"             data-i18n="theme_manager_settings.section_types.other">Other</option>
                        </select>
                    </div>
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.title">Title</label><input type="text" id="sectionTitle" class="form-control"></div>
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.subtitle">Subtitle</label><input type="text" id="sectionSubtitle" class="form-control"></div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label data-i18n="theme_manager_settings.form.layout">Layout</label>
                        <select id="sectionLayout" class="form-control">
                            <option value="grid"     data-i18n="theme_manager_settings.layouts.grid">Grid</option>
                            <option value="slider"   data-i18n="theme_manager_settings.layouts.slider">Slider</option>
                            <option value="list"     data-i18n="theme_manager_settings.layouts.list">List</option>
                            <option value="carousel" data-i18n="theme_manager_settings.layouts.carousel">Carousel</option>
                            <option value="masonry"  data-i18n="theme_manager_settings.layouts.masonry">Masonry</option>
                        </select>
                    </div>
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.items_per_row">Items/Row</label><input type="number" id="sectionItemsPerRow" class="form-control" value="4"></div>
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.sort_order">Sort Order</label><input type="number" id="sectionSortOrder" class="form-control" value="0"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.bg_color">BG Color</label><input type="color" id="sectionBgColor" class="form-control" value="#FFFFFF"></div>
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.text_color">Text Color</label><input type="color" id="sectionTextColor" class="form-control" value="#000000"></div>
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.padding">Padding</label><input type="text" id="sectionPadding" class="form-control" value="40px 0"></div>
                </div>
                <div class="form-group"><label data-i18n="theme_manager_settings.form.data_source">Data Source</label><input type="text" id="sectionDataSource" class="form-control" placeholder="e.g. /api/products?is_featured=1"></div>
                <div class="form-group"><label data-i18n="theme_manager_settings.form.custom_css">Custom CSS</label><textarea id="sectionCustomCss" class="form-control" rows="2"></textarea></div>
                <div class="form-group"><label data-i18n="theme_manager_settings.form.custom_html">Custom HTML</label><textarea id="sectionCustomHtml" class="form-control" rows="3"></textarea></div>
                <div class="form-group">
                    <label><input type="checkbox" id="sectionIsActive" checked> <span data-i18n="theme_manager_settings.form.active">Active</span></label>
                </div>
                <div class="inline-form-actions">
                    <button class="btn btn-primary btn-sm" id="btnSaveSection" data-i18n="theme_manager_settings.form.save">Save</button>
                    <button class="btn btn-secondary btn-sm" id="btnCancelSection" data-i18n="theme_manager_settings.form.cancel">Cancel</button>
                </div>
            </div>
        </div>

        <!-- TAB: System Settings -->
        <div class="tab-content" id="tab-system" style="display:none">
            <div class="settings-header">
                <h3 data-i18n="theme_manager_settings.system.title">System Settings</h3>
                <button class="btn btn-sm btn-primary" id="btnAddSystem">
                    <i class="fas fa-plus"></i> <span data-i18n="theme_manager_settings.system.add">Add Setting</span>
                </button>
            </div>
            <div id="systemSettingsList" class="settings-list"></div>
            <div id="systemForm" class="inline-form" style="display:none">
                <input type="hidden" id="systemId">
                <div class="form-row">
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.key">Key</label><input type="text" id="systemKey" class="form-control"></div>
                    <div class="form-group"><label data-i18n="theme_manager_settings.form.category">Category</label><input type="text" id="systemCategory" class="form-control"></div>
                    <div class="form-group">
                        <label data-i18n="theme_manager_settings.form.type">Type</label>
                        <select id="systemType" class="form-control">
                            <option value="text"    data-i18n="theme_manager_settings.types.text">Text</option>
                            <option value="number"  data-i18n="theme_manager_settings.types.number">Number</option>
                            <option value="boolean" data-i18n="theme_manager_settings.types.boolean">Boolean</option>
                            <option value="json"    data-i18n="theme_manager_settings.types.json">JSON</option>
                            <option value="file"    data-i18n="theme_manager_settings.types.file">File</option>
                            <option value="email"   data-i18n="theme_manager_settings.types.email">Email</option>
                        </select>
                    </div>
                </div>
                <div class="form-group"><label data-i18n="theme_manager_settings.form.value">Value</label><textarea id="systemValue" class="form-control" rows="2"></textarea></div>
                <div class="form-group"><label data-i18n="theme_manager_settings.form.description">Description</label><textarea id="systemDescription" class="form-control" rows="2"></textarea></div>
                <div class="form-row">
                    <div class="form-group">
                        <label data-i18n="theme_manager_settings.form.public">Public</label>
                        <select id="systemIsPublic" class="form-control">
                            <option value="0" data-i18n="theme_manager_settings.form.no">No</option>
                            <option value="1" data-i18n="theme_manager_settings.form.yes">Yes</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label data-i18n="theme_manager_settings.form.editable">Editable</label>
                        <select id="systemIsEditable" class="form-control">
                            <option value="1" data-i18n="theme_manager_settings.form.yes">Yes</option>
                            <option value="0" data-i18n="theme_manager_settings.form.no">No</option>
                        </select>
                    </div>
                </div>
                <div class="inline-form-actions">
                    <button class="btn btn-primary btn-sm" id="btnSaveSystem" data-i18n="theme_manager_settings.form.save">Save</button>
                    <button class="btn btn-secondary btn-sm" id="btnCancelSystem" data-i18n="theme_manager_settings.form.cancel">Cancel</button>
                </div>
            </div>
        </div>

        <!-- TAB: Translations -->
        <div class="tab-content" id="tab-translations" style="display:none">
            <div class="settings-header">
                <h3 data-i18n="theme_manager_settings.translations.title">Translations</h3>
            </div>
            <div style="background:rgba(59,130,246,0.08); border:1px solid rgba(59,130,246,0.25); border-radius:8px; padding:12px; margin-bottom:16px; font-size:0.85rem; color:var(--text-secondary,#94a3b8);">
                <i class="fas fa-info-circle" style="color:#3b82f6;"></i>
                Add translated <strong style="color:var(--text-primary,#fff);">name</strong> and <strong style="color:var(--text-primary,#fff);">description</strong> for any language.
            </div>
            <div id="themeTranslationPanels" class="translation-panels"></div>
            <div class="form-group" style="margin-top:12px;">
                <label for="themeLangSelect" data-i18n="theme_manager_settings.translations.select_lang">Select Language</label>
                <div style="display:flex; gap:8px; align-items:flex-end;">
                    <select id="themeLangSelect" class="form-control" style="flex:1;">
                        <option value="" data-i18n="theme_manager_settings.translations.choose_lang">Choose language</option>
                    </select>
                    <button type="button" id="btnAddThemeTranslation" class="btn btn-primary">
                        <i class="fas fa-plus"></i> <span data-i18n="theme_manager_settings.translations.add_btn">Add Translation</span>
                    </button>
                </div>
            </div>
        </div>

    </div><!-- end themeFormView -->

</div><!-- end themes-page -->

<!-- ═══════════ INIT SCRIPTS ═══════════ -->
<script src="/admin/assets/js/themes-system.js?v=<?= time() ?>"></script>
/////////////////////////////////////////////////////////////////////////////////



/**
 * themes-system.js
 * Theme management - Products-pattern IIFE module
 * List → Form with tabs (Info, Design, Colors, Fonts, Buttons, Cards, Homepage)
 */
(function() {
    'use strict';

    // ════════════════════════════════════════
    // CONFIG & STATE
    // ════════════════════════════════════════
    const CFG = (typeof THEMES_CONFIG !== 'undefined') ? THEMES_CONFIG : {};
    const API = CFG.API || {};
    const TENANT_ID = CFG.TENANT_ID || 1;
    const LANG = CFG.LANG || 'en';

    const state = {
        themes: [],
        editingThemeId: null,
        i18n: {},
        // Related data for current theme
        designSettings: [],
        colorSettings: [],
        fontSettings: [],
        buttonStyles: [],
        cardStyles: [],
        homepageSections: [],
        systemSettings: []
    };

    // DOM elements cache
    const el = {};

    // ════════════════════════════════════════
    // INIT
    // ════════════════════════════════════════
    function init() {
        console.log('[ThemesSystem] init()');
        cacheElements();
        bindEvents();
        loadI18n();
        loadThemes();
    }

    function cacheElements() {
        const $ = id => document.getElementById(id);
        el.alertsContainer = $('alertsContainer');
        // List view
        el.listView = $('themesListView');
        el.loading = $('themesLoading');
        el.tableContainer = $('themesTableContainer');
        el.tableBody = $('themesTableBody');
        el.empty = $('themesEmpty');
        el.search = $('themeSearch');
        el.statusFilter = $('themeStatusFilter');
        el.btnAdd = $('btnAddTheme');
        // Form view
        el.formView = $('themeFormView');
        el.formTitle = $('formTitle');
        el.btnCancel = $('btnCancelForm');
        el.btnSave = $('btnSaveTheme');
        el.btnDelete = $('btnDeleteTheme');
        // Theme fields
        el.themeId = $('themeId');
        el.themeName = $('themeName');
        el.themeSlug = $('themeSlug');
        el.themeDescription = $('themeDescription');
        el.themeVersion = $('themeVersion');
        el.themeAuthor = $('themeAuthor');
        el.themeThumbnailUrl = $('themeThumbnailUrl');
        el.themePreviewUrl = $('themePreviewUrl');
        el.themeIsActive = $('themeIsActive');
        el.themeIsDefault = $('themeIsDefault');
        // Settings lists
        el.designSettingsList = $('designSettingsList');
        el.colorSettingsList = $('colorSettingsList');
        el.fontSettingsList = $('fontSettingsList');
        el.buttonStylesList = $('buttonStylesList');
        el.cardStylesList = $('cardStylesList');
        el.homepageSectionsList = $('homepageSectionsList');
        el.systemSettingsList = $('systemSettingsList');
    }

    function bindEvents() {
        // List view
        if (el.btnAdd) el.btnAdd.onclick = () => showForm();
        if (el.search) el.search.oninput = filterThemes;
        if (el.statusFilter) el.statusFilter.onchange = filterThemes;
        // Form view
        if (el.btnCancel) el.btnCancel.onclick = hideForm;
        if (el.btnSave) el.btnSave.onclick = saveTheme;
        if (el.btnDelete) el.btnDelete.onclick = deleteTheme;
        // Form tabs
        document.querySelectorAll('.themes-page .form-tab').forEach(tab => {
            tab.onclick = function() {
                const target = this.getAttribute('data-tab');
                document.querySelectorAll('.themes-page .form-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                document.querySelectorAll('.themes-page .tab-content').forEach(c => {
                    c.style.display = 'none';
                    c.classList.remove('active');
                });
                const tabEl = document.getElementById('tab-' + target);
                if (tabEl) {
                    tabEl.style.display = 'block';
                    tabEl.classList.add('active');
                }
            };
        });
        // Settings add/save/cancel buttons
        bindSettingsButtons('Design', 'design');
        bindSettingsButtons('Color', 'color');
        bindSettingsButtons('Font', 'font');
        bindSettingsButtons('Button', 'button');
        bindSettingsButtons('Card', 'card');
        bindSettingsButtons('Section', 'section');
        bindSettingsButtons('System', 'system');
    }

    function bindSettingsButtons(name, prefix) {
        const btnAdd = document.getElementById('btnAdd' + name);
        const btnSave = document.getElementById('btnSave' + name);
        const btnCancel = document.getElementById('btnCancel' + name);
        const form = document.getElementById(prefix + 'Form');
        if (btnAdd) btnAdd.onclick = () => {
            resetSettingForm(prefix);
            if (form) form.style.display = 'block';
        };
        if (btnCancel) btnCancel.onclick = () => {
            if (form) form.style.display = 'none';
        };
        if (btnSave) btnSave.onclick = () => saveSetting(prefix, btnSave);
    }

    // ════════════════════════════════════════
    // i18n
    // ════════════════════════════════════════
    async function loadI18n() {
        try {
            const res = await fetch('/languages/AdminUiTheme/' + LANG + '.json');
            if (res.ok) {
                state.i18n = await res.json();
                applyI18n();
            }
        } catch (e) {
            console.warn('[ThemesSystem] i18n load failed:', e);
        }
    }

    function t(key, fallback) {
        const parts = key.split('.');
        let val = state.i18n;
        for (const p of parts) {
            if (val && typeof val === 'object' && p in val) {
                val = val[p];
            } else {
                return fallback || key;
            }
        }
        return (typeof val === 'string') ? val : (fallback || key);
    }

    function applyI18n() {
        document.querySelectorAll('.themes-page [data-i18n]').forEach(el => {
            const key = el.getAttribute('data-i18n');
            const translated = t(key);
            if (translated !== key) el.textContent = translated;
        });
        document.querySelectorAll('.themes-page [data-i18n-placeholder]').forEach(el => {
            const key = el.getAttribute('data-i18n-placeholder');
            const translated = t(key);
            if (translated !== key) el.placeholder = translated;
        });
    }

    // ════════════════════════════════════════
    // THEMES LIST
    // ════════════════════════════════════════
    async function loadThemes() {
        showLoading(true);
        try {
            const url = API.themes + '?tenant_id=' + TENANT_ID + '&format=json';
            const res = await fetch(url);
            const json = await res.json();
            state.themes = extractItems(json);
            renderThemes(state.themes);
        } catch (e) {
            console.error('[ThemesSystem] loadThemes error:', e);
            showAlert('error', t('theme_manager.messages.error.load_failed', 'Failed to load themes'));
        } finally {
            showLoading(false);
        }
    }

    function extractItems(json) {
        if (!json) return [];
        if (json.success && json.data) {
            if (Array.isArray(json.data)) return json.data;
            if (json.data.items && Array.isArray(json.data.items)) return json.data.items;
            if (json.data.data && Array.isArray(json.data.data)) return json.data.data;
        }
        if (Array.isArray(json)) return json;
        return [];
    }

    function renderThemes(themes) {
        if (!el.tableBody) return;
        if (!themes || themes.length === 0) {
            if (el.tableContainer) el.tableContainer.style.display = 'none';
            if (el.empty) el.empty.style.display = 'flex';
            return;
        }
        if (el.tableContainer) el.tableContainer.style.display = 'block';
        if (el.empty) el.empty.style.display = 'none';

        el.tableBody.innerHTML = themes.map(th => {
            const statusClass = th.is_active ? 'badge-success' : 'badge-secondary';
            const statusText = th.is_active ? t('theme_manager.status.active', 'Active') : t('theme_manager.status.inactive', 'Inactive');
            const defaultBadge = th.is_default ? '<span class="badge badge-primary">' + t('theme_manager.status.default', 'Default') + '</span>' : '';
            const name = escapeHtml(th.name || '');
            const slug = escapeHtml(th.slug || '');
            return '<tr>' +
                '<td>' + th.id + '</td>' +
                '<td><strong>' + name + '</strong></td>' +
                '<td><code>' + slug + '</code></td>' +
                '<td>' + escapeHtml(th.version || '1.0.0') + '</td>' +
                '<td><span class="badge ' + statusClass + '">' + statusText + '</span></td>' +
                '<td>' + defaultBadge + '</td>' +
                '<td class="actions-cell">' +
                    '<button class="btn btn-sm btn-primary" onclick="ThemesSystem.editTheme(' + th.id + ')">' +
                        '<i class="fas fa-edit"></i> ' + t('theme_manager.table.actions.edit', 'Edit') +
                    '</button> ' +
                    '<button class="btn btn-sm btn-danger" onclick="ThemesSystem.removeTheme(' + th.id + ')">' +
                        '<i class="fas fa-trash"></i> ' + t('theme_manager.table.actions.delete', 'Delete') +
                    '</button>' +
                '</td>' +
            '</tr>';
        }).join('');
    }

    function filterThemes() {
        let filtered = state.themes;
        const search = (el.search && el.search.value || '').toLowerCase();
        const status = el.statusFilter ? el.statusFilter.value : '';
        if (search) {
            filtered = filtered.filter(th =>
                (th.name || '').toLowerCase().includes(search) ||
                (th.slug || '').toLowerCase().includes(search)
            );
        }
        if (status !== '') {
            filtered = filtered.filter(th => String(th.is_active) === status);
        }
        renderThemes(filtered);
    }

    // ════════════════════════════════════════
    // FORM: SHOW / HIDE
    // ════════════════════════════════════════
    function showForm(themeId) {
        state.editingThemeId = themeId || null;
        // Reset form
        const form = document.getElementById('themeForm');
        if (form) form.reset();
        if (el.themeId) el.themeId.value = '';
        if (el.themeVersion) el.themeVersion.value = '1.0.0';
        if (el.themeIsActive) el.themeIsActive.value = '1';
        if (el.themeIsDefault) el.themeIsDefault.checked = false;

        // Reset tabs to first
        document.querySelectorAll('.themes-page .form-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.themes-page .tab-content').forEach(c => {
            c.style.display = 'none';
            c.classList.remove('active');
        });
        const firstTab = document.querySelector('.themes-page .form-tab');
        const infoTab = document.getElementById('tab-info');
        if (firstTab) firstTab.classList.add('active');
        if (infoTab) { infoTab.style.display = 'block'; infoTab.classList.add('active'); }

        // Clear settings lists
        clearAllSettingsLists();
        hideAllSettingForms();

        if (themeId) {
            // Edit mode
            if (el.formTitle) el.formTitle.textContent = t('theme_manager.form.edit_title', 'Edit Theme');
            if (el.btnDelete) el.btnDelete.style.display = 'inline-block';
            populateThemeForm(themeId);
            loadAllRelatedData(themeId);
        } else {
            // Add mode
            if (el.formTitle) el.formTitle.textContent = t('theme_manager.form.add_title', 'Add Theme');
            if (el.btnDelete) el.btnDelete.style.display = 'none';
        }

        // Show form, hide list
        if (el.listView) el.listView.style.display = 'none';
        if (el.formView) el.formView.style.display = 'block';
    }

    function hideForm() {
        if (el.formView) el.formView.style.display = 'none';
        if (el.listView) el.listView.style.display = 'block';
        state.editingThemeId = null;
    }

    function populateThemeForm(themeId) {
        const theme = state.themes.find(th => String(th.id) === String(themeId));
        if (!theme) return;
        if (el.themeId) el.themeId.value = theme.id;
        if (el.themeName) el.themeName.value = theme.name || '';
        if (el.themeSlug) el.themeSlug.value = theme.slug || '';
        if (el.themeDescription) el.themeDescription.value = theme.description || '';
        if (el.themeVersion) el.themeVersion.value = theme.version || '1.0.0';
        if (el.themeAuthor) el.themeAuthor.value = theme.author || '';
        if (el.themeThumbnailUrl) el.themeThumbnailUrl.value = theme.thumbnail_url || '';
        if (el.themePreviewUrl) el.themePreviewUrl.value = theme.preview_url || '';
        if (el.themeIsActive) el.themeIsActive.value = theme.is_active ? '1' : '0';
        if (el.themeIsDefault) el.themeIsDefault.checked = !!theme.is_default;
    }

    // ════════════════════════════════════════
    // SAVE / DELETE THEME
    // ════════════════════════════════════════
    let saveThemeInFlight = false;

    async function saveTheme() {
        if (saveThemeInFlight) { console.warn('[ThemesSystem] saveTheme already running, skipping'); return; }
        const name = (el.themeName && el.themeName.value || '').trim();
        let slug = (el.themeSlug && el.themeSlug.value || '').trim();
        if (!name) {
            showAlert('warning', t('theme_manager.form.fields.name.required', 'Theme name is required'));
            return;
        }
        if (!slug) {
            slug = name.toLowerCase().replace(/[^a-z0-9\u0600-\u06FF]+/g, '-').replace(/^-|-$/g, '');
            if (el.themeSlug) el.themeSlug.value = slug;
        }

        const themeId = el.themeId ? el.themeId.value : '';
        const isEdit = !!themeId;

        const payload = {
            tenant_id: TENANT_ID,
            name: name,
            slug: slug,
            description: (el.themeDescription && el.themeDescription.value || '').trim(),
            version: (el.themeVersion && el.themeVersion.value || '1.0.0').trim(),
            author: (el.themeAuthor && el.themeAuthor.value || '').trim(),
            thumbnail_url: (el.themeThumbnailUrl && el.themeThumbnailUrl.value || '').trim() || null,
            preview_url: (el.themePreviewUrl && el.themePreviewUrl.value || '').trim() || null,
            is_active: el.themeIsActive ? parseInt(el.themeIsActive.value) : 1,
            is_default: el.themeIsDefault ? (el.themeIsDefault.checked ? 1 : 0) : 0
        };

        if (isEdit) payload.id = parseInt(themeId);

        const btn = el.btnSave;
        saveThemeInFlight = true;
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ...'; }

        try {
            const url = isEdit ? API.themes + '?id=' + themeId : API.themes;
            const res = await fetch(url, {
                method: isEdit ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const json = await res.json();
            if (json.success) {
                showAlert('success', t('theme_manager.messages.success.save', 'Theme saved successfully'));
                await loadThemes();
                hideForm();
            } else {
                showAlert('error', json.message || t('theme_manager.messages.error.save_failed', 'Failed to save'));
            }
        } catch (e) {
            console.error('[ThemesSystem] saveTheme error:', e);
            showAlert('error', t('theme_manager.messages.error.save_failed', 'Failed to save'));
        } finally {
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> ' + t('theme_manager.form.buttons.save', 'Save'); }
            saveThemeInFlight = false;
        }
    }

    async function deleteTheme() {
        const themeId = el.themeId ? el.themeId.value : '';
        if (!themeId) return;
        if (!confirm(t('theme_manager.messages.confirm.delete', 'Are you sure you want to delete this theme?'))) return;

        try {
            const res = await fetch(API.themes + '?id=' + themeId, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' }
            });
            const json = await res.json();
            if (json.success) {
                showAlert('success', t('theme_manager.messages.success.delete', 'Theme deleted'));
                await loadThemes();
                hideForm();
            } else {
                showAlert('error', json.message || t('theme_manager.messages.error.delete_failed', 'Failed to delete'));
            }
        } catch (e) {
            console.error('[ThemesSystem] deleteTheme error:', e);
            showAlert('error', t('theme_manager.messages.error.delete_failed', 'Failed to delete'));
        }
    }

    async function removeTheme(themeId) {
        if (!confirm(t('theme_manager.messages.confirm.delete', 'Are you sure you want to delete this theme?'))) return;
        try {
            const res = await fetch(API.themes + '?id=' + themeId, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' }
            });
            const json = await res.json();
            if (json.success) {
                showAlert('success', t('theme_manager.messages.success.delete', 'Theme deleted'));
                await loadThemes();
            } else {
                showAlert('error', json.message || 'Failed to delete');
            }
        } catch (e) {
            showAlert('error', 'Failed to delete');
        }
    }

    // ════════════════════════════════════════
    // LOAD ALL RELATED DATA FOR A THEME
    // ════════════════════════════════════════
    async function loadAllRelatedData(themeId) {
        await Promise.all([
            loadSettings('design', API.designSettings, themeId),
            loadSettings('color', API.colorSettings, themeId),
            loadSettings('font', API.fontSettings, themeId),
            loadSettings('button', API.buttonStyles, themeId),
            loadSettings('card', API.cardStyles, themeId),
            loadSettings('section', API.homepageSections, themeId),
            loadSettings('system', API.systemSettings, themeId)
        ]);
    }

    async function loadSettings(type, apiUrl, themeId) {
        if (!apiUrl) return;
        try {
            const url = apiUrl + '?theme_id=' + themeId + '&tenant_id=' + TENANT_ID + '&format=json';
            const res = await fetch(url);
            const json = await res.json();
            const items = extractItems(json);
            state[type + 'Settings'] = items;
            renderSettingsList(type, items);
        } catch (e) {
            console.warn('[ThemesSystem] loadSettings(' + type + ') error:', e);
        }
    }

    // ════════════════════════════════════════
    // RENDER SETTINGS LISTS
    // ════════════════════════════════════════
    function getSettingsListEl(type) {
        const map = {
            design: el.designSettingsList,
            color: el.colorSettingsList,
            font: el.fontSettingsList,
            button: el.buttonStylesList,
            card: el.cardStylesList,
            section: el.homepageSectionsList,
            system: el.systemSettingsList
        };
        return map[type];
    }

    function renderSettingsList(type, items) {
        const listEl = getSettingsListEl(type);
        if (!listEl) return;

        if (!items || items.length === 0) {
            listEl.innerHTML = '<div class="empty-settings">' + t('theme_manager_settings.empty', 'No items found') + '</div>';
            return;
        }

        listEl.innerHTML = items.map(item => {
            const itemId = item.id;
            let display = '';

            if (type === 'design') {
                display = '<strong>' + escapeHtml(item.setting_name || item.setting_key || '') + '</strong>' +
                          ' <span class="badge badge-secondary">' + escapeHtml(item.setting_type || 'text') + '</span>' +
                          ' <span class="badge badge-info">' + escapeHtml(item.category || '') + '</span>' +
                          '<div class="setting-value">' + escapeHtml(String(item.setting_value || '').substring(0, 100)) + '</div>';
            } else if (type === 'color') {
                display = '<span class="color-swatch" style="background:' + escapeHtml(item.color_value || '#000') + '"></span> ' +
                          '<strong>' + escapeHtml(item.setting_name || item.setting_key || '') + '</strong>' +
                          ' <code>' + escapeHtml(item.color_value || '') + '</code>' +
                          ' <span class="badge badge-info">' + escapeHtml(item.category || '') + '</span>';
            } else if (type === 'font') {
                display = '<strong>' + escapeHtml(item.setting_name || item.setting_key || '') + '</strong>' +
                          ' <span style="font-family:' + escapeHtml(item.font_family || '') + '">' + escapeHtml(item.font_family || '') + '</span>' +
                          ' <span class="badge badge-info">' + escapeHtml(item.category || '') + '</span>';
            } else if (type === 'button') {
                display = '<strong>' + escapeHtml(item.name || '') + '</strong>' +
                          ' <span class="badge badge-secondary">' + escapeHtml(item.button_type || '') + '</span>' +
                          ' <span class="color-swatch" style="background:' + escapeHtml(item.background_color || '#007bff') + '"></span>' +
                          ' <span class="color-swatch" style="background:' + escapeHtml(item.text_color || '#fff') + ';border:1px solid #ccc"></span>';
            } else if (type === 'card') {
                display = '<strong>' + escapeHtml(item.name || '') + '</strong>' +
                          ' <span class="badge badge-secondary">' + escapeHtml(item.card_type || '') + '</span>' +
                          ' <span class="color-swatch" style="background:' + escapeHtml(item.background_color || '#fff') + ';border:1px solid #ccc"></span>';
            } else if (type === 'section') {
                display = '<strong>' + escapeHtml(item.title || item.section_type || '') + '</strong>' +
                          ' <span class="badge badge-secondary">' + escapeHtml(item.section_type || '') + '</span>' +
                          ' <span class="badge badge-info">' + escapeHtml(item.layout_type || '') + '</span>' +
                          (item.is_active ? ' <span class="badge badge-success">' + t('theme_manager.status.active', 'Active') + '</span>' : ' <span class="badge badge-secondary">' + t('theme_manager.status.inactive', 'Inactive') + '</span>');
            } else if (type === 'system') {
                display = '<strong>' + escapeHtml(item.setting_key || '') + '</strong>' +
                          ' <span class="badge badge-secondary">' + escapeHtml(item.setting_type || 'text') + '</span>' +
                          ' <span class="badge badge-info">' + escapeHtml(item.category || '') + '</span>' +
                          '<div class="setting-value">' + escapeHtml(String(item.setting_value || '').substring(0, 100)) + '</div>' +
                          (item.is_public ? ' <span class="badge badge-success">' + t('theme_manager_settings.form.public', 'Public') + '</span>' : '');
            }

            return '<div class="settings-item" data-id="' + itemId + '">' +
                '<div class="settings-item-content">' + display + '</div>' +
                '<div class="settings-item-actions">' +
                    '<button class="btn btn-xs btn-primary" onclick="ThemesSystem.editSetting(\'' + type + '\',' + itemId + ')">' +
                        '<i class="fas fa-edit"></i></button> ' +
                    '<button class="btn btn-xs btn-danger" onclick="ThemesSystem.deleteSetting(\'' + type + '\',' + itemId + ')">' +
                        '<i class="fas fa-trash"></i></button>' +
                '</div></div>';
        }).join('');
    }

    function clearAllSettingsLists() {
        ['design', 'color', 'font', 'button', 'card', 'section', 'system'].forEach(type => {
            const listEl = getSettingsListEl(type);
            if (listEl) listEl.innerHTML = '';
            state[type + 'Settings'] = [];
        });
    }

    function hideAllSettingForms() {
        ['design', 'color', 'font', 'button', 'card', 'section', 'system'].forEach(prefix => {
            const form = document.getElementById(prefix + 'Form');
            if (form) form.style.display = 'none';
        });
    }

    // ════════════════════════════════════════
    // SETTINGS CRUD
    // ════════════════════════════════════════
    function resetSettingForm(prefix) {
        const idField = document.getElementById(prefix + 'Id');
        if (idField) idField.value = '';

        // Reset all inputs in the form
        const form = document.getElementById(prefix + 'Form');
        if (form) {
            form.querySelectorAll('input:not([type=hidden]), textarea, select').forEach(f => {
                if (f.type === 'checkbox') f.checked = true;
                else if (f.type === 'color') f.value = f.defaultValue || '#000000';
                else if (f.type === 'number') f.value = f.defaultValue || '0';
                else if (f.tagName === 'SELECT') f.selectedIndex = 0;
                else f.value = '';
            });
        }
    }

    function getApiForType(type) {
        const map = {
            design: API.designSettings,
            color: API.colorSettings,
            font: API.fontSettings,
            button: API.buttonStyles,
            card: API.cardStyles,
            section: API.homepageSections,
            system: API.systemSettings
        };
        return map[type];
    }

    function collectSettingData(prefix) {
        const $ = id => document.getElementById(id);
        const themeId = el.themeId ? parseInt(el.themeId.value) : null;

        if (prefix === 'design') {
            return {
                theme_id: themeId,
                tenant_id: TENANT_ID,
                setting_key: ($(prefix + 'Key') && $(prefix + 'Key').value || '').trim(),
                setting_name: ($(prefix + 'Name') && $(prefix + 'Name').value || '').trim(),
                setting_value: ($(prefix + 'Value') && $(prefix + 'Value').value || '').trim(),
                setting_type: $('designType') ? $('designType').value : 'text',
                category: $('designCategory') ? $('designCategory').value : 'other',
                is_active: $('designIsActive') ? parseInt($('designIsActive').value) : 1,
                sort_order: $('designSortOrder') ? parseInt($('designSortOrder').value) || 0 : 0
            };
        } else if (prefix === 'color') {
            return {
                theme_id: themeId,
                tenant_id: TENANT_ID,
                setting_key: ($('colorKey') && $('colorKey').value || '').trim(),
                setting_name: ($('colorName') && $('colorName').value || '').trim(),
                color_value: $('colorValue') ? $('colorValue').value : '#000000',
                category: $('colorCategory') ? $('colorCategory').value : 'other',
                is_active: $('colorIsActive') ? parseInt($('colorIsActive').value) : 1,
                sort_order: $('colorSortOrder') ? parseInt($('colorSortOrder').value) || 0 : 0
            };
        } else if (prefix === 'font') {
            return {
                theme_id: themeId,
                tenant_id: TENANT_ID,
                setting_key: ($('fontKey') && $('fontKey').value || '').trim(),
                setting_name: ($('fontName') && $('fontName').value || '').trim(),
                font_family: ($('fontFamily') && $('fontFamily').value || '').trim(),
                font_size: ($('fontSize') && $('fontSize').value || '').trim() || null,
                font_weight: ($('fontWeight') && $('fontWeight').value || '').trim() || null,
                line_height: ($('fontLineHeight') && $('fontLineHeight').value || '').trim() || null,
                category: $('fontCategory') ? $('fontCategory').value : 'other',
                is_active: $('fontIsActive') ? parseInt($('fontIsActive').value) : 1,
                sort_order: $('fontSortOrder') ? parseInt($('fontSortOrder').value) || 0 : 0
            };
        } else if (prefix === 'button') {
            return {
                theme_id: themeId,
                tenant_id: TENANT_ID,
                name: ($('buttonName') && $('buttonName').value || '').trim(),
                slug: ($('buttonSlug') && $('buttonSlug').value || '').trim(),
                button_type: $('buttonType') ? $('buttonType').value : 'primary',
                background_color: $('buttonBgColor') ? $('buttonBgColor').value : '#007bff',
                text_color: $('buttonTextColor') ? $('buttonTextColor').value : '#ffffff',
                border_color: $('buttonBorderColor') ? $('buttonBorderColor').value : null,
                border_width: $('buttonBorderWidth') ? parseInt($('buttonBorderWidth').value) || 0 : 0,
                border_radius: $('buttonBorderRadius') ? parseInt($('buttonBorderRadius').value) || 4 : 4,
                padding: ($('buttonPadding') && $('buttonPadding').value || '10px 20px').trim(),
                font_size: ($('buttonFontSize') && $('buttonFontSize').value || '14px').trim(),
                font_weight: ($('buttonFontWeight') && $('buttonFontWeight').value || 'normal').trim(),
                hover_background_color: $('buttonHoverBg') ? $('buttonHoverBg').value : null,
                hover_text_color: $('buttonHoverText') ? $('buttonHoverText').value : null,
                hover_border_color: $('buttonHoverBorder') ? $('buttonHoverBorder').value : null,
                is_active: $('buttonIsActive') ? parseInt($('buttonIsActive').value) : 1
            };
        } else if (prefix === 'card') {
            return {
                theme_id: themeId,
                tenant_id: TENANT_ID,
                name: ($('cardName') && $('cardName').value || '').trim(),
                slug: ($('cardSlug') && $('cardSlug').value || '').trim(),
                card_type: $('cardType') ? $('cardType').value : 'product',
                background_color: $('cardBgColor') ? $('cardBgColor').value : '#FFFFFF',
                border_color: $('cardBorderColor') ? $('cardBorderColor').value : '#E0E0E0',
                border_width: $('cardBorderWidth') ? parseInt($('cardBorderWidth').value) || 1 : 1,
                border_radius: $('cardBorderRadius') ? parseInt($('cardBorderRadius').value) || 8 : 8,
                shadow_style: ($('cardShadow') && $('cardShadow').value || 'none').trim(),
                padding: ($('cardPadding') && $('cardPadding').value || '16px').trim(),
                hover_effect: $('cardHoverEffect') ? $('cardHoverEffect').value : 'none',
                text_align: $('cardTextAlign') ? $('cardTextAlign').value : 'left',
                image_aspect_ratio: ($('cardImageRatio') && $('cardImageRatio').value || '1:1').trim(),
                is_active: $('cardIsActive') ? parseInt($('cardIsActive').value) : 1
            };
        } else if (prefix === 'section') {
            return {
                theme_id: themeId,
                tenant_id: TENANT_ID,
                section_type: $('sectionType') ? $('sectionType').value : 'other',
                title: ($('sectionTitle') && $('sectionTitle').value || '').trim() || null,
                subtitle: ($('sectionSubtitle') && $('sectionSubtitle').value || '').trim() || null,
                layout_type: $('sectionLayout') ? $('sectionLayout').value : 'grid',
                items_per_row: $('sectionItemsPerRow') ? parseInt($('sectionItemsPerRow').value) || 4 : 4,
                background_color: $('sectionBgColor') ? $('sectionBgColor').value : '#FFFFFF',
                text_color: $('sectionTextColor') ? $('sectionTextColor').value : '#000000',
                padding: ($('sectionPadding') && $('sectionPadding').value || '40px 0').trim(),
                custom_css: ($('sectionCustomCss') && $('sectionCustomCss').value || '').trim() || null,
                custom_html: ($('sectionCustomHtml') && $('sectionCustomHtml').value || '').trim() || null,
                data_source: ($('sectionDataSource') && $('sectionDataSource').value || '').trim() || null,
                is_active: $('sectionIsActive') ? ($('sectionIsActive').checked ? 1 : 0) : 1,
                sort_order: $('sectionSortOrder') ? parseInt($('sectionSortOrder').value) || 0 : 0
            };
        } else if (prefix === 'system') {
            return {
                tenant_id: TENANT_ID,
                setting_key: ($('systemKey') && $('systemKey').value || '').trim(),
                setting_value: ($('systemValue') && $('systemValue').value || '').trim(),
                setting_type: $('systemType') ? $('systemType').value : 'text',
                category: ($('systemCategory') && $('systemCategory').value || '').trim(),
                description: ($('systemDescription') && $('systemDescription').value || '').trim() || null,
                is_public: $('systemIsPublic') ? parseInt($('systemIsPublic').value) : 0,
                is_editable: $('systemIsEditable') ? parseInt($('systemIsEditable').value) : 1
            };
        }
        return {};
    }

    function populateSettingForm(prefix, item) {
        const $ = id => document.getElementById(id);
        const idField = $(prefix + 'Id');
        if (idField) idField.value = item.id;

        if (prefix === 'design') {
            if ($('designKey')) $('designKey').value = item.setting_key || '';
            if ($('designName')) $('designName').value = item.setting_name || '';
            if ($('designValue')) $('designValue').value = item.setting_value || '';
            if ($('designType')) $('designType').value = item.setting_type || 'text';
            if ($('designCategory')) $('designCategory').value = item.category || 'other';
            if ($('designIsActive')) $('designIsActive').value = item.is_active != null ? String(item.is_active) : '1';
            if ($('designSortOrder')) $('designSortOrder').value = item.sort_order || 0;
        } else if (prefix === 'color') {
            if ($('colorKey')) $('colorKey').value = item.setting_key || '';
            if ($('colorName')) $('colorName').value = item.setting_name || '';
            if ($('colorValue')) $('colorValue').value = item.color_value || '#000000';
            if ($('colorCategory')) $('colorCategory').value = item.category || 'other';
            if ($('colorIsActive')) $('colorIsActive').value = item.is_active != null ? String(item.is_active) : '1';
            if ($('colorSortOrder')) $('colorSortOrder').value = item.sort_order || 0;
        } else if (prefix === 'font') {
            if ($('fontKey')) $('fontKey').value = item.setting_key || '';
            if ($('fontName')) $('fontName').value = item.setting_name || '';
            if ($('fontFamily')) $('fontFamily').value = item.font_family || '';
            if ($('fontSize')) $('fontSize').value = item.font_size || '';
            if ($('fontWeight')) $('fontWeight').value = item.font_weight || '';
            if ($('fontLineHeight')) $('fontLineHeight').value = item.line_height || '';
            if ($('fontCategory')) $('fontCategory').value = item.category || 'other';
            if ($('fontIsActive')) $('fontIsActive').value = item.is_active != null ? String(item.is_active) : '1';
            if ($('fontSortOrder')) $('fontSortOrder').value = item.sort_order || 0;
        } else if (prefix === 'button') {
            if ($('buttonName')) $('buttonName').value = item.name || '';
            if ($('buttonSlug')) $('buttonSlug').value = item.slug || '';
            if ($('buttonType')) $('buttonType').value = item.button_type || 'primary';
            if ($('buttonBgColor')) $('buttonBgColor').value = item.background_color || '#007bff';
            if ($('buttonTextColor')) $('buttonTextColor').value = item.text_color || '#ffffff';
            if ($('buttonBorderColor')) $('buttonBorderColor').value = item.border_color || '#000000';
            if ($('buttonBorderWidth')) $('buttonBorderWidth').value = item.border_width || 0;
            if ($('buttonBorderRadius')) $('buttonBorderRadius').value = item.border_radius || 4;
            if ($('buttonPadding')) $('buttonPadding').value = item.padding || '10px 20px';
            if ($('buttonFontSize')) $('buttonFontSize').value = item.font_size || '14px';
            if ($('buttonFontWeight')) $('buttonFontWeight').value = item.font_weight || 'normal';
            if ($('buttonHoverBg')) $('buttonHoverBg').value = item.hover_background_color || '#000000';
            if ($('buttonHoverText')) $('buttonHoverText').value = item.hover_text_color || '#000000';
            if ($('buttonHoverBorder')) $('buttonHoverBorder').value = item.hover_border_color || '#000000';
            if ($('buttonIsActive')) $('buttonIsActive').value = item.is_active != null ? String(item.is_active) : '1';
        } else if (prefix === 'card') {
            if ($('cardName')) $('cardName').value = item.name || '';
            if ($('cardSlug')) $('cardSlug').value = item.slug || '';
            // Derive card_type from slug prefix when the stored value is empty (legacy rows).
            // Only use the derived value if it matches a known allowed type.
            const knownCardTypes = ['product','category','vendor','blog','feature','testimonial',
                                    'auction','notification','discount','jobs','plan','other'];
            const derivedFromSlug = ((item.slug || '').split('-')[0] || '').toLowerCase();
            const cardTypeFallback = (item.card_type && item.card_type !== '')
                ? item.card_type
                : (knownCardTypes.includes(derivedFromSlug) ? derivedFromSlug : 'product');
            if ($('cardType')) $('cardType').value = cardTypeFallback;
            if ($('cardBgColor')) $('cardBgColor').value = item.background_color || '#FFFFFF';
            if ($('cardBorderColor')) $('cardBorderColor').value = item.border_color || '#E0E0E0';
            if ($('cardBorderWidth')) $('cardBorderWidth').value = item.border_width || 1;
            if ($('cardBorderRadius')) $('cardBorderRadius').value = item.border_radius || 8;
            if ($('cardShadow')) $('cardShadow').value = item.shadow_style || 'none';
            if ($('cardPadding')) $('cardPadding').value = item.padding || '16px';
            if ($('cardHoverEffect')) $('cardHoverEffect').value = item.hover_effect || 'none';
            if ($('cardTextAlign')) $('cardTextAlign').value = item.text_align || 'left';
            if ($('cardImageRatio')) $('cardImageRatio').value = item.image_aspect_ratio || '1:1';
            if ($('cardIsActive')) $('cardIsActive').value = item.is_active != null ? String(item.is_active) : '1';
        } else if (prefix === 'section') {
            if ($('sectionType')) $('sectionType').value = item.section_type || 'other';
            if ($('sectionTitle')) $('sectionTitle').value = item.title || '';
            if ($('sectionSubtitle')) $('sectionSubtitle').value = item.subtitle || '';
            if ($('sectionLayout')) $('sectionLayout').value = item.layout_type || 'grid';
            if ($('sectionItemsPerRow')) $('sectionItemsPerRow').value = item.items_per_row || 4;
            if ($('sectionSortOrder')) $('sectionSortOrder').value = item.sort_order || 0;
            if ($('sectionBgColor')) $('sectionBgColor').value = item.background_color || '#FFFFFF';
            if ($('sectionTextColor')) $('sectionTextColor').value = item.text_color || '#000000';
            if ($('sectionPadding')) $('sectionPadding').value = item.padding || '40px 0';
            if ($('sectionDataSource')) $('sectionDataSource').value = item.data_source || '';
            if ($('sectionCustomCss')) $('sectionCustomCss').value = item.custom_css || '';
            if ($('sectionCustomHtml')) $('sectionCustomHtml').value = item.custom_html || '';
            if ($('sectionIsActive')) $('sectionIsActive').checked = !!item.is_active;
        } else if (prefix === 'system') {
            if ($('systemKey')) $('systemKey').value = item.setting_key || '';
            if ($('systemValue')) $('systemValue').value = item.setting_value || '';
            if ($('systemType')) $('systemType').value = item.setting_type || 'text';
            if ($('systemCategory')) $('systemCategory').value = item.category || '';
            if ($('systemDescription')) $('systemDescription').value = item.description || '';
            if ($('systemIsPublic')) $('systemIsPublic').value = item.is_public != null ? String(item.is_public) : '0';
            if ($('systemIsEditable')) $('systemIsEditable').value = item.is_editable != null ? String(item.is_editable) : '1';
        }
    }

    let saveSettingInFlight = false;

    async function saveSetting(prefix, btn) {
        if (saveSettingInFlight) { console.warn('[ThemesSystem] saveSetting already running, skipping'); return; }
        const apiUrl = getApiForType(prefix);
        if (!apiUrl) return;

        const idField = document.getElementById(prefix + 'Id');
        const itemId = idField ? idField.value : '';
        const isEdit = !!itemId;
        const data = collectSettingData(prefix);

        if (isEdit) data.id = parseInt(itemId);

        saveSettingInFlight = true;
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ...'; }

        try {
            const url = isEdit ? apiUrl + '?id=' + itemId : apiUrl;
            const res = await fetch(url, {
                method: isEdit ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const json = await res.json();
            if (json.success) {
                showAlert('success', 'Saved successfully');
                const form = document.getElementById(prefix + 'Form');
                if (form) form.style.display = 'none';
                // Reload this settings list
                const themeId = el.themeId ? el.themeId.value : state.editingThemeId;
                if (themeId) await loadSettings(prefix, apiUrl, themeId);
            } else {
                showAlert('error', json.message || 'Failed to save');
            }
        } catch (e) {
            console.error('[ThemesSystem] saveSetting(' + prefix + ') error:', e);
            showAlert('error', 'Failed to save');
        } finally {
            if (btn) { btn.disabled = false; btn.innerHTML = 'Save'; }
            saveSettingInFlight = false;
        }
    }

    function editSetting(type, itemId) {
        const stateKey = type + 'Settings';
        const items = state[stateKey] || [];
        const item = items.find(i => String(i.id) === String(itemId));
        if (!item) return;

        resetSettingForm(type);
        populateSettingForm(type, item);
        const form = document.getElementById(type + 'Form');
        if (form) form.style.display = 'block';

        // Switch to the correct tab
        const tabMap = { design: 'design', color: 'colors', font: 'fonts', button: 'buttons', card: 'cards', section: 'homepage', system: 'system' };
        const tabName = tabMap[type];
        if (tabName) {
            document.querySelectorAll('.themes-page .form-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.themes-page .tab-content').forEach(c => {
                c.style.display = 'none';
                c.classList.remove('active');
            });
            const tabBtn = document.querySelector('.themes-page .form-tab[data-tab="' + tabName + '"]');
            const tabContent = document.getElementById('tab-' + tabName);
            if (tabBtn) tabBtn.classList.add('active');
            if (tabContent) { tabContent.style.display = 'block'; tabContent.classList.add('active'); }
        }
    }

    async function deleteSetting(type, itemId) {
        if (!confirm('Are you sure you want to delete this item?')) return;
        const apiUrl = getApiForType(type);
        if (!apiUrl) return;

        try {
            const res = await fetch(apiUrl + '?id=' + itemId, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' }
            });
            const json = await res.json();
            if (json.success) {
                showAlert('success', 'Deleted successfully');
                const themeId = el.themeId ? el.themeId.value : state.editingThemeId;
                if (themeId) await loadSettings(type, apiUrl, themeId);
            } else {
                showAlert('error', json.message || 'Failed to delete');
            }
        } catch (e) {
            showAlert('error', 'Failed to delete');
        }
    }

    // ════════════════════════════════════════
    // UTILITIES
    // ════════════════════════════════════════
    function showLoading(show) {
        if (el.loading) el.loading.style.display = show ? 'flex' : 'none';
        if (el.tableContainer && show) el.tableContainer.style.display = 'none';
        if (el.empty && show) el.empty.style.display = 'none';
    }

    function showAlert(type, message) {
        if (!el.alertsContainer) return;
        const alertClass = type === 'error' ? 'alert-danger' : type === 'warning' ? 'alert-warning' : 'alert-success';
        const alertEl = document.createElement('div');
        alertEl.className = 'alert ' + alertClass;
        alertEl.innerHTML = '<span>' + escapeHtml(message) + '</span>' +
                           '<button class="alert-close" onclick="this.parentElement.remove()">&times;</button>';
        el.alertsContainer.appendChild(alertEl);
        setTimeout(() => { if (alertEl.parentElement) alertEl.remove(); }, 5000);
    }

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ════════════════════════════════════════
    // PUBLIC API
    // ════════════════════════════════════════
    window.ThemesSystem = {
        init: init,
        editTheme: function(id) { showForm(id); },
        removeTheme: removeTheme,
        editSetting: editSetting,
        deleteSetting: deleteSetting
    };

    // Auto-init when script loads (same pattern as permissions-system.js)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();

<?php if (!$isFragment): ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<?php endif; ?> 

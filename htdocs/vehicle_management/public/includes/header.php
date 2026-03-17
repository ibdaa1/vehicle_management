<?php
/**
 * Shared Header Include
 * 
 * Provides: Auth check, theme loading from DB, unified header + sidebar.
 * Usage: include __DIR__ . '/includes/header.php';
 * 
 * Before including, set $pageTitle and $activePage variables:
 *   $pageTitle = 'لوحة التحكم';
 *   $activePage = 'dashboard';
 */

require_once __DIR__ . '/config.php';

// Start session for auth
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Load theme from DB
$theme = vm_get_theme();
$settings = vm_get_settings();

// Page variables (set by including page, or defaults)
$pageTitle   = $pageTitle   ?? ($settings['system_title_ar'] ?? 'نظام إدارة المركبات');
$activePage  = $activePage  ?? 'dashboard';
$requireAuth = $requireAuth ?? true;
$langKey     = $pageLangKey ?? $activePage;

// Site settings
$siteNameAr   = $settings['site_name_ar']         ?? 'بلدية مدينة الشارقة';
$siteNameEn   = $settings['site_name_en']         ?? 'Sharjah City Municipality';
$systemTitleAr = $settings['system_title_ar']      ?? 'نظام متابعة وإدارة السيارات';
$systemTitleEn = $settings['system_title_en']      ?? 'Vehicle Management System';
$footerAr      = $settings['footer_text_ar']       ?? '© 2025 بلدية مدينة الشارقة - جميع الحقوق محفوظة';
$footerEn      = $settings['footer_text_en']       ?? '© 2025 Sharjah City Municipality - All Rights Reserved';
$logoUrl       = $settings['logo_url']             ?? $publicUrl . '/logo/shjmunlogo.png';

// Generate CSS variables from theme colors
$cssVars = '';
if ($theme && !empty($theme['colors'])) {
    $colorMap = [
        'primary_dark'   => '--primary-dark',
        'primary_main'   => '--primary-main',
        'primary_light'  => '--primary-light',
        'accent_gold'    => '--accent-gold',
        'accent_beige'   => '--accent-beige',
        'bg_main'        => '--bg-main',
        'bg_card'        => '--bg-card',
        'bg_sidebar'     => '--bg-sidebar',
        'text_primary'   => '--text-primary',
        'text_secondary' => '--text-secondary',
        'text_light'     => '--text-light',
        'border_default' => '--border-default',
        'status_success' => '--status-success',
        'status_warning' => '--status-warning',
        'status_danger'  => '--status-danger',
        'status_info'    => '--status-info',
        'header_bg'      => '--header-bg',
        'header_text'    => '--header-text',
        'footer_bg'      => '--footer-bg',
        'footer_text'    => '--footer-text',
        'sidebar_text'   => '--sidebar-text',
        'sidebar_active_bg' => '--sidebar-active-bg',
    ];
    $vars = [];
    foreach ($colorMap as $dbKey => $cssKey) {
        if (!empty($theme['colors'][$dbKey])) {
            $vars[] = $cssKey . ':' . htmlspecialchars($theme['colors'][$dbKey], ENT_QUOTES);
        }
    }
    if (!empty($theme['design'])) {
        $designMap = [
            'header_height'          => '--header-height',
            'sidebar_width'          => '--sidebar-width',
            'sidebar_collapsed_width'=> '--sidebar-collapsed-width',
            'card_border_radius'     => '--card-border-radius',
            'card_shadow'            => '--card-shadow',
            'layout_max_width'       => '--layout-max-width',
            'layout_padding'         => '--layout-padding',
        ];
        foreach ($designMap as $dbKey => $cssKey) {
            if (!empty($theme['design'][$dbKey])) {
                $vars[] = $cssKey . ':' . htmlspecialchars($theme['design'][$dbKey], ENT_QUOTES);
            }
        }
    }
    if ($vars) {
        $cssVars = ':root{' . implode(';', $vars) . '}';
    }
}

// Menu items with permissions
$menuItems = [
    ['key' => 'dashboard',    'icon' => '📊', 'page' => 'dashboard',           'label_ar' => 'لوحة التحكم',    'label_en' => 'Dashboard',      'perm' => null],
    ['key' => 'my_vehicles',  'icon' => '🚙', 'page' => 'my_vehicles',         'label_ar' => 'مركباتي',        'label_en' => 'My Vehicles',    'perm' => null],
    ['key' => 'admin_vehicles','icon' => '🚐', 'page' => 'admin_vehicles',     'label_ar' => 'إدارة المركبات','label_en' => 'Admin Vehicles', 'perm' => 'manage_movements'],
    ['key' => 'vehicles',     'icon' => '🚗', 'page' => 'vehicle_list',        'label_ar' => 'المركبات',       'label_en' => 'Vehicles',       'perm' => 'manage_vehicles'],
    ['key' => 'movements',   'icon' => '🔄', 'page' => 'movements',           'label_ar' => 'الحركات',        'label_en' => 'Movements',   'perm' => 'manage_movements'],
    ['key' => 'maintenance', 'icon' => '🔧', 'page' => 'maintenance',         'label_ar' => 'الصيانة',        'label_en' => 'Maintenance', 'perm' => 'manage_maintenance'],
    ['key' => 'violations',  'icon' => '⚠️', 'page' => 'violations',          'label_ar' => 'المخالفات',      'label_en' => 'Violations',  'perm' => 'manage_violations'],
    ['key' => 'divider'],
    ['key' => 'users',       'icon' => '👥', 'page' => 'users',               'label_ar' => 'المستخدمين',     'label_en' => 'Users',       'perm' => 'manage_users'],
    ['key' => 'roles',       'icon' => '🔑', 'page' => 'roles',               'label_ar' => 'الأدوار',        'label_en' => 'Roles',       'perm' => 'manage_roles'],
    ['key' => 'settings',    'icon' => '⚙️', 'page' => 'settings',            'label_ar' => 'الإعدادات',      'label_en' => 'Settings',    'perm' => 'manage_settings'],
    ['key' => 'divider'],
    ['key' => 'profile',     'icon' => '👤', 'page' => 'profile',             'label_ar' => 'الملف الشخصي',   'label_en' => 'Profile',     'perm' => null],
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <script>
    // Immediately apply stored language direction before any rendering to prevent RTL→LTR flash
    (function(){
        try {
            var lang = localStorage.getItem('lang');
            if (lang === 'en') {
                document.documentElement.setAttribute('lang', 'en');
                document.documentElement.setAttribute('dir', 'ltr');
            }
        } catch(e) {}
    })();
    </script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="<?= htmlspecialchars(!empty($theme['colors']['primary_dark']) ? $theme['colors']['primary_dark'] : '#1a1a2e') ?>">
    <link rel="manifest" href="<?= $publicUrl ?>/manifest.php">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= $publicUrl ?>/css/theme.css">
    <?php if ($cssVars): ?>
    <style><?= $cssVars ?></style>
    <?php endif; ?>
</head>
<body>
    <script>
    // Apply stored direction to body immediately to match CSS selectors
    (function(){
        try {
            var lang = localStorage.getItem('lang');
            if (lang === 'en') {
                document.body.setAttribute('dir', 'ltr');
            }
        } catch(e) {}
    })();
    </script>
    <!-- ========== HEADER ========== -->
    <header class="app-header">
        <div class="logo-area">
            <button class="btn btn-ghost btn-icon" data-action="toggle-sidebar-mobile" style="color:var(--text-light)">&#9776;</button>
            <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Logo" id="headerLogo" onerror="this.style.display='none'">
            <h1 id="headerTitle" data-title-ar="<?= htmlspecialchars($systemTitleAr) ?>" data-title-en="<?= htmlspecialchars($systemTitleEn) ?>"><?= htmlspecialchars($systemTitleAr) ?></h1>
        </div>
        <div class="header-actions">
            <button class="btn btn-ghost btn-icon" data-action="toggle-theme" title="تبديل المظهر" id="themeBtn">&#9728;&#65039;</button>
            <button class="btn btn-ghost btn-sm" data-action="toggle-lang" id="langBtn">EN</button>
            <div class="user-info"></div>
            <button class="btn btn-ghost btn-sm" data-action="logout" id="logoutBtn" data-label-ar="خروج" data-label-en="Logout">خروج</button>
        </div>
    </header>

    <!-- ========== SIDEBAR ========== -->
    <aside class="app-sidebar">
        <nav class="menu-list" id="sidebarMenu">
            <?php foreach ($menuItems as $item): ?>
                <?php if (isset($item['key']) && $item['key'] === 'divider'): ?>
                    <div class="menu-divider"></div>
                <?php else: ?>
                    <a class="menu-item<?= ($activePage === $item['page']) ? ' active' : '' ?>"
                       href="<?= $publicUrl ?>/dashboard.php?page=<?= urlencode($item['page']) ?>&_v=<?= time() ?>"
                       data-perm="<?= htmlspecialchars($item['perm'] ?? '') ?>">
                        <span class="menu-icon"><?= $item['icon'] ?></span>
                        <span class="menu-label" data-label-ar="<?= htmlspecialchars($item['label_ar']) ?>" data-label-en="<?= htmlspecialchars($item['label_en']) ?>"><?= htmlspecialchars($item['label_ar']) ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </aside>

    <script>
    // Apply stored language to header/sidebar text immediately to prevent Arabic flash in English mode
    (function(){
        try {
            var lang = localStorage.getItem('lang');
            if (lang === 'en') {
                document.querySelectorAll('.menu-label[data-label-en]').forEach(function(el) {
                    el.textContent = el.getAttribute('data-label-en');
                });
                var ht = document.getElementById('headerTitle');
                if (ht) ht.textContent = ht.getAttribute('data-title-en') || ht.textContent;
                var lb = document.getElementById('langBtn');
                if (lb) lb.textContent = 'AR';
                var lo = document.getElementById('logoutBtn');
                if (lo) lo.textContent = lo.getAttribute('data-label-en') || lo.textContent;
            }
        } catch(e) {}
    })();
    </script>

    <!-- ========== MAIN CONTENT ========== -->
    <main class="app-main">

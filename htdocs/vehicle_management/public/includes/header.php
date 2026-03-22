<?php
/**
 * Shared Header Include
 *
 * Provides: Auth check, theme loading from DB, unified header + sidebar.
 * Usage: include __DIR__ . '/includes/header.php';
 *
 * Before including, set $pageTitle and $activePage:
 *   $pageTitle  = 'لوحة التحكم';
 *   $activePage = 'dashboard';
 *
 * NOTE: Sidebar toggle click-events are bound exclusively in app.js
 * (_bindGlobalEvents). This file only restores the persisted collapsed
 * state via an inline <script> so there is zero flash before JS loads.
 */

require_once __DIR__ . '/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$theme    = vm_get_theme();
$settings = vm_get_settings();

$pageTitle   = $pageTitle   ?? ($settings['system_title_ar'] ?? 'نظام إدارة المركبات');
$activePage  = $activePage  ?? 'dashboard';
$requireAuth = $requireAuth ?? true;
$langKey     = $pageLangKey ?? $activePage;

$siteNameAr    = $settings['site_name_ar']    ?? 'بلدية مدينة الشارقة';
$siteNameEn    = $settings['site_name_en']    ?? 'Sharjah City Municipality';
$systemTitleAr = $settings['system_title_ar'] ?? 'نظام متابعة وإدارة السيارات';
$systemTitleEn = $settings['system_title_en'] ?? 'Vehicle Management System';
$footerAr      = $settings['footer_text_ar']  ?? '© 2025 بلدية مدينة الشارقة - جميع الحقوق محفوظة';
$footerEn      = $settings['footer_text_en']  ?? '© 2025 Sharjah City Municipality - All Rights Reserved';
$logoUrl       = $settings['logo_url']        ?? $publicUrl . '/logo/shjmunlogo.png';

// Build CSS variables from theme
$cssVars = '';
if ($theme && !empty($theme['colors'])) {
    $colorMap = [
        'primary_dark'      => '--primary-dark',
        'primary_main'      => '--primary-main',
        'primary_light'     => '--primary-light',
        'accent_gold'       => '--accent-gold',
        'accent_beige'      => '--accent-beige',
        'bg_main'           => '--bg-main',
        'bg_card'           => '--bg-card',
        'bg_sidebar'        => '--bg-sidebar',
        'text_primary'      => '--text-primary',
        'text_secondary'    => '--text-secondary',
        'text_light'        => '--text-light',
        'border_default'    => '--border-default',
        'status_success'    => '--status-success',
        'status_warning'    => '--status-warning',
        'status_danger'     => '--status-danger',
        'status_info'       => '--status-info',
        'header_bg'         => '--header-bg',
        'header_text'       => '--header-text',
        'footer_bg'         => '--footer-bg',
        'footer_text'       => '--footer-text',
        'sidebar_text'      => '--sidebar-text',
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
            'header_height'           => '--header-height',
            'sidebar_width'           => '--sidebar-width',
            'sidebar_collapsed_width' => '--sidebar-collapsed-width',
            'card_border_radius'      => '--card-border-radius',
            'card_shadow'             => '--card-shadow',
            'layout_max_width'        => '--layout-max-width',
            'layout_padding'          => '--layout-padding',
        ];
        foreach ($designMap as $dbKey => $cssKey) {
            if (!empty($theme['design'][$dbKey])) {
                $vars[] = $cssKey . ':' . htmlspecialchars($theme['design'][$dbKey], ENT_QUOTES);
            }
        }
    }
    if ($vars) $cssVars = ':root{' . implode(';', $vars) . '}';
}

$menuItems = [
    ['key' => 'dashboard',      'icon' => '📊', 'page' => 'dashboard',      'label_ar' => 'لوحة التحكم',    'label_en' => 'Dashboard',      'perm' => null],
    ['key' => 'my_vehicles',    'icon' => '🚙', 'page' => 'my_vehicles',    'label_ar' => 'مركباتي',         'label_en' => 'My Vehicles',    'perm' => null],
    ['key' => 'admin_vehicles', 'icon' => '🚐', 'page' => 'admin_vehicles', 'label_ar' => 'إدارة المركبات',  'label_en' => 'Admin Vehicles', 'perm' => 'manage_movements'],
    ['key' => 'vehicles',       'icon' => '🚗', 'page' => 'vehicle_list',   'label_ar' => 'المركبات',        'label_en' => 'Vehicles',       'perm' => 'manage_vehicles'],
    ['key' => 'movements',      'icon' => '🔄', 'page' => 'movements',      'label_ar' => 'الحركات',         'label_en' => 'Movements',      'perm' => 'manage_movements'],
    ['key' => 'maintenance',    'icon' => '🔧', 'page' => 'maintenance',    'label_ar' => 'الصيانة',         'label_en' => 'Maintenance',    'perm' => 'manage_maintenance'],
    ['key' => 'violations',     'icon' => '⚠️', 'page' => 'violations',     'label_ar' => 'المخالفات',       'label_en' => 'Violations',     'perm' => 'manage_violations'],
    ['key' => 'divider'],
    ['key' => 'users',          'icon' => '👥', 'page' => 'users',          'label_ar' => 'المستخدمين',      'label_en' => 'Users',          'perm' => 'manage_users'],
    ['key' => 'roles',          'icon' => '🔑', 'page' => 'roles',          'label_ar' => 'الأدوار',         'label_en' => 'Roles',          'perm' => 'manage_roles'],
    ['key' => 'settings',       'icon' => '⚙️', 'page' => 'settings',       'label_ar' => 'الإعدادات',       'label_en' => 'Settings',       'perm' => 'manage_settings'],
    ['key' => 'divider'],
    ['key' => 'profile',        'icon' => '👤', 'page' => 'profile',        'label_ar' => 'الملف الشخصي',    'label_en' => 'Profile',        'perm' => null],
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <script>
    // Apply stored language direction BEFORE any rendering — prevents RTL→LTR flash
    (function(){
        try {
            var lang = localStorage.getItem('lang');
            if (lang === 'en') {
                document.documentElement.setAttribute('lang', 'en');
                document.documentElement.setAttribute('dir',  'ltr');
            }
        } catch(e){}
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

    <style>
    /* ══════════════════════════════════════════
       SIDEBAR — Collapse to icons only
       ══════════════════════════════════════════ */
    .app-sidebar {
        overflow: hidden;
        transition: width 0.28s cubic-bezier(.4,0,.2,1);
    }
    .app-sidebar.collapsed {
        width: var(--sidebar-collapsed-width, 60px) !important;
    }
    .app-sidebar.collapsed .menu-label {
        opacity: 0;
        max-width: 0;
        overflow: hidden;
        white-space: nowrap;
        pointer-events: none;
        transition: opacity .15s, max-width .2s;
    }
    .app-sidebar:not(.collapsed) .menu-label {
        opacity: 1;
        max-width: 200px;
        transition: opacity .2s .06s, max-width .22s;
    }
    .app-sidebar.collapsed .menu-item {
        justify-content: center !important;
        padding: 13px 0 !important;
        gap: 0 !important;
    }
    .app-sidebar.collapsed .menu-icon { font-size: 1.2rem; margin: 0; }
    .app-sidebar.collapsed .menu-divider { margin: 5px 10px; }

    /* Tooltip when collapsed */
    .app-sidebar.collapsed .menu-item { position: relative; }
    .app-sidebar.collapsed .menu-item::after {
        content: attr(data-tooltip);
        position: absolute;
        inset-inline-start: calc(100% + 8px);
        top: 50%;
        transform: translateY(-50%);
        background: rgba(0,0,0,.82);
        color: #fff;
        font-size: .78rem;
        font-weight: 600;
        padding: 5px 11px;
        border-radius: 7px;
        white-space: nowrap;
        pointer-events: none;
        opacity: 0;
        transition: opacity .15s;
        z-index: 9999;
        box-shadow: 0 4px 16px rgba(0,0,0,.22);
    }
    .app-sidebar.collapsed .menu-item:hover::after { opacity: 1; }

    /* ══════════════════════════════════════════
       SIDEBAR MENU ITEMS
       ══════════════════════════════════════════ */
    .app-sidebar .menu-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 11px 14px;
        margin: 2px 6px;
        border-radius: 9px;
        font-size: .875rem;
        color: rgba(255,255,255,.72);
        text-decoration: none;
        white-space: nowrap;
        transition: background .18s, color .18s, border-color .18s;
        border: 1.5px solid transparent;
    }
    .app-sidebar .menu-item:hover {
        background: rgba(255,255,255,.1);
        color: #fff;
        border-color: rgba(255,255,255,.08);
    }
    .app-sidebar .menu-item.active {
        background: rgba(212,175,55,.14);
        color: var(--accent-gold, #d4af37);
        border-color: rgba(212,175,55,.35);
        font-weight: 700;
    }
    .app-sidebar .menu-icon { font-size: 1.1rem; width: 22px; text-align: center; flex-shrink: 0; }
    .app-sidebar .menu-divider { height: 1px; background: rgba(255,255,255,.09); margin: 6px 14px; }
    .app-sidebar::-webkit-scrollbar { width: 4px; }
    .app-sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,.12); border-radius: 4px; }

    /* ══════════════════════════════════════════
       HEADER
       ══════════════════════════════════════════ */
    .app-header {
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-bottom: 1px solid rgba(255,255,255,.07);
    }
    .app-header .logo-area { gap: 10px; min-width: 0; }
    .app-header .logo-area img { height: 34px; width: auto; flex-shrink: 0; }
    .app-header .logo-area h1 {
        font-size: .96rem; font-weight: 600;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .app-header .header-actions { gap: 8px; flex-shrink: 0; }

    /* Desktop collapse button */
    #sidebarCollapseBtn {
        width: 34px; height: 34px;
        border-radius: 7px;
        border: 1px solid rgba(255,255,255,.15);
        background: rgba(255,255,255,.08);
        color: var(--text-light, #fff);
        font-size: 1rem;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        transition: background .18s;
        flex-shrink: 0;
    }
    #sidebarCollapseBtn:hover { background: rgba(255,255,255,.18); }
    @media(max-width:768px){ #sidebarCollapseBtn { display: none; } }

    /* Mobile hamburger: hide on desktop */
    #mobileMenuBtn { display: none; }
    @media(max-width:768px){ #mobileMenuBtn { display: flex; } }

    /* Logout */
    #logoutBtn {
        border: 1px solid rgba(220,53,69,.35) !important;
        background: rgba(220,53,69,.15) !important;
        border-radius: 8px !important;
        font-size: .78rem !important;
        font-weight: 600 !important;
        white-space: nowrap;
    }
    #logoutBtn:hover { background: rgba(220,53,69,.32) !important; }

    /* ══════════════════════════════════════════
       MOBILE overlay
       ══════════════════════════════════════════ */
    #sidebarOverlay {
        display: none;
        position: fixed; inset: 0;
        background: rgba(0,0,0,.45);
        z-index: 850;
    }
    #sidebarOverlay.active { display: block; }

    @media(max-width:768px) {
        .app-sidebar { width: var(--sidebar-collapsed-width, 60px) !important; }
        .app-sidebar.mobile-open {
            width: var(--sidebar-width, 240px) !important;
            z-index: 900;
            box-shadow: 4px 0 32px rgba(0,0,0,.35);
        }
        .app-sidebar.mobile-open .menu-label { opacity: 1 !important; max-width: 200px !important; }
        .app-sidebar.mobile-open .menu-item  { justify-content: flex-start !important; padding: 11px 14px !important; gap: 10px !important; }
        .app-header .logo-area h1 { max-width: 160px; font-size: .88rem; }
    }
    @media(max-width:480px) {
        .app-header .logo-area h1 { display: none; }
        .app-header .user-info span:not(.avatar) { display: none; }
        #logoutBtn { padding: 5px 8px !important; font-size: .74rem !important; }
    }
    </style>
</head>
<body>
    <script>
    // Apply stored direction to body immediately
    (function(){
        try {
            if (localStorage.getItem('lang') === 'en') document.body.setAttribute('dir','ltr');
        } catch(e){}
    })();
    </script>

    <!-- Mobile sidebar overlay (click handled in app.js _bindGlobalEvents) -->
    <div id="sidebarOverlay"></div>

    <!-- ═══════ HEADER ═══════ -->
    <header class="app-header">
        <div class="logo-area">
            <!-- Mobile: open/close -->
            <button id="mobileMenuBtn"
                    class="btn btn-ghost btn-icon"
                    data-action="toggle-sidebar-mobile"
                    style="color:var(--text-light)"
                    aria-label="Toggle menu">&#9776;</button>
            <!-- Desktop: collapse to icons -->
            <button id="sidebarCollapseBtn"
                    data-action="toggle-sidebar"
                    aria-label="Collapse sidebar">&#9776;</button>
            <img src="<?= htmlspecialchars($logoUrl) ?>"
                 alt="Logo"
                 id="headerLogo"
                 onerror="this.style.display='none'">
            <h1 id="headerTitle"
                data-title-ar="<?= htmlspecialchars($systemTitleAr) ?>"
                data-title-en="<?= htmlspecialchars($systemTitleEn) ?>">
                <?= htmlspecialchars($systemTitleAr) ?>
            </h1>
        </div>
        <div class="header-actions">
            <div class="user-info"></div>
            <button class="btn btn-ghost btn-sm"
                    data-action="logout"
                    id="logoutBtn"
                    data-label-ar="خروج"
                    data-label-en="Logout">خروج</button>
        </div>
    </header>

    <!-- ═══════ SIDEBAR ═══════ -->
    <aside class="app-sidebar" id="appSidebar">
        <nav class="menu-list" id="sidebarMenu">
            <?php foreach ($menuItems as $item): ?>
                <?php if (isset($item['key']) && $item['key'] === 'divider'): ?>
                    <div class="menu-divider"></div>
                <?php else: ?>
                    <a class="menu-item<?= ($activePage === $item['page']) ? ' active' : '' ?>"
                       href="<?= $publicUrl ?>/dashboard.php?page=<?= urlencode($item['page']) ?>&_v=<?= time() ?>"
                       data-perm="<?= htmlspecialchars($item['perm'] ?? '') ?>"
                       data-tooltip="<?= htmlspecialchars($item['label_ar']) ?>"
                       data-tooltip-en="<?= htmlspecialchars($item['label_en']) ?>">
                        <span class="menu-icon"><?= $item['icon'] ?></span>
                        <span class="menu-label"
                              data-label-ar="<?= htmlspecialchars($item['label_ar']) ?>"
                              data-label-en="<?= htmlspecialchars($item['label_en']) ?>">
                            <?= htmlspecialchars($item['label_ar']) ?>
                        </span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </aside>

    <script>
    // ─── Inline init: zero-flash state restore ────────────────────────────
    // Runs synchronously before DOMContentLoaded so nothing flickers.
    // Does NOT bind any click events — that is done in app.js.
    // ─────────────────────────────────────────────────────────────────────
    (function(){
        try {
            var lang  = localStorage.getItem('lang');
            var isEn  = (lang === 'en');

            // 1. Language labels
            document.querySelectorAll('.menu-label[data-label-en]').forEach(function(el){
                el.textContent = el.getAttribute(isEn ? 'data-label-en' : 'data-label-ar');
            });
            // 2. Tooltip attribute for collapsed sidebar
            document.querySelectorAll('.menu-item[data-tooltip-en]').forEach(function(el){
                if (isEn) el.setAttribute('data-tooltip', el.getAttribute('data-tooltip-en'));
            });
            // 3. Header text
            var ht = document.getElementById('headerTitle');
            if (ht) ht.textContent = ht.getAttribute(isEn ? 'data-title-en' : 'data-title-ar') || ht.textContent;
            var lo = document.getElementById('logoutBtn');
            if (lo) lo.textContent = lo.getAttribute(isEn ? 'data-label-en' : 'data-label-ar') || lo.textContent;

            // 4. Restore collapsed state — class added before paint
            var sidebar = document.getElementById('appSidebar');
            if (sidebar && localStorage.getItem('sidebar_collapsed') === '1') {
                sidebar.classList.add('collapsed');
            }
        } catch(e){}
    })();
    </script>

    <!-- ═══════ MAIN ═══════ -->
    <main class="app-main">
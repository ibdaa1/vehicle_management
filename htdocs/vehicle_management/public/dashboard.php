<?php
/**
 * Dashboard Shell — Unified Entry Point
 * 
 * All pages load inside this shell via ?page= parameter.
 * Includes shared header.php (auth, theme, sidebar) and footer.php.
 * Loads the requested fragment from fragments/ directory.
 * 
 * Usage: dashboard.php?page=dashboard
 *        dashboard.php?page=vehicles
 *        dashboard.php?page=maintenance
 *        etc.
 */

// Determine which fragment to load — validate against allowlist
$page = isset($_GET['page']) ? preg_replace('/[^a-z0-9_-]/i', '', $_GET['page']) : 'dashboard';

// Page metadata per fragment (also serves as allowlist)
$pageMeta = [
    'dashboard'   => ['title' => 'لوحة التحكم',           'active' => 'dashboard'],
    'vehicles'    => ['title' => 'إدارة المركبات',         'active' => 'vehicles'],
    'vehicle_form'=> ['title' => 'بيانات المركبة',         'active' => 'vehicles'],
    'movements'   => ['title' => 'حركات المركبات',         'active' => 'movements'],
    'maintenance' => ['title' => 'الصيانة',               'active' => 'maintenance'],
    'violations'  => ['title' => 'المخالفات',             'active' => 'violations'],
    'users'       => ['title' => 'إدارة المستخدمين',      'active' => 'users'],
    'roles'       => ['title' => 'إدارة الأدوار',         'active' => 'roles'],
    'settings'    => ['title' => 'الإعدادات',             'active' => 'settings'],
    'profile'     => ['title' => 'الملف الشخصي',          'active' => 'profile'],
];

// Enforce allowlist: only permit known page keys
if (!isset($pageMeta[$page])) {
    $page = 'dashboard';
}

// Fragment file path (double-check file exists)
$fragmentFile = __DIR__ . '/fragments/' . $page . '.php';
if (!file_exists($fragmentFile)) {
    $fragmentFile = __DIR__ . '/fragments/dashboard.php';
    $page = 'dashboard';
}

$meta = $pageMeta[$page] ?? ['title' => 'لوحة التحكم', 'active' => 'dashboard'];
$pageTitle  = $meta['title'];
$activePage = $meta['active'];

// Include header (renders HTML <head>, header bar, sidebar, opens <main>)
include __DIR__ . '/includes/header.php';

// Include the fragment (renders main content only)
include $fragmentFile;

// Include footer (closes </main>, renders footer, loads JS)
include __DIR__ . '/includes/footer.php';

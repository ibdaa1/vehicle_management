<?php
/**
 * Dashboard Shell — Unified Entry Point
 * 
 * All pages load inside this shell via ?page= parameter.
 * Includes shared header.php (auth, theme, sidebar) and footer.php.
 * Loads the requested fragment from fragments/ directory.
 * 
 * Usage: dashboard.php?page=dashboard
 *        dashboard.php?page=vehicle_list
 *        dashboard.php?page=maintenance
 *        etc.
 */

// Prevent caching so that ?page= parameter is always respected
// Critical for InfinityFree and other hosting that may cache PHP output ignoring query params
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
header('Vary: *');
header('Surrogate-Control: no-store');
header('X-Accel-Expires: 0');

// Determine which fragment to load — validate against allowlist
$page = isset($_GET['page']) ? preg_replace('/[^a-z0-9_-]/i', '', $_GET['page']) : 'dashboard';

// Page metadata per fragment (also serves as allowlist)
// 'perm' key defines the required permission to access this page (null = no permission required)
$pageMeta = [
    'dashboard'    => ['title' => 'لوحة التحكم',           'active' => 'dashboard',     'perm' => null],
    'my_vehicles'  => ['title' => 'مركباتي',               'active' => 'my_vehicles',   'perm' => null],
    'vehicle_list' => ['title' => 'إدارة المركبات',         'active' => 'vehicle_list',  'perm' => 'manage_vehicles'],
    'movements'    => ['title' => 'حركات المركبات',         'active' => 'movements',     'perm' => 'manage_movements'],
    'maintenance'  => ['title' => 'الصيانة',               'active' => 'maintenance',   'perm' => 'manage_maintenance'],
    'violations'   => ['title' => 'المخالفات',             'active' => 'violations',    'perm' => 'manage_violations'],
    'users'        => ['title' => 'إدارة المستخدمين',      'active' => 'users',         'perm' => 'manage_users'],
    'roles'        => ['title' => 'إدارة الأدوار',         'active' => 'roles',         'perm' => 'manage_roles'],
    'settings'     => ['title' => 'الإعدادات',             'active' => 'settings',      'perm' => 'manage_settings'],
    'profile'      => ['title' => 'الملف الشخصي',          'active' => 'profile',       'perm' => null],
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

$meta = $pageMeta[$page] ?? ['title' => 'لوحة التحكم', 'active' => 'dashboard', 'perm' => null];
$pageTitle  = $meta['title'];
$activePage = $meta['active'];
$requiredPerm = $meta['perm'] ?? null;

// Include header (renders HTML <head>, header bar, sidebar, opens <main>)
include __DIR__ . '/includes/header.php';
?>
<!-- Page-level permission gate: wraps fragment content -->
<div id="pageContent" data-required-perm="<?= htmlspecialchars($requiredPerm ?? '') ?>" data-page="<?= htmlspecialchars($page) ?>" style="<?= $requiredPerm ? 'display:none' : '' ?>">
<?php
// Include the fragment (renders main content only)
include $fragmentFile;
?>
</div>
<div id="accessDenied" style="display:none;text-align:center;padding:80px 20px;">
    <div style="font-size:4rem;margin-bottom:16px;">🔒</div>
    <h2 id="accessDeniedTitle" data-label-ar="غير مصرح بالوصول" data-label-en="Access Denied">غير مصرح بالوصول</h2>
    <p id="accessDeniedMsg" data-label-ar="ليس لديك صلاحية للوصول إلى هذه الصفحة" data-label-en="You do not have permission to access this page" style="color:var(--text-secondary,#666);margin-top:8px;">ليس لديك صلاحية للوصول إلى هذه الصفحة</p>
    <a href="dashboard.php?page=dashboard" class="btn btn-primary" style="margin-top:24px;display:inline-block;padding:10px 24px;border-radius:8px;text-decoration:none;" id="accessDeniedBack" data-label-ar="العودة للوحة التحكم" data-label-en="Back to Dashboard">العودة للوحة التحكم</a>
</div>
<?php
// Include footer (closes </main>, renders footer, loads JS)
include __DIR__ . '/includes/footer.php';

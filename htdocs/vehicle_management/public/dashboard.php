<?php
/**
 * Dashboard Shell — Unified Entry Point
 *
 * All pages load inside this shell via ?page= parameter.
 * Includes shared header.php (auth, theme, sidebar) and footer.php.
 * Loads the requested fragment from fragments/ directory.
 *
 * Usage: dashboard.php?page=dashboard
 *        dashboard.php?page=my_vehicles
 *        dashboard.php?page=admin_vehicles
 *        dashboard.php?page=vehicle_list
 *        dashboard.php?page=movements
 *        dashboard.php?page=maintenance
 *        dashboard.php?page=violations
 *        dashboard.php?page=users
 *        dashboard.php?page=roles
 *        dashboard.php?page=settings
 *        dashboard.php?page=profile
 */

// Prevent caching so that ?page= parameter is always respected
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
    'dashboard'      => ['title' => 'Dashboard',           'active' => 'dashboard',       'perm' => null],
    'my_vehicles'    => ['title' => 'My Vehicles',         'active' => 'my_vehicles',     'perm' => null],
    'admin_vehicles' => ['title' => 'Admin Vehicles',      'active' => 'admin_vehicles',  'perm' => 'manage_movements'],
    'vehicle_list'   => ['title' => 'Vehicle Management',  'active' => 'vehicle_list',    'perm' => null],
    'movements'      => ['title' => 'Movements',           'active' => 'movements',       'perm' => 'manage_movements'],
    'maintenance'    => ['title' => 'Maintenance',         'active' => 'maintenance',     'perm' => 'manage_maintenance'],
    'violations'     => ['title' => 'Violations',          'active' => 'violations',      'perm' => 'manage_violations'],
    'users'          => ['title' => 'User Management',     'active' => 'users',           'perm' => 'manage_users'],
    'roles'          => ['title' => 'Roles & Permissions', 'active' => 'roles',           'perm' => 'manage_roles'],
    'settings'       => ['title' => 'Settings',            'active' => 'settings',        'perm' => 'manage_settings'],
    'profile'        => ['title' => 'My Profile',          'active' => 'profile',         'perm' => null],
];

// Enforce allowlist: only permit known page keys
if (!isset($pageMeta[$page])) {
    $page = 'dashboard';
}

// Fragment file path (double-check file exists)
$fragmentFile = __DIR__ . '/fragments/' . $page . '.php';
if (!file_exists($fragmentFile)) {
    $fragmentFile = __DIR__ . '/fragments/dashboard.php';
    $page         = 'dashboard';
}

$meta         = $pageMeta[$page] ?? ['title' => 'Dashboard', 'active' => 'dashboard', 'perm' => null];
$pageTitle    = $meta['title'];
$activePage   = $meta['active'];
$requiredPerm = $meta['perm'] ?? null;

// Include header (renders HTML <head>, header bar, sidebar, opens <main>)
include __DIR__ . '/includes/header.php';
?>

<!-- Page-level permission gate: wraps fragment content -->
<div id="pageContent"
     data-required-perm="<?= htmlspecialchars($requiredPerm ?? '') ?>"
     data-page="<?= htmlspecialchars($page) ?>"
     style="<?= $requiredPerm ? 'display:none' : '' ?>">
<?php
// Include the fragment (renders main content only)
include $fragmentFile;
?>
</div>

<!-- Access denied fallback (shown by JS if user lacks permission) -->
<div id="accessDenied" style="display:none;text-align:center;padding:80px 20px;">
    <div style="font-size:4rem;margin-bottom:16px;">🔒</div>
    <h2 id="accessDeniedTitle">Access Denied</h2>
    <p id="accessDeniedMsg" style="color:var(--text-secondary,#666);margin-top:8px;">
        You do not have permission to access this page
    </p>
    <a href="dashboard.php?page=dashboard"
       class="btn btn-primary"
       style="margin-top:24px;display:inline-block;padding:10px 24px;border-radius:8px;text-decoration:none;"
       id="accessDeniedBack">Back to Dashboard</a>
</div>

<?php
// Include footer (closes </main>, renders footer, loads JS)
include __DIR__ . '/includes/footer.php';
?>
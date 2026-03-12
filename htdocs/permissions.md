<?php
declare(strict_types=1);

/**
 * /admin/fragments/permissions.php
 * Production Version - Updated for new admin_context.php
 * 
 * ✅ Uses new permission system (role-based + resource-based)
 * ✅ Compatible with tenant_users table
 * ✅ No deprecated fields
 * ✅ Production-ready
 */

// ════════════════════════════════════════════════════════════
// DETECT REQUEST TYPE
// ════════════════════════════════════════════════════════════
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$isEmbedded = isset($_GET['embedded']) || isset($_POST['embedded']);
$isFragment = $isAjax || $isEmbedded;

// ════════════════════════════════════════════════════════════
// LOAD CONTEXT / HEADER
// ════════════════════════════════════════════════════════════
if ($isFragment) {
    // Load admin_context to provide helper functions in fragment mode
    require_once __DIR__ . '/../includes/admin_context.php';
} else {
    require_once __DIR__ . '/../includes/header.php';
}

// ════════════════════════════════════════════════════════════
// VERIFY USER IS LOGGED IN
// ════════════════════════════════════════════════════════════
if (!is_admin_logged_in()) {
    if ($isFragment) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    } else {
        header('Location: /admin/login.php');
        exit;
    }
}

// ════════════════════════════════════════════════════════════
// GET USER CONTEXT & PERMISSIONS
// ════════════════════════════════════════════════════════════
$user = admin_user();
$lang = admin_lang();
$dir = admin_dir();
$csrf = admin_csrf();
$tenantId = admin_tenant_id();
$pdo = admin_db();

// ════════════════════════════════════════════════════════════
// CHECK PERMISSIONS
// ════════════════════════════════════════════════════════════

// Method 1: Using role-based permissions
$canManagePermissions = can('permissions.manage') || can('manage_permissions');

// Method 2: Using resource-based permissions (recommended for granular control)
$canViewAll = can_view_all('permissions');
$canViewOwn = can_view_own('permissions');
$canViewTenant = can_view_tenant('permissions');
$canCreate = can_create('permissions');
$canEditAll = can_edit_all('permissions');
$canEditOwn = can_edit_own('permissions');
$canDeleteAll = can_delete_all('permissions');
$canDeleteOwn = can_delete_own('permissions');

// Combined permissions for UI
$canView = $canViewAll || $canViewOwn || $canViewTenant || $canManagePermissions;
$canEdit = $canEditAll || $canEditOwn || $canManagePermissions;
$canDelete = $canDeleteAll || $canDeleteOwn || $canManagePermissions;

// Super admin always has access
$isSuperAdmin = is_super_admin();
if ($isSuperAdmin) {
    $canView = $canEdit = $canDelete = $canCreate = true;
}

// If user has no view permission at all, deny access
if (!$canView && !$isSuperAdmin) {
    if ($isFragment) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    } else {
        http_response_code(403);
        die('Access denied: You do not have permission to view permissions');
    }
}

// ════════════════════════════════════════════════════════════
// TRANSLATION HELPERS
// ════════════════════════════════════════════════════════════
if (!function_exists('__t')) {
    function __t($key, $fallback = '') {
        if (function_exists('i18n_get')) {
            $v = i18n_get($key);
            return $v ?? ($fallback ?? $key);
        }
        return $fallback ?? $key;
    }
}

if (!function_exists('__tr')) {
    function __tr($key, $replacements = []) {
        $text = __t($key, $key);
        foreach ($replacements as $ph => $val) {
            $text = str_replace("{" . $ph . "}", (string)$val, $text);
        }
        return $text;
    }
}

// ════════════════════════════════════════════════════════════
// DB-DRIVEN CSS VARS HELPER (Permissions)
// ════════════════════════════════════════════════════════════
if (!function_exists('renderFragmentThemeVars')) {
    function renderFragmentThemeVars(array $theme): void {
        echo ':root {' . PHP_EOL;
        foreach ($theme['color_settings'] ?? [] as $c) {
            if (empty($c['setting_key']) || !isset($c['color_value'])) continue;
            $k = htmlspecialchars($c['setting_key'], ENT_QUOTES);
            $h = htmlspecialchars(str_replace('_', '-', $c['setting_key']), ENT_QUOTES);
            $v = htmlspecialchars($c['color_value'], ENT_QUOTES);
            echo "    --{$k}: {$v};" . PHP_EOL;
            if ($h !== $k) echo "    --{$h}: {$v};" . PHP_EOL;
        }
        foreach ($theme['font_settings'] ?? [] as $f) {
            if (empty($f['setting_key'])) continue;
            $sk = htmlspecialchars($f['setting_key'], ENT_QUOTES);
            $sh = htmlspecialchars(str_replace('_', '-', $f['setting_key']), ENT_QUOTES);
            if (!empty($f['font_family'])) {
                $ff = htmlspecialchars($f['font_family'], ENT_QUOTES);
                echo "    --{$sk}-family: {$ff};" . PHP_EOL;
                if ($sh !== $sk) echo "    --{$sh}-family: {$ff};" . PHP_EOL;
            }
            if (!empty($f['font_size'])) {
                $fs = htmlspecialchars($f['font_size'], ENT_QUOTES);
                echo "    --{$sk}-size: {$fs};" . PHP_EOL;
                if ($sh !== $sk) echo "    --{$sh}-size: {$fs};" . PHP_EOL;
            }
        }
        foreach ($theme['design_settings'] ?? [] as $d) {
            if (empty($d['setting_key']) || !isset($d['setting_value'])) continue;
            $dk = htmlspecialchars($d['setting_key'], ENT_QUOTES);
            $dh = htmlspecialchars(str_replace('_', '-', $d['setting_key']), ENT_QUOTES);
            $dv = htmlspecialchars($d['setting_value'], ENT_QUOTES);
            echo "    --{$dk}: {$dv};" . PHP_EOL;
            if ($dh !== $dk) echo "    --{$dh}: {$dv};" . PHP_EOL;
        }
        echo '}' . PHP_EOL;
    }
}

// ════════════════════════════════════════════════════════════
// GET TENANTS FOR SUPER ADMIN
// ════════════════════════════════════════════════════════════
$allTenants = [];
if ($isSuperAdmin && $pdo instanceof PDO) {
    $stmt = $pdo->query("SELECT id, name FROM tenants ORDER BY name");
    $allTenants = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ════════════════════════════════════════════════════════════
// API BASE
// ════════════════════════════════════════════════════════════
$apiBase = '/api';

?>
<!-- DB-driven CSS vars (all settings, colors, fonts from database) -->
<style id="db-theme-vars-permissions">
<?php renderFragmentThemeVars($GLOBALS['ADMIN_UI']['theme'] ?? []); ?>
<?php if (!empty($GLOBALS['ADMIN_UI']['theme']['generated_css'])): ?>
<?= $GLOBALS['ADMIN_UI']['theme']['generated_css'] ?>
<?php endif; ?>
</style>

<!-- Force load CSS if embedded -->
<?php if ($isFragment): ?>
<link rel="stylesheet" href="/admin/assets/css/permissions-system.css">
<?php endif; ?>

<!-- Page Meta -->
<meta data-page="permissions"
      data-assets-css="/admin/assets/css/permissions-system.css"
      data-i18n-files="/languages/Permissions/<?= rawurlencode($lang) ?>.json">

<!-- Page Container -->
<div class="page-container" id="permissionsPageContainer" dir="<?= htmlspecialchars($dir) ?>">

    <!-- Alerts Container -->
    <div class="alerts-container" id="alertsContainer"></div>

    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header-content">
            <h1 class="page-title" data-i18n="permissions.title"><?= __t('permissions.title', 'Permissions Management') ?></h1>
            <p class="page-subtitle" data-i18n="permissions.subtitle"><?= __t('permissions.subtitle', 'Manage Roles, Permissions, and Access Control') ?></p>
        </div>
        <div class="page-header-actions">
            <?php if ($isSuperAdmin && !empty($allTenants)): ?>
            <select id="tenantSelector" class="form-control" style="width:240px;">
                <option value="0" <?= ($tenantId === null || $tenantId == 0) ? 'selected' : '' ?> data-i18n="permissions.global_no_tenant"><?= __t('permissions.global_no_tenant', 'Global (no tenant)') ?></option>
                <?php foreach ($allTenants as $tenant): ?>
                <option value="<?= (int)$tenant['id'] ?>" <?= $tenant['id'] == $tenantId ? 'selected' : '' ?>>
                    <?= htmlspecialchars($tenant['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php else: ?>
            <div style="min-width:200px;padding:6px 10px;background:#0f172a;color:#cbd5e1;border-radius:6px;text-align:center;">
                <span data-i18n="permissions.tenant_label"><?= __t('permissions.tenant_label', 'Tenant') ?></span>: 
                <?= $tenantId === null ? '<strong data-i18n="permissions.global">'.  __t('permissions.global', 'Global') .'</strong>' : 'ID ' . (int)$tenantId ?>
            </div>
            <?php endif; ?>
            <button class="btn btn-secondary btn-sm" onclick="PermissionsApp.refreshAll()">
                <i class="fas fa-sync"></i> <span data-i18n="permissions.btn_refresh"><?= __t('permissions.btn_refresh', 'Refresh') ?></span>
            </button>
        </div>
    </div>

    <!-- Main Tabs -->
    <div class="main-tabs">
        <button class="main-tab active" data-tab="roles">
            <i class="fas fa-users-cog"></i> <span data-i18n="permissions.tab_roles"><?= __t('permissions.tab_roles', 'Roles') ?></span>
        </button>
        <button class="main-tab" data-tab="permissions">
            <i class="fas fa-key"></i> <span data-i18n="permissions.tab_permissions"><?= __t('permissions.tab_permissions', 'Permissions') ?></span>
        </button>
        <button class="main-tab" data-tab="assign">
            <i class="fas fa-link"></i> <span data-i18n="permissions.tab_assign"><?= __t('permissions.tab_assign', 'Assign') ?></span>
        </button>
        <button class="main-tab" data-tab="resources">
            <i class="fas fa-table-cells"></i> <span data-i18n="permissions.tab_resources"><?= __t('permissions.tab_resources', 'Resources') ?></span>
        </button>
    </div>

    <!-- TAB: ROLES -->
    <div class="tab-content active" id="tab-roles">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-users-cog"></i> <span data-i18n="permissions.roles_list"><?= __t('permissions.roles_list', 'Roles List') ?></span>
                </h3>
                <div class="actions">
                    <input type="text" id="rolesSearch" class="form-control" data-i18n-placeholder="permissions.search_roles" placeholder="<?= __t('permissions.search_roles', 'Search roles...') ?>" style="width:250px;">
                    <?php if ($canCreate): ?>
                    <button class="btn btn-primary" onclick="PermissionsApp.openRoleModal()">
                        <i class="fas fa-plus"></i> <span data-i18n="permissions.add_role"><?= __t('permissions.add_role', 'Add Role') ?></span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div id="rolesLoading" class="loading">
                    <div class="spinner"></div>
                    <p data-i18n="permissions.loading"><?= __t('permissions.loading', 'Loading...') ?></p>
                </div>
                <div id="rolesContent" style="display:none;">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th data-i18n="permissions.table.id"><?= __t('permissions.table.id', 'ID') ?></th>
                                    <th data-i18n="permissions.table.name"><?= __t('permissions.table.name', 'Name') ?></th>
                                    <th data-i18n="permissions.table.key"><?= __t('permissions.table.key', 'Key') ?></th>
                                    <th data-i18n="permissions.table.created"><?= __t('permissions.table.created', 'Created') ?></th>
                                    <th data-i18n="permissions.table.actions"><?= __t('permissions.table.actions', 'Actions') ?></th>
                                </tr>
                            </thead>
                            <tbody id="rolesTableBody"></tbody>
                        </table>
                    </div>
                </div>
                <div id="rolesEmpty" class="empty-state" style="display:none;">
                    <i class="fas fa-users-cog"></i>
                    <h3 data-i18n="permissions.no_roles"><?= __t('permissions.no_roles', 'No Roles') ?></h3>
                    <?php if ($canCreate): ?>
                    <button class="btn btn-primary" onclick="PermissionsApp.openRoleModal()">
                        <i class="fas fa-plus"></i> <span data-i18n="permissions.add_first_role"><?= __t('permissions.add_first_role', 'Add First Role') ?></span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB: PERMISSIONS -->
    <div class="tab-content" id="tab-permissions">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-key"></i> <span data-i18n="permissions.permissions_list"><?= __t('permissions.permissions_list', 'Permissions List') ?></span>
                </h3>
                <div class="actions">
                    <input type="text" id="permissionsSearch" class="form-control" data-i18n-placeholder="permissions.search_permissions" placeholder="<?= __t('permissions.search_permissions', 'Search permissions...') ?>" style="width:250px;">
                    <?php if ($canCreate): ?>
                    <button class="btn btn-primary" onclick="PermissionsApp.openPermissionModal()">
                        <i class="fas fa-plus"></i> <span data-i18n="permissions.add_permission"><?= __t('permissions.add_permission', 'Add Permission') ?></span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div id="permissionsLoading" class="loading">
                    <div class="spinner"></div>
                    <p data-i18n="permissions.loading"><?= __t('permissions.loading', 'Loading...') ?></p>
                </div>
                <div id="permissionsContent" style="display:none;">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th data-i18n="permissions.table.id"><?= __t('permissions.table.id', 'ID') ?></th>
                                    <th data-i18n="permissions.table.name"><?= __t('permissions.table.name', 'Name') ?></th>
                                    <th data-i18n="permissions.table.key"><?= __t('permissions.table.key', 'Key') ?></th>
                                    <th data-i18n="permissions.table.description"><?= __t('permissions.table.description', 'Description') ?></th>
                                    <th data-i18n="permissions.table.actions"><?= __t('permissions.table.actions', 'Actions') ?></th>
                                </tr>
                            </thead>
                            <tbody id="permissionsTableBody"></tbody>
                        </table>
                    </div>
                </div>
                <div id="permissionsEmpty" class="empty-state" style="display:none;">
                    <i class="fas fa-key"></i>
                    <h3 data-i18n="permissions.no_permissions"><?= __t('permissions.no_permissions', 'No Permissions') ?></h3>
                    <?php if ($canCreate): ?>
                    <button class="btn btn-primary" onclick="PermissionsApp.openPermissionModal()">
                        <i class="fas fa-plus"></i> <span data-i18n="permissions.add_first_permission"><?= __t('permissions.add_first_permission', 'Add First Permission') ?></span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB: ASSIGN -->
    <div class="tab-content" id="tab-assign">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-tag"></i> <span data-i18n="permissions.select_role"><?= __t('permissions.select_role', 'Select Role') ?></span></h3>
                <input type="text" id="assignRolesSearch" class="form-control" data-i18n-placeholder="permissions.search_roles" placeholder="<?= __t('permissions.search_roles', 'Search roles...') ?>" style="width:250px;">
            </div>
            <div class="card-body">
                <div class="role-selector" id="assignRoleSelector"></div>
            </div>
        </div>
        <div class="card" id="assignCard" style="display:none;">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-link"></i> <span data-i18n="permissions.permissions_for"><?= __t('permissions.permissions_for', 'Permissions for') ?></span> <span id="assignRoleName"></span>
                </h3>
                <div class="actions">
                    <input type="text" id="assignPermSearch" class="form-control" data-i18n-placeholder="permissions.search" placeholder="<?= __t('permissions.search', 'Search...') ?>" style="width:200px;">
                    <button class="btn btn-primary btn-sm" onclick="PermissionsApp.selectAllAssign()">
                        <i class="fas fa-check-double"></i> <span data-i18n="permissions.select_all"><?= __t('permissions.select_all', 'Select All') ?></span>
                    </button>
                    <button class="btn btn-secondary btn-sm" onclick="PermissionsApp.deselectAllAssign()">
                        <i class="fas fa-times"></i> <span data-i18n="permissions.clear"><?= __t('permissions.clear', 'Clear') ?></span>
                    </button>
                    <?php if ($canEdit): ?>
                    <button class="btn btn-success" id="btnSaveAssign">
                        <i class="fas fa-save"></i> <span data-i18n="permissions.save"><?= __t('permissions.save', 'Save') ?></span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body" id="assignContent"></div>
        </div>
    </div>

    <!-- TAB: RESOURCES -->
    <div class="tab-content" id="tab-resources">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-tag"></i> <span data-i18n="permissions.select_role"><?= __t('permissions.select_role', 'Select Role') ?></span></h3>
                <input type="text" id="resourceRolesSearch" class="form-control" data-i18n-placeholder="permissions.search_roles" placeholder="<?= __t('permissions.search_roles', 'Search roles...') ?>" style="width:250px;">
            </div>
            <div class="card-body">
                <div class="role-selector" id="resourcesRoleSelector"></div>
            </div>
        </div>
        <div class="card" id="resourcesCard" style="display:none;">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-table-cells"></i> <span data-i18n="permissions.resources_for"><?= __t('permissions.resources_for', 'Resources for') ?></span> <span id="resourceRoleName"></span>
                </h3>
                <div class="actions">
                    <input type="text" id="resourcesSearch" class="form-control" data-i18n-placeholder="permissions.search" placeholder="<?= __t('permissions.search', 'Search...') ?>" style="width:200px;">
                    <?php if ($canCreate): ?>
                    <button class="btn btn-primary btn-sm" onclick="PermissionsApp.openResourcePermModal()">
                        <i class="fas fa-plus"></i> <span data-i18n="permissions.add_resource_permission"><?= __t('permissions.add_resource_permission', 'Add Resource Permission') ?></span>
                    </button>
                    <?php endif; ?>
                    <?php if ($canEdit): ?>
                    <button class="btn btn-success" id="btnSaveResource">
                        <i class="fas fa-save"></i> <span data-i18n="permissions.save_changes"><?= __t('permissions.save_changes', 'Save Changes') ?></span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div id="resourcesLoading" class="loading">
                    <div class="spinner"></div>
                    <p data-i18n="permissions.loading"><?= __t('permissions.loading', 'Loading...') ?></p>
                </div>
                <div id="resourcesContent" style="display:none;">
                    <div class="table-responsive">
                        <table class="data-table resource-table">
                            <thead>
                                <tr>
                                    <th class="sticky-col" data-i18n="permissions.table.permission_resource"><?= __t('permissions.table.permission_resource', 'Permission / Resource') ?></th>
                                    <th data-i18n="permissions.table.view_all"><?= __t('permissions.table.view_all', 'View All') ?></th>
                                    <th data-i18n="permissions.table.view_own"><?= __t('permissions.table.view_own', 'View Own') ?></th>
                                    <th data-i18n="permissions.table.view_tenant"><?= __t('permissions.table.view_tenant', 'View Tenant') ?></th>
                                    <th data-i18n="permissions.table.create"><?= __t('permissions.table.create', 'Create') ?></th>
                                    <th data-i18n="permissions.table.edit_all"><?= __t('permissions.table.edit_all', 'Edit All') ?></th>
                                    <th data-i18n="permissions.table.edit_own"><?= __t('permissions.table.edit_own', 'Edit Own') ?></th>
                                    <th data-i18n="permissions.table.delete_all"><?= __t('permissions.table.delete_all', 'Delete All') ?></th>
                                    <th data-i18n="permissions.table.delete_own"><?= __t('permissions.table.delete_own', 'Delete Own') ?></th>
                                </tr>
                            </thead>
                            <tbody id="resourcesTableBody"></tbody>
                        </table>
                    </div>
                </div>
                <div id="resourcesEmpty" class="empty-state" style="display:none;">
                    <i class="fas fa-table-cells"></i>
                    <h3 data-i18n="permissions.no_resource_permissions"><?= __t('permissions.no_resource_permissions', 'No Resource Permissions') ?></h3>
                    <p data-i18n="permissions.no_resource_permissions_desc"><?= __t('permissions.no_resource_permissions_desc', 'This role has no resource-level permissions configured') ?></p>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- MODALS -->
<div class="modal" id="roleModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title" id="roleModalTitle" data-i18n="permissions.add_role"><?= __t('permissions.add_role', 'Add Role') ?></h3>
            <button class="modal-close" onclick="PermissionsApp.closeRoleModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="roleForm" onsubmit="return false;">
                <input type="hidden" id="roleId">
                <div class="form-group">
                    <label class="form-label" data-i18n="permissions.form.display_name"><?= __t('permissions.form.display_name', 'Display Name') ?> *</label>
                    <input type="text" class="form-control" id="roleDisplayName" required data-i18n-placeholder="permissions.form.display_name_placeholder" placeholder="<?= __t('permissions.form.display_name_placeholder', 'e.g., Super Admin') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" data-i18n="permissions.form.key_name"><?= __t('permissions.form.key_name', 'Key Name') ?> *</label>
                    <input type="text" class="form-control" id="roleKeyName" required pattern="[a-z_]+" data-i18n-placeholder="permissions.form.key_name_placeholder" placeholder="<?= __t('permissions.form.key_name_placeholder', 'e.g., super_admin') ?>">
                    <small class="form-text" data-i18n="permissions.form.key_name_hint"><?= __t('permissions.form.key_name_hint', 'lowercase and underscores only') ?></small>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="PermissionsApp.closeRoleModal()" data-i18n="permissions.cancel"><?= __t('permissions.cancel', 'Cancel') ?></button>
            <button class="btn btn-primary" id="btnSaveRole">
                <i class="fas fa-save"></i> <span data-i18n="permissions.save"><?= __t('permissions.save', 'Save') ?></span>
            </button>
        </div>
    </div>
</div>

<div class="modal" id="permissionModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title" id="permissionModalTitle" data-i18n="permissions.add_permission"><?= __t('permissions.add_permission', 'Add Permission') ?></h3>
            <button class="modal-close" onclick="PermissionsApp.closePermissionModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="permissionForm" onsubmit="return false;">
                <input type="hidden" id="permissionId">
                <div class="form-group">
                    <label class="form-label" data-i18n="permissions.form.display_name"><?= __t('permissions.form.display_name', 'Display Name') ?> *</label>
                    <input type="text" class="form-control" id="permissionDisplayName" required data-i18n-placeholder="permissions.form.permission_display_placeholder" placeholder="<?= __t('permissions.form.permission_display_placeholder', 'e.g., Manage Users') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" data-i18n="permissions.form.key_name"><?= __t('permissions.form.key_name', 'Key Name') ?> *</label>
                    <input type="text" class="form-control" id="permissionKeyName" required pattern="[a-z_]+" data-i18n-placeholder="permissions.form.permission_key_placeholder" placeholder="<?= __t('permissions.form.permission_key_placeholder', 'e.g., manage_users') ?>">
                    <small class="form-text" data-i18n="permissions.form.key_name_hint"><?= __t('permissions.form.key_name_hint', 'lowercase and underscores only') ?></small>
                </div>
                <div class="form-group">
                    <label class="form-label" data-i18n="permissions.form.description"><?= __t('permissions.form.description', 'Description') ?></label>
                    <textarea class="form-control" id="permissionDescription" rows="3" data-i18n-placeholder="permissions.form.description_placeholder" placeholder="<?= __t('permissions.form.description_placeholder', 'Describe what this permission allows...') ?>"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="PermissionsApp.closePermissionModal()" data-i18n="permissions.cancel"><?= __t('permissions.cancel', 'Cancel') ?></button>
            <button class="btn btn-primary" id="btnSavePermission">
                <i class="fas fa-save"></i> <span data-i18n="permissions.save"><?= __t('permissions.save', 'Save') ?></span>
            </button>
        </div>
    </div>
</div>

<!-- Resource Permission Modal (create/edit single resource_permission) -->
<div class="modal" id="resourcePermModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title" id="resourcePermModalTitle" data-i18n="permissions.add_resource_permission"><?= __t('permissions.add_resource_permission', 'Add Resource Permission') ?></h3>
            <button class="modal-close" onclick="PermissionsApp.closeResourcePermModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="resourcePermForm" onsubmit="return false;">
                <input type="hidden" id="rpId">
                <div class="form-group">
                    <label data-i18n="permissions.form.resource_type"><?= __t('permissions.form.resource_type', 'Resource Type') ?> *</label>
                    <input id="rpResourceType" class="form-control" required data-i18n-placeholder="permissions.form.resource_type_placeholder" placeholder="<?= __t('permissions.form.resource_type_placeholder', 'e.g., users') ?>">
                </div>

                <div class="form-group">
                    <label data-i18n="permissions.form.permission"><?= __t('permissions.form.permission', 'Permission') ?> *</label>
                    <select id="rpPermissionId" class="form-control" required>
                        <option value="" data-i18n="permissions.form.loading"><?= __t('permissions.form.loading', 'Loading...') ?></option>
                    </select>
                </div>

                <div class="form-group">
                    <label data-i18n="permissions.form.role_optional"><?= __t('permissions.form.role_optional', 'Role (optional)') ?></label>
                    <select id="rpRoleId" class="form-control">
                        <option value="" data-i18n="permissions.form.any_global"><?= __t('permissions.form.any_global', '— Any / Global —') ?></option>
                    </select>
                </div>

                <div class="form-group">
                    <label data-i18n="permissions.form.tenant_optional"><?= __t('permissions.form.tenant_optional', 'Tenant (optional)') ?></label>
                    <select id="rpTenantId" class="form-control">
                        <option value="0" data-i18n="permissions.global_no_tenant"><?= __t('permissions.global_no_tenant', 'Global (no tenant)') ?></option>
                        <?php foreach ($allTenants as $tenant): ?>
                            <option value="<?= (int)$tenant['id'] ?>"><?= htmlspecialchars($tenant['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group flags-grid">
                    <label><input type="checkbox" id="rp_can_view_all"> <span data-i18n="permissions.flags.view_all"><?= __t('permissions.flags.view_all', 'View All') ?></span></label>
                    <label><input type="checkbox" id="rp_can_view_own"> <span data-i18n="permissions.flags.view_own"><?= __t('permissions.flags.view_own', 'View Own') ?></span></label>
                    <label><input type="checkbox" id="rp_can_view_tenant"> <span data-i18n="permissions.flags.view_tenant"><?= __t('permissions.flags.view_tenant', 'View Tenant') ?></span></label>
                    <label><input type="checkbox" id="rp_can_create"> <span data-i18n="permissions.flags.create"><?= __t('permissions.flags.create', 'Create') ?></span></label>
                    <label><input type="checkbox" id="rp_can_edit_all"> <span data-i18n="permissions.flags.edit_all"><?= __t('permissions.flags.edit_all', 'Edit All') ?></span></label>
                    <label><input type="checkbox" id="rp_can_edit_own"> <span data-i18n="permissions.flags.edit_own"><?= __t('permissions.flags.edit_own', 'Edit Own') ?></span></label>
                    <label><input type="checkbox" id="rp_can_delete_all"> <span data-i18n="permissions.flags.delete_all"><?= __t('permissions.flags.delete_all', 'Delete All') ?></span></label>
                    <label><input type="checkbox" id="rp_can_delete_own"> <span data-i18n="permissions.flags.delete_own"><?= __t('permissions.flags.delete_own', 'Delete Own') ?></span></label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="PermissionsApp.closeResourcePermModal()" data-i18n="permissions.cancel"><?= __t('permissions.cancel', 'Cancel') ?></button>
            <button class="btn btn-primary" id="btnSaveResourcePerm">
                <i class="fas fa-save"></i> <span data-i18n="permissions.save"><?= __t('permissions.save', 'Save') ?></span>
            </button>
        </div>
    </div>
</div>

<script>
window.APP_CONFIG = window.APP_CONFIG || {};
window.APP_CONFIG.API_BASE = window.APP_CONFIG.API_BASE || '<?= $apiBase ?>';
window.APP_CONFIG.TENANT_ID = window.APP_CONFIG.TENANT_ID || <?= $tenantId === null ? 0 : (int)$tenantId ?>;
window.APP_CONFIG.CSRF_TOKEN = window.APP_CONFIG.CSRF_TOKEN || '<?= htmlspecialchars($csrf, ENT_QUOTES) ?>';
window.APP_CONFIG.IS_SUPER_ADMIN = <?= $isSuperAdmin ? 'true' : 'false' ?>;
window.APP_CONFIG.USER_LANG = '<?= addslashes($lang) ?>';

// Page permissions available to JS
window.PAGE_PERMISSIONS = <?= json_encode([
    'canCreate' => $canCreate,
    'canEdit' => $canEdit,
    'canDelete' => $canDelete,
    'canViewAll' => $canViewAll,
    'canViewOwn' => $canViewOwn,
    'canViewTenant' => $canViewTenant,
    'canEditAll' => $canEditAll,
    'canEditOwn' => $canEditOwn,
    'canDeleteAll' => $canDeleteAll,
    'canDeleteOwn' => $canDeleteOwn,
    'isSuperAdmin' => $isSuperAdmin
], JSON_UNESCAPED_UNICODE) ?>;
</script>

<script src="/admin/assets/js/permissions-system.js?v=<?= time() ?>"></script>

<?php
// Load footer if standalone
if (!$isFragment) {
    require_once __DIR__ . '/../includes/footer.php';
}
?> 


//////////////////////////////
(function () {
  'use strict';

  // ----------------------------
  // Configuration & State
  // ----------------------------
  const APP_CONFIG = window.APP_CONFIG || {
    API_BASE: '/api',
    TENANT_ID: 0,
    CSRF_TOKEN: '',
    IS_SUPER_ADMIN: false,
    USER_LANG: 'en'
  };

  APP_CONFIG.TENANT_ID = Number(APP_CONFIG.TENANT_ID || 0);
  const LANG = (APP_CONFIG.USER_LANG || 'en').toLowerCase().startsWith('ar') ? 'ar' : 'en';
  const IS_RTL = LANG === 'ar';

  const STR = {
    en: {
      loading: 'Loading...',
      saving: 'Saving...',
      saved: 'Saved',
      save_failed: 'Failed to save',
      fill_required: 'Please fill required fields',
      select_role_first: 'Please select a role first',
      refresh: 'Refreshing data...',
      delete_confirm: 'This action cannot be undone. Continue?',
      error_generic: 'An unexpected error occurred',
      global_no_tenant: 'Global (no tenant)',
      create_resource_permission: 'Add Resource Permission',
      edit_resource_permission: 'Edit Resource Permission',
      no_changes: 'No changes to save',
      updated_success: 'Updated successfully'
    },
    ar: {
      loading: 'جارٍ التحميل...',
      saving: 'جارٍ الحفظ...',
      saved: 'تم الحفظ',
      save_failed: 'فشل الحفظ',
      fill_required: 'يرجى ملء الحقول المطلوبة',
      select_role_first: 'يرجى اختيار دور أولاً',
      refresh: 'جارٍ تحديث البيانات...',
      delete_confirm: 'هذا الإجراء لا يمكن التراجع عنه. متابعة؟',
      error_generic: 'حدث خطأ غير متوقع',
      global_no_tenant: 'عام (بدون مستأجر)',
      create_resource_permission: 'إضافة صلاحية مورد',
      edit_resource_permission: 'تعديل صلاحية مورد',
      no_changes: 'لا توجد تغييرات للحفظ',
      updated_success: 'تم التحديث بنجاح'
    }
  }[LANG];

  function t(k) { return STR[k] || k; }

  const STATE = {
    tenantId: APP_CONFIG.TENANT_ID,
    roles: [],
    permissions: [],
    tenants: [],
    selectedRoleId: null,
    selectedResourceRoleId: null
  };

  // Simple in-memory cache
  const CACHE = new Map();
  const CACHE_TTL = 1000 * 60 * 5; // 5 minutes

  function cacheSet(key, value) { CACHE.set(key, { ts: Date.now(), v: value }); }
  function cacheGet(key) {
    const r = CACHE.get(key);
    if (!r) return null;
    if (Date.now() - r.ts > CACHE_TTL) { CACHE.delete(key); return null; }
    return r.v;
  }
  function cacheDel(prefix) {
    for (const k of Array.from(CACHE.keys())) if (k.startsWith(prefix)) CACHE.delete(k);
  }

  // ----------------------------
  // Utilities
  // ----------------------------
  function log(...args) { console.debug('[PermissionsApp]', ...args); }
  function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; }
  function buildQS(obj) {
    return Object.keys(obj).filter(k => obj[k] !== undefined && obj[k] !== null && obj[k] !== '').map(k => `${encodeURIComponent(k)}=${encodeURIComponent(obj[k])}`).join('&');
  }
  function payloadTenantValue(v) { 
    // Handle tenant_id conversion: 0 → null for backend
    if (v === 0 || v === '0' || v === null || v === undefined || v === '') {
      return null;
    }
    return Number(v);
  }

  // show alert
  function showAlert(type, msg, ttl = 5000) {
    const container = document.getElementById('alertsContainer');
    if (!container) return;
    const div = document.createElement('div');
    div.className = `alert alert-${type}`;
    div.role = 'alert';
    div.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> <span>${escapeHtml(msg)}</span>`;
    container.appendChild(div);
    setTimeout(() => { div.style.opacity = '0'; setTimeout(() => div.remove(), 300); }, ttl);
  }

  // fetch wrapper
  async function apiCall(path, opts = {}) {
    if (!APP_CONFIG || !APP_CONFIG.API_BASE) throw new Error('APP_CONFIG.API_BASE not set');
    const url = APP_CONFIG.API_BASE.replace(/\/$/, '') + path;
    const method = (opts.method || 'GET').toUpperCase();
    const headers = Object.assign({ 'Content-Type': 'application/json', 'X-CSRF-Token': APP_CONFIG.CSRF_TOKEN }, opts.headers || {});
    const cfg = { method, headers, credentials: 'same-origin' };
    if (opts.body !== undefined) cfg.body = JSON.stringify(opts.body);

    const isGet = method === 'GET';
    const attempts = isGet ? 3 : 1;
    let lastErr;
    for (let i = 0; i < attempts; i++) {
      try {
        log('apiCall', method, url, opts.body || '');
        const res = await fetch(url, cfg);
        const text = await res.text();
        let json;
        try { json = text ? JSON.parse(text) : {}; } catch (e) { throw new Error('Invalid JSON response from server'); }
        if (!res.ok) {
          const msg = json && json.message ? json.message : `HTTP ${res.status}`;
          throw new Error(msg);
        }
        if (json && typeof json === 'object' && 'success' in json) {
          if (!json.success) throw new Error(json.message || 'Request failed');
          return json;
        }
        return { success: true, data: json };
      } catch (err) {
        lastErr = err;
        log('apiCall error', err.message, 'attempt', i + 1);
        if (i < attempts - 1) await new Promise(r => setTimeout(r, 200 * (i + 1)));
      }
    }
    throw lastErr;
  }

  // ----------------------------
  // Data loaders
  // ----------------------------
  async function loadRolesIfNeeded(force = false) {
    const key = `roles:${STATE.tenantId}`;
    if (!force) {
      const c = cacheGet(key);
      if (c) { STATE.roles = c; return c; }
    }
    const q = buildQS({ tenant_id: STATE.tenantId || 0 });
    const res = await apiCall(`/roles?${q}`);
    const rows = res.data || [];
    STATE.roles = Array.isArray(rows) ? rows : [];
    cacheSet(key, STATE.roles);
    return STATE.roles;
  }

  async function loadPermissionsIfNeeded(force = false) {
    const key = `permissions:${STATE.tenantId}`;
    if (!force) {
      const c = cacheGet(key);
      if (c) { STATE.permissions = c; return c; }
    }
    const q = buildQS({ tenant_id: STATE.tenantId || 0 });
    const res = await apiCall(`/permissions?${q}`);
    const rows = res.data || [];
    STATE.permissions = Array.isArray(rows) ? rows : [];
    cacheSet(key, STATE.permissions);
    return STATE.permissions;
  }

  async function loadTenantsIfNeeded(force = false) {
    const key = `tenants:all`;
    if (!force) {
      const c = cacheGet(key);
      if (c) { STATE.tenants = c; return c; }
    }
    const res = await apiCall('/tenants');
    const rows = res.data || [];
    STATE.tenants = Array.isArray(rows) ? rows : [];
    cacheSet(key, STATE.tenants);
    return STATE.tenants;
  }

  // ----------------------------
  // Roles CRUD
  // ----------------------------
  async function renderRolesTable() {
    const loading = document.getElementById('rolesLoading');
    const content = document.getElementById('rolesContent');
    const empty = document.getElementById('rolesEmpty');
    const tbody = document.getElementById('rolesTableBody');
    if (!tbody) return;
    loading.style.display = 'block';
    content.style.display = 'none';
    empty.style.display = 'none';
    try {
      await loadRolesIfNeeded(true);
      const rows = STATE.roles;
      if (!rows.length) { loading.style.display = 'none'; empty.style.display = 'block'; tbody.innerHTML = ''; return; }
      tbody.innerHTML = rows.map(r => `
        <tr>
          <td>${r.id}</td>
          <td><strong>${escapeHtml(r.display_name)}</strong></td>
          <td><code>${escapeHtml(r.key_name)}</code></td>
          <td><small>${escapeHtml(r.created_at || '')}</small></td>
          <td>
            <button class="btn btn-sm btn-primary" data-role-edit="${r.id}" aria-label="${t('edit')} role"><i class="fas fa-edit"></i></button>
            <button class="btn btn-sm btn-danger" data-role-delete="${r.id}" data-role-name="${escapeHtml(r.display_name)}" aria-label="${t('delete')} role"><i class="fas fa-trash"></i></button>
          </td>
        </tr>
      `).join('');
      tbody.querySelectorAll('[data-role-edit]').forEach(b => b.addEventListener('click', () => openRoleModal(Number(b.dataset.roleEdit))));
      tbody.querySelectorAll('[data-role-delete]').forEach(b => b.addEventListener('click', () => deleteRole(Number(b.dataset.roleDelete), b.dataset.roleName)));
      loading.style.display = 'none';
      content.style.display = 'block';
    } catch (err) {
      loading.style.display = 'none';
      empty.style.display = 'block';
      console.error('renderRolesTable error', err);
      showAlert('error', `${t('error_generic')}: ${err.message}`);
    }
  }

  function openRoleModal(id = null) {
    const modal = document.getElementById('roleModal');
    const title = document.getElementById('roleModalTitle');
    const form = document.getElementById('roleForm');
    if (!modal || !form) return;
    form.reset();
    document.getElementById('roleId').value = '';
    if (id) {
      const r = STATE.roles.find(x => Number(x.id) === Number(id));
      if (r) {
        title.textContent = LANG === 'ar' ? 'تعديل الدور' : 'Edit Role';
        document.getElementById('roleId').value = r.id;
        document.getElementById('roleDisplayName').value = r.display_name;
        document.getElementById('roleKeyName').value = r.key_name;
      }
    } else {
      title.textContent = LANG === 'ar' ? 'إضافة دور' : 'Add Role';
    }
    modal.classList.add('active');
    setTimeout(() => document.getElementById('roleDisplayName').focus(), 120);
  }

  function closeRoleModal() {
    const modal = document.getElementById('roleModal');
    if (modal) modal.classList.remove('active');
  }

  let saveRoleInFlight = false;

  async function saveRole() {
    if (saveRoleInFlight) { console.warn('saveRole already running, skipping'); return; }
    const btn = document.getElementById('btnSaveRole');
    if (!btn) return;
    const id = document.getElementById('roleId').value || null;
    const display_name = document.getElementById('roleDisplayName').value.trim();
    const key_name = document.getElementById('roleKeyName').value.trim();
    if (!display_name || !key_name) { showAlert('warning', t('fill_required')); return; }
    saveRoleInFlight = true;
    btn.disabled = true;
    btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${t('saving')}`;
    try {
      const payload = { tenant_id: payloadTenantValue(STATE.tenantId), display_name, key_name };
      if (id) {
        payload.id = Number(id);
        await apiCall('/roles', { method: 'PUT', body: payload });
      } else {
        await apiCall('/roles', { method: 'POST', body: payload });
      }
      cacheDel('roles:');
      await renderRolesTable();
      closeRoleModal();
      showAlert('success', t('saved'));
    } catch (err) {
      console.error('saveRole', err);
      showAlert('error', `${t('save_failed')}: ${err.message}`);
    } finally {
      btn.disabled = false;
      btn.innerHTML = `<i class="fas fa-save"></i> ${LANG === 'ar' ? 'حفظ' : 'Save'}`;
      saveRoleInFlight = false;
    }
  }

  async function deleteRole(id, name) {
    if (!confirm(`${t('delete_confirm')}\n${name}`)) return;
    try {
      await apiCall('/roles', { method: 'DELETE', body: { id: Number(id), tenant_id: payloadTenantValue(STATE.tenantId) } });
      cacheDel('roles:'); await renderRolesTable();
      showAlert('success', 'Deleted');
    } catch (err) {
      console.error('deleteRole', err);
      showAlert('error', `${t('error_generic')}: ${err.message}`);
    }
  }

  // ----------------------------
  // Permissions CRUD
  // ----------------------------
  async function renderPermissionsTable() {
    const loading = document.getElementById('permissionsLoading');
    const content = document.getElementById('permissionsContent');
    const empty = document.getElementById('permissionsEmpty');
    const tbody = document.getElementById('permissionsTableBody');
    if (!tbody) return;
    loading.style.display = 'block'; content.style.display = 'none'; empty.style.display = 'none';
    try {
      await loadPermissionsIfNeeded(true);
      const rows = STATE.permissions;
      if (!rows.length) { loading.style.display = 'none'; empty.style.display = 'block'; tbody.innerHTML = ''; return; }
      tbody.innerHTML = rows.map(p => `
        <tr>
          <td>${p.id}</td>
          <td><strong>${escapeHtml(p.display_name)}</strong></td>
          <td><code>${escapeHtml(p.key_name)}</code></td>
          <td><small>${escapeHtml(p.description || '')}</small></td>
          <td>
            <button class="btn btn-sm btn-primary" data-perm-edit="${p.id}"><i class="fas fa-edit"></i></button>
            <button class="btn btn-sm btn-danger" data-perm-delete="${p.id}" data-perm-name="${escapeHtml(p.display_name)}"><i class="fas fa-trash"></i></button>
          </td>
        </tr>
      `).join('');
      tbody.querySelectorAll('[data-perm-edit]').forEach(b => b.addEventListener('click', () => openPermissionModal(Number(b.dataset.permEdit))));
      tbody.querySelectorAll('[data-perm-delete]').forEach(b => b.addEventListener('click', () => deletePermission(Number(b.dataset.permDelete), b.dataset.permName)));
      loading.style.display = 'none'; content.style.display = 'block';
    } catch (err) {
      loading.style.display = 'none'; empty.style.display = 'block';
      console.error('renderPermissionsTable', err);
      showAlert('error', `${t('error_generic')}: ${err.message}`);
    }
  }

  function openPermissionModal(id = null) {
    const modal = document.getElementById('permissionModal');
    const title = document.getElementById('permissionModalTitle');
    const form = document.getElementById('permissionForm');
    if (!modal || !form) return;
    form.reset(); document.getElementById('permissionId').value = '';
    if (id) {
      const p = STATE.permissions.find(x => Number(x.id) === Number(id));
      if (p) {
        title.textContent = LANG === 'ar' ? 'تعديل صلاحية' : 'Edit Permission';
        document.getElementById('permissionId').value = p.id;
        document.getElementById('permissionDisplayName').value = p.display_name;
        document.getElementById('permissionKeyName').value = p.key_name;
        document.getElementById('permissionDescription').value = p.description || '';
      }
    } else {
      title.textContent = LANG === 'ar' ? 'إضافة صلاحية' : 'Add Permission';
    }
    modal.classList.add('active'); setTimeout(() => document.getElementById('permissionDisplayName').focus(), 120);
  }

  function closePermissionModal() { const modal = document.getElementById('permissionModal'); if (modal) modal.classList.remove('active'); }

  let savePermissionInFlight = false;

  async function savePermission() {
    if (savePermissionInFlight) { console.warn('savePermission already running, skipping'); return; }
    const btn = document.getElementById('btnSavePermission');
    if (!btn) return;
    const id = document.getElementById('permissionId').value || null;
    const display_name = document.getElementById('permissionDisplayName').value.trim();
    const key_name = document.getElementById('permissionKeyName').value.trim();
    const description = document.getElementById('permissionDescription').value.trim();
    if (!display_name || !key_name) { showAlert('warning', t('fill_required')); return; }
    savePermissionInFlight = true;
    btn.disabled = true; btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${t('saving')}`;
    try {
      const payload = { tenant_id: payloadTenantValue(STATE.tenantId), display_name, key_name, description };
      if (id) { payload.id = Number(id); await apiCall('/permissions', { method: 'PUT', body: payload }); }
      else await apiCall('/permissions', { method: 'POST', body: payload });
      cacheDel('permissions:'); await renderPermissionsTable(); closePermissionModal(); showAlert('success', t('saved'));
    } catch (err) {
      console.error('savePermission', err);
      showAlert('error', `${t('save_failed')}: ${err.message}`);
    } finally {
      btn.disabled = false;
      btn.innerHTML = `<i class="fas fa-save"></i> ${LANG === 'ar' ? 'حفظ' : 'Save'}`;
      savePermissionInFlight = false;
    }
  }

  async function deletePermission(id, name) {
    if (!confirm(`${t('delete_confirm')}\n${name}`)) return;
    try { await apiCall('/permissions', { method: 'DELETE', body: { id: Number(id), tenant_id: payloadTenantValue(STATE.tenantId) } }); cacheDel('permissions:'); await renderPermissionsTable(); showAlert('success', 'Deleted'); }
    catch (err) { console.error('deletePermission', err); showAlert('error', `${t('error_generic')}: ${err.message}`); }
  }

  // ----------------------------
  // Assign
  // ----------------------------
  async function loadAssignRoles() {
    const container = document.getElementById('assignRoleSelector');
    if (!container) return;
    if (!STATE.roles.length) await loadRolesIfNeeded(true);
    container.innerHTML = STATE.roles.map(r => `<div class="role-card" data-role-id="${r.id}" data-role-name="${escapeHtml(r.display_name)}">${escapeHtml(r.display_name)}</div>`).join('');
    container.querySelectorAll('.role-card').forEach(card => card.addEventListener('click', () => {
      const id = Number(card.dataset.roleId); const name = card.dataset.roleName || '';
      selectAssignRole(id, name);
    }));
  }

  async function selectAssignRole(roleId, roleName) {
    STATE.selectedRoleId = roleId;
    document.querySelectorAll('#assignRoleSelector .role-card').forEach(c => c.classList.remove('selected'));
    const el = document.querySelector(`#assignRoleSelector .role-card[data-role-id="${roleId}"]`);
    if (el) el.classList.add('selected');
    document.getElementById('assignRoleName').textContent = roleName;
    document.getElementById('assignCard').style.display = 'block';
    try {
      if (!STATE.permissions.length) await loadPermissionsIfNeeded(true);
      const q = buildQS({ role_id: roleId, tenant_id: STATE.tenantId || 0 });
      const res = await apiCall(`/role_permissions?${q}`);
      const assigned = (res.data || []).map(x => Number(x.permission_id));
      const grouped = {};
      STATE.permissions.forEach(p => {
        const cat = p.key_name.split('_')[0] || 'general';
        grouped[cat] = grouped[cat] || [];
        grouped[cat].push(p);
      });
      let html = '';
      Object.keys(grouped).forEach(cat => {
        html += `<div class="permission-group"><h4 class="permission-group-title">${escapeHtml(cat)}</h4><div class="permission-grid">`;
        grouped[cat].forEach(p => {
          const checked = assigned.includes(p.id) ? 'checked' : '';
          html += `<label class="permission-checkbox-item"><input type="checkbox" class="assign-cb" data-id="${p.id}" ${checked}><div class="perm-meta"><div class="perm-name">${escapeHtml(p.display_name)}</div><div class="perm-desc">${escapeHtml(p.description || p.key_name)}</div></div></label>`;
        });
        html += `</div></div>`;
      });
      document.getElementById('assignContent').innerHTML = html;
    } catch (err) {
      console.error('selectAssignRole', err); showAlert('error', `${t('error_generic')}: ${err.message}`);
    }
  }

  let saveAssignInFlight = false;

  async function saveAssign() {
    if (!STATE.selectedRoleId) { showAlert('warning', t('select_role_first')); return; }
    if (saveAssignInFlight) { console.warn('saveAssign already running, skipping'); return; }
    const btn = document.getElementById('btnSaveAssign');
    if (!btn) return;
    saveAssignInFlight = true;
    btn.disabled = true; btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${t('saving')}`;
    try {
      const selected = Array.from(document.querySelectorAll('.assign-cb:checked')).map(cb => Number(cb.dataset.id));
      const payload = selected.map(pid => ({ tenant_id: payloadTenantValue(STATE.tenantId), role_id: STATE.selectedRoleId, permission_id: pid }));
      await apiCall('/role_permissions', { method: 'POST', body: payload });
      cacheDel('role_permissions:'); showAlert('success', t('saved'));
    } catch (err) {
      console.error('saveAssign', err);
      showAlert('error', `${t('save_failed')}: ${err.message}`);
    } finally {
      btn.disabled = false;
      btn.innerHTML = `<i class="fas fa-save"></i> ${LANG === 'ar' ? 'حفظ' : 'Save'}`;
      saveAssignInFlight = false;
    }
  }

  // ----------------------------
  // Resource Permissions
  // ----------------------------
  async function loadResourceRolesSelector() {
    const container = document.getElementById('resourcesRoleSelector');
    if (!container) return;
    if (!STATE.roles.length) await loadRolesIfNeeded(true);
    container.innerHTML = STATE.roles.map(r => `<div class="role-card" data-role-id="${r.id}" data-role-name="${escapeHtml(r.display_name)}">${escapeHtml(r.display_name)}</div>`).join('');
    container.querySelectorAll('.role-card').forEach(card => card.addEventListener('click', () => {
      const id = Number(card.dataset.roleId); const name = card.dataset.roleName || '';
      selectResourceRole(id, name);
    }));
  }

  async function selectResourceRole(roleId, roleName) {
    STATE.selectedResourceRoleId = roleId;
    document.querySelectorAll('#resourcesRoleSelector .role-card').forEach(c => c.classList.remove('selected'));
    const el = document.querySelector(`#resourcesRoleSelector .role-card[data-role-id="${roleId}"]`);
    if (el) el.classList.add('selected');
    document.getElementById('resourceRoleName').textContent = roleName;
    document.getElementById('resourcesCard').style.display = 'block';

    const loading = document.getElementById('resourcesLoading');
    const content = document.getElementById('resourcesContent');
    const empty = document.getElementById('resourcesEmpty');
    const tbody = document.getElementById('resourcesTableBody');
    loading.style.display = 'block'; content.style.display = 'none'; empty.style.display = 'none'; tbody.innerHTML = '';
    try {
      const q = buildQS({ role_id: roleId, tenant_id: STATE.tenantId || 0 });
      const res = await apiCall(`/resource_permissions?${q}`);
      const rows = res.data || [];
      if (!rows.length) { loading.style.display = 'none'; empty.style.display = 'block'; return; }
      
      // FIXED: Store tenant_id as 0 for null to avoid dataset issues
      tbody.innerHTML = rows.map(p => {
        const tenantId = p.tenant_id === null ? 0 : p.tenant_id;
        return `
        <tr data-id="${p.id}" data-tenant-id="${tenantId}">
          <td class="sticky-col">
            <div style="font-weight:600">${escapeHtml(p.display_name || p.key_name)}</div>
            <div style="font-size:.75rem;color:#666">${escapeHtml(p.resource_type)}</div>
            <div style="font-size:.7rem;color:#999">${p.tenant_id ? 'Tenant: ' + p.tenant_id : 'Global'}${p.role_id ? ' / Role: ' + p.role_id : ''}</div>
          </td>
          <td><input type="checkbox" class="res-cb" data-field="can_view_all" ${p.can_view_all ? 'checked' : ''}></td>
          <td><input type="checkbox" class="res-cb" data-field="can_view_own" ${p.can_view_own ? 'checked' : ''}></td>
          <td><input type="checkbox" class="res-cb" data-field="can_view_tenant" ${p.can_view_tenant ? 'checked' : ''}></td>
          <td><input type="checkbox" class="res-cb" data-field="can_create" ${p.can_create ? 'checked' : ''}></td>
          <td><input type="checkbox" class="res-cb" data-field="can_edit_all" ${p.can_edit_all ? 'checked' : ''}></td>
          <td><input type="checkbox" class="res-cb" data-field="can_edit_own" ${p.can_edit_own ? 'checked' : ''}></td>
          <td><input type="checkbox" class="res-cb" data-field="can_delete_all" ${p.can_delete_all ? 'checked' : ''}></td>
          <td><input type="checkbox" class="res-cb" data-field="can_delete_own" ${p.can_delete_own ? 'checked' : ''}></td>
          <td>
            <button class="btn btn-xs btn-secondary" data-rp-edit="${p.id}"><i class="fas fa-edit"></i></button>
            <button class="btn btn-xs btn-danger" data-rp-delete="${p.id}"><i class="fas fa-trash"></i></button>
          </td>
        </tr>`;
      }).join('');
      
      tbody.querySelectorAll('[data-rp-edit]').forEach(b => b.addEventListener('click', () => openResourcePermModal(Number(b.dataset.rpEdit))));
      tbody.querySelectorAll('[data-rp-delete]').forEach(b => b.addEventListener('click', () => deleteResourcePerm(Number(b.dataset.rpDelete))));
      loading.style.display = 'none'; content.style.display = 'block';
    } catch (err) {
      loading.style.display = 'none'; empty.style.display = 'block';
      console.error('selectResourceRole', err); showAlert('error', `${t('error_generic')}: ${err.message}`);
    }
  }

  function openResourcePermModal(id = null) {
    const modal = document.getElementById('resourcePermModal');
    if (!modal) return;
    Promise.all([loadPermissionsIfNeeded(true), loadRolesIfNeeded(true)]).then(() => {
      const permSelect = document.getElementById('rpPermissionId');
      const roleSelect = document.getElementById('rpRoleId');
      if (permSelect) permSelect.innerHTML = `<option value="">-- Select --</option>` + STATE.permissions.map(p => `<option value="${p.id}">${escapeHtml(p.display_name)} (${escapeHtml(p.key_name)})</option>`).join('');
      if (roleSelect) roleSelect.innerHTML = `<option value="">— Any / Global —</option>` + STATE.roles.map(r => `<option value="${r.id}">${escapeHtml(r.display_name)}</option>`).join('');
      document.getElementById('resourcePermForm').reset();
      document.getElementById('rpId').value = '';
      const tsel = document.getElementById('rpTenantId'); if (tsel) tsel.value = String(STATE.tenantId || 0);
      if (!id) {
        document.getElementById('resourcePermModalTitle').textContent = t('create_resource_permission');
        modal.classList.add('active');
        setTimeout(() => document.getElementById('rpResourceType').focus(), 120);
        return;
      }
      apiCall(`/resource_permissions/${id}`).then(res => {
        const rp = res.data;
        if (!rp) throw new Error('Not found');
        document.getElementById('rpId').value = rp.id;
        document.getElementById('rpResourceType').value = rp.resource_type || '';
        document.getElementById('rpPermissionId').value = rp.permission_id || '';
        document.getElementById('rpRoleId').value = rp.role_id ? String(rp.role_id) : '';
        document.getElementById('rpTenantId').value = rp.tenant_id === null ? '0' : String(rp.tenant_id);
        ['can_view_all','can_view_own','can_view_tenant','can_create','can_edit_all','can_edit_own','can_delete_all','can_delete_own'].forEach(f => {
          const el = document.getElementById('rp_' + f);
          if (el) el.checked = !!rp[f];
        });
        document.getElementById('resourcePermModalTitle').textContent = t('edit_resource_permission');
        modal.classList.add('active');
      }).catch(err => { console.error('openResourcePermModal load', err); showAlert('error', `Failed to load resource permission: ${err.message}`); });
    }).catch(err => { console.error('openResourcePermModal init', err); showAlert('error', `Failed to prepare modal: ${err.message}`); });
  }

  function closeResourcePermModal() { const m = document.getElementById('resourcePermModal'); if (m) m.classList.remove('active'); }

  let saveResourcePermInFlight = false;

  async function saveResourcePerm() {
    if (saveResourcePermInFlight) { console.warn('saveResourcePerm already running, skipping'); return; }
    const btn = document.getElementById('btnSaveResourcePerm'); if (!btn) return;
    const id = document.getElementById('rpId').value || null;
    const resource_type = document.getElementById('rpResourceType').value.trim();
    const permission_id = Number(document.getElementById('rpPermissionId').value || 0);
    const roleVal = document.getElementById('rpRoleId').value; const role_id = roleVal === '' ? null : Number(roleVal);
    const tenantVal = document.getElementById('rpTenantId').value; const tenant_id = tenantVal === '0' ? null : Number(tenantVal);
    if (!resource_type || !permission_id) { showAlert('warning', t('fill_required')); return; }
    saveResourcePermInFlight = true;
    btn.disabled = true; btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${t('saving')}`;
    try {
      const payload = { resource_type, permission_id, role_id, tenant_id,
        can_view_all: document.getElementById('rp_can_view_all').checked ? 1 : 0,
        can_view_own: document.getElementById('rp_can_view_own').checked ? 1 : 0,
        can_view_tenant: document.getElementById('rp_can_view_tenant').checked ? 1 : 0,
        can_create: document.getElementById('rp_can_create').checked ? 1 : 0,
        can_edit_all: document.getElementById('rp_can_edit_all').checked ? 1 : 0,
        can_edit_own: document.getElementById('rp_can_edit_own').checked ? 1 : 0,
        can_delete_all: document.getElementById('rp_can_delete_all').checked ? 1 : 0,
        can_delete_own: document.getElementById('rp_can_delete_own').checked ? 1 : 0
      };
      if (id) { payload.id = Number(id); await apiCall('/resource_permissions', { method: 'PUT', body: payload }); }
      else await apiCall('/resource_permissions', { method: 'POST', body: payload });
      cacheDel('resource_permissions:');
      closeResourcePermModal();
      // Refresh table first, then show success message
      if (STATE.selectedResourceRoleId) {
        await selectResourceRole(STATE.selectedResourceRoleId, document.getElementById('resourceRoleName').textContent);
      }
      // Show success message AFTER table refresh completes
      showAlert('success', t('saved'));
    } catch (err) {
      console.error('saveResourcePerm', err);
      showAlert('error', `${t('save_failed')}: ${err.message}`);
    } finally {
      btn.disabled = false;
      btn.innerHTML = `<i class="fas fa-save"></i> ${LANG === 'ar' ? 'حفظ' : 'Save'}`;
      saveResourcePermInFlight = false;
    }
  }

  async function deleteResourcePerm(id) {
    if (!confirm(t('delete_confirm'))) return;
    try {
      await apiCall(`/resource_permissions/${id}`, { method: 'DELETE' });
      cacheDel('resource_permissions:'); showAlert('success', 'Deleted');
      if (STATE.selectedResourceRoleId) await selectResourceRole(STATE.selectedResourceRoleId, document.getElementById('resourceRoleName').textContent);
    } catch (err) { console.error('deleteResourcePerm', err); showAlert('error', `${t('error_generic')}: ${err.message}`); }
  }

  // ----------------------------
  // SAVE RESOURCES - FIXED VERSION
  // ----------------------------
  let saveResourcesInFlight = false;

  async function saveResources() {
    if (!STATE.selectedResourceRoleId) { 
      showAlert('warning', t('select_role_first')); 
      return; 
    }
    
    if (saveResourcesInFlight) { 
      console.warn('saveResources already running, skipping'); 
      return; 
    }
    
    saveResourcesInFlight = true;
    const btn = document.getElementById('btnSaveResource');
    
    if (btn) {
      btn.disabled = true;
      btn.style.pointerEvents = 'none';
      btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${t('saving')}`;
    }

    try {
      const rows = Array.from(document.querySelectorAll('#resourcesTableBody tr'));
      const items = [];

      for (const row of rows) {
        const id = row.dataset.id;
        if (!id) continue;
        
        const obj = { id: Number(id) };
        
        // Collect checkbox values
        row.querySelectorAll('.res-cb').forEach(cb => { 
          obj[cb.dataset.field] = cb.checked ? 1 : 0; 
        });
        
        // Always include role_id
        obj.role_id = Number(STATE.selectedResourceRoleId);
        
        // FIXED: Handle tenant_id properly
        const rowTenantId = row.dataset.tenantId;
        if (rowTenantId !== undefined && rowTenantId !== '') {
          // Convert to number first
          const tenantIdNum = Number(rowTenantId);
          // Convert 0 to null for backend
          obj.tenant_id = tenantIdNum === 0 ? null : tenantIdNum;
        } else {
          // Fallback to state tenantId, also convert 0 to null
          obj.tenant_id = STATE.tenantId === 0 ? null : Number(STATE.tenantId);
        }
        
        items.push(obj);
      }

      if (!items.length) { 
        showAlert('info', t('no_changes')); 
        return; 
      }

      console.log('Sending bulk update:', items);
      
      // Send as bulk update
      const response = await apiCall('/resource_permissions', {
        method: 'PUT',
        body: { updates: items }
      });
      
      if (response.success) {
        cacheDel('resource_permissions:');
        showAlert('success', `${t('updated_success')}: ${items.length} items`);
        
        // Refresh the table
        if (STATE.selectedResourceRoleId) {
          const roleName = document.getElementById('resourceRoleName').textContent;
          await selectResourceRole(STATE.selectedResourceRoleId, roleName);
        }
      } else {
        throw new Error(response.message || t('save_failed'));
      }
      
    } catch (err) {
      console.error('saveResources error:', err);
      showAlert('error', `${t('save_failed')}: ${err.message || t('error_generic')}`);
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.style.pointerEvents = 'auto';
        btn.innerHTML = `<i class="fas fa-save"></i> ${LANG === 'ar' ? 'حفظ التغييرات' : 'Save Changes'}`;
      }
      saveResourcesInFlight = false;
    }
  }

  // ----------------------------
  // Initialization
  // ----------------------------
  function bindUI() {
    const tsel = document.getElementById('tenantSelector');
    if (tsel) tsel.addEventListener('change', async (e) => {
      STATE.tenantId = Number(e.target.value || 0);
      cacheDel('roles:'); cacheDel('permissions:'); cacheDel('resource_permissions:');
      await renderRolesTable(); await renderPermissionsTable(); await loadResourceRolesSelector();
    });

    const btnSaveRole = document.getElementById('btnSaveRole');
    if (btnSaveRole) btnSaveRole.addEventListener('click', saveRole);

    const btnSavePerm = document.getElementById('btnSavePermission');
    if (btnSavePerm) btnSavePerm.addEventListener('click', savePermission);

    const btnSaveRP = document.getElementById('btnSaveResourcePerm');
    if (btnSaveRP) btnSaveRP.addEventListener('click', saveResourcePerm);

    const btnSaveResources = document.getElementById('btnSaveResource');
    if (btnSaveResources) btnSaveResources.addEventListener('click', saveResources);

    const btnSaveAssign = document.getElementById('btnSaveAssign');
    if (btnSaveAssign) btnSaveAssign.addEventListener('click', saveAssign);

    document.querySelectorAll('.main-tab').forEach(btn => btn.addEventListener('click', () => {
      document.querySelectorAll('.main-tab').forEach(t => t.classList.remove('active')); btn.classList.add('active');
      const name = btn.dataset.tab; document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active')); const ct = document.getElementById('tab-' + name); if (ct) ct.classList.add('active');
      if (name === 'assign') loadAssignRoles();
      if (name === 'resources') loadResourceRolesSelector();
    }));

    const rs = document.getElementById('rolesSearch'); if (rs) rs.addEventListener('input', e => { const q = e.target.value.toLowerCase(); document.querySelectorAll('#rolesTableBody tr').forEach(r => r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none'); });
    const ps = document.getElementById('permissionsSearch'); if (ps) ps.addEventListener('input', e => { const q = e.target.value.toLowerCase(); document.querySelectorAll('#permissionsTableBody tr').forEach(r => r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none'); });
    const rrs = document.getElementById('resourceRolesSearch'); if (rrs) rrs.addEventListener('input', e => { const q = e.target.value.toLowerCase(); document.querySelectorAll('#resourcesRoleSelector .role-card').forEach(c => c.style.display = c.textContent.toLowerCase().includes(q) ? '' : 'none'); });
  }

  async function init() {
    try {
      if (IS_RTL) document.documentElement.dir = 'rtl';
      bindUI();
      await Promise.all([loadTenantsIfNeeded(true), loadRolesIfNeeded(true), loadPermissionsIfNeeded(true)]);
      await renderRolesTable();
      await renderPermissionsTable();
      await loadResourceRolesSelector();
      log('PermissionsApp initialized');
    } catch (err) {
      console.error('init error', err);
      showAlert('error', `${t('error_generic')}: ${err.message}`);
    }
  }

  // ----------------------------
  // Public API
  // ----------------------------
  const Public = {
    loadRoles: renderRolesTable,
    openRoleModal,
    closeRoleModal,
    saveRole,
    deleteRole,
    loadPermissions: renderPermissionsTable,
    openPermissionModal,
    closePermissionModal,
    savePermission,
    deletePermission,
    loadAssignRoles,
    selectAssignRole,
    saveAssign,
    selectAllAssign: () => document.querySelectorAll('.assign-cb').forEach(cb => cb.checked = true),
    deselectAllAssign: () => document.querySelectorAll('.assign-cb').forEach(cb => cb.checked = false),
    loadResourceRoles: loadResourceRolesSelector,
    selectResourceRole,
    saveResources,
    openResourcePermModal,
    closeResourcePermModal,
    saveResourcePerm,
    deleteResourcePerm,
    refreshAll: async () => { cacheDel('roles:'); cacheDel('permissions:'); cacheDel('resource_permissions:'); await renderRolesTable(); await renderPermissionsTable(); await loadResourceRolesSelector(); showAlert('info', t('refresh')); }
  };

  window.PermissionsApp = Public;

  window.openRoleModal = (id) => Public.openRoleModal(id);
  window.closeRoleModal = () => Public.closeRoleModal();
  window.saveRole = () => Public.saveRole();
  window.openPermissionModal = (id) => Public.openPermissionModal(id);
  window.closePermissionModal = () => Public.closePermissionModal();
  window.savePermission = () => Public.savePermission();
  window.openResourcePermModal = (id) => Public.openResourcePermModal(id);
  window.closeResourcePermModal = () => Public.closeResourcePermModal();
  window.saveResourcePerm = () => Public.saveResourcePerm();
  window.saveResources = () => Public.saveResources();
  window.selectAssignRole = (id, name) => Public.selectAssignRole(id, name);
  window.saveAssign = () => Public.saveAssign();

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
//////////////////////////////







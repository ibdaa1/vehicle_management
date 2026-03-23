<?php
/**
 * Roles Fragment — Full Role & Permission Management (Admin)
 * Loaded inside dashboard.php shell.
 * 
 * Features:
 * - Roles CRUD (list, add, edit, delete)
 * - Permission matrix: grouped by module, checkbox per role
 * - Resource-level permissions (view_all, view_own, create, edit_all, etc.)
 */
?>
<style>
/* Fix LTR layout flash */
html[dir="ltr"] body{direction:ltr;font-family:var(--font-en)}
html[dir="ltr"] .app-sidebar{right:auto;left:0}
html[dir="ltr"] .app-main{margin-right:0;margin-left:var(--sidebar-width)}
html[dir="ltr"] .app-footer{margin-right:0;margin-left:var(--sidebar-width)}
html[dir="ltr"] .app-sidebar.collapsed~.app-main{margin-right:0;margin-left:var(--sidebar-collapsed-width)}
.r-toolbar{display:flex;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px}
.r-toolbar .search-box{position:relative;flex:1;min-width:200px;max-width:360px}
.r-toolbar .search-box input{width:100%;padding-inline-end:36px}
.r-toolbar .search-box .search-icon{position:absolute;top:50%;inset-inline-end:12px;transform:translateY(-50%);color:var(--text-secondary);pointer-events:none}
.r-toolbar-end{display:flex;align-items:center;gap:10px;margin-inline-start:auto}
.r-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px}
@media(max-width:768px){.r-stats{grid-template-columns:1fr 1fr}}
.r-stat{background:var(--bg-card);padding:16px;border-radius:12px;box-shadow:var(--card-shadow);border:1px solid var(--border-default);text-align:center}
.r-stat .r-stat-val{font-size:1.5rem;font-weight:700;color:var(--text-primary)}
.r-stat .r-stat-lbl{font-size:.8rem;color:var(--text-secondary);margin-top:4px}
.data-table{width:100%;border-collapse:collapse;background:var(--bg-card);border-radius:12px;overflow:hidden;box-shadow:var(--card-shadow)}
.data-table th,.data-table td{padding:12px 16px;text-align:start;border-bottom:1px solid var(--border-default);font-size:.875rem}
.data-table th{background:var(--primary-dark);color:var(--text-light);font-weight:600;white-space:nowrap}
.data-table tr:hover{background:var(--bg-main)}
.data-table .table-actions{display:flex;gap:6px;justify-content:center}
.data-table .table-actions .btn-icon{width:32px;height:32px;border-radius:8px;border:none;cursor:pointer;font-size:.85rem;display:inline-flex;align-items:center;justify-content:center;transition:all .3s}
.btn-edit{background:var(--status-info);color:#fff}.btn-edit:hover{opacity:.85}
.btn-delete{background:var(--status-danger);color:#fff}.btn-delete:hover{opacity:.85}
.btn-view{background:var(--primary-main);color:#fff}.btn-view:hover{opacity:.85}
.btn-perm{background:var(--accent-gold,#c69c3f);color:#fff}.btn-perm:hover{opacity:.85}
.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:600}
.badge-role{background:var(--bg-main);color:var(--primary-dark);border:1px solid var(--border-default)}
.badge-count{background:var(--primary-main);color:#fff;min-width:24px;text-align:center}
.badge-builtin{background:#e2e3e5;color:#383d41}
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;justify-content:center;align-items:center}
.modal-overlay.active{display:flex}
.modal-content{background:var(--bg-card);border-radius:16px;width:90%;max-width:760px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.3)}
.modal-content.wide{max-width:900px}
.modal-header{display:flex;justify-content:space-between;align-items:center;padding:20px 24px;border-bottom:1px solid var(--border-default)}
.modal-header h3{margin:0;font-size:1.1rem;color:var(--text-primary)}
.modal-close{background:none;border:none;font-size:1.3rem;cursor:pointer;color:var(--text-secondary);padding:4px 8px;border-radius:6px}
.modal-close:hover{background:var(--bg-main)}
.modal-body{padding:24px}
.modal-footer{display:flex;gap:12px;justify-content:flex-end;padding:16px 24px;border-top:1px solid var(--border-default)}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:0 16px}
@media(max-width:600px){.form-grid{grid-template-columns:1fr}}
.form-grid .full-width{grid-column:1/-1}
.form-group{margin-bottom:16px}
.form-group label{display:block;margin-bottom:6px;font-size:.85rem;font-weight:600;color:var(--text-secondary)}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:10px 14px;border:1px solid var(--border-default);border-radius:8px;background:var(--bg-card);color:var(--text-primary);font-size:.9rem}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{outline:none;border-color:var(--primary-main);box-shadow:0 0 0 3px rgba(58,81,58,.15)}
.empty-state{text-align:center;padding:48px 24px;color:var(--text-secondary)}
.empty-state .empty-icon{font-size:3rem;margin-bottom:12px;opacity:.5}
.table-wrapper{overflow-x:auto;border-radius:12px;border:1px solid var(--border-default)}
@media(max-width:768px){.r-toolbar{flex-direction:column;align-items:stretch}.r-toolbar-end{margin-inline-start:0;justify-content:space-between}.r-toolbar .search-box{max-width:100%}}
/* Permission Matrix */
.perm-section{margin-bottom:24px}
.perm-section-title{font-size:.95rem;font-weight:700;color:var(--primary-dark);margin-bottom:12px;padding:8px 14px;background:var(--bg-main);border-radius:8px;border-inline-start:4px solid var(--primary-main)}
.perm-grid{display:grid;gap:8px}
.perm-item{display:flex;align-items:center;gap:10px;padding:8px 14px;border-radius:8px;border:1px solid var(--border-default);background:var(--bg-card);transition:background .2s}
.perm-item:hover{background:var(--bg-main)}
.perm-item input[type="checkbox"]{width:18px;height:18px;accent-color:var(--primary-main);cursor:pointer;flex-shrink:0}
.perm-item .perm-label{flex:1}
.perm-item .perm-key{font-size:.8rem;color:var(--text-secondary);font-family:monospace}
.perm-item .perm-name{font-weight:600;font-size:.9rem;color:var(--text-primary)}
.perm-actions{display:flex;gap:8px;margin-bottom:16px}
.perm-actions button{font-size:.8rem;padding:6px 14px;border-radius:6px;border:1px solid var(--border-default);cursor:pointer;background:var(--bg-card);color:var(--text-secondary);transition:all .2s}
.perm-actions button:hover{background:var(--primary-main);color:#fff;border-color:var(--primary-main)}
/* Resource Permissions */
.res-table{width:100%;border-collapse:collapse;font-size:.85rem}
.res-table th,.res-table td{padding:8px 10px;border:1px solid var(--border-default);text-align:center}
.res-table th{background:var(--primary-dark);color:var(--text-light);font-weight:600;white-space:nowrap;font-size:.78rem}
.res-table td:first-child{text-align:start;font-weight:600;white-space:nowrap;background:var(--bg-main)}
.res-table input[type="checkbox"]{width:16px;height:16px;accent-color:var(--primary-main);cursor:pointer}
/* Tabs */
.perm-tabs{display:flex;gap:4px;margin-bottom:20px;border-bottom:2px solid var(--border-default);padding-bottom:0}
.perm-tab{padding:10px 20px;cursor:pointer;font-weight:600;font-size:.9rem;color:var(--text-secondary);border-bottom:3px solid transparent;transition:all .2s;border-radius:8px 8px 0 0}
.perm-tab:hover{color:var(--primary-main);background:var(--bg-main)}
.perm-tab.active{color:var(--primary-main);border-bottom-color:var(--primary-main);background:var(--bg-card)}
.tab-content{display:none}
.tab-content.active{display:block}
</style>

<div class="page-header">
    <h2 id="rolesPageTitle" data-label-en="Roles &amp; Permissions Management" data-label-ar="إدارة الأدوار والصلاحيات">Roles &amp; Permissions Management</h2>
</div>

<!-- Stats -->
<div class="r-stats">
    <div class="r-stat"><div class="r-stat-val" id="rTotal">—</div><div class="r-stat-lbl" id="lbl_total_roles" data-label-en="Total Roles" data-label-ar="إجمالي الأدوار">Total Roles</div></div>
    <div class="r-stat"><div class="r-stat-val" id="rPerms" style="color:var(--status-info)">—</div><div class="r-stat-lbl" id="lbl_total_perms" data-label-en="Total Permissions" data-label-ar="إجمالي الصلاحيات">Total Permissions</div></div>
    <div class="r-stat"><div class="r-stat-val" id="rModules" style="color:var(--accent-gold,#c69c3f)">—</div><div class="r-stat-lbl" id="lbl_modules" data-label-en="Modules" data-label-ar="الوحدات">Modules</div></div>
</div>

<!-- Toolbar -->
<div class="r-toolbar">
    <div class="search-box">
        <input type="text" class="form-control" id="roleSearch" placeholder="Search by name or key..." data-placeholder-en="Search by name or key..." data-placeholder-ar="بحث بالاسم أو المفتاح...">
        <span class="search-icon">🔍</span>
    </div>
    <div class="r-toolbar-end">
        <button class="btn btn-primary" id="btnAddRole">➕ <span id="lbl_add_role" data-label-en="Add Role" data-label-ar="إضافة دور">Add Role</span></button>
    </div>
</div>

<!-- Roles Table -->
<div class="section-card">
    <div class="table-wrapper table-responsive">
        <table class="data-table" id="rolesTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th id="th_key" data-label-en="Key" data-label-ar="المفتاح">Key</th>
                    <th id="th_name" data-label-en="Display Name" data-label-ar="اسم العرض">Display Name</th>
                    <th id="th_perms" data-label-en="Permissions" data-label-ar="الصلاحيات">Permissions</th>
                    <th id="th_created" data-label-en="Created" data-label-ar="تاريخ الإنشاء">Created</th>
                    <th id="th_actions" data-label-en="Actions" data-label-ar="الإجراءات">Actions</th>
                </tr>
            </thead>
            <tbody id="rolesBody">
                <tr><td colspan="6" class="empty-state"><div class="empty-icon">🔑</div><p id="lbl_loading" data-label-en="Loading..." data-label-ar="جاري التحميل...">Loading...</p></td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Role Modal -->
<div class="modal-overlay" id="roleFormModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="roleFormTitle" data-label-en="➕ Add Role" data-label-ar="➕ إضافة دور">➕ Add Role</h3>
            <button class="modal-close" onclick="RolesPage.closeModal('roleFormModal')">✕</button>
        </div>
        <div class="modal-body">
            <form id="roleForm" class="form-grid" onsubmit="return false;">
                <input type="hidden" id="editRoleId" value="">
                <div class="form-group">
                    <label id="lbl_key_name" data-label-en="Key Name *" data-label-ar="اسم المفتاح *">Key Name *</label>
                    <input type="text" id="fRoleKey" required placeholder="manager" pattern="[a-z0-9_]+">
                </div>
                <div class="form-group">
                    <label id="lbl_display_name" data-label-en="Display Name *" data-label-ar="اسم العرض *">Display Name *</label>
                    <input type="text" id="fRoleName" required placeholder="Manager">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="RolesPage.closeModal('roleFormModal')" id="lbl_cancel" data-label-en="Cancel" data-label-ar="إلغاء">Cancel</button>
            <button class="btn btn-primary" id="saveRoleBtn" onclick="RolesPage.saveRole()">💾 <span id="lbl_save" data-label-en="Save" data-label-ar="حفظ">Save</span></button>
        </div>
    </div>
</div>

<!-- Permissions Modal (per role) -->
<div class="modal-overlay" id="permModal">
    <div class="modal-content wide">
        <div class="modal-header">
            <h3 id="permModalTitle" data-label-en="🔐 Role Permissions" data-label-ar="🔐 صلاحيات الدور">🔐 Role Permissions</h3>
            <button class="modal-close" onclick="RolesPage.closeModal('permModal')">✕</button>
        </div>
        <div class="modal-body">
            <!-- Tabs -->
            <div class="perm-tabs">
                <div class="perm-tab active" data-tab="basic" onclick="RolesPage.switchTab('basic')" id="tab_basic" data-label-en="Basic Permissions" data-label-ar="الصلاحيات الأساسية">Basic Permissions</div>
                <div class="perm-tab" data-tab="resource" onclick="RolesPage.switchTab('resource')" id="tab_resource" data-label-en="Resource Permissions" data-label-ar="صلاحيات الموارد">Resource Permissions</div>
            </div>

            <!-- Basic Permissions Tab -->
            <div class="tab-content active" id="tabBasic">
                <div class="perm-actions">
                    <button onclick="RolesPage.selectAll()">✅ <span id="lbl_select_all" data-label-en="Select All" data-label-ar="تحديد الكل">Select All</span></button>
                    <button onclick="RolesPage.deselectAll()">⬜ <span id="lbl_deselect_all" data-label-en="Deselect All" data-label-ar="إلغاء التحديد">Deselect All</span></button>
                </div>
                <div id="permList">
                    <div class="empty-state"><div class="empty-icon">🔐</div><p id="lbl_loading2" data-label-en="Loading..." data-label-ar="جاري التحميل...">Loading...</p></div>
                </div>
            </div>

            <!-- Resource Permissions Tab -->
            <div class="tab-content" id="tabResource">
                <p style="color:var(--text-secondary);margin-bottom:16px;font-size:.85rem" id="lbl_fine_grained" data-label-en="Fine-grained access control per resource" data-label-ar="التحكم الدقيق بالوصول لكل مورد">Fine-grained access control per resource</p>
                <div class="table-wrapper">
                    <table class="res-table" id="resTable">
                        <thead>
                            <tr>
                                <th id="th_resource" data-label-en="Resource" data-label-ar="المورد">Resource</th>
                                <th id="th_view_all" data-label-en="View All" data-label-ar="عرض الكل">View All</th>
                                <th id="th_view_own" data-label-en="View Own" data-label-ar="عرض الخاصة">View Own</th>
                                <th id="th_create" data-label-en="Create" data-label-ar="إنشاء">Create</th>
                                <th id="th_edit_all" data-label-en="Edit All" data-label-ar="تعديل الكل">Edit All</th>
                                <th id="th_edit_own" data-label-en="Edit Own" data-label-ar="تعديل الخاصة">Edit Own</th>
                                <th id="th_delete_all" data-label-en="Delete All" data-label-ar="حذف الكل">Delete All</th>
                                <th id="th_delete_own" data-label-en="Delete Own" data-label-ar="حذف الخاصة">Delete Own</th>
                            </tr>
                        </thead>
                        <tbody id="resBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="RolesPage.closeModal('permModal')" id="lbl_close" data-label-en="Close" data-label-ar="إغلاق">Close</button>
            <button class="btn btn-primary" id="savePermBtn" onclick="RolesPage.savePermissions()">💾 <span id="lbl_save_perms" data-label-en="Save Permissions" data-label-ar="حفظ الصلاحيات">Save Permissions</span></button>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
(function(){
    let allRoles = [];
    let allPermissions = [];
    let currentRoleId = null;
    var rCanEdit=false, rCanDelete=false;

    /* Use global i18n */
    function t(key, fallback) {
        return (typeof i18n !== 'undefined' && i18n.t) ? i18n.t(key) : fallback;
    }

    /* Module labels via i18n */
    const moduleKeys = {
        users: 'mod_users', vehicles: 'mod_vehicles', movements: 'mod_movements',
        violations: 'mod_violations', maintenance: 'mod_maintenance',
        reports: 'mod_reports', admin: 'mod_admin', references: 'mod_references',
        settings: 'mod_settings'
    };
    const moduleFallbacks = {
        users: 'Users', vehicles: 'Vehicles', movements: 'Movements',
        violations: 'Violations', maintenance: 'Maintenance',
        reports: 'Reports', admin: 'Admin', references: 'References',
        settings: 'Settings'
    };

    const resourceTypes = [
        { key: 'users',       label_key: 'mod_users',        label: 'Users' },
        { key: 'vehicles',    label_key: 'mod_vehicles',     label: 'Vehicles' },
        { key: 'movements',   label_key: 'mod_movements',    label: 'Movements' },
        { key: 'violations',  label_key: 'mod_violations',   label: 'Violations' },
        { key: 'maintenance', label_key: 'mod_maintenance',  label: 'Maintenance' },
        { key: 'reports',     label_key: 'mod_reports',      label: 'Reports' },
        { key: 'settings',    label_key: 'mod_settings',     label: 'Settings' }
    ];

    function escHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    /* Apply i18n to static labels */
    function applyLang() {
        // Retry if i18n translations are not loaded yet
        if(!i18n.strings || !Object.keys(i18n.strings).length){
            setTimeout(applyLang,100);
            return;
        }
        var map = {
            'rolesPageTitle':   'roles_permissions',
            'lbl_total_roles':  'total_roles',
            'lbl_total_perms':  'total_permissions',
            'lbl_modules':      'modules',
            'th_key':           'key',
            'th_name':          'display_name',
            'th_perms':         'permissions',
            'th_created':       'created_at',
            'th_actions':       'actions',
            'lbl_add_role':     'add_role',
            'lbl_key_name':     'key_name',
            'lbl_display_name': 'display_name_label',
            'lbl_cancel':       'cancel',
            'lbl_save':         'save',
            'lbl_close':        'close',
            'tab_basic':        'basic_permissions',
            'tab_resource':     'resource_permissions',
            'lbl_select_all':   'select_all',
            'lbl_deselect_all': 'deselect_all',
            'lbl_fine_grained': 'fine_grained_access',
            'th_resource':      'resource',
            'th_view_all':      'view_all',
            'th_view_own':      'view_own',
            'th_create':        'create',
            'th_edit_all':      'edit_all',
            'th_edit_own':      'edit_own',
            'th_delete_all':    'delete_all',
            'th_delete_own':    'delete_own',
            'lbl_save_perms':   'save_permissions'
        };
        Object.keys(map).forEach(function(id) {
            var el = document.getElementById(id);
            if (el && typeof i18n !== 'undefined') {
                var val = i18n.t(map[id]);
                if (val && val !== map[id]) el.textContent = val;
            }
        });
        /* Search placeholder */
        var rs = document.getElementById('roleSearch');
        if (rs) rs.placeholder = t('search_role', 'Search by name or key...');
    }

    /* ---- Load Data ---- */
    async function loadRoles() {
        try {
            const res = await API.get('/roles');
            allRoles = (res && res.data) || [];
        } catch(e) {
            allRoles = [];
        }
        updateStats();
        renderTable();
    }

    async function loadPermissions() {
        try {
            const res = await API.get('/permissions?grouped=1');
            allPermissions = (res && res.data) || {};
        } catch(e) {
            allPermissions = {};
        }
        updateStats();
    }

    function updateStats() {
        document.getElementById('rTotal').textContent = allRoles.length;
        let permCount = 0;
        const modules = Object.keys(allPermissions);
        modules.forEach(function(m) { permCount += (allPermissions[m] || []).length; });
        document.getElementById('rPerms').textContent = permCount;
        document.getElementById('rModules').textContent = modules.length;
    }

    /* ---- Render Roles Table ---- */
    function renderTable() {
        const search = (document.getElementById('roleSearch').value || '').toLowerCase();
        let filtered = allRoles.filter(function(r) {
            if (search) {
                var haystack = [r.key_name, r.display_name].join(' ').toLowerCase();
                if (haystack.indexOf(search) === -1) return false;
            }
            return true;
        });

        var tbody = document.getElementById('rolesBody');
        if (filtered.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="empty-state"><div class="empty-icon">🔑</div><p>' + t('no_roles', 'No roles found') + '</p></td></tr>';
            return;
        }

        tbody.innerHTML = filtered.map(function(r, i) {
            var isBuiltin = (parseInt(r.id) === 1 || parseInt(r.id) === 2);
            var builtinBadge = isBuiltin ? ' <span class="badge badge-builtin">' + t('builtin', 'System') + '</span>' : '';
            var permBadge = '<span class="badge badge-count">' + (r.permission_count || 0) + '</span>';
            var created = r.created_at ? r.created_at.substring(0, 10) : '\u2014';
            var deleteBtn = isBuiltin
                ? ''
                : '<button class="btn-icon btn-delete" title="' + t('delete', 'Delete') + '" data-action="delete" data-id="' + parseInt(r.id) + '">🗑️</button>';

            return '<tr>' +
                '<td>' + (i + 1) + '</td>' +
                '<td><code>' + escHtml(r.key_name) + '</code></td>' +
                '<td><strong>' + escHtml(r.display_name) + '</strong>' + builtinBadge + '</td>' +
                '<td>' + permBadge + '</td>' +
                '<td>' + created + '</td>' +
                '<td class="table-actions">' +
                    (rCanEdit ? '<button class="btn-icon btn-perm" title="' + t('permissions', 'Permissions') + '" data-action="perm" data-id="' + parseInt(r.id) + '">🔐</button>' : '') +
                    (rCanEdit ? '<button class="btn-icon btn-edit" title="' + t('edit', 'Edit') + '" data-action="edit" data-id="' + parseInt(r.id) + '">✏️</button>' : '') +
                    (rCanDelete ? deleteBtn : '') +
                '</td></tr>';
        }).join('');
    }

    /* ---- Table Event Delegation ---- */
    document.getElementById('rolesBody').addEventListener('click', function(e) {
        var btn = e.target.closest('[data-action]');
        if (!btn) return;
        var action = btn.getAttribute('data-action');
        var id = parseInt(btn.getAttribute('data-id'));
        if (action === 'edit') RolesPage.editRole(id);
        else if (action === 'delete') {
            var r = allRoles.find(function(x){ return x.id == id; });
            RolesPage.deleteRole(id, r ? r.display_name : '');
        }
        else if (action === 'perm') RolesPage.openPermissions(id);
    });

    /* ---- Modals ---- */
    window.RolesPage = {
        closeModal: function(modalId) {
            document.getElementById(modalId).classList.remove('active');
        },

        openAddRole: function() {
            document.getElementById('editRoleId').value = '';
            document.getElementById('roleFormTitle').textContent = '➕ ' + t('add_role', 'Add Role');
            document.getElementById('roleForm').reset();
            document.getElementById('fRoleKey').disabled = false;
            document.getElementById('roleFormModal').classList.add('active');
        },

        editRole: function(id) {
            var r = allRoles.find(function(x){ return x.id == id; });
            if (!r) return;
            document.getElementById('editRoleId').value = r.id;
            document.getElementById('roleFormTitle').textContent = '✏️ ' + t('edit_role', 'Edit Role') + ': ' + r.display_name;
            document.getElementById('fRoleKey').value = r.key_name;
            document.getElementById('fRoleName').value = r.display_name;
            document.getElementById('fRoleKey').disabled = (parseInt(r.id) === 1 || parseInt(r.id) === 2);
            document.getElementById('roleFormModal').classList.add('active');
        },

        saveRole: async function() {
            var id = document.getElementById('editRoleId').value;
            var payload = {
                key_name: document.getElementById('fRoleKey').value.trim(),
                display_name: document.getElementById('fRoleName').value.trim()
            };

            if (!payload.key_name || !payload.display_name) {
                UI.showToast(t('key_name_required', 'Key and name are required'), 'error');
                return;
            }

            var btn = document.getElementById('saveRoleBtn');
            btn.disabled = true;
            btn.textContent = t('saving', 'Saving...');

            try {
                if (id) {
                    await API.put('/roles/' + id, payload);
                    UI.showToast(t('role_updated', 'Role updated successfully') + ' ✓', 'success');
                } else {
                    await API.post('/roles', payload);
                    UI.showToast(t('role_created', 'Role created successfully') + ' ✓', 'success');
                }
                RolesPage.closeModal('roleFormModal');
                await loadRoles();
            } catch(e) {
                UI.showToast(e.message || t('save_failed', 'Save failed'), 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = '💾 ' + t('save', 'Save');
            }
        },

        deleteRole: async function(id, name) {
            if (!confirm(t('confirm_delete_role', 'Are you sure you want to delete role:') + ' ' + name + '?')) return;
            try {
                await API.del('/roles/' + id);
                UI.showToast(t('role_deleted', 'Role deleted successfully'), 'success');
                await loadRoles();
            } catch(e) {
                UI.showToast(e.message || t('delete_failed', 'Delete failed'), 'error');
            }
        },

        openPermissions: async function(roleId) {
            currentRoleId = roleId;
            var r = allRoles.find(function(x){ return x.id == roleId; });
            document.getElementById('permModalTitle').textContent = '🔐 ' + t('permissions', 'Permissions') + ': ' + (r ? r.display_name : '#' + roleId);

            var roleData = null;
            try {
                var res = await API.get('/roles/' + roleId);
                roleData = (res && res.data) || null;
            } catch(e) {
                UI.showToast(t('load_failed', 'Failed to load data'), 'error');
                return;
            }

            renderPermissionsList(roleData);
            renderResourceTable(roleData);
            RolesPage.switchTab('basic');
            document.getElementById('permModal').classList.add('active');
        },

        switchTab: function(tab) {
            document.querySelectorAll('.perm-tab').forEach(function(t){ t.classList.remove('active'); });
            document.querySelectorAll('.tab-content').forEach(function(t){ t.classList.remove('active'); });
            document.querySelector('.perm-tab[data-tab="' + tab + '"]').classList.add('active');
            document.getElementById(tab === 'basic' ? 'tabBasic' : 'tabResource').classList.add('active');
        },

        selectAll: function() {
            document.querySelectorAll('#permList input[type="checkbox"]').forEach(function(cb){ cb.checked = true; });
        },
        deselectAll: function() {
            document.querySelectorAll('#permList input[type="checkbox"]').forEach(function(cb){ cb.checked = false; });
        },

        savePermissions: async function() {
            if (!currentRoleId) return;

            var btn = document.getElementById('savePermBtn');
            btn.disabled = true;
            btn.textContent = t('saving', 'Saving...');

            try {
                var checkedIds = [];
                document.querySelectorAll('#permList input[type="checkbox"]:checked').forEach(function(cb) {
                    checkedIds.push(parseInt(cb.value));
                });
                await API.put('/roles/' + currentRoleId + '/permissions', { permission_ids: checkedIds });

                var rows = document.querySelectorAll('#resBody tr[data-resource]');
                for (var ri = 0; ri < rows.length; ri++) {
                    var row = rows[ri];
                    var resourceType = row.getAttribute('data-resource');
                    var permId = parseInt(row.getAttribute('data-perm-id') || '0');
                    if (!permId) continue;

                    var flags = {
                        can_view_all: row.querySelector('[data-flag="can_view_all"]').checked,
                        can_view_own: row.querySelector('[data-flag="can_view_own"]').checked,
                        can_create: row.querySelector('[data-flag="can_create"]').checked,
                        can_edit_all: row.querySelector('[data-flag="can_edit_all"]').checked,
                        can_edit_own: row.querySelector('[data-flag="can_edit_own"]').checked,
                        can_delete_all: row.querySelector('[data-flag="can_delete_all"]').checked,
                        can_delete_own: row.querySelector('[data-flag="can_delete_own"]').checked
                    };

                    await API.put('/roles/' + currentRoleId + '/resource-permissions', {
                        permission_id: permId,
                        resource_type: resourceType,
                        can_view_all: flags.can_view_all,
                        can_view_own: flags.can_view_own,
                        can_create: flags.can_create,
                        can_edit_all: flags.can_edit_all,
                        can_edit_own: flags.can_edit_own,
                        can_delete_all: flags.can_delete_all,
                        can_delete_own: flags.can_delete_own
                    });
                }

                UI.showToast(t('permissions_saved', 'Permissions saved successfully') + ' ✓', 'success');
                await loadRoles();
            } catch(e) {
                UI.showToast(e.message || t('save_failed', 'Save failed'), 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = '💾 ' + t('save_permissions', 'Save Permissions');
            }
        }
    };

    /* ---- Render Permission Checkboxes ---- */
    function renderPermissionsList(roleData) {
        var assignedIds = {};
        if (roleData && roleData.permissions) {
            roleData.permissions.forEach(function(p) { assignedIds[p.id] = true; });
        }

        var html = '';
        var modules = Object.keys(allPermissions);
        if (modules.length === 0) {
            html = '<div class="empty-state"><p>' + t('no_permissions', 'No permissions') + '</p></div>';
        } else {
            modules.forEach(function(mod) {
                var perms = allPermissions[mod] || [];
                var key = moduleKeys[mod] || null;
                var modLabel = (key && typeof i18n !== 'undefined') ? i18n.t(key) : (moduleFallbacks[mod] || mod);
                html += '<div class="perm-section">';
                html += '<div class="perm-section-title">📦 ' + escHtml(modLabel) + ' (' + perms.length + ')</div>';
                html += '<div class="perm-grid">';
                perms.forEach(function(p) {
                    var checked = assignedIds[p.id] ? ' checked' : '';
                    html += '<div class="perm-item">' +
                        '<input type="checkbox" value="' + parseInt(p.id) + '"' + checked + '>' +
                        '<div class="perm-label">' +
                            '<div class="perm-name">' + escHtml(p.display_name) + '</div>' +
                            '<div class="perm-key">' + escHtml(p.key_name) + '</div>' +
                        '</div></div>';
                });
                html += '</div></div>';
            });
        }
        document.getElementById('permList').innerHTML = html;
    }

    /* ---- Render Resource Permissions Table ---- */
    function renderResourceTable(roleData) {
        var existingRes = {};
        if (roleData && roleData.resource_permissions) {
            roleData.resource_permissions.forEach(function(rp) {
                existingRes[rp.resource_type] = rp;
            });
        }

        var flatPerms = [];
        Object.keys(allPermissions).forEach(function(mod) {
            (allPermissions[mod] || []).forEach(function(p) { flatPerms.push(p); });
        });

        var flags = ['can_view_all','can_view_own','can_create','can_edit_all','can_edit_own','can_delete_all','can_delete_own'];
        var html = '';

        resourceTypes.forEach(function(rt) {
            var readPerm = flatPerms.find(function(p) { return p.key_name === rt.key + '_read'; });
            var permId = readPerm ? readPerm.id : 0;
            var existing = existingRes[rt.key] || {};
            var label = (typeof i18n !== 'undefined') ? i18n.t(rt.label_key) : rt.label;

            html += '<tr data-resource="' + rt.key + '" data-perm-id="' + permId + '">';
            html += '<td>' + escHtml(label) + '</td>';
            flags.forEach(function(f) {
                var checked = existing[f] ? ' checked' : '';
                html += '<td><input type="checkbox" data-flag="' + f + '"' + checked + '></td>';
            });
            html += '</tr>';
        });

        document.getElementById('resBody').innerHTML = html;
    }

    /* ---- Event Listeners ---- */
    document.getElementById('btnAddRole').addEventListener('click', function() { RolesPage.openAddRole(); });
    document.getElementById('roleSearch').addEventListener('input', renderTable);

    document.querySelectorAll('.modal-overlay').forEach(function(m) {
        m.addEventListener('click', function(e) { if (e.target === m) m.classList.remove('active'); });
    });

    /* ---- Init ---- */
    (function initRolePerms(){
        var user=Auth.getUser();
        if(!user){setTimeout(initRolePerms,100);return;}
        var perms=(user.permissions)||[];
        rCanEdit=perms.includes('manage_roles')||perms.includes('*');
        rCanDelete=perms.includes('manage_roles')||perms.includes('*');
        if(!rCanEdit){var ab=document.getElementById('btnAddRole');if(ab)ab.style.display='none';}
        applyLang();
        loadPermissions().then(function() { loadRoles(); });
    })();
})();
</script>
<?php $pageScripts = ob_get_clean(); ?>
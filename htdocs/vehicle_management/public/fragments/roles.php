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
.data-table th,.data-table td{padding:12px 16px;text-align:right;border-bottom:1px solid var(--border-default);font-size:.875rem}
.data-table th{background:var(--primary-dark);color:var(--text-light);font-weight:600;white-space:nowrap}
.data-table tr:hover{background:var(--bg-main)}
.data-table .table-actions{display:flex;gap:6px;justify-content:center}
.data-table .table-actions .btn-icon{width:32px;height:32px;border-radius:8px;border:none;cursor:pointer;font-size:.85rem;display:inline-flex;align-items:center;justify-content:center;transition:all .3s}
.btn-edit{background:var(--status-info);color:#fff}
.btn-edit:hover{opacity:.85}
.btn-delete{background:var(--status-danger);color:#fff}
.btn-delete:hover{opacity:.85}
.btn-view{background:var(--primary-main);color:#fff}
.btn-view:hover{opacity:.85}
.btn-perm{background:var(--accent-gold,#c69c3f);color:#fff}
.btn-perm:hover{opacity:.85}
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
.res-table td:first-child{text-align:right;font-weight:600;white-space:nowrap;background:var(--bg-main)}
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
    <h2 data-label-ar="إدارة الأدوار والصلاحيات" data-label-en="Roles & Permissions Management">إدارة الأدوار والصلاحيات</h2>
</div>

<!-- Stats -->
<div class="r-stats">
    <div class="r-stat"><div class="r-stat-val" id="rTotal">—</div><div class="r-stat-lbl" data-label-ar="إجمالي الأدوار" data-label-en="Total Roles">إجمالي الأدوار</div></div>
    <div class="r-stat"><div class="r-stat-val" id="rPerms" style="color:var(--status-info)">—</div><div class="r-stat-lbl" data-label-ar="إجمالي الصلاحيات" data-label-en="Total Permissions">إجمالي الصلاحيات</div></div>
    <div class="r-stat"><div class="r-stat-val" id="rModules" style="color:var(--accent-gold,#c69c3f)">—</div><div class="r-stat-lbl" data-label-ar="الوحدات" data-label-en="Modules">الوحدات</div></div>
</div>

<!-- Toolbar -->
<div class="r-toolbar">
    <div class="search-box">
        <input type="text" class="form-control" id="roleSearch" placeholder="بحث بالاسم أو المفتاح...">
        <span class="search-icon">🔍</span>
    </div>
    <div class="r-toolbar-end">
        <button class="btn btn-primary" id="btnAddRole">➕ إضافة دور جديد</button>
    </div>
</div>

<!-- Roles Table -->
<div class="section-card">
    <div class="table-wrapper table-responsive">
        <table class="data-table" id="rolesTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th data-label-ar="المفتاح" data-label-en="Key">المفتاح</th>
                    <th data-label-ar="الاسم" data-label-en="Display Name">الاسم</th>
                    <th data-label-ar="الصلاحيات" data-label-en="Permissions">الصلاحيات</th>
                    <th data-label-ar="تاريخ الإنشاء" data-label-en="Created">تاريخ الإنشاء</th>
                    <th data-label-ar="إجراءات" data-label-en="Actions">إجراءات</th>
                </tr>
            </thead>
            <tbody id="rolesBody">
                <tr><td colspan="6" class="empty-state"><div class="empty-icon">🔑</div><p>جارٍ التحميل...</p></td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Role Modal -->
<div class="modal-overlay" id="roleFormModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="roleFormTitle">➕ إضافة دور جديد</h3>
            <button class="modal-close" onclick="RolesPage.closeModal('roleFormModal')">✕</button>
        </div>
        <div class="modal-body">
            <form id="roleForm" class="form-grid" onsubmit="return false;">
                <input type="hidden" id="editRoleId" value="">
                <div class="form-group">
                    <label data-label-ar="المفتاح (بالإنجليزية) *" data-label-en="Key Name *">المفتاح (بالإنجليزية) *</label>
                    <input type="text" id="fRoleKey" required placeholder="manager" pattern="[a-z0-9_]+">
                </div>
                <div class="form-group">
                    <label data-label-ar="الاسم المعروض *" data-label-en="Display Name *">الاسم المعروض *</label>
                    <input type="text" id="fRoleName" required placeholder="مشرف / Manager">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="RolesPage.closeModal('roleFormModal')">إلغاء</button>
            <button class="btn btn-primary" id="saveRoleBtn" onclick="RolesPage.saveRole()">💾 حفظ</button>
        </div>
    </div>
</div>

<!-- Permissions Modal (per role) -->
<div class="modal-overlay" id="permModal">
    <div class="modal-content wide">
        <div class="modal-header">
            <h3 id="permModalTitle">🔐 صلاحيات الدور</h3>
            <button class="modal-close" onclick="RolesPage.closeModal('permModal')">✕</button>
        </div>
        <div class="modal-body">
            <!-- Tabs -->
            <div class="perm-tabs">
                <div class="perm-tab active" data-tab="basic" onclick="RolesPage.switchTab('basic')" data-label-ar="الصلاحيات الأساسية" data-label-en="Basic Permissions">الصلاحيات الأساسية</div>
                <div class="perm-tab" data-tab="resource" onclick="RolesPage.switchTab('resource')" data-label-ar="صلاحيات الموارد" data-label-en="Resource Permissions">صلاحيات الموارد</div>
            </div>

            <!-- Basic Permissions Tab -->
            <div class="tab-content active" id="tabBasic">
                <div class="perm-actions">
                    <button onclick="RolesPage.selectAll()">✅ تحديد الكل</button>
                    <button onclick="RolesPage.deselectAll()">⬜ إلغاء الكل</button>
                </div>
                <div id="permList">
                    <div class="empty-state"><div class="empty-icon">🔐</div><p>جارٍ التحميل...</p></div>
                </div>
            </div>

            <!-- Resource Permissions Tab -->
            <div class="tab-content" id="tabResource">
                <p style="color:var(--text-secondary);margin-bottom:16px;font-size:.85rem" data-label-ar="تحكم دقيق بصلاحيات الوصول لكل مورد" data-label-en="Fine-grained access control per resource">تحكم دقيق بصلاحيات الوصول لكل مورد</p>
                <div class="table-wrapper">
                    <table class="res-table" id="resTable">
                        <thead>
                            <tr>
                                <th data-label-ar="المورد" data-label-en="Resource">المورد</th>
                                <th data-label-ar="عرض الكل" data-label-en="View All">عرض الكل</th>
                                <th data-label-ar="عرض الخاصة" data-label-en="View Own">عرض الخاصة</th>
                                <th data-label-ar="إنشاء" data-label-en="Create">إنشاء</th>
                                <th data-label-ar="تعديل الكل" data-label-en="Edit All">تعديل الكل</th>
                                <th data-label-ar="تعديل الخاصة" data-label-en="Edit Own">تعديل الخاصة</th>
                                <th data-label-ar="حذف الكل" data-label-en="Delete All">حذف الكل</th>
                                <th data-label-ar="حذف الخاصة" data-label-en="Delete Own">حذف الخاصة</th>
                            </tr>
                        </thead>
                        <tbody id="resBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="RolesPage.closeModal('permModal')">إغلاق</button>
            <button class="btn btn-primary" id="savePermBtn" onclick="RolesPage.savePermissions()">💾 حفظ الصلاحيات</button>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
(function(){
    let allRoles = [];
    let allPermissions = [];
    let currentRoleId = null;

    const moduleLabels = {
        users: 'المستخدمين',
        vehicles: 'المركبات',
        movements: 'الحركات',
        violations: 'المخالفات',
        maintenance: 'الصيانة',
        reports: 'التقارير',
        admin: 'الإدارة',
        references: 'المراجع',
        settings: 'الإعدادات'
    };

    const resourceTypes = [
        { key: 'users', label: 'المستخدمين' },
        { key: 'vehicles', label: 'المركبات' },
        { key: 'movements', label: 'الحركات' },
        { key: 'violations', label: 'المخالفات' },
        { key: 'maintenance', label: 'الصيانة' },
        { key: 'reports', label: 'التقارير' },
        { key: 'settings', label: 'الإعدادات' }
    ];

    function escHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
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
            tbody.innerHTML = '<tr><td colspan="6" class="empty-state"><div class="empty-icon">🔑</div><p>لا توجد أدوار</p></td></tr>';
            return;
        }

        tbody.innerHTML = filtered.map(function(r, i) {
            var isBuiltin = (parseInt(r.id) === 1 || parseInt(r.id) === 2);
            var builtinBadge = isBuiltin ? ' <span class="badge badge-builtin">نظامي</span>' : '';
            var permBadge = '<span class="badge badge-count">' + (r.permission_count || 0) + '</span>';
            var created = r.created_at ? r.created_at.substring(0, 10) : '\u2014';
            var deleteBtn = isBuiltin
                ? ''
                : '<button class="btn-icon btn-delete" title="حذف" data-action="delete" data-id="' + parseInt(r.id) + '">🗑️</button>';

            return '<tr>' +
                '<td>' + (i + 1) + '</td>' +
                '<td><code>' + escHtml(r.key_name) + '</code></td>' +
                '<td><strong>' + escHtml(r.display_name) + '</strong>' + builtinBadge + '</td>' +
                '<td>' + permBadge + '</td>' +
                '<td>' + created + '</td>' +
                '<td class="table-actions">' +
                    '<button class="btn-icon btn-perm" title="الصلاحيات" data-action="perm" data-id="' + parseInt(r.id) + '">🔐</button>' +
                    '<button class="btn-icon btn-edit" title="تعديل" data-action="edit" data-id="' + parseInt(r.id) + '">✏️</button>' +
                    deleteBtn +
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

        /* ---- Add Role ---- */
        openAddRole: function() {
            document.getElementById('editRoleId').value = '';
            document.getElementById('roleFormTitle').textContent = '➕ إضافة دور جديد';
            document.getElementById('roleForm').reset();
            document.getElementById('fRoleKey').disabled = false;
            document.getElementById('roleFormModal').classList.add('active');
        },

        /* ---- Edit Role ---- */
        editRole: function(id) {
            var r = allRoles.find(function(x){ return x.id == id; });
            if (!r) return;
            document.getElementById('editRoleId').value = r.id;
            document.getElementById('roleFormTitle').textContent = '✏️ تعديل الدور: ' + r.display_name;
            document.getElementById('fRoleKey').value = r.key_name;
            document.getElementById('fRoleName').value = r.display_name;
            document.getElementById('fRoleKey').disabled = (parseInt(r.id) === 1 || parseInt(r.id) === 2);
            document.getElementById('roleFormModal').classList.add('active');
        },

        /* ---- Save Role ---- */
        saveRole: async function() {
            var id = document.getElementById('editRoleId').value;
            var payload = {
                key_name: document.getElementById('fRoleKey').value.trim(),
                display_name: document.getElementById('fRoleName').value.trim()
            };

            if (!payload.key_name || !payload.display_name) {
                UI.showToast('المفتاح والاسم مطلوبان', 'error');
                return;
            }

            var btn = document.getElementById('saveRoleBtn');
            btn.disabled = true;
            btn.textContent = 'جارٍ الحفظ...';

            try {
                if (id) {
                    await API.put('/roles/' + id, payload);
                    UI.showToast('تم تحديث الدور بنجاح ✓', 'success');
                } else {
                    await API.post('/roles', payload);
                    UI.showToast('تم إنشاء الدور بنجاح ✓', 'success');
                }
                RolesPage.closeModal('roleFormModal');
                await loadRoles();
            } catch(e) {
                UI.showToast(e.message || 'فشل في الحفظ', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = '💾 حفظ';
            }
        },

        /* ---- Delete Role ---- */
        deleteRole: async function(id, name) {
            if (!confirm('هل أنت متأكد من حذف الدور: ' + name + '؟')) return;
            try {
                await API.del('/roles/' + id);
                UI.showToast('تم حذف الدور بنجاح', 'success');
                await loadRoles();
            } catch(e) {
                UI.showToast(e.message || 'فشل في الحذف', 'error');
            }
        },

        /* ---- Permissions Modal ---- */
        openPermissions: async function(roleId) {
            currentRoleId = roleId;
            var r = allRoles.find(function(x){ return x.id == roleId; });
            document.getElementById('permModalTitle').textContent = '🔐 صلاحيات: ' + (r ? r.display_name : '#' + roleId);

            // Load role details with permissions
            var roleData = null;
            try {
                var res = await API.get('/roles/' + roleId);
                roleData = (res && res.data) || null;
            } catch(e) {
                UI.showToast('فشل في تحميل بيانات الدور', 'error');
                return;
            }

            renderPermissionsList(roleData);
            renderResourceTable(roleData);
            RolesPage.switchTab('basic');
            document.getElementById('permModal').classList.add('active');
        },

        /* ---- Tab Switching ---- */
        switchTab: function(tab) {
            document.querySelectorAll('.perm-tab').forEach(function(t){ t.classList.remove('active'); });
            document.querySelectorAll('.tab-content').forEach(function(t){ t.classList.remove('active'); });
            document.querySelector('.perm-tab[data-tab="' + tab + '"]').classList.add('active');
            document.getElementById(tab === 'basic' ? 'tabBasic' : 'tabResource').classList.add('active');
        },

        /* ---- Select All / Deselect ---- */
        selectAll: function() {
            document.querySelectorAll('#permList input[type="checkbox"]').forEach(function(cb){ cb.checked = true; });
        },
        deselectAll: function() {
            document.querySelectorAll('#permList input[type="checkbox"]').forEach(function(cb){ cb.checked = false; });
        },

        /* ---- Save Permissions ---- */
        savePermissions: async function() {
            if (!currentRoleId) return;

            var btn = document.getElementById('savePermBtn');
            btn.disabled = true;
            btn.textContent = 'جارٍ الحفظ...';

            try {
                // Save basic permissions
                var checkedIds = [];
                document.querySelectorAll('#permList input[type="checkbox"]:checked').forEach(function(cb) {
                    checkedIds.push(parseInt(cb.value));
                });
                await API.put('/roles/' + currentRoleId + '/permissions', { permission_ids: checkedIds });

                // Save resource permissions
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

                UI.showToast('تم حفظ الصلاحيات بنجاح ✓', 'success');
                await loadRoles();
            } catch(e) {
                UI.showToast(e.message || 'فشل في حفظ الصلاحيات', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = '💾 حفظ الصلاحيات';
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
            html = '<div class="empty-state"><p>لا توجد صلاحيات</p></div>';
        } else {
            modules.forEach(function(mod) {
                var perms = allPermissions[mod] || [];
                var modLabel = moduleLabels[mod] || mod;
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

        // Build a map of resource_type → first permission id from that module
        var flatPerms = [];
        Object.keys(allPermissions).forEach(function(mod) {
            (allPermissions[mod] || []).forEach(function(p) { flatPerms.push(p); });
        });

        var flags = ['can_view_all','can_view_own','can_create','can_edit_all','can_edit_own','can_delete_all','can_delete_own'];
        var html = '';

        resourceTypes.forEach(function(rt) {
            // Find a read permission for this resource type
            var readPerm = flatPerms.find(function(p) { return p.key_name === rt.key + '_read'; });
            var permId = readPerm ? readPerm.id : 0;
            var existing = existingRes[rt.key] || {};

            html += '<tr data-resource="' + rt.key + '" data-perm-id="' + permId + '">';
            html += '<td>' + escHtml(rt.label) + '</td>';
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
    loadPermissions().then(function() { loadRoles(); });
})();
</script>
<?php $pageScripts = ob_get_clean(); ?>

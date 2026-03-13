<?php
/**
 * Users Fragment — Full CRUD with table, search, filters, add/edit/delete modals
 * Loaded inside dashboard.php shell.
 */
?>
<style>
.u-toolbar{display:flex;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px}
.u-toolbar .search-box{position:relative;flex:1;min-width:200px;max-width:360px}
.u-toolbar .search-box input{width:100%;padding-inline-end:36px}
.u-toolbar .search-box .search-icon{position:absolute;top:50%;inset-inline-end:12px;transform:translateY(-50%);color:var(--text-secondary);pointer-events:none}
.u-toolbar .filters{display:flex;gap:10px;flex-wrap:wrap}
.u-toolbar .filters select{min-width:140px}
.u-toolbar-end{display:flex;align-items:center;gap:10px;margin-inline-start:auto}
.u-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px}
@media(max-width:768px){.u-stats{grid-template-columns:1fr 1fr}}
.u-stat{background:var(--bg-card);padding:16px;border-radius:12px;box-shadow:var(--card-shadow);border:1px solid var(--border-default);text-align:center}
.u-stat .u-stat-val{font-size:1.5rem;font-weight:700;color:var(--text-primary)}
.u-stat .u-stat-lbl{font-size:.8rem;color:var(--text-secondary);margin-top:4px}
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
.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:600}
.badge-active{background:#d4edda;color:#155724}
.badge-inactive{background:#f8d7da;color:#721c24}
.badge-role{background:var(--bg-main);color:var(--primary-dark);border:1px solid var(--border-default)}
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;justify-content:center;align-items:center}
.modal-overlay.active{display:flex}
.modal-content{background:var(--bg-card);border-radius:16px;width:90%;max-width:640px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.3)}
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
.form-group input,.form-group select{width:100%;padding:10px 14px;border:1px solid var(--border-default);border-radius:8px;background:var(--bg-card);color:var(--text-primary);font-size:.9rem}
.form-group input:focus,.form-group select:focus{outline:none;border-color:var(--primary-main);box-shadow:0 0 0 3px rgba(58,81,58,.15)}
.empty-state{text-align:center;padding:48px 24px;color:var(--text-secondary)}
.empty-state .empty-icon{font-size:3rem;margin-bottom:12px;opacity:.5}
.table-wrapper{overflow-x:auto;border-radius:12px;border:1px solid var(--border-default)}
.u-page{display:flex;justify-content:center;gap:8px;margin-top:16px;flex-wrap:wrap;align-items:center}
.u-page button{padding:6px 14px;border:1px solid var(--border-default);border-radius:6px;background:var(--bg-card);cursor:pointer;transition:all .3s;color:var(--text-primary);font-size:.85rem}
.u-page button:hover:not(:disabled){background:var(--primary-main);color:var(--text-light)}
.u-page button.active{background:var(--primary-main);color:var(--text-light);border-color:var(--primary-main)}
.u-page button:disabled{opacity:.4;cursor:not-allowed}
.u-page-info{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-top:8px;font-size:.85rem;color:var(--text-secondary)}
.u-page-info .pg-goto{display:flex;align-items:center;gap:6px}
.u-page-info .pg-goto input{width:60px;height:30px;text-align:center;border:1px solid var(--border-default);border-radius:6px;font-size:.85rem}
.u-page-info .pg-goto button{height:30px;padding:0 10px;border:1px solid var(--primary-main);background:var(--primary-main);color:var(--text-light);border-radius:6px;cursor:pointer;font-size:.8rem}
@media(max-width:768px){.u-toolbar{flex-direction:column;align-items:stretch}.u-toolbar-end{margin-inline-start:0;justify-content:space-between}.u-toolbar .search-box{max-width:100%}}
.user-detail{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.user-detail .detail-item{padding:8px 0}
.user-detail .detail-label{font-size:.8rem;color:var(--text-secondary);margin-bottom:2px}
.user-detail .detail-value{font-weight:600;color:var(--text-primary)}
@media(max-width:480px){.user-detail{grid-template-columns:1fr}}
</style>

<div class="page-header">
    <h2>إدارة المستخدمين</h2>
</div>

<!-- Stats -->
<div class="u-stats">
    <div class="u-stat"><div class="u-stat-val" id="uTotal">—</div><div class="u-stat-lbl">إجمالي المستخدمين</div></div>
    <div class="u-stat"><div class="u-stat-val" id="uActive" style="color:var(--status-success)">—</div><div class="u-stat-lbl">نشط</div></div>
    <div class="u-stat"><div class="u-stat-val" id="uInactive" style="color:var(--status-danger)">—</div><div class="u-stat-lbl">غير نشط</div></div>
</div>

<!-- Toolbar -->
<div class="u-toolbar">
    <div class="search-box">
        <input type="text" class="form-control" id="userSearch" placeholder="بحث بالاسم أو البريد أو الرقم الوظيفي...">
        <span class="search-icon">🔍</span>
    </div>
    <div class="filters">
        <select class="form-select" id="filterRole"><option value="">كل الأدوار</option></select>
        <select class="form-select" id="filterActive">
            <option value="">الكل</option>
            <option value="1">نشط</option>
            <option value="0">غير نشط</option>
        </select>
        <select class="form-select" id="filterGender">
            <option value="">كل الجنس</option>
            <option value="men">ذكر</option>
            <option value="women">أنثى</option>
        </select>
    </div>
    <div class="u-toolbar-end">
        <button class="btn btn-primary" onclick="openAddUser()">➕ إضافة مستخدم</button>
    </div>
</div>

<!-- Users Table -->
<div class="section-card">
    <div class="table-wrapper table-responsive">
        <table class="data-table" id="usersTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>الرقم الوظيفي</th>
                    <th>اسم المستخدم</th>
                    <th>البريد</th>
                    <th>الهاتف</th>
                    <th>الدور</th>
                    <th>الحالة</th>
                    <th>الجنس</th>
                    <th>تاريخ الإنشاء</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody id="usersBody">
                <tr><td colspan="10" class="empty-state"><div class="empty-icon">👥</div><p>جارٍ التحميل...</p></td></tr>
            </tbody>
        </table>
    </div>
</div>
<div class="u-page" id="uPagination"></div>
<div class="u-page-info" id="uPaginationInfo"></div>

<!-- View User Modal -->
<div class="modal-overlay" id="viewModal">
    <div class="modal-content">
        <div class="modal-header"><h3>👤 بيانات المستخدم</h3><button class="modal-close" onclick="closeModal('viewModal')">✕</button></div>
        <div class="modal-body" id="viewBody"></div>
        <div class="modal-footer"><button class="btn btn-secondary" onclick="closeModal('viewModal')">إغلاق</button></div>
    </div>
</div>

<!-- Add/Edit User Modal -->
<div class="modal-overlay" id="formModal">
    <div class="modal-content">
        <div class="modal-header"><h3 id="formTitle">إضافة مستخدم</h3><button class="modal-close" onclick="closeModal('formModal')">✕</button></div>
        <div class="modal-body">
            <form id="userForm" class="form-grid" onsubmit="return false;">
                <input type="hidden" id="editUserId" value="">
                <div class="form-group">
                    <label>الرقم الوظيفي</label>
                    <input type="text" id="fEmpId" placeholder="EMP001">
                </div>
                <div class="form-group">
                    <label>اسم المستخدم *</label>
                    <input type="text" id="fUsername" required placeholder="username">
                </div>
                <div class="form-group">
                    <label>البريد الإلكتروني</label>
                    <input type="email" id="fEmail" placeholder="user@example.com">
                </div>
                <div class="form-group">
                    <label>الهاتف</label>
                    <input type="text" id="fPhone" placeholder="05xxxxxxxx">
                </div>
                <div class="form-group">
                    <label id="fPasswordLabel">كلمة المرور *</label>
                    <input type="password" id="fPassword" placeholder="••••••••">
                </div>
                <div class="form-group">
                    <label>الدور</label>
                    <select id="fRoleId"></select>
                </div>
                <div class="form-group">
                    <label>الجنس</label>
                    <select id="fGender">
                        <option value="">-- غير محدد --</option>
                        <option value="men">ذكر</option>
                        <option value="women">أنثى</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>اللغة المفضلة</label>
                    <select id="fLang">
                        <option value="ar">العربية</option>
                        <option value="en">English</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>الحالة</label>
                    <select id="fActive">
                        <option value="1">نشط</option>
                        <option value="0">غير نشط</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('formModal')">إلغاء</button>
            <button class="btn btn-primary" id="saveBtn" onclick="saveUser()">💾 حفظ</button>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
(function(){
    let allUsers = [];
    let roles = [];

    async function loadRoles() {
        try {
            const res = await API.get('/roles/public');
            roles = (res && res.data) || (res && res.roles) || [];
        } catch(e) {
            roles = [];
        }
        const sel = document.getElementById('filterRole');
        const fSel = document.getElementById('fRoleId');
        roles.forEach(r => {
            sel.innerHTML += '<option value="'+r.id+'">'+r.display_name+'</option>';
            fSel.innerHTML += '<option value="'+r.id+'">'+r.display_name+'</option>';
        });
    }

    async function loadUsers() {
        try {
            const res = await API.get('/users');
            allUsers = (res && res.data) || [];
        } catch(e) {
            allUsers = [];
        }
        updateStats();
        renderTable();
    }

    function updateStats() {
        const total = allUsers.length;
        const active = allUsers.filter(u => parseInt(u.is_active) === 1).length;
        document.getElementById('uTotal').textContent = total;
        document.getElementById('uActive').textContent = active;
        document.getElementById('uInactive').textContent = total - active;
    }

    var uCurrentPage = 1, uPerPage = 15;

    function renderTable() {
        const search = (document.getElementById('userSearch').value || '').toLowerCase();
        const roleFilter = document.getElementById('filterRole').value;
        const activeFilter = document.getElementById('filterActive').value;
        const genderFilter = document.getElementById('filterGender').value;

        var filtered = allUsers.filter(u => {
            if (search) {
                const haystack = [u.username, u.email, u.emp_id, u.phone].join(' ').toLowerCase();
                if (!haystack.includes(search)) return false;
            }
            if (roleFilter && String(u.role_id) !== roleFilter) return false;
            if (activeFilter !== '' && String(u.is_active) !== activeFilter) return false;
            if (genderFilter && (u.gender || '') !== genderFilter) return false;
            return true;
        });

        const tbody = document.getElementById('usersBody');
        if (filtered.length === 0) {
            tbody.innerHTML = '<tr><td colspan="10" class="empty-state"><div class="empty-icon">👥</div><p>لا يوجد مستخدمون</p></td></tr>';
            renderUsersPagination(0, 0);
            return;
        }

        var totalItems = filtered.length;
        var totalPg = Math.ceil(totalItems / uPerPage);
        if (uCurrentPage > totalPg) uCurrentPage = totalPg;
        var start = (uCurrentPage - 1) * uPerPage;
        var pageData = filtered.slice(start, start + uPerPage);

        tbody.innerHTML = pageData.map((u, i) => {
            const statusBadge = parseInt(u.is_active) === 1
                ? '<span class="badge badge-active">نشط</span>'
                : '<span class="badge badge-inactive">غير نشط</span>';
            const roleBadge = '<span class="badge badge-role">' + (u.role_name || '\u2014') + '</span>';
            const genderLabel = u.gender === 'men' ? 'ذكر' : u.gender === 'women' ? 'أنثى' : '\u2014';
            const created = u.created_at ? u.created_at.substring(0, 10) : '\u2014';
            return '<tr>' +
                '<td data-label="#">' + (start + i + 1) + '</td>' +
                '<td data-label="الرقم الوظيفي">' + escHtml(u.emp_id || '\u2014') + '</td>' +
                '<td data-label="اسم المستخدم"><strong>' + escHtml(u.username) + '</strong></td>' +
                '<td data-label="البريد">' + escHtml(u.email || '\u2014') + '</td>' +
                '<td data-label="الهاتف">' + escHtml(u.phone || '\u2014') + '</td>' +
                '<td data-label="الدور">' + roleBadge + '</td>' +
                '<td data-label="الحالة">' + statusBadge + '</td>' +
                '<td data-label="الجنس">' + genderLabel + '</td>' +
                '<td data-label="تاريخ الإنشاء">' + created + '</td>' +
                '<td data-label="الإجراءات" class="table-actions">' +
                    '<button class="btn-icon btn-view" title="عرض" data-action="view" data-id="' + parseInt(u.id) + '">👁</button>' +
                    '<button class="btn-icon btn-edit" title="تعديل" data-action="edit" data-id="' + parseInt(u.id) + '">✏️</button>' +
                    '<button class="btn-icon btn-delete" title="حذف" data-action="delete" data-id="' + parseInt(u.id) + '">🗑️</button>' +
                '</td></tr>';
        }).join('');
        renderUsersPagination(totalItems, totalPg);
    }

    function renderUsersPagination(totalItems, totalPg) {
        var pg = document.getElementById('uPagination');
        var info = document.getElementById('uPaginationInfo');
        if (totalPg <= 1) { pg.innerHTML = ''; info.innerHTML = totalItems ? '<span>' + i18n.t('total_records') + ': ' + totalItems + '</span>' : ''; return; }
        var h = '<button ' + (uCurrentPage <= 1 ? 'disabled' : '') + ' onclick="UsersPage.goPage(' + (uCurrentPage - 1) + ')">' + i18n.t('previous') + '</button>';
        var start = Math.max(1, uCurrentPage - 3), end = Math.min(totalPg, uCurrentPage + 3);
        if (start > 1) { h += '<button onclick="UsersPage.goPage(1)">1</button>'; if (start > 2) h += '<span style="padding:0 4px">…</span>'; }
        for (var i = start; i <= end; i++) {
            h += '<button class="' + (i === uCurrentPage ? 'active' : '') + '" onclick="UsersPage.goPage(' + i + ')">' + i + '</button>';
        }
        if (end < totalPg) { if (end < totalPg - 1) h += '<span style="padding:0 4px">…</span>'; h += '<button onclick="UsersPage.goPage(' + totalPg + ')">' + totalPg + '</button>'; }
        h += '<button ' + (uCurrentPage >= totalPg ? 'disabled' : '') + ' onclick="UsersPage.goPage(' + (uCurrentPage + 1) + ')">' + i18n.t('next') + '</button>';
        pg.innerHTML = h;
        info.innerHTML = '<span>' + i18n.t('total_records') + ': ' + totalItems + ' | ' + i18n.t('page') + ' ' + uCurrentPage + ' ' + i18n.t('of') + ' ' + totalPg + '</span>' +
            '<div class="pg-goto"><label>' + i18n.t('go_to_page') + ':</label><input type="number" min="1" max="' + totalPg + '" id="uGotoInput" value="' + uCurrentPage + '"><button onclick="UsersPage.gotoPage()">↵</button></div>';
    }

    window.UsersPage = {
        goPage: function(p) { var totalPg = Math.ceil(allUsers.length / uPerPage); if (p < 1) p = 1; if (p > totalPg) p = totalPg; uCurrentPage = p; renderTable(); window.scrollTo({top: 0, behavior: 'smooth'}); },
        gotoPage: function() { var inp = document.getElementById('uGotoInput'); if (inp) { var p = parseInt(inp.value); if (p && p >= 1) this.goPage(p); } }
    };

    // Event delegation for table action buttons
    document.getElementById('usersBody').addEventListener('click', function(e) {
        var btn = e.target.closest('[data-action]');
        if (!btn) return;
        var action = btn.getAttribute('data-action');
        var id = parseInt(btn.getAttribute('data-id'));
        if (action === 'view') viewUser(id);
        else if (action === 'edit') editUser(id);
        else if (action === 'delete') {
            var u = allUsers.find(function(x){ return x.id == id; });
            deleteUser(id, u ? u.username : '');
        }
    });

    function escHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    window.openAddUser = function() {
        document.getElementById('editUserId').value = '';
        document.getElementById('formTitle').textContent = '➕ إضافة مستخدم جديد';
        document.getElementById('fPasswordLabel').textContent = 'كلمة المرور *';
        document.getElementById('fPassword').required = true;
        document.getElementById('userForm').reset();
        document.getElementById('fActive').value = '1';
        document.getElementById('fLang').value = 'ar';
        document.getElementById('formModal').classList.add('active');
    };

    window.editUser = function(id) {
        const u = allUsers.find(x => x.id == id);
        if (!u) return;
        document.getElementById('editUserId').value = u.id;
        document.getElementById('formTitle').textContent = '✏️ تعديل المستخدم: ' + u.username;
        document.getElementById('fPasswordLabel').textContent = 'كلمة المرور (اتركها فارغة لعدم التغيير)';
        document.getElementById('fPassword').required = false;
        document.getElementById('fEmpId').value = u.emp_id || '';
        document.getElementById('fUsername').value = u.username || '';
        document.getElementById('fEmail').value = u.email || '';
        document.getElementById('fPhone').value = u.phone || '';
        document.getElementById('fPassword').value = '';
        document.getElementById('fRoleId').value = u.role_id || '';
        document.getElementById('fGender').value = u.gender || '';
        document.getElementById('fLang').value = u.preferred_language || 'ar';
        document.getElementById('fActive').value = u.is_active != null ? String(u.is_active) : '1';
        document.getElementById('formModal').classList.add('active');
    };

    window.viewUser = function(id) {
        const u = allUsers.find(x => x.id == id);
        if (!u) return;
        const statusText = parseInt(u.is_active) === 1 ? '<span class="badge badge-active">نشط</span>' : '<span class="badge badge-inactive">غير نشط</span>';
        document.getElementById('viewBody').innerHTML =
            '<div class="user-detail">' +
            '<div class="detail-item"><div class="detail-label">الرقم الوظيفي</div><div class="detail-value">' + escHtml(u.emp_id || '\u2014') + '</div></div>' +
            '<div class="detail-item"><div class="detail-label">اسم المستخدم</div><div class="detail-value">' + escHtml(u.username) + '</div></div>' +
            '<div class="detail-item"><div class="detail-label">البريد</div><div class="detail-value">' + escHtml(u.email || '\u2014') + '</div></div>' +
            '<div class="detail-item"><div class="detail-label">الهاتف</div><div class="detail-value">' + escHtml(u.phone || '\u2014') + '</div></div>' +
            '<div class="detail-item"><div class="detail-label">الدور</div><div class="detail-value"><span class="badge badge-role">' + escHtml(u.role_name || '\u2014') + '</span></div></div>' +
            '<div class="detail-item"><div class="detail-label">الحالة</div><div class="detail-value">' + statusText + '</div></div>' +
            '<div class="detail-item"><div class="detail-label">الجنس</div><div class="detail-value">' + (u.gender === 'men' ? 'ذكر' : u.gender === 'women' ? 'أنثى' : '\u2014') + '</div></div>' +
            '<div class="detail-item"><div class="detail-label">اللغة</div><div class="detail-value">' + (u.preferred_language === 'ar' ? 'العربية' : 'English') + '</div></div>' +
            '<div class="detail-item"><div class="detail-label">تاريخ الإنشاء</div><div class="detail-value">' + escHtml(u.created_at || '\u2014') + '</div></div>' +
            '<div class="detail-item"><div class="detail-label">آخر تحديث</div><div class="detail-value">' + escHtml(u.updated_at || '\u2014') + '</div></div>' +
            '</div>';
        document.getElementById('viewModal').classList.add('active');
    };

    window.saveUser = async function() {
        const id = document.getElementById('editUserId').value;
        const payload = {
            emp_id: document.getElementById('fEmpId').value.trim(),
            username: document.getElementById('fUsername').value.trim(),
            email: document.getElementById('fEmail').value.trim(),
            phone: document.getElementById('fPhone').value.trim(),
            role_id: parseInt(document.getElementById('fRoleId').value) || 3,
            gender: document.getElementById('fGender').value || null,
            preferred_language: document.getElementById('fLang').value || 'ar',
            is_active: parseInt(document.getElementById('fActive').value),
        };

        const pw = document.getElementById('fPassword').value;
        if (pw) payload.password = pw;

        if (!payload.username) {
            UI.showToast('اسم المستخدم مطلوب', 'error');
            return;
        }
        if (!id && !pw) {
            UI.showToast('كلمة المرور مطلوبة للمستخدم الجديد', 'error');
            return;
        }

        const btn = document.getElementById('saveBtn');
        btn.disabled = true;
        btn.textContent = 'جارٍ الحفظ...';

        try {
            if (id) {
                await API.put('/users/' + id, payload);
                UI.showToast('تم تحديث المستخدم بنجاح ✓', 'success');
            } else {
                await API.post('/users', payload);
                UI.showToast('تم إنشاء المستخدم بنجاح ✓', 'success');
            }
            closeModal('formModal');
            await loadUsers();
        } catch(e) {
            UI.showToast(e.message || 'فشل في الحفظ', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = '💾 حفظ';
        }
    };

    window.deleteUser = async function(id, name) {
        if (!confirm('هل أنت متأكد من حذف المستخدم: ' + name + '؟')) return;
        try {
            await API.del('/users/' + id);
            UI.showToast('تم حذف المستخدم بنجاح', 'success');
            await loadUsers();
        } catch(e) {
            UI.showToast(e.message || 'فشل في الحذف', 'error');
        }
    };

    window.closeModal = function(modalId) {
        document.getElementById(modalId).classList.remove('active');
    };

    function resetPageAndRender() { uCurrentPage = 1; renderTable(); }
    document.getElementById('userSearch').addEventListener('input', resetPageAndRender);
    document.getElementById('filterRole').addEventListener('change', resetPageAndRender);
    document.getElementById('filterActive').addEventListener('change', resetPageAndRender);
    document.getElementById('filterGender').addEventListener('change', resetPageAndRender);

    document.querySelectorAll('.modal-overlay').forEach(m => {
        m.addEventListener('click', e => { if (e.target === m) m.classList.remove('active'); });
    });

    loadRoles().then(() => loadUsers());
})();
</script>
<?php $pageScripts = ob_get_clean(); ?>

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
/* Toggle switch */
.toggle-switch{position:relative;display:inline-block;width:42px;height:24px;cursor:pointer}
.toggle-switch input{opacity:0;width:0;height:0}
.toggle-slider{position:absolute;inset:0;background:#ccc;border-radius:24px;transition:.3s}
.toggle-slider:before{content:"";position:absolute;height:18px;width:18px;bottom:3px;left:3px;background:#fff;border-radius:50%;transition:.3s}
.toggle-switch input:checked+.toggle-slider{background:var(--status-success,#4caf50)}
.toggle-switch input:checked+.toggle-slider:before{transform:translateX(18px)}
</style>

<div class="page-header">
    <h2 id="pageTitle">User Management</h2>
</div>

<!-- Stats -->
<div class="u-stats">
    <div class="u-stat"><div class="u-stat-val" id="uTotal">—</div><div class="u-stat-lbl" id="lblTotalUsers">Total Users</div></div>
    <div class="u-stat"><div class="u-stat-val" id="uActive" style="color:var(--status-success)">—</div><div class="u-stat-lbl" id="lblActiveUsers">Active</div></div>
    <div class="u-stat"><div class="u-stat-val" id="uInactive" style="color:var(--status-danger)">—</div><div class="u-stat-lbl" id="lblInactiveUsers">Inactive</div></div>
</div>

<!-- Toolbar -->
<div class="u-toolbar">
    <div class="search-box">
        <input type="text" class="form-control" id="userSearch" placeholder="Search user...">
        <span class="search-icon">🔍</span>
    </div>
    <div class="filters">
        <select class="form-select" id="filterRole"><option value="" id="optAllRoles">All Roles</option></select>
        <select class="form-select" id="filterSector"><option value="" id="optAllSectors">All Sectors</option></select>
        <select class="form-select" id="filterDept"><option value="" id="optAllDepts">All Departments</option></select>
        <select class="form-select" id="filterSection"><option value="" id="optAllSections">All Sections</option></select>
        <select class="form-select" id="filterDivision"><option value="" id="optAllDivisions">All Divisions</option></select>
        <select class="form-select" id="filterActive">
            <option value="" id="optAllStatus">All</option>
            <option value="1" id="optActiveStatus">Active</option>
            <option value="0" id="optInactiveStatus">Inactive</option>
        </select>
        <select class="form-select" id="filterGender">
            <option value="" id="optAllGenders">All Genders</option>
            <option value="men" id="optMaleGender">Male</option>
            <option value="women" id="optFemaleGender">Female</option>
        </select>
    </div>
    <div class="u-toolbar-end">
        <button class="btn btn-primary" id="btnAddUser" onclick="openAddUser()">➕ Add User</button>
    </div>
</div>

<!-- Users Table -->
<div class="section-card">
    <div class="table-wrapper table-responsive">
        <table class="data-table" id="usersTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th id="thEmpId">Employee ID</th>
                    <th id="thUsername">Username</th>
                    <th id="thEmail">Email</th>
                    <th id="thPhone">Phone</th>
                    <th id="thRole">Role</th>
                    <th id="thSector">Sector</th>
                    <th id="thDept">Department</th>
                    <th id="thSection">Section</th>
                    <th id="thDivision">Division</th>
                    <th id="thStatus">Status</th>
                    <th id="thGender">Gender</th>
                    <th id="thCreatedAt">Created At</th>
                    <th id="thActions">Actions</th>
                </tr>
            </thead>
            <tbody id="usersBody">
                <tr><td colspan="14" class="empty-state"><div class="empty-icon">👥</div><p>Loading...</p></td></tr>
            </tbody>
        </table>
    </div>
</div>
<div class="u-page" id="uPagination"></div>
<div class="u-page-info" id="uPaginationInfo"></div>

<!-- View User Modal -->
<div class="modal-overlay" id="viewModal">
    <div class="modal-content">
        <div class="modal-header"><h3 id="viewModalTitle">👤 User Details</h3><button class="modal-close" onclick="closeModal('viewModal')">✕</button></div>
        <div class="modal-body" id="viewBody"></div>
        <div class="modal-footer"><button class="btn btn-secondary" id="btnCloseView" onclick="closeModal('viewModal')">Close</button></div>
    </div>
</div>

<!-- Add/Edit User Modal -->
<div class="modal-overlay" id="formModal">
    <div class="modal-content">
        <div class="modal-header"><h3 id="formTitle">Add User</h3><button class="modal-close" onclick="closeModal('formModal')">✕</button></div>
        <div class="modal-body">
            <form id="userForm" class="form-grid" onsubmit="return false;">
                <input type="hidden" id="editUserId" value="">
                <div class="form-group">
                    <label id="lblEmpId">Employee ID</label>
                    <input type="text" id="fEmpId" placeholder="EMP001">
                </div>
                <div class="form-group">
                    <label id="lblUsername">Username *</label>
                    <input type="text" id="fUsername" required placeholder="username">
                </div>
                <div class="form-group">
                    <label id="lblEmail">Email</label>
                    <input type="email" id="fEmail" placeholder="user@example.com">
                </div>
                <div class="form-group">
                    <label id="lblPhone">Phone</label>
                    <input type="text" id="fPhone" placeholder="05xxxxxxxx">
                </div>
                <div class="form-group">
                    <label id="fPasswordLabel">Password *</label>
                    <input type="password" id="fPassword" placeholder="••••••••">
                </div>
                <div class="form-group">
                    <label id="lblRole">Role</label>
                    <select id="fRoleId"></select>
                </div>
                <div class="form-group">
                    <label id="lblFormSector">Sector</label>
                    <select id="fSectorId"><option value="">-- All --</option></select>
                </div>
                <div class="form-group">
                    <label id="lblFormDept">Department</label>
                    <select id="fDeptId"><option value="">-- All --</option></select>
                </div>
                <div class="form-group">
                    <label id="lblFormSection">Section</label>
                    <select id="fSectionId"><option value="">-- All --</option></select>
                </div>
                <div class="form-group">
                    <label id="lblFormDivision">Division</label>
                    <select id="fDivisionId"><option value="">-- All --</option></select>
                </div>
                <div class="form-group">
                    <label id="lblFormGender">Gender</label>
                    <select id="fGender">
                        <option value="" id="fOptUnspecified">-- Unspecified --</option>
                        <option value="men" id="fOptMale">Male</option>
                        <option value="women" id="fOptFemale">Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label id="lblLang">Preferred Language</label>
                    <select id="fLang">
                        <option value="ar" id="fOptArabic">Arabic</option>
                        <option value="en" id="fOptEnglish">English</option>
                    </select>
                </div>
                <div class="form-group">
                    <label id="lblFormStatus">Status</label>
                    <select id="fActive">
                        <option value="1" id="fOptActive">Active</option>
                        <option value="0" id="fOptInactive">Inactive</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" id="btnCancel" onclick="closeModal('formModal')">Cancel</button>
            <button class="btn btn-primary" id="saveBtn" onclick="saveUser()">💾 Save</button>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
(function(){
    let allUsers = [];
    let roles = [];
    var uCanCreate=false, uCanEdit=false, uCanDelete=false;

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

    let uRefs = {sectors:[],departments:[],sections:[],divisions:[]};
    async function loadUserRefs() {
        try {
            const res = await API.get('/references');
            uRefs = (res && res.data) || res || {sectors:[],departments:[],sections:[],divisions:[]};
        } catch(e) { uRefs = {sectors:[],departments:[],sections:[],divisions:[]}; }
        var sd = document.getElementById('fSectorId');
        sd.innerHTML = '<option value="">--</option>';
        var filterSel = document.getElementById('filterSector');
        var prevFilterVal = filterSel ? filterSel.value : '';
        if(filterSel) filterSel.innerHTML = '<option value="">' + i18n.t('all_sectors') + '</option>';
        (uRefs.sectors||[]).forEach(function(s){
            sd.innerHTML += '<option value="'+s.id+'">'+(s.name||s.name_en)+'</option>';
            if(filterSel) filterSel.innerHTML += '<option value="'+s.id+'">'+(s.name||s.name_en)+'</option>';
        });
        if(prevFilterVal && filterSel) filterSel.value = prevFilterVal;
        // Populate filter department dropdown
        var filterDept = document.getElementById('filterDept');
        filterDept.innerHTML = '<option value="">' + i18n.t('all_departments') + '</option>';
        (uRefs.departments||[]).forEach(function(d){
            filterDept.innerHTML += '<option value="'+d.department_id+'">'+(d.name_ar||d.name_en)+'</option>';
        });
        // Populate filter section dropdown (all initially)
        cascadeFilterSection('');
        // Populate filter division dropdown (all initially)
        cascadeFilterDivision('');
        var dd = document.getElementById('fDeptId');
        dd.innerHTML = '<option value="">--</option>';
        (uRefs.departments||[]).forEach(function(d){
            dd.innerHTML += '<option value="'+d.department_id+'">'+(d.name_ar||d.name_en)+'</option>';
        });
        dd.addEventListener('change', function(){ cascadeSection(this.value); });
        document.getElementById('fSectionId').addEventListener('change', function(){ cascadeDivision(this.value); });
    }
    // Filter cascading for department→section→division
    function cascadeFilterSection(did) {
        var s = document.getElementById('filterSection');
        s.innerHTML = '<option value="">' + i18n.t('all_sections') + '</option>';
        (uRefs.sections||[]).filter(function(sc){ return !did || sc.department_id == did; }).forEach(function(sc){
            s.innerHTML += '<option value="'+sc.section_id+'">'+(sc.name_ar||sc.name_en)+'</option>';
        });
        cascadeFilterDivision('');
    }
    function cascadeFilterDivision(sid) {
        var d = document.getElementById('filterDivision');
        d.innerHTML = '<option value="">' + i18n.t('all_divisions') + '</option>';
        (uRefs.divisions||[]).filter(function(dv){ return !sid || dv.section_id == sid; }).forEach(function(dv){
            d.innerHTML += '<option value="'+dv.division_id+'">'+(dv.name_ar||dv.name_en)+'</option>';
        });
    }
    // Form cascading for department→section→division
    function cascadeSection(did) {
        var s = document.getElementById('fSectionId');
        s.innerHTML = '<option value="">--</option>';
        (uRefs.sections||[]).filter(function(sc){ return sc.department_id == did; }).forEach(function(sc){
            s.innerHTML += '<option value="'+sc.section_id+'">'+(sc.name_ar||sc.name_en)+'</option>';
        });
        cascadeDivision('');
    }
    function cascadeDivision(sid) {
        var d = document.getElementById('fDivisionId');
        d.innerHTML = '<option value="">--</option>';
        (uRefs.divisions||[]).filter(function(dv){ return dv.section_id == sid; }).forEach(function(dv){
            d.innerHTML += '<option value="'+dv.division_id+'">'+(dv.name_ar||dv.name_en)+'</option>';
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

    var uCurrentPage = 1, uPerPage = 100;

    function renderTable() {
        const search = (document.getElementById('userSearch').value || '').toLowerCase();
        const roleFilter = document.getElementById('filterRole').value;
        const sectorFilter = document.getElementById('filterSector').value;
        const deptFilter = document.getElementById('filterDept').value;
        const sectionFilter = document.getElementById('filterSection').value;
        const divisionFilter = document.getElementById('filterDivision').value;
        const activeFilter = document.getElementById('filterActive').value;
        const genderFilter = document.getElementById('filterGender').value;

        var filtered = allUsers.filter(u => {
            if (search) {
                const haystack = [u.username, u.email, u.emp_id, u.phone].join(' ').toLowerCase();
                if (!haystack.includes(search)) return false;
            }
            if (roleFilter && String(u.role_id) !== roleFilter) return false;
            if (sectorFilter && String(u.sector_id || '') !== sectorFilter) return false;
            if (deptFilter && String(u.department_id || '') !== deptFilter) return false;
            if (sectionFilter && String(u.section_id || '') !== sectionFilter) return false;
            if (divisionFilter && String(u.division_id || '') !== divisionFilter) return false;
            if (activeFilter !== '' && String(u.is_active) !== activeFilter) return false;
            if (genderFilter && (u.gender || '') !== genderFilter) return false;
            return true;
        });

        const tbody = document.getElementById('usersBody');
        if (filtered.length === 0) {
            tbody.innerHTML = '<tr><td colspan="14" class="empty-state"><div class="empty-icon">👥</div><p>' + i18n.t('no_users') + '</p></td></tr>';
            renderUsersPagination(0, 0);
            return;
        }

        var totalItems = filtered.length;
        var totalPg = Math.ceil(totalItems / uPerPage);
        if (uCurrentPage > totalPg) uCurrentPage = totalPg;
        var start = (uCurrentPage - 1) * uPerPage;
        var pageData = filtered.slice(start, start + uPerPage);

        tbody.innerHTML = pageData.map((u, i) => {
            const isActive = parseInt(u.is_active) === 1;
            const toggleSwitch = '<label class="toggle-switch" title="' + (isActive ? i18n.t('active') : i18n.t('inactive')) + '">' +
                '<input type="checkbox" ' + (isActive ? 'checked' : '') + ' onchange="UsersPage.toggleActive(' + parseInt(u.id) + ', this.checked)">' +
                '<span class="toggle-slider"></span></label>';
            const roleBadge = '<span class="badge badge-role">' + (u.role_name || '\u2014') + '</span>';
            var deptName = '\u2014', sectName = '\u2014', divName = '\u2014', sectorName = '\u2014';
            if (u.sector_id) { var sc = (uRefs.sectors||[]).find(function(s){ return s.id == u.sector_id; }); if (sc) sectorName = sc.name || sc.name_en; }
            if (u.department_id) { var dd = (uRefs.departments||[]).find(function(d){ return d.department_id == u.department_id; }); if (dd) deptName = dd.name_ar || dd.name_en; }
            if (u.section_id) { var ss = (uRefs.sections||[]).find(function(s){ return s.section_id == u.section_id; }); if (ss) sectName = ss.name_ar || ss.name_en; }
            if (u.division_id) { var dv = (uRefs.divisions||[]).find(function(d){ return d.division_id == u.division_id; }); if (dv) divName = dv.name_ar || dv.name_en; }
            const genderLabel = u.gender === 'men' ? i18n.t('male') : u.gender === 'women' ? i18n.t('female') : '\u2014';
            const created = u.created_at ? u.created_at.substring(0, 10) : '\u2014';
            return '<tr>' +
                '<td data-label="#">' + (start + i + 1) + '</td>' +
                '<td data-label="' + i18n.t('employee_id') + '">' + escHtml(u.emp_id || '\u2014') + '</td>' +
                '<td data-label="' + i18n.t('username') + '"><strong>' + escHtml(u.username) + '</strong></td>' +
                '<td data-label="' + i18n.t('email') + '">' + escHtml(u.email || '\u2014') + '</td>' +
                '<td data-label="' + i18n.t('phone') + '">' + escHtml(u.phone || '\u2014') + '</td>' +
                '<td data-label="' + i18n.t('role') + '">' + roleBadge + '</td>' +
                '<td data-label="' + i18n.t('sector') + '">' + escHtml(sectorName) + '</td>' +
                '<td data-label="' + i18n.t('department') + '">' + escHtml(deptName) + '</td>' +
                '<td data-label="' + i18n.t('section') + '">' + escHtml(sectName) + '</td>' +
                '<td data-label="' + i18n.t('division') + '">' + escHtml(divName) + '</td>' +
                '<td data-label="' + i18n.t('status') + '">' + toggleSwitch + '</td>' +
                '<td data-label="' + i18n.t('gender') + '">' + genderLabel + '</td>' +
                '<td data-label="' + i18n.t('created_at') + '">' + created + '</td>' +
                '<td data-label="' + i18n.t('actions') + '" class="table-actions">' +
                    '<button class="btn-icon btn-view" title="' + i18n.t('view') + '" data-action="view" data-id="' + parseInt(u.id) + '">👁</button>' +
                    (uCanEdit ? '<button class="btn-icon btn-edit" title="' + i18n.t('edit') + '" data-action="edit" data-id="' + parseInt(u.id) + '">✏️</button>' : '') +
                    (uCanDelete ? '<button class="btn-icon btn-delete" title="' + i18n.t('delete') + '" data-action="delete" data-id="' + parseInt(u.id) + '">🗑️</button>' : '') +
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
        gotoPage: function() { var inp = document.getElementById('uGotoInput'); if (inp) { var p = parseInt(inp.value); if (p && p >= 1) this.goPage(p); } },
        toggleActive: async function(id, checked) {
            try {
                var u = allUsers.find(function(x){ return x.id == id; });
                if (!u) return;
                var newVal = checked ? 1 : 0;
                await API.put('/users/' + id, { is_active: newVal });
                u.is_active = newVal;
                updateStats();
                Toast.show(i18n.t('success'), 'success');
            } catch(e) {
                Toast.show(i18n.t('error') + ': ' + e.message, 'error');
                renderTable();
            }
        }
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
        document.getElementById('formTitle').textContent = '➕ ' + i18n.t('add_user');
        document.getElementById('fPasswordLabel').textContent = i18n.t('password') + ' *';
        document.getElementById('fPassword').required = true;
        document.getElementById('userForm').reset();
        document.getElementById('fActive').value = '1';
        document.getElementById('fLang').value = 'ar';
        document.getElementById('fSectorId').value = '';
        document.getElementById('fDeptId').value = '';
        cascadeSection('');
        document.getElementById('formModal').classList.add('active');
    };

    window.editUser = function(id) {
        const u = allUsers.find(x => x.id == id);
        if (!u) return;
        document.getElementById('editUserId').value = u.id;
        document.getElementById('formTitle').textContent = '✏️ ' + i18n.t('edit_user') + ': ' + u.username;
        document.getElementById('fPasswordLabel').textContent = i18n.t('password_optional');
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
        document.getElementById('fSectorId').value = u.sector_id || '';
        document.getElementById('fDeptId').value = u.department_id || '';
        cascadeSection(u.department_id || '');
        setTimeout(function(){ document.getElementById('fSectionId').value = u.section_id || ''; cascadeDivision(u.section_id || ''); setTimeout(function(){ document.getElementById('fDivisionId').value = u.division_id || ''; },50); },50);
        document.getElementById('formModal').classList.add('active');
    };

    window.viewUser = function(id) {
        const u = allUsers.find(x => x.id == id);
        if (!u) return;
        var deptName = '\u2014', sectName = '\u2014', divName = '\u2014', sectorName = '\u2014';
        if(u.sector_id) { var sc=(uRefs.sectors||[]).find(function(s){return s.id==u.sector_id;}); if(sc) sectorName=sc.name||sc.name_en; }
        if(u.department_id) { var dd=(uRefs.departments||[]).find(function(d){return d.department_id==u.department_id;}); if(dd) deptName=dd.name_ar||dd.name_en; }
        if(u.section_id) { var ss=(uRefs.sections||[]).find(function(s){return s.section_id==u.section_id;}); if(ss) sectName=ss.name_ar||ss.name_en; }
        if(u.division_id) { var dv=(uRefs.divisions||[]).find(function(d){return d.division_id==u.division_id;}); if(dv) divName=dv.name_ar||dv.name_en; }
        const statusText = parseInt(u.is_active) === 1 ? '<span class="badge badge-active">' + i18n.t('active') + '</span>' : '<span class="badge badge-inactive">' + i18n.t('inactive') + '</span>';
        document.getElementById('viewBody').innerHTML =
            '<div class="user-detail">' +
            '<div class="detail-item"><div class="detail-label">' + i18n.t('employee_id') + '</div><div class="detail-value">' + escHtml(u.emp_id || '\u2014') + '</div></div>' +
            '<div class="detail-item"><div class="detail-label">' + i18n.t('username') + '</div><div class="detail-value">' + escHtml(u.username) + '</div></div>' +
            '<div class="detail-item"><div class="detail-label">' + i18n.t('email') + '</div><div class="detail-value">' + escHtml(u.email || '\u2014') + '</div></div>' +
            '<div class="detail-item"><div class="detail-label">' + i18n.t('phone') + '</div><div class="detail-value">' + escHtml(u.phone || '\u2014') + '</div></div>' +
            '<div class="detail-item"><div class="detail-label">' + i18n.t('role') + '</div><div class="detail-value"><span class="badge badge-role">' + escHtml(u.role_name || '\u2014') + '</span></div></div>' +
            '<div class="detail-item"><div class="detail-label">' + i18n.t('sector') + '</div><div class="detail-value">' + escHtml(sectorName) + '</div></div>' +
            '<div class="detail-item"><div class="detail-label">' + i18n.t('department') + '</div><div class="detail-value">' + escHtml(deptName) + '</div></div>' +
            '<div class="detail-item"><div class="detail-label">' + i18n.t('section') + '</div><div class="detail-value">' + escHtml(sectName) + '</div></div>' +
            '<div class="detail-item"><div class="detail-label">' + i18n.t('division') + '</div><div class="detail-value">' + escHtml(divName) + '</div></div>' +
            '<div class="detail-item"><div class="detail-label">' + i18n.t('status') + '</div><div class="detail-value">' + statusText + '</div></div>' +
            '<div class="detail-item"><div class="detail-label">' + i18n.t('gender') + '</div><div class="detail-value">' + (u.gender === 'men' ? i18n.t('male') : u.gender === 'women' ? i18n.t('female') : '\u2014') + '</div></div>' +
            '<div class="detail-item"><div class="detail-label">' + i18n.t('preferred_language') + '</div><div class="detail-value">' + (u.preferred_language === 'ar' ? i18n.t('arabic') : i18n.t('english')) + '</div></div>' +
            '<div class="detail-item"><div class="detail-label">' + i18n.t('created_at') + '</div><div class="detail-value">' + escHtml(u.created_at || '\u2014') + '</div></div>' +
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
            sector_id: document.getElementById('fSectorId').value ? parseInt(document.getElementById('fSectorId').value) : null,
            department_id: document.getElementById('fDeptId').value ? parseInt(document.getElementById('fDeptId').value) : null,
            section_id: document.getElementById('fSectionId').value ? parseInt(document.getElementById('fSectionId').value) : null,
            division_id: document.getElementById('fDivisionId').value ? parseInt(document.getElementById('fDivisionId').value) : null,
        };

        const pw = document.getElementById('fPassword').value;
        if (pw) payload.password = pw;

        if (!payload.username) {
            UI.showToast(i18n.t('username_required'), 'error');
            return;
        }
        if (!id && !pw) {
            UI.showToast(i18n.t('password_required'), 'error');
            return;
        }

        const btn = document.getElementById('saveBtn');
        btn.disabled = true;
        btn.textContent = i18n.t('saving') + '...';

        try {
            if (id) {
                await API.put('/users/' + id, payload);
                UI.showToast(i18n.t('user_updated'), 'success');
            } else {
                await API.post('/users', payload);
                UI.showToast(i18n.t('user_created'), 'success');
            }
            closeModal('formModal');
            await loadUsers();
        } catch(e) {
            UI.showToast(e.message || i18n.t('save_failed'), 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = '💾 ' + i18n.t('save');
        }
    };

    window.deleteUser = async function(id, name) {
        if (!confirm(i18n.t('confirm_delete_user') + ': ' + name + '?')) return;
        try {
            await API.del('/users/' + id);
            UI.showToast(i18n.t('user_deleted'), 'success');
            await loadUsers();
        } catch(e) {
            UI.showToast(e.message || i18n.t('delete_failed'), 'error');
        }
    };

    window.closeModal = function(modalId) {
        document.getElementById(modalId).classList.remove('active');
    };

    function resetPageAndRender() { uCurrentPage = 1; renderTable(); }
    document.getElementById('userSearch').addEventListener('input', resetPageAndRender);
    document.getElementById('filterRole').addEventListener('change', resetPageAndRender);
    document.getElementById('filterSector').addEventListener('change', resetPageAndRender);
    document.getElementById('filterDept').addEventListener('change', function(){
        cascadeFilterSection(this.value);
        resetPageAndRender();
    });
    document.getElementById('filterSection').addEventListener('change', function(){
        cascadeFilterDivision(this.value);
        resetPageAndRender();
    });
    document.getElementById('filterDivision').addEventListener('change', resetPageAndRender);
    document.getElementById('filterActive').addEventListener('change', resetPageAndRender);
    document.getElementById('filterGender').addEventListener('change', resetPageAndRender);

    document.querySelectorAll('.modal-overlay').forEach(m => {
        m.addEventListener('click', e => { if (e.target === m) m.classList.remove('active'); });
    });

    function translateStatic() {
        var map = {
            'pageTitle': 'user_management',
            'lblTotalUsers': 'total_users',
            'lblActiveUsers': 'active',
            'lblInactiveUsers': 'inactive',
            'optAllRoles': 'all_roles',
            'optAllSectors': 'all_sectors',
            'optAllDepts': 'all_departments',
            'optAllSections': 'all_sections',
            'optAllDivisions': 'all_divisions',
            'optAllStatus': 'all',
            'optActiveStatus': 'active',
            'optInactiveStatus': 'inactive',
            'optAllGenders': 'all_genders',
            'optMaleGender': 'male',
            'optFemaleGender': 'female',
            'thEmpId': 'employee_id',
            'thUsername': 'username',
            'thEmail': 'email',
            'thPhone': 'phone',
            'thRole': 'role',
            'thSector': 'sector',
            'thDept': 'department',
            'thSection': 'section',
            'thDivision': 'division',
            'thStatus': 'status',
            'thGender': 'gender',
            'thCreatedAt': 'created_at',
            'thActions': 'actions',
            'viewModalTitle': 'user_details',
            'btnCloseView': 'close',
            'formTitle': 'add_user',
            'lblEmpId': 'emp_id',
            'lblEmail': 'email',
            'lblPhone': 'phone',
            'lblRole': 'role',
            'lblFormSector': 'sector',
            'lblFormDept': 'department',
            'lblFormSection': 'section',
            'lblFormDivision': 'division',
            'lblFormGender': 'gender',
            'fOptUnspecified': 'unspecified',
            'fOptMale': 'male',
            'fOptFemale': 'female',
            'lblLang': 'preferred_language',
            'fOptArabic': 'arabic',
            'fOptEnglish': 'english',
            'lblFormStatus': 'status',
            'fOptActive': 'active',
            'fOptInactive': 'inactive',
            'btnCancel': 'cancel'
        };
        for (var id in map) {
            var el = document.getElementById(id);
            if (el) el.textContent = i18n.t(map[id]);
        }
        // Elements with special formatting
        document.getElementById('viewModalTitle').textContent = '👤 ' + i18n.t('user_details');
        document.getElementById('lblUsername').textContent = i18n.t('username') + ' *';
        document.getElementById('fPasswordLabel').textContent = i18n.t('password') + ' *';
        document.getElementById('btnAddUser').textContent = '➕ ' + i18n.t('add_user');
        document.getElementById('saveBtn').textContent = '💾 ' + i18n.t('save');
        document.getElementById('fOptUnspecified').textContent = '-- ' + i18n.t('unspecified') + ' --';
        // Placeholder
        document.getElementById('userSearch').placeholder = i18n.t('search_user');
    }

    translateStatic();
    (function initUserPerms(){
        var user=Auth.getUser();
        if(!user){setTimeout(initUserPerms,100);return;}
        var perms=(user.permissions)||[];
        uCanCreate=perms.includes('manage_users')||perms.includes('*');
        uCanEdit=perms.includes('manage_users')||perms.includes('*');
        uCanDelete=perms.includes('manage_users')||perms.includes('*');
        if(!uCanCreate){var ab=document.getElementById('btnAddUser');if(ab)ab.style.display='none';}
        loadRoles().then(() => loadUserRefs()).then(() => loadUsers());
    })();
})();
</script>
<?php $pageScripts = ob_get_clean(); ?>
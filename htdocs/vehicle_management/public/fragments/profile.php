<?php
/**
 * Profile Fragment — User Profile & Movement History
 * Loaded inside dashboard.php shell.
 * Shows personal data (editable except role), password change, and vehicle movement history.
 */
?>
<style>
html[dir="ltr"] body{direction:ltr;font-family:var(--font-en)}
html[dir="ltr"] .app-sidebar{right:auto;left:0}
html[dir="ltr"] .app-main{margin-right:0;margin-left:var(--sidebar-width)}
html[dir="ltr"] .app-footer{margin-right:0;margin-left:var(--sidebar-width)}
html[dir="ltr"] .app-sidebar.collapsed~.app-main{margin-right:0;margin-left:var(--sidebar-collapsed-width)}
.pf-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:28px}
.pf-card{background:var(--bg-card,#fff);border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.06);border:1px solid var(--border-default,#eee);padding:24px}
.pf-card-title{font-size:1.1rem;font-weight:700;color:var(--text-primary);margin-bottom:20px;display:flex;align-items:center;gap:8px}
.pf-form .fg{margin-bottom:16px}
.pf-form label{display:block;font-weight:600;font-size:.85rem;margin-bottom:6px;color:var(--text-secondary,#555)}
.pf-form input,.pf-form select{width:100%;padding:10px 14px;border:1px solid var(--border-default,#ddd);border-radius:8px;font-size:.95rem;background:var(--bg-main,#fff);color:var(--text-primary)}
.pf-form input:disabled,.pf-form select:disabled{background:var(--bg-main,#f5f5f5);opacity:.7;cursor:not-allowed}
.pf-form input:focus,.pf-form select:focus{outline:none;border-color:var(--primary-main,#1a5276);box-shadow:0 0 0 3px rgba(26,82,118,.1)}
.pf-badge{display:inline-block;padding:3px 12px;border-radius:20px;font-size:.8rem;font-weight:600}
.pf-badge.role{background:rgba(26,82,118,.1);color:var(--primary-main,#1a5276)}
.pf-btn{padding:10px 24px;border:none;border-radius:8px;cursor:pointer;font-weight:600;font-size:.9rem;display:inline-flex;align-items:center;gap:6px;transition:background .2s}
.pf-btn-primary{background:var(--primary-main,#1a5276);color:#fff}
.pf-btn-primary:hover{opacity:.9}
.pf-btn-primary:disabled{opacity:.5;cursor:not-allowed}
.pf-btn-warning{background:var(--status-warning,#f0ad4e);color:#1a1a2e}
.pf-btn-warning:hover{opacity:.9}
.pf-success{color:#28a745;font-size:.85rem;margin-top:8px;display:none}
.pf-error{color:#dc3545;font-size:.85rem;margin-top:8px;display:none}
.pf-mv-table{width:100%;border-collapse:separate;border-spacing:0;background:var(--bg-card,#fff);border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.pf-mv-table th{background:var(--primary-dark,#1a5276);color:#fff;padding:12px 14px;font-size:.85rem;white-space:nowrap}
.pf-mv-table td{padding:10px 14px;border-bottom:1px solid var(--border-default,#eee);font-size:.9rem}
.pf-mv-table tr:hover td{background:rgba(26,82,118,.04)}
.pf-mv-badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.8rem;font-weight:600}
.pf-mv-badge.pickup{background:#d4edda;color:#155724}
.pf-mv-badge.return{background:#cce5ff;color:#004085}
.pf-mv-badge.unpaid{background:#f8d7da;color:#721c24}
.pf-mv-badge.paid{background:#d4edda;color:#155724}
.pf-empty{text-align:center;padding:40px 20px;color:var(--text-secondary,#999)}
.pf-empty .ico{font-size:2.5rem;margin-bottom:10px;opacity:.5}
.pf-section{margin-bottom:28px}
@media(max-width:768px){.pf-grid{grid-template-columns:1fr}}
</style>

<div class="page-header">
    <h2 id="pfPageTitle" data-label-en="My Profile" data-label-ar="ملفي الشخصي">My Profile</h2>
</div>

<!-- PROFILE INFO & EDIT SECTION -->
<div class="pf-grid">
    <!-- Profile Info Card -->
    <div class="pf-card">
        <div class="pf-card-title">
            <span>👤</span>
            <span id="pfInfoTitle" data-label-en="Personal Information" data-label-ar="المعلومات الشخصية">Personal Information</span>
        </div>
        <form id="pfEditForm" class="pf-form" onsubmit="return false;">
            <div class="fg">
                <label id="pfLblEmpId" data-label-en="Employee ID" data-label-ar="الرقم الوظيفي">Employee ID</label>
                <input type="text" id="pfEmpId" disabled>
            </div>
            <div class="fg">
                <label id="pfLblUsername" data-label-en="Username" data-label-ar="اسم المستخدم">Username</label>
                <input type="text" id="pfUsername" disabled>
            </div>
            <div class="fg">
                <label id="pfLblEmail" data-label-en="Email" data-label-ar="البريد الإلكتروني">Email</label>
                <input type="email" id="pfEmail">
            </div>
            <div class="fg">
                <label id="pfLblPhone" data-label-en="Phone" data-label-ar="الهاتف">Phone</label>
                <input type="text" id="pfPhone">
            </div>
            <div class="fg">
                <label id="pfLblGender" data-label-en="Gender" data-label-ar="الجنس">Gender</label>
                <select id="pfGender">
                    <option value="">--</option>
                    <option value="male" data-label-en="Male" data-label-ar="ذكر">Male</option>
                    <option value="female" data-label-en="Female" data-label-ar="أنثى">Female</option>
                </select>
            </div>
            <div class="fg">
                <label id="pfLblRole" data-label-en="Role" data-label-ar="الدور">Role</label>
                <input type="text" id="pfRole" disabled>
            </div>
            <div class="fg">
                <label id="pfLblSector" data-label-en="Sector" data-label-ar="القطاع">Sector</label>
                <select id="pfSector"><option value="">--</option></select>
            </div>
            <div class="fg">
                <label id="pfLblDept" data-label-en="Department" data-label-ar="الإدارة">Department</label>
                <select id="pfDept"><option value="">--</option></select>
            </div>
            <div class="fg">
                <label id="pfLblSection" data-label-en="Section" data-label-ar="القسم">Section</label>
                <select id="pfSection"><option value="">--</option></select>
            </div>
            <div class="fg">
                <label id="pfLblDivision" data-label-en="Division" data-label-ar="الشعبة">Division</label>
                <select id="pfDivision"><option value="">--</option></select>
            </div>
            <div class="fg">
                <label id="pfLblLang" data-label-en="Preferred Language" data-label-ar="اللغة المفضلة">Preferred Language</label>
                <select id="pfLang">
                    <option value="ar" data-label-ar="العربية" data-label-en="Arabic">Arabic</option>
                    <option value="en">English</option>
                </select>
            </div>
            <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
                <button type="button" class="pf-btn pf-btn-primary" id="pfSaveBtn" onclick="ProfilePage.saveProfile()">
                    <span>💾</span> <span id="pfSaveBtnLabel" data-label-en="Save Changes" data-label-ar="حفظ التغييرات">Save Changes</span>
                </button>
                <span class="pf-success" id="pfSaveSuccess" data-label-en="Saved successfully" data-label-ar="تم الحفظ بنجاح">Saved successfully</span>
                <span class="pf-error" id="pfSaveError"></span>
            </div>
        </form>
    </div>

    <!-- Password Change Card -->
    <div class="pf-card">
        <div class="pf-card-title">
            <span>🔑</span>
            <span id="pfPwdTitle" data-label-en="Change Password" data-label-ar="تغيير كلمة المرور">Change Password</span>
        </div>
        <form id="pfPwdForm" class="pf-form" onsubmit="return false;">
            <div class="fg">
                <label id="pfLblCurPwd" data-label-en="Current Password" data-label-ar="كلمة المرور الحالية">Current Password</label>
                <input type="password" id="pfCurPwd" autocomplete="current-password">
            </div>
            <div class="fg">
                <label id="pfLblNewPwd" data-label-en="New Password" data-label-ar="كلمة المرور الجديدة">New Password</label>
                <input type="password" id="pfNewPwd" autocomplete="new-password">
            </div>
            <div class="fg">
                <label id="pfLblConfPwd" data-label-en="Confirm Password" data-label-ar="تأكيد كلمة المرور">Confirm Password</label>
                <input type="password" id="pfConfPwd" autocomplete="new-password">
            </div>
            <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
                <button type="button" class="pf-btn pf-btn-warning" id="pfPwdBtn" onclick="ProfilePage.changePassword()">
                    <span>🔒</span> <span id="pfPwdBtnLabel" data-label-en="Change Password" data-label-ar="تغيير كلمة المرور">Change Password</span>
                </button>
                <span class="pf-success" id="pfPwdSuccess" data-label-en="Password changed successfully" data-label-ar="تم تغيير كلمة المرور بنجاح">Password changed successfully</span>
                <span class="pf-error" id="pfPwdError"></span>
            </div>
        </form>
    </div>
</div>

<!-- MOVEMENT HISTORY SECTION -->
<div class="pf-section">
    <div class="pf-card" style="max-width:100%">
        <div class="pf-card-title">
            <span>🚗</span>
            <span id="pfMvTitle" data-label-en="Vehicle Movement History" data-label-ar="سجل حركات المركبات">Vehicle Movement History</span>
        </div>
        <div style="overflow-x:auto">
            <table class="pf-mv-table" id="pfMvTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th id="pfMvThCode" data-label-en="Vehicle Code" data-label-ar="رقم المركبة">Vehicle Code</th>
                        <th id="pfMvThType" data-label-en="Operation Type" data-label-ar="نوع العملية">Operation</th>
                        <th id="pfMvThDate" data-label-en="Date &amp; Time" data-label-ar="التاريخ والوقت">Date &amp; Time</th>
                        <th id="pfMvThVType" data-label-en="Vehicle Type" data-label-ar="نوع المركبة">Vehicle Type</th>
                        <th id="pfMvThCond" data-label-en="Vehicle Condition" data-label-ar="حالة المركبة">Condition</th>
                        <th id="pfMvThNotes" data-label-en="Notes" data-label-ar="ملاحظات">Notes</th>
                    </tr>
                </thead>
                <tbody id="pfMvBody">
                    <tr><td colspan="7" class="pf-empty"><div class="spinner spinner-sm"></div><span id="lbl_mv_loading" data-label-en="Loading..." data-label-ar="جاري التحميل...">Loading...</span></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- VIOLATIONS SECTION -->
<div class="pf-section">
    <div class="pf-card" style="max-width:100%">
        <div class="pf-card-title">
            <span>⚠️</span>
            <span id="pfVlTitle" data-label-en="My Violations" data-label-ar="مخالفاتي">My Violations</span>
        </div>
        <div style="overflow-x:auto">
            <table class="pf-mv-table" id="pfVlTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th id="pfVlThCode" data-label-en="Vehicle Code" data-label-ar="رقم المركبة">Vehicle Code</th>
                        <th id="pfVlThDate" data-label-en="Violation Date" data-label-ar="تاريخ المخالفة">Violation Date</th>
                        <th id="pfVlThAmount" data-label-en="Amount" data-label-ar="المبلغ">Amount</th>
                        <th id="pfVlThStatus" data-label-en="Status" data-label-ar="الحالة">Status</th>
                        <th id="pfVlThVType" data-label-en="Vehicle Type" data-label-ar="نوع المركبة">Vehicle Type</th>
                        <th id="pfVlThNotes" data-label-en="Notes" data-label-ar="ملاحظات">Notes</th>
                    </tr>
                </thead>
                <tbody id="pfVlBody">
                    <tr><td colspan="7" class="pf-empty"><div class="spinner spinner-sm"></div><span id="lbl_vl_loading" data-label-en="Loading..." data-label-ar="جاري التحميل...">Loading...</span></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
(function () {
    'use strict';

    var currentUser = null;
    var profileData = null;
    var pfRefs = {sectors:[],departments:[],sections:[],divisions:[]};

    function $(id) { return document.getElementById(id); }

    function esc(s){var d=document.createElement('div');d.textContent=s||'';return d.innerHTML;}

    function getLang() { return localStorage.getItem('lang') || 'ar'; }
    function t(key, fallback) { return (typeof i18n !== 'undefined' && i18n.t) ? i18n.t(key) : fallback; }

    /* Apply i18n to static labels */
    function applyLang() {
        // Retry if i18n translations are not loaded yet
        if(!i18n.strings || !Object.keys(i18n.strings).length){
            setTimeout(applyLang,100);
            return;
        }
        var tt=function(k){return i18n.t(k);};
        var setEl=function(id,k){var el=document.getElementById(id);if(el){var val=tt(k);if(val&&val!==k)el.textContent=val;}};
        // Page title
        setEl('pfPageTitle','my_profile');
        // Personal Info
        setEl('pfInfoTitle','personal_information');
        setEl('pfLblEmpId','employee_id');
        setEl('pfLblUsername','username');
        setEl('pfLblEmail','email');
        setEl('pfLblPhone','phone');
        setEl('pfLblGender','gender');
        setEl('pfLblRole','role');
        setEl('pfLblSector','sector');
        setEl('pfLblDept','department');
        setEl('pfLblSection','section');
        setEl('pfLblDivision','division');
        setEl('pfLblLang','preferred_language');
        setEl('pfSaveBtnLabel','save_changes');
        setEl('pfSaveSuccess','saved_successfully');
        // Password
        setEl('pfPwdTitle','change_password');
        setEl('pfLblCurPwd','current_password');
        setEl('pfLblNewPwd','new_password');
        setEl('pfLblConfPwd','confirm_password');
        setEl('pfPwdBtnLabel','change_password');
        setEl('pfPwdSuccess','password_changed');
        // Movement History
        setEl('pfMvTitle','movement_history');
        setEl('pfMvThCode','vehicle_code');
        setEl('pfMvThType','operation_type');
        setEl('pfMvThDate','date_time');
        setEl('pfMvThVType','vehicle_type');
        setEl('pfMvThCond','vehicle_condition');
        setEl('pfMvThNotes','notes');
        // Violations
        setEl('pfVlTitle','my_violations');
        setEl('pfVlThCode','vehicle_code');
        setEl('pfVlThDate','violation_date');
        setEl('pfVlThAmount','amount');
        setEl('pfVlThStatus','status');
        setEl('pfVlThVType','vehicle_type');
        setEl('pfVlThNotes','notes');
        // Loading labels
        setEl('lbl_mv_loading','loading');
        setEl('lbl_vl_loading','loading');
    }

    /* Load reference data for dropdowns */
    async function loadRefs() {
        try {
            var res = await API.get('/references');
            pfRefs = (res && res.data) || res || {sectors:[],departments:[],sections:[],divisions:[]};
        } catch(e) { pfRefs = {sectors:[],departments:[],sections:[],divisions:[]}; }

        var isEn = getLang() === 'en';

        // Populate sector dropdown
        var sd = $('pfSector');
        sd.innerHTML = '<option value="">--</option>';
        (pfRefs.sectors||[]).forEach(function(s){
            sd.innerHTML += '<option value="'+s.id+'">'+ esc(isEn ? (s.name_en||s.name) : (s.name||s.name_en)) +'</option>';
        });

        // Populate department dropdown
        var dd = $('pfDept');
        dd.innerHTML = '<option value="">--</option>';
        (pfRefs.departments||[]).forEach(function(d){
            dd.innerHTML += '<option value="'+d.department_id+'">'+ esc(isEn ? (d.name_en||d.name_ar) : (d.name_ar||d.name_en)) +'</option>';
        });

        // Wire cascading
        dd.addEventListener('change', function(){ pfCascadeSection(this.value); });
        $('pfSection').addEventListener('change', function(){ pfCascadeDivision(this.value); });

        // Initial cascade
        pfCascadeSection('');
    }

    function pfCascadeSection(did) {
        var isEn = getLang() === 'en';
        var s = $('pfSection');
        s.innerHTML = '<option value="">--</option>';
        (pfRefs.sections||[]).filter(function(sc){ return !did || sc.department_id == did; }).forEach(function(sc){
            s.innerHTML += '<option value="'+sc.section_id+'">'+ esc(isEn ? (sc.name_en||sc.name_ar) : (sc.name_ar||sc.name_en)) +'</option>';
        });
        pfCascadeDivision('');
    }

    function pfCascadeDivision(sid) {
        var isEn = getLang() === 'en';
        var d = $('pfDivision');
        d.innerHTML = '<option value="">--</option>';
        (pfRefs.divisions||[]).filter(function(dv){ return !sid || dv.section_id == sid; }).forEach(function(dv){
            d.innerHTML += '<option value="'+dv.division_id+'">'+ esc(isEn ? (dv.name_en||dv.name_ar) : (dv.name_ar||dv.name_en)) +'</option>';
        });
    }

    /* Load profile data */
    async function loadProfile() {
        try {
            var res = await API.get('/profile');
            var data = (res && res.data) || res || {};
            profileData = data;
            populateForm(data);
        } catch (e) {
            console.error('Failed to load profile:', e);
        }
    }

    function populateForm(data) {
        $('pfEmpId').value = data.emp_id || '';
        $('pfUsername').value = data.username || '';
        $('pfEmail').value = data.email || '';
        $('pfPhone').value = data.phone || '';
        $('pfRole').value = data.role_name || (t('role', 'Role') + ' #' + (data.role_id || ''));

        // Gender select
        var genderSel = $('pfGender');
        if (genderSel) genderSel.value = data.gender || '';

        // Sector select
        var sectorSel = $('pfSector');
        if (sectorSel) sectorSel.value = data.sector_id || '';

        // Department select
        var deptSel = $('pfDept');
        if (deptSel) deptSel.value = data.department_id || '';

        // Cascade section based on department, then set value
        pfCascadeSection(data.department_id || '');
        var sectionSel = $('pfSection');
        if (sectionSel) sectionSel.value = data.section_id || '';

        // Cascade division based on section, then set value
        pfCascadeDivision(data.section_id || '');
        var divisionSel = $('pfDivision');
        if (divisionSel) divisionSel.value = data.division_id || '';

        var langSel = $('pfLang');
        if (langSel) langSel.value = data.preferred_language || 'ar';
    }

    async function saveProfile() {
        var btn = $('pfSaveBtn');
        var successEl = $('pfSaveSuccess');
        var errorEl = $('pfSaveError');
        successEl.style.display = 'none';
        errorEl.style.display = 'none';
        btn.disabled = true;

        var payload = {
            email: $('pfEmail').value.trim(),
            phone: $('pfPhone').value.trim(),
            preferred_language: $('pfLang').value,
            gender: $('pfGender').value,
            sector_id: $('pfSector').value || null,
            department_id: $('pfDept').value || null,
            section_id: $('pfSection').value || null,
            division_id: $('pfDivision').value || null
        };

        try {
            await API.put('/profile', payload);
            successEl.style.display = 'inline';
            setTimeout(function() { successEl.style.display = 'none'; }, 3000);
        } catch (e) {
            errorEl.textContent = (e && e.message) || t('error_occurred', 'An error occurred');
            errorEl.style.display = 'inline';
        } finally {
            btn.disabled = false;
        }
    }

    async function changePassword() {
        var btn = $('pfPwdBtn');
        var successEl = $('pfPwdSuccess');
        var errorEl = $('pfPwdError');
        successEl.style.display = 'none';
        errorEl.style.display = 'none';

        var curPwd = $('pfCurPwd').value;
        var newPwd = $('pfNewPwd').value;
        var confPwd = $('pfConfPwd').value;

        if (!curPwd || !newPwd) {
            errorEl.textContent = t('fill_all_fields', 'Please fill all fields');
            errorEl.style.display = 'inline';
            return;
        }
        if (newPwd.length < 4) {
            errorEl.textContent = t('password_min_length', 'New password must be at least 4 characters');
            errorEl.style.display = 'inline';
            return;
        }
        if (newPwd !== confPwd) {
            errorEl.textContent = t('password_mismatch', 'Passwords do not match');
            errorEl.style.display = 'inline';
            return;
        }

        btn.disabled = true;
        try {
            await API.put('/profile/password', {
                current_password: curPwd,
                new_password: newPwd
            });
            successEl.style.display = 'inline';
            $('pfCurPwd').value = '';
            $('pfNewPwd').value = '';
            $('pfConfPwd').value = '';
            setTimeout(function() { successEl.style.display = 'none'; }, 3000);
        } catch (e) {
            errorEl.textContent = (e && e.message) || t('error_occurred', 'An error occurred');
            errorEl.style.display = 'inline';
        } finally {
            btn.disabled = false;
        }
    }

    async function loadMovements() {
        var tbody = $('pfMvBody');
        if (!tbody) return;

        try {
            var res = await API.get('/profile/movements');
            var rows = (res && res.data) || res || [];
            if (!Array.isArray(rows)) rows = [];
            renderMovements(rows);
        } catch (e) {
            console.error('Failed to load movements:', e);
            tbody.innerHTML = '<tr><td colspan="7" class="pf-empty">' +
                '<div class="ico">⚠️</div><p>' + t('load_failed', 'Failed to load history') + '</p></td></tr>';
        }
    }

    function renderMovements(rows) {
        var tbody = $('pfMvBody');
        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="pf-empty">' +
                '<div class="ico">🚗</div><p>' + t('no_movements', 'No movements recorded') + '</p></td></tr>';
            return;
        }

        var html = '';
        rows.forEach(function(mv, i) {
            var isPickup = (mv.operation_type === 'pickup');
            var opLabel = isPickup ? t('pickup', 'Pickup') : t('return', 'Return');
            var opClass = isPickup ? 'pickup' : 'return';
            var dateStr = mv.movement_datetime || mv.created_at || '';
            if (dateStr) {
                try {
                    var d = new Date(dateStr);
                    dateStr = d.toLocaleDateString(getLang() === 'en' ? 'en-US' : 'ar-SA', {
                        year: 'numeric', month: 'short', day: 'numeric',
                        hour: '2-digit', minute: '2-digit'
                    });
                } catch(e2) {}
            }

            html += '<tr>';
            html += '<td>' + (i + 1) + '</td>';
            html += '<td><strong>' + esc(mv.vehicle_code) + '</strong></td>';
            html += '<td><span class="pf-mv-badge ' + opClass + '">' + esc(opLabel) + '</span></td>';
            html += '<td>' + esc(dateStr) + '</td>';
            html += '<td>' + esc(mv.vehicle_type || '—') + '</td>';
            html += '<td>' + esc(mv.vehicle_condition || '—') + '</td>';
            html += '<td>' + esc(mv.notes || '—') + '</td>';
            html += '</tr>';
        });

        tbody.innerHTML = html;
    }

    window.ProfilePage = {
        saveProfile: saveProfile,
        changePassword: changePassword
    };

    async function loadViolations() {
        var tbody = $('pfVlBody');
        if (!tbody) return;

        try {
            var res = await API.get('/profile/violations');
            var rows = (res && res.data) || res || [];
            if (!Array.isArray(rows)) rows = [];
            renderViolations(rows);
        } catch (e) {
            console.error('Failed to load violations:', e);
            tbody.innerHTML = '<tr><td colspan="7" class="pf-empty">' +
                '<div class="ico">⚠️</div><p>' + t('load_failed', 'Failed to load violations') + '</p></td></tr>';
        }
    }

    function renderViolations(rows) {
        var tbody = $('pfVlBody');
        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="pf-empty">' +
                '<div class="ico">✅</div><p>' + t('no_violations_recorded', 'No violations recorded') + '</p></td></tr>';
            return;
        }

        var html = '';
        rows.forEach(function(vl, i) {
            var isPaid = (vl.violation_status === 'paid');
            var statusLabel = isPaid ? t('paid', 'Paid') : t('unpaid', 'Unpaid');
            var statusClass = isPaid ? 'paid' : 'unpaid';
            var dateStr = vl.violation_datetime || vl.created_at || '';
            if (dateStr) {
                try {
                    var d = new Date(dateStr);
                    dateStr = d.toLocaleDateString(getLang() === 'en' ? 'en-US' : 'ar-SA', {
                        year: 'numeric', month: 'short', day: 'numeric',
                        hour: '2-digit', minute: '2-digit'
                    });
                } catch(e2) {}
            }

            html += '<tr>';
            html += '<td>' + (i + 1) + '</td>';
            html += '<td><strong>' + esc(vl.vehicle_code) + '</strong></td>';
            html += '<td>' + esc(dateStr) + '</td>';
            html += '<td>' + esc(vl.violation_amount || '0') + '</td>';
            html += '<td><span class="pf-mv-badge ' + statusClass + '">' + esc(statusLabel) + '</span></td>';
            html += '<td>' + esc(vl.vehicle_type || '—') + '</td>';
            html += '<td>' + esc(vl.notes || '—') + '</td>';
            html += '</tr>';
        });

        tbody.innerHTML = html;
    }

    (async function init() {
        if (window.__pageDenied) return;
        var user = Auth.getUser();
        if (!user) { setTimeout(init, 100); return; }
        currentUser = user;
        applyLang();
        await loadRefs();
        loadProfile();
        loadMovements();
        loadViolations();
    })();
})();
</script>
<?php $pageScripts = ob_get_clean(); ?>
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
    <h2 id="pfPageTitle">My Profile</h2>
</div>

<!-- PROFILE INFO & EDIT SECTION -->
<div class="pf-grid">
    <!-- Profile Info Card -->
    <div class="pf-card">
        <div class="pf-card-title">
            <span>👤</span>
            <span id="pfInfoTitle">Personal Information</span>
        </div>
        <form id="pfEditForm" class="pf-form" onsubmit="return false;">
            <div class="fg">
                <label id="pfLblEmpId">Employee ID</label>
                <input type="text" id="pfEmpId" disabled>
            </div>
            <div class="fg">
                <label id="pfLblUsername">Username</label>
                <input type="text" id="pfUsername" disabled>
            </div>
            <div class="fg">
                <label id="pfLblEmail">Email</label>
                <input type="email" id="pfEmail">
            </div>
            <div class="fg">
                <label id="pfLblPhone">Phone</label>
                <input type="text" id="pfPhone">
            </div>
            <div class="fg">
                <label id="pfLblGender">Gender</label>
                <input type="text" id="pfGender" disabled>
            </div>
            <div class="fg">
                <label id="pfLblRole">Role</label>
                <input type="text" id="pfRole" disabled>
            </div>
            <div class="fg">
                <label id="pfLblSector">Sector</label>
                <input type="text" id="pfSector" disabled>
            </div>
            <div class="fg">
                <label id="pfLblDept">Department</label>
                <input type="text" id="pfDept" disabled>
            </div>
            <div class="fg">
                <label id="pfLblSection">Section</label>
                <input type="text" id="pfSection" disabled>
            </div>
            <div class="fg">
                <label id="pfLblDivision">Division</label>
                <input type="text" id="pfDivision" disabled>
            </div>
            <div class="fg">
                <label id="pfLblLang">Preferred Language</label>
                <select id="pfLang">
                    <option value="ar" data-label-ar="العربية" data-label-en="Arabic">Arabic</option>
                    <option value="en">English</option>
                </select>
            </div>
            <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
                <button type="button" class="pf-btn pf-btn-primary" id="pfSaveBtn" onclick="ProfilePage.saveProfile()">
                    <span>💾</span> <span id="pfSaveBtnLabel">Save Changes</span>
                </button>
                <span class="pf-success" id="pfSaveSuccess">Saved successfully</span>
                <span class="pf-error" id="pfSaveError"></span>
            </div>
        </form>
    </div>

    <!-- Password Change Card -->
    <div class="pf-card">
        <div class="pf-card-title">
            <span>🔑</span>
            <span id="pfPwdTitle">Change Password</span>
        </div>
        <form id="pfPwdForm" class="pf-form" onsubmit="return false;">
            <div class="fg">
                <label id="pfLblCurPwd">Current Password</label>
                <input type="password" id="pfCurPwd" autocomplete="current-password">
            </div>
            <div class="fg">
                <label id="pfLblNewPwd">New Password</label>
                <input type="password" id="pfNewPwd" autocomplete="new-password">
            </div>
            <div class="fg">
                <label id="pfLblConfPwd">Confirm Password</label>
                <input type="password" id="pfConfPwd" autocomplete="new-password">
            </div>
            <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
                <button type="button" class="pf-btn pf-btn-warning" id="pfPwdBtn" onclick="ProfilePage.changePassword()">
                    <span>🔒</span> <span id="pfPwdBtnLabel">Change Password</span>
                </button>
                <span class="pf-success" id="pfPwdSuccess">Password changed successfully</span>
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
            <span id="pfMvTitle">Vehicle Movement History</span>
        </div>
        <div style="overflow-x:auto">
            <table class="pf-mv-table" id="pfMvTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th id="pfMvThCode">Vehicle Code</th>
                        <th id="pfMvThType">Operation</th>
                        <th id="pfMvThDate">Date &amp; Time</th>
                        <th id="pfMvThVType">Vehicle Type</th>
                        <th id="pfMvThCond">Condition</th>
                        <th id="pfMvThNotes">Notes</th>
                    </tr>
                </thead>
                <tbody id="pfMvBody">
                    <tr><td colspan="7" class="pf-empty"><div class="spinner spinner-sm"></div><span id="lbl_mv_loading">Loading...</span></td></tr>
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
            <span id="pfVlTitle">My Violations</span>
        </div>
        <div style="overflow-x:auto">
            <table class="pf-mv-table" id="pfVlTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th id="pfVlThCode">Vehicle Code</th>
                        <th id="pfVlThDate">Violation Date</th>
                        <th id="pfVlThAmount">Amount</th>
                        <th id="pfVlThStatus">Status</th>
                        <th id="pfVlThVType">Vehicle Type</th>
                        <th id="pfVlThNotes">Notes</th>
                    </tr>
                </thead>
                <tbody id="pfVlBody">
                    <tr><td colspan="7" class="pf-empty"><div class="spinner spinner-sm"></div><span id="lbl_vl_loading">Loading...</span></td></tr>
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
        var map = {
            'pfPageTitle':    'my_profile',
            'pfInfoTitle':    'personal_information',
            'pfLblEmpId':     'employee_id',
            'pfLblUsername':  'username',
            'pfLblEmail':     'email',
            'pfLblPhone':     'phone',
            'pfLblGender':    'gender',
            'pfLblRole':      'role',
            'pfLblSector':    'sector',
            'pfLblDept':      'department',
            'pfLblSection':   'section',
            'pfLblDivision':  'division',
            'pfLblLang':      'preferred_language',
            'pfSaveBtnLabel': 'save_changes',
            'pfSaveSuccess':  'saved_successfully',
            'pfPwdTitle':     'change_password',
            'pfLblCurPwd':    'current_password',
            'pfLblNewPwd':    'new_password',
            'pfLblConfPwd':   'confirm_password',
            'pfPwdBtnLabel':  'change_password',
            'pfPwdSuccess':   'password_changed',
            'pfMvTitle':      'movement_history',
            'pfMvThCode':     'vehicle_code',
            'pfMvThType':     'operation_type',
            'pfMvThDate':     'date_time',
            'pfMvThVType':    'vehicle_type',
            'pfMvThCond':     'vehicle_condition',
            'pfMvThNotes':    'notes',
            'pfVlTitle':      'my_violations',
            'pfVlThCode':     'vehicle_code',
            'pfVlThDate':     'violation_date',
            'pfVlThAmount':   'amount',
            'pfVlThStatus':   'status',
            'pfVlThVType':    'vehicle_type',
            'pfVlThNotes':    'notes'
        };
        if (typeof i18n === 'undefined') return;
        Object.keys(map).forEach(function(id) {
            var el = document.getElementById(id);
            if (!el) return;
            var val = i18n.t(map[id]);
            if (val && val !== map[id]) el.textContent = val;
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

        var genderMap = {
            male:   t('male', 'Male'),
            female: t('female', 'Female')
        };
        $('pfGender').value = genderMap[data.gender] || data.gender || '';

        $('pfSector').value = data.sector_name || data.sector_name_en || t('not_set', 'Not set');
        $('pfDept').value = data.department_name_ar || t('not_set', 'Not set');
        $('pfSection').value = data.section_name_ar || t('not_set', 'Not set');
        $('pfDivision').value = data.division_name_ar || t('not_set', 'Not set');

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
            preferred_language: $('pfLang').value
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

    (function init() {
        if (window.__pageDenied) return;
        var user = Auth.getUser();
        if (!user) { setTimeout(init, 100); return; }
        currentUser = user;
        applyLang();
        loadProfile();
        loadMovements();
        loadViolations();
    })();
})();
</script>
<?php $pageScripts = ob_get_clean(); ?>
<?php
/**
 * Profile Fragment — User Profile & Movement History
 * Loaded inside dashboard.php shell.
 * Shows personal data (editable except role), password change, and vehicle movement history.
 */
?>
<style>
/* === Profile Fragment Styles === */
.pf-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:28px}
.pf-card{background:var(--bg-card,#fff);border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.06);border:1px solid var(--border-default,#eee);padding:24px}
.pf-card-title{font-size:1.1rem;font-weight:700;color:var(--text-primary);margin-bottom:20px;display:flex;align-items:center;gap:8px}
.pf-form .fg{margin-bottom:16px}
.pf-form label{display:block;font-weight:600;font-size:.85rem;margin-bottom:6px;color:var(--text-secondary,#555)}
.pf-form input,.pf-form select{width:100%;padding:10px 14px;border:1px solid var(--border-default,#ddd);border-radius:8px;font-size:.95rem;background:var(--bg-main,#fff);color:var(--text-primary)}
.pf-form input:disabled,.pf-form select:disabled{background:var(--bg-main,#f5f5f5);opacity:.7;cursor:not-allowed}
.pf-form input:focus,.pf-form select:focus{outline:none;border-color:var(--primary-main,#1a5276);box-shadow:0 0 0 3px rgba(26,82,118,.1)}
.pf-info-row{display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--border-default,#eee)}
.pf-info-row:last-child{border-bottom:none}
.pf-info-label{font-size:.85rem;color:var(--text-secondary,#666);font-weight:600}
.pf-info-value{font-size:.95rem;color:var(--text-primary)}
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
/* Movement history table */
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
@media(max-width:768px){
    .pf-grid{grid-template-columns:1fr}
}
</style>

<div class="page-header">
    <h2 id="pfPageTitle" data-label-ar="الملف الشخصي" data-label-en="My Profile">الملف الشخصي</h2>
</div>

<!-- ===== PROFILE INFO & EDIT SECTION ===== -->
<div class="pf-grid">
    <!-- Profile Info Card -->
    <div class="pf-card">
        <div class="pf-card-title">
            <span>👤</span>
            <span id="pfInfoTitle" data-label-ar="البيانات الشخصية" data-label-en="Personal Information">البيانات الشخصية</span>
        </div>
        <form id="pfEditForm" class="pf-form" onsubmit="return false;">
            <div class="fg">
                <label id="pfLblEmpId" data-label-ar="رقم الموظف" data-label-en="Employee ID">رقم الموظف</label>
                <input type="text" id="pfEmpId" disabled>
            </div>
            <div class="fg">
                <label id="pfLblUsername" data-label-ar="اسم المستخدم" data-label-en="Username">اسم المستخدم</label>
                <input type="text" id="pfUsername" disabled>
            </div>
            <div class="fg">
                <label id="pfLblEmail" data-label-ar="البريد الإلكتروني" data-label-en="Email">البريد الإلكتروني</label>
                <input type="email" id="pfEmail">
            </div>
            <div class="fg">
                <label id="pfLblPhone" data-label-ar="الهاتف" data-label-en="Phone">الهاتف</label>
                <input type="text" id="pfPhone">
            </div>
            <div class="fg">
                <label id="pfLblGender" data-label-ar="الجنس" data-label-en="Gender">الجنس</label>
                <input type="text" id="pfGender" disabled>
            </div>
            <div class="fg">
                <label id="pfLblRole" data-label-ar="الدور" data-label-en="Role">الدور</label>
                <input type="text" id="pfRole" disabled>
            </div>
            <div class="fg">
                <label id="pfLblDept" data-label-ar="الإدارة" data-label-en="Department">الإدارة</label>
                <input type="text" id="pfDept" disabled>
            </div>
            <div class="fg">
                <label id="pfLblSection" data-label-ar="القسم" data-label-en="Section">القسم</label>
                <input type="text" id="pfSection" disabled>
            </div>
            <div class="fg">
                <label id="pfLblDivision" data-label-ar="الشعبة" data-label-en="Division">الشعبة</label>
                <input type="text" id="pfDivision" disabled>
            </div>
            <div class="fg">
                <label id="pfLblLang" data-label-ar="اللغة المفضلة" data-label-en="Preferred Language">اللغة المفضلة</label>
                <select id="pfLang">
                    <option value="ar">العربية</option>
                    <option value="en">English</option>
                </select>
            </div>
            <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
                <button type="button" class="pf-btn pf-btn-primary" id="pfSaveBtn" onclick="ProfilePage.saveProfile()">
                    <span>💾</span> <span data-label-ar="حفظ التعديلات" data-label-en="Save Changes">حفظ التعديلات</span>
                </button>
                <span class="pf-success" id="pfSaveSuccess" data-label-ar="تم الحفظ بنجاح" data-label-en="Saved successfully">تم الحفظ بنجاح</span>
                <span class="pf-error" id="pfSaveError"></span>
            </div>
        </form>
    </div>

    <!-- Password Change Card -->
    <div class="pf-card">
        <div class="pf-card-title">
            <span>🔑</span>
            <span id="pfPwdTitle" data-label-ar="تغيير كلمة المرور" data-label-en="Change Password">تغيير كلمة المرور</span>
        </div>
        <form id="pfPwdForm" class="pf-form" onsubmit="return false;">
            <div class="fg">
                <label id="pfLblCurPwd" data-label-ar="كلمة المرور الحالية" data-label-en="Current Password">كلمة المرور الحالية</label>
                <input type="password" id="pfCurPwd" autocomplete="current-password">
            </div>
            <div class="fg">
                <label id="pfLblNewPwd" data-label-ar="كلمة المرور الجديدة" data-label-en="New Password">كلمة المرور الجديدة</label>
                <input type="password" id="pfNewPwd" autocomplete="new-password">
            </div>
            <div class="fg">
                <label id="pfLblConfPwd" data-label-ar="تأكيد كلمة المرور" data-label-en="Confirm Password">تأكيد كلمة المرور</label>
                <input type="password" id="pfConfPwd" autocomplete="new-password">
            </div>
            <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
                <button type="button" class="pf-btn pf-btn-warning" id="pfPwdBtn" onclick="ProfilePage.changePassword()">
                    <span>🔒</span> <span data-label-ar="تغيير كلمة المرور" data-label-en="Change Password">تغيير كلمة المرور</span>
                </button>
                <span class="pf-success" id="pfPwdSuccess" data-label-ar="تم تغيير كلمة المرور بنجاح" data-label-en="Password changed successfully">تم تغيير كلمة المرور بنجاح</span>
                <span class="pf-error" id="pfPwdError"></span>
            </div>
        </form>
    </div>
</div>

<!-- ===== MOVEMENT HISTORY SECTION ===== -->
<div class="pf-section">
    <div class="pf-card" style="max-width:100%">
        <div class="pf-card-title">
            <span>🚗</span>
            <span id="pfMvTitle" data-label-ar="سجل حركات المركبات" data-label-en="Vehicle Movement History">سجل حركات المركبات</span>
        </div>
        <div style="overflow-x:auto">
            <table class="pf-mv-table" id="pfMvTable">
                <thead>
                    <tr>
                        <th data-label-ar="#" data-label-en="#">#</th>
                        <th id="pfMvThCode" data-label-ar="رمز المركبة" data-label-en="Vehicle Code">رمز المركبة</th>
                        <th id="pfMvThType" data-label-ar="نوع العملية" data-label-en="Operation">نوع العملية</th>
                        <th id="pfMvThDate" data-label-ar="التاريخ والوقت" data-label-en="Date & Time">التاريخ والوقت</th>
                        <th id="pfMvThVType" data-label-ar="نوع المركبة" data-label-en="Vehicle Type">نوع المركبة</th>
                        <th id="pfMvThCond" data-label-ar="حالة المركبة" data-label-en="Condition">حالة المركبة</th>
                        <th id="pfMvThNotes" data-label-ar="ملاحظات" data-label-en="Notes">ملاحظات</th>
                    </tr>
                </thead>
                <tbody id="pfMvBody">
                    <tr><td colspan="7" class="pf-empty"><div class="spinner spinner-sm"></div><span data-label-ar="جارٍ التحميل..." data-label-en="Loading...">جارٍ التحميل...</span></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===== VIOLATIONS SECTION ===== -->
<div class="pf-section">
    <div class="pf-card" style="max-width:100%">
        <div class="pf-card-title">
            <span>⚠️</span>
            <span id="pfVlTitle" data-label-ar="المخالفات المرتبطة بي" data-label-en="My Violations">المخالفات المرتبطة بي</span>
        </div>
        <div style="overflow-x:auto">
            <table class="pf-mv-table" id="pfVlTable">
                <thead>
                    <tr>
                        <th data-label-ar="#" data-label-en="#">#</th>
                        <th id="pfVlThCode" data-label-ar="رمز المركبة" data-label-en="Vehicle Code">رمز المركبة</th>
                        <th id="pfVlThDate" data-label-ar="تاريخ المخالفة" data-label-en="Violation Date">تاريخ المخالفة</th>
                        <th id="pfVlThAmount" data-label-ar="المبلغ" data-label-en="Amount">المبلغ</th>
                        <th id="pfVlThStatus" data-label-ar="الحالة" data-label-en="Status">الحالة</th>
                        <th id="pfVlThVType" data-label-ar="نوع المركبة" data-label-en="Vehicle Type">نوع المركبة</th>
                        <th id="pfVlThNotes" data-label-ar="ملاحظات" data-label-en="Notes">ملاحظات</th>
                    </tr>
                </thead>
                <tbody id="pfVlBody">
                    <tr><td colspan="7" class="pf-empty"><div class="spinner spinner-sm"></div><span data-label-ar="جارٍ التحميل..." data-label-en="Loading...">جارٍ التحميل...</span></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
/* ============================================
   Profile Fragment — Personal Data & Movements
   ============================================ */
(function () {
    'use strict';

    var currentUser = null;
    var profileData = null;

    function $(id) { return document.getElementById(id); }

    function esc(s) {
        return typeof UI !== 'undefined' && UI._escapeHtml
            ? UI._escapeHtml(String(s || ''))
            : String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function getLang() { return localStorage.getItem('lang') || 'ar'; }
    function t(ar, en) { return getLang() === 'en' ? en : ar; }

    /* ---------- Apply i18n to static labels ---------- */
    function applyLang() {
        var isEn = (getLang() === 'en');
        document.querySelectorAll('[data-label-ar]').forEach(function(el) {
            el.textContent = el.getAttribute(isEn ? 'data-label-en' : 'data-label-ar') || el.textContent;
        });
    }

    /* ---------- Load profile data ---------- */
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

    /* ---------- Populate form with profile data ---------- */
    function populateForm(data) {
        $('pfEmpId').value = data.emp_id || '';
        $('pfUsername').value = data.username || '';
        $('pfEmail').value = data.email || '';
        $('pfPhone').value = data.phone || '';
        $('pfRole').value = data.role_name || (t('دور', 'Role') + ' #' + (data.role_id || ''));

        var genderMap = { male: t('ذكر', 'Male'), female: t('أنثى', 'Female') };
        $('pfGender').value = genderMap[data.gender] || data.gender || '';

        $('pfDept').value = data.department_name_ar || t('غير محدد', 'Not set');
        $('pfSection').value = data.section_name_ar || t('غير محدد', 'Not set');
        $('pfDivision').value = data.division_name_ar || t('غير محدد', 'Not set');

        var langSel = $('pfLang');
        if (langSel) langSel.value = data.preferred_language || 'ar';
    }

    /* ---------- Save profile changes ---------- */
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
            errorEl.textContent = (e && e.message) || t('حدث خطأ', 'An error occurred');
            errorEl.style.display = 'inline';
        } finally {
            btn.disabled = false;
        }
    }

    /* ---------- Change password ---------- */
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
            errorEl.textContent = t('يرجى ملء جميع الحقول', 'Please fill all fields');
            errorEl.style.display = 'inline';
            return;
        }
        if (newPwd.length < 4) {
            errorEl.textContent = t('كلمة المرور الجديدة يجب أن تكون 4 أحرف على الأقل', 'New password must be at least 4 characters');
            errorEl.style.display = 'inline';
            return;
        }
        if (newPwd !== confPwd) {
            errorEl.textContent = t('كلمة المرور الجديدة غير متطابقة', 'Passwords do not match');
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
            errorEl.textContent = (e && e.message) || t('حدث خطأ', 'An error occurred');
            errorEl.style.display = 'inline';
        } finally {
            btn.disabled = false;
        }
    }

    /* ---------- Load movement history ---------- */
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
                '<div class="ico">⚠️</div><p>' + t('فشل تحميل السجل', 'Failed to load history') + '</p></td></tr>';
        }
    }

    /* ---------- Render movement rows ---------- */
    function renderMovements(rows) {
        var tbody = $('pfMvBody');
        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="pf-empty">' +
                '<div class="ico">🚗</div><p>' + t('لا توجد حركات مسجلة', 'No movements recorded') + '</p></td></tr>';
            return;
        }

        var html = '';
        rows.forEach(function(mv, i) {
            var isPickup = (mv.operation_type === 'pickup');
            var opLabel = isPickup ? t('استلام', 'Pickup') : t('إرجاع', 'Return');
            var opClass = isPickup ? 'pickup' : 'return';
            var dateStr = mv.movement_datetime || mv.created_at || '';
            if (dateStr) {
                try {
                    var d = new Date(dateStr);
                    dateStr = d.toLocaleDateString(getLang() === 'en' ? 'en-US' : 'ar-SA', {
                        year: 'numeric', month: 'short', day: 'numeric',
                        hour: '2-digit', minute: '2-digit'
                    });
                } catch(e) { /* keep original */ }
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

    /* ---------- Expose globally ---------- */
    window.ProfilePage = {
        saveProfile: saveProfile,
        changePassword: changePassword
    };

    /* ---------- Load violations ---------- */
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
                '<div class="ico">⚠️</div><p>' + t('فشل تحميل المخالفات', 'Failed to load violations') + '</p></td></tr>';
        }
    }

    /* ---------- Render violation rows ---------- */
    function renderViolations(rows) {
        var tbody = $('pfVlBody');
        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="pf-empty">' +
                '<div class="ico">✅</div><p>' + t('لا توجد مخالفات مسجلة', 'No violations recorded') + '</p></td></tr>';
            return;
        }

        var html = '';
        rows.forEach(function(vl, i) {
            var isPaid = (vl.violation_status === 'paid');
            var statusLabel = isPaid ? t('مدفوعة', 'Paid') : t('غير مدفوعة', 'Unpaid');
            var statusClass = isPaid ? 'paid' : 'unpaid';
            var dateStr = vl.violation_datetime || vl.created_at || '';
            if (dateStr) {
                try {
                    var d = new Date(dateStr);
                    dateStr = d.toLocaleDateString(getLang() === 'en' ? 'en-US' : 'ar-SA', {
                        year: 'numeric', month: 'short', day: 'numeric',
                        hour: '2-digit', minute: '2-digit'
                    });
                } catch(e) { /* keep original */ }
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

    /* ---------- Init with retry for Auth ---------- */
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

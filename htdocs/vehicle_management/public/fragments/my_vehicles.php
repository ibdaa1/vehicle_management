<?php
/**
 * My Vehicles Fragment — Employee Self-Service
 * Loaded inside dashboard.php shell.
 * Shows private vehicles (emp_id match) and shift vehicles (round-robin by gender).
 * Pickup/Return buttons only visible with manage_movements permission.
 */
?>
<style>
/* === My Vehicles Fragment Styles === */
.mv-section-title{font-size:1.1rem;font-weight:700;color:var(--text-primary);margin-bottom:16px;display:flex;align-items:center;gap:8px}
.mv-section-card{background:var(--bg-card);border-radius:12px;box-shadow:var(--card-shadow);border:1px solid var(--border-default);padding:20px;margin-bottom:28px}
.mv-vehicles-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px}
.mv-v-card{background:var(--bg-card);border-radius:12px;padding:18px;box-shadow:var(--card-shadow);border:1px solid var(--border-default);transition:transform .25s,box-shadow .25s}
.mv-v-card:hover{transform:translateY(-3px);box-shadow:0 6px 20px rgba(0,0,0,.1)}
.mv-v-card-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
.mv-v-code{font-size:1.1rem;font-weight:700;color:var(--text-primary)}
.mv-v-badge{padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:600;color:#fff}
.mv-v-badge.available{background:var(--status-success)}
.mv-v-badge.checked_out{background:var(--status-danger)}
.mv-v-badge.operational{background:var(--status-success)}
.mv-v-badge.maintenance{background:var(--status-warning);color:#1a1a2e}
.mv-v-badge.out_of_service{background:var(--status-danger)}
.mv-v-type{font-size:.9rem;color:var(--text-primary);margin-bottom:8px;font-weight:500}
.mv-v-detail{font-size:.8rem;color:var(--text-secondary);display:flex;align-items:center;gap:6px;margin-top:4px}
.mv-v-detail .icon{font-size:.9rem}
.mv-v-mode-badge{display:inline-block;padding:2px 8px;border-radius:4px;font-size:.75rem;font-weight:500;margin-top:8px}
.mv-v-mode-badge.private{background:rgba(212,175,55,.15);color:#a88a1e}
.mv-v-mode-badge.shift{background:rgba(23,162,184,.12);color:#17a2b8}
.mv-v-actions{margin-top:14px;display:flex;gap:8px}
.mv-v-actions .btn{padding:8px 16px;font-size:.85rem;border-radius:8px;border:none;cursor:pointer;font-weight:600;display:inline-flex;align-items:center;gap:6px;transition:background .2s}
.mv-btn-pickup{background:var(--primary-main);color:var(--text-light)}
.mv-btn-pickup:hover{opacity:.9}
.mv-btn-pickup:disabled{opacity:.5;cursor:not-allowed}
.mv-btn-return{background:var(--status-warning);color:#1a1a2e}
.mv-btn-return:hover{opacity:.9}
.mv-empty-state{text-align:center;padding:40px 24px;color:var(--text-secondary)}
.mv-empty-state .empty-icon{font-size:2.5rem;margin-bottom:10px;opacity:.5}
.mv-info-banner{background:rgba(23,162,184,.08);border:1px solid rgba(23,162,184,.2);border-radius:10px;padding:14px 20px;margin-bottom:20px;color:var(--text-primary);font-size:.9rem;display:flex;align-items:center;gap:10px}
.mv-info-banner .icon{font-size:1.3rem}
.mv-order-badge{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;background:var(--primary-main);color:var(--text-light);font-weight:700;font-size:.85rem;margin-inline-end:8px}
@media(max-width:768px){
    .mv-vehicles-grid{grid-template-columns:1fr}
}
</style>

<div class="page-header">
    <h2 id="mvPageTitle" data-label-ar="مركباتي" data-label-en="My Vehicles">مركباتي</h2>
</div>

<!-- Info Banner -->
<div class="mv-info-banner" id="mvInfoBanner">
    <span class="icon">ℹ️</span>
    <span id="mvInfoText" data-label-ar="يتم عرض المركبة الخاصة بك فقط، وللورديات يظهر المركبة التي عليها الدور بحسب الجنس" data-label-en="Only your private vehicle is shown. For shifts, the next vehicle in the round-robin rotation for your gender is displayed.">يتم عرض المركبة الخاصة بك فقط، وللورديات يظهر المركبة التي عليها الدور بحسب الجنس</span>
</div>

<!-- ===== PRIVATE VEHICLES SECTION ===== -->
<div class="mv-section-card" id="mvPrivateSection">
    <div class="mv-section-title">
        <span>🔒</span>
        <span id="mvPrivateTitle" data-label-ar="مركباتي الخاصة" data-label-en="My Private Vehicles">مركباتي الخاصة</span>
    </div>
    <div id="mvPrivateGrid" class="mv-vehicles-grid">
        <div class="mv-empty-state"><div class="spinner spinner-sm"></div><span>جاري التحميل...</span></div>
    </div>
</div>

<!-- ===== SHIFT VEHICLES SECTION ===== -->
<div class="mv-section-card" id="mvShiftSection">
    <div class="mv-section-title">
        <span>🔄</span>
        <span id="mvShiftTitle" data-label-ar="مركبة الوردية التالية (حسب الدور)" data-label-en="Next Shift Vehicle (Round-Robin)">مركبة الوردية التالية (حسب الدور)</span>
    </div>
    <div id="mvShiftGrid" class="mv-vehicles-grid">
        <div class="mv-empty-state"><div class="spinner spinner-sm"></div><span>جاري التحميل...</span></div>
    </div>
</div>

<?php ob_start(); ?>
<script>
/* ============================================
   My Vehicles Fragment — Employee Self-Service
   Round-robin shift vehicles: only shows the
   next-in-turn vehicle for the employee's gender.
   Pickup/Return buttons require manage_movements permission.
   ============================================ */
(function () {
    'use strict';

    var currentUser = null;
    var perms = [];
    var hasMovementPermission = false;

    /* ---------- Helpers ---------- */
    function esc(s) { return typeof UI !== 'undefined' && UI._escapeHtml ? UI._escapeHtml(String(s || '')) : String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

    function getLang() {
        return localStorage.getItem('lang') || 'ar';
    }

    function t(arText, enText) {
        return getLang() === 'en' ? enText : arText;
    }

    function statusBadge(status) {
        var map = {
            operational:    { cls: 'operational',    ar: 'تعمل',       en: 'Operational' },
            maintenance:    { cls: 'maintenance',    ar: 'صيانة',       en: 'Maintenance' },
            out_of_service: { cls: 'out_of_service', ar: 'خارج الخدمة', en: 'Out of Service' }
        };
        var s = map[status] || map.operational;
        var label = getLang() === 'en' ? s.en : s.ar;
        return '<span class="mv-v-badge ' + s.cls + '">' + esc(label) + '</span>';
    }

    function availBadge(available) {
        if (available) {
            return '<span class="mv-v-badge available">' + esc(t('متاحة', 'Available')) + '</span>';
        }
        return '<span class="mv-v-badge checked_out">' + esc(t('مستلمة', 'Checked Out')) + '</span>';
    }

    function modeBadge(mode) {
        if (mode === 'private') {
            return '<span class="mv-v-mode-badge private">' + t('خاصة', 'Private') + '</span>';
        }
        return '<span class="mv-v-mode-badge shift">' + t('ورديات', 'Shift') + '</span>';
    }

    /* ---------- Build a vehicle card ---------- */
    function buildCard(v, opts) {
        opts = opts || {};
        var isAvailable = v.available !== false && v.available !== 0 && v.available !== '0';
        var isOperational = v.status === 'operational';
        var canPickup = isAvailable && isOperational;
        var isCheckedByMe = !isAvailable && (v.last_holder || '') === (currentUser.username || '');

        var html = '<div class="mv-v-card">';
        html += '<div class="mv-v-card-head">';
        if (opts.turnOrder) {
            html += '<span><span class="mv-order-badge">' + opts.turnOrder + '</span><span class="mv-v-code">' + esc(v.vehicle_code) + '</span></span>';
        } else {
            html += '<span class="mv-v-code">' + esc(v.vehicle_code) + '</span>';
        }
        html += availBadge(isAvailable);
        html += '</div>';

        html += '<div class="mv-v-type">' + esc(v.type || v.vehicle_type || '—') + '</div>';
        html += statusBadge(v.status);
        html += modeBadge(v.vehicle_mode);

        html += '<div class="mv-v-detail"><span class="icon">👤</span> ' + t('السائق', 'Driver') + ': ' + esc(v.driver_name || '—') + '</div>';
        if (v.vehicle_category) {
            html += '<div class="mv-v-detail"><span class="icon">🚗</span> ' + t('الفئة', 'Category') + ': ' + esc(v.vehicle_category) + '</div>';
        }
        if (!isAvailable && v.last_holder) {
            html += '<div class="mv-v-detail"><span class="icon">📋</span> ' + t('المستلم الحالي', 'Current Holder') + ': ' + esc(v.last_holder) + '</div>';
        }

        /* Pickup/Return buttons — only for users with manage_movements permission */
        if (hasMovementPermission) {
            html += '<div class="mv-v-actions">';
            if (canPickup) {
                html += '<button class="btn mv-btn-pickup" onclick="MyVehiclesFragment.pickup(\'' + esc(v.vehicle_code) + '\')">';
                html += '<span>🚗</span> ' + t('استلام', 'Pickup');
                html += '</button>';
            } else if (isCheckedByMe) {
                html += '<button class="btn mv-btn-return" onclick="MyVehiclesFragment.returnVehicle(\'' + esc(v.vehicle_code) + '\')">';
                html += '<span>↩️</span> ' + t('إرجاع', 'Return');
                html += '</button>';
            }
            html += '</div>';
        }

        html += '</div>';
        return html;
    }

    /* ---------- Load my vehicles ---------- */
    async function loadMyVehicles() {
        try {
            var res = await API.get('/vehicles/my-vehicles');
            var data = (res && res.data) || res || {};
            renderPrivate(data.private || []);
            renderShift(data.shift_next, data.shift_my_current, data.shift_total || 0);
        } catch (e) {
            console.error('Failed to load my vehicles:', e);
            renderPrivate([]);
            renderShift(null, null, 0);
        }
    }

    /* ---------- Render private vehicles ---------- */
    function renderPrivate(vehicles) {
        var container = document.getElementById('mvPrivateGrid');
        if (!container) return;
        if (!vehicles.length) {
            container.innerHTML = '<div class="mv-empty-state"><div class="empty-icon">🔒</div><p>' +
                t('لا توجد مركبات خاصة مسجلة لك', 'No private vehicles assigned to you') + '</p></div>';
            return;
        }
        container.innerHTML = vehicles.map(function(v) { return buildCard(v); }).join('');
    }

    /* ---------- Render shift vehicles (next-in-turn only) ---------- */
    function renderShift(nextVehicle, myCurrentVehicle, totalShift) {
        var container = document.getElementById('mvShiftGrid');
        if (!container) return;
        var cards = '';

        // Show vehicle currently held by user (for return)
        if (myCurrentVehicle) {
            cards += buildCard(myCurrentVehicle, { turnOrder: null });
        }

        // Show the next-in-turn vehicle (for pickup)
        if (nextVehicle && (!myCurrentVehicle || String(nextVehicle.vehicle_code) != String(myCurrentVehicle.vehicle_code))) {
            cards += buildCard(nextVehicle, { turnOrder: nextVehicle.turn_order || 1 });
        }

        if (!cards) {
            var msg = '';
            if (totalShift > 0) {
                msg = t('جميع مركبات الورديات مستلمة حالياً', 'All shift vehicles are currently checked out');
            } else {
                msg = t('لا توجد مركبات ورديات متاحة', 'No shift vehicles available');
            }
            container.innerHTML = '<div class="mv-empty-state"><div class="empty-icon">🔄</div><p>' + msg + '</p></div>';
            return;
        }
        container.innerHTML = cards;
    }

    /* ---------- Pickup action ---------- */
    async function pickup(vehicleCode) {
        if (!hasMovementPermission) return;
        var msg = t('هل تريد استلام المركبة', 'Do you want to pick up vehicle') + ' ' + vehicleCode + '?';
        if (!confirm(msg)) return;

        try {
            await API.post('/movements', {
                vehicle_code: vehicleCode,
                operation_type: 'pickup',
                performed_by: currentUser.emp_id
            });
            if (typeof UI !== 'undefined' && UI.showToast) {
                UI.showToast(t('تم استلام المركبة بنجاح', 'Vehicle picked up successfully'), 'success');
            }
            loadMyVehicles();
        } catch (e) {
            if (typeof UI !== 'undefined' && UI.showToast) {
                UI.showToast(e.message || t('خطأ', 'Error'), 'error');
            }
        }
    }

    /* ---------- Return action ---------- */
    async function returnVehicle(vehicleCode) {
        if (!hasMovementPermission) return;
        var msg = t('إرجاع المركبة', 'Return Vehicle') + ' ' + vehicleCode + '?';
        if (!confirm(msg)) return;

        try {
            await API.post('/movements', {
                vehicle_code: vehicleCode,
                operation_type: 'return',
                performed_by: currentUser.emp_id
            });
            if (typeof UI !== 'undefined' && UI.showToast) {
                UI.showToast(t('تم إرجاع المركبة بنجاح', 'Vehicle returned successfully'), 'success');
            }
            loadMyVehicles();
        } catch (e) {
            if (typeof UI !== 'undefined' && UI.showToast) {
                UI.showToast(e.message || t('خطأ', 'Error'), 'error');
            }
        }
    }

    /* ---------- Apply language to fragment labels ---------- */
    function applyFragmentLang() {
        var lang = getLang();
        var isEn = (lang === 'en');
        document.querySelectorAll('[data-label-ar]').forEach(function(el) {
            el.textContent = el.getAttribute(isEn ? 'data-label-en' : 'data-label-ar') || el.textContent;
        });
    }

    /* ---------- Expose globally ---------- */
    window.MyVehiclesFragment = { pickup: pickup, returnVehicle: returnVehicle };

    /* ---------- Init (wait for Auth.check() in app.js to complete) ---------- */
    document.addEventListener('DOMContentLoaded', async function() {
        await new Promise(function(r){ setTimeout(r, 150); });
        currentUser = Auth.getUser();
        perms = (currentUser && currentUser.permissions) || [];
        hasMovementPermission = perms.includes('movements_create') || perms.includes('*');

        if (!currentUser) {
            var container = document.getElementById('mvPrivateGrid');
            if (container) {
                container.innerHTML = '<div class="mv-empty-state"><div class="empty-icon">🔒</div><p>' +
                    t('يرجى تسجيل الدخول', 'Please log in') + '</p></div>';
            }
            var sContainer = document.getElementById('mvShiftGrid');
            if (sContainer) {
                sContainer.innerHTML = '';
            }
        } else {
            applyFragmentLang();
            loadMyVehicles();
        }
    });
})();
</script>
<?php $pageScripts = ob_get_clean(); ?>

<?php
/**
 * My Vehicles Fragment — Employee Self-Service
 * Loaded inside dashboard.php shell.
 * Shows private vehicles (emp_id match).
 * Admin/SuperAdmin see ALL shift/department vehicles with turn order rotation.
 * Employee sees only the next-in-turn vehicle (filtered by sector_id and gender).
 */
?>
<style>
html[dir="ltr"] body{direction:ltr;font-family:var(--font-en)}
html[dir="ltr"] .app-sidebar{right:auto;left:0}
html[dir="ltr"] .app-main{margin-right:0;margin-left:var(--sidebar-width)}
html[dir="ltr"] .app-footer{margin-right:0;margin-left:var(--sidebar-width)}
html[dir="ltr"] .app-sidebar.collapsed~.app-main{margin-right:0;margin-left:var(--sidebar-collapsed-width)}
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
.mv-v-card.next-turn{border:2px solid var(--primary-main);box-shadow:0 0 12px rgba(var(--primary-main-rgb,59,130,246),.25)}
.mv-next-label{display:inline-block;padding:2px 8px;border-radius:4px;font-size:.72rem;font-weight:600;background:var(--primary-main);color:var(--text-light);margin-inline-start:6px}
.mv-v-card.my-current{border:2px solid var(--status-warning);box-shadow:0 0 12px rgba(255,193,7,.25)}
.mv-holder-info{background:rgba(220,53,69,.06);border-radius:8px;padding:8px 12px;margin-top:8px;font-size:.8rem}
@media(max-width:768px){.mv-vehicles-grid{grid-template-columns:1fr}}
</style>

<div class="page-header">
    <h2 id="mvPageTitle">My Vehicles</h2>
</div>

<!-- Info Banner -->
<div class="mv-info-banner" id="mvInfoBanner">
    <span class="icon">ℹ️</span>
    <span id="mvInfoText">Your private vehicles, shift vehicles, and available department vehicles are displayed here.</span>
</div>

<!-- PRIVATE VEHICLES SECTION -->
<div class="mv-section-card" id="mvPrivateSection">
    <div class="mv-section-title">
        <span>🔒</span>
        <span id="mvPrivateTitle">My Private Vehicles</span>
    </div>
    <div id="mvPrivateGrid" class="mv-vehicles-grid">
        <div class="mv-empty-state"><div class="spinner spinner-sm"></div><span id="lbl_private_loading">Loading...</span></div>
    </div>
</div>

<!-- SHIFT VEHICLES SECTION -->
<div class="mv-section-card" id="mvShiftSection">
    <div class="mv-section-title">
        <span>🔄</span>
        <span id="mvShiftTitle">Shift Vehicles</span>
    </div>
    <div id="mvShiftGrid" class="mv-vehicles-grid">
        <div class="mv-empty-state"><div class="spinner spinner-sm"></div><span id="lbl_shift_loading">Loading...</span></div>
    </div>
</div>

<!-- DEPARTMENT VEHICLES SECTION -->
<div class="mv-section-card" id="mvDeptSection">
    <div class="mv-section-title">
        <span>🏢</span>
        <span id="mvDeptTitle">Available Department Vehicles</span>
    </div>
    <div id="mvDeptGrid" class="mv-vehicles-grid">
        <div class="mv-empty-state"><div class="spinner spinner-sm"></div><span id="lbl_dept_loading">Loading...</span></div>
    </div>
</div>

<?php ob_start(); ?>
<script>
(function () {
    'use strict';

    var currentUser = null;
    var perms = [];
    var hasMovementPermission = false;
    var hasAdminMovementPermission = false;
    var userMap = {};

    function esc(s) { return typeof UI !== 'undefined' && UI._escapeHtml ? UI._escapeHtml(String(s || '')) : String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
    function getLang() { return localStorage.getItem('lang') || 'ar'; }
    function t(key, fallback) { return (typeof i18n !== 'undefined' && i18n.t) ? i18n.t(key) : fallback; }

    /* Apply i18n to static labels */
    function applyFragmentLang() {
        var map = {
            'mvPageTitle':    'my_vehicles',
            'mvInfoText':     'my_vehicles_info',
            'mvPrivateTitle': 'my_private_vehicles',
            'mvShiftTitle':   'shift_vehicles',
            'mvDeptTitle':    'available_dept_vehicles'
        };
        if (typeof i18n === 'undefined') return;
        Object.keys(map).forEach(function(id) {
            var el = document.getElementById(id);
            if (!el) return;
            var val = i18n.t(map[id]);
            if (val && val !== map[id]) el.textContent = val;
        });
    }

    function statusBadge(status) {
        var map = {
            operational:    { cls: 'operational',    key: 'operational',    label: 'Operational' },
            maintenance:    { cls: 'maintenance',    key: 'maintenance',    label: 'Maintenance' },
            out_of_service: { cls: 'out_of_service', key: 'out_of_service', label: 'Out of Service' }
        };
        var s = map[status] || map.operational;
        var label = t(s.key, s.label);
        return '<span class="mv-v-badge ' + s.cls + '">' + esc(label) + '</span>';
    }

    function availBadge(available) {
        if (available) {
            return '<span class="mv-v-badge available">' + esc(t('available', 'Available')) + '</span>';
        }
        return '<span class="mv-v-badge checked_out">' + esc(t('checked_out', 'Checked Out')) + '</span>';
    }

    function modeBadge(mode) {
        if (mode === 'private') {
            return '<span class="mv-v-mode-badge private">' + t('private', 'Private') + '</span>';
        }
        return '<span class="mv-v-mode-badge shift">' + t('shift', 'Shift') + '</span>';
    }

    /* Build a vehicle card */
    function buildCard(v, opts) {
        opts = opts || {};
        var isAvailable = v.available !== false && v.available !== 0 && v.available !== '0';
        var isOperational = v.status === 'operational';
        var canPickup = isAvailable && isOperational;
        var isCheckedByMe = !isAvailable && (v.last_holder || '') === (currentUser.emp_id || '');
        var isNextTurn = opts.isNextTurn || false;
        var isMyCurrentVehicle = opts.isMyCurrentVehicle || false;

        var cardClass = 'mv-v-card';
        if (isNextTurn) cardClass += ' next-turn';
        if (isMyCurrentVehicle) cardClass += ' my-current';

        var html = '<div class="' + cardClass + '">';
        html += '<div class="mv-v-card-head">';
        if (opts.turnOrder) {
            html += '<span><span class="mv-order-badge">' + opts.turnOrder + '</span><span class="mv-v-code">' + esc(v.vehicle_code) + '</span>';
            if (isNextTurn) {
                html += '<span class="mv-next-label">' + t('next_turn', 'Next Turn') + '</span>';
            }
            html += '</span>';
        } else {
            html += '<span class="mv-v-code">' + esc(v.vehicle_code) + '</span>';
        }
        html += availBadge(isAvailable);
        html += '</div>';

        html += '<div class="mv-v-type">' + esc(v.type || v.vehicle_type || '—') + '</div>';
        html += statusBadge(v.status);
        html += modeBadge(v.vehicle_mode);

        html += '<div class="mv-v-detail"><span class="icon">👤</span> ' + t('driver', 'Driver') + ': ' + esc(v.driver_name || '—') + '</div>';
        if (v.vehicle_category) {
            html += '<div class="mv-v-detail"><span class="icon">🚗</span> ' + t('category', 'Category') + ': ' + esc(v.vehicle_category) + '</div>';
        }
        if (!isAvailable && v.last_holder) {
            var holderInfo = userMap[v.last_holder] || {};
            var holderName = holderInfo.name || v.last_holder;
            var holderSector = holderInfo.sector_name || '';
            html += '<div class="mv-holder-info">';
            html += '<div class="mv-v-detail" style="margin-top:0"><span class="icon">👤</span> ' + t('current_holder', 'Current Holder') + ': <strong>' + esc(holderName) + '</strong></div>';
            if (holderSector) {
                html += '<div class="mv-v-detail"><span class="icon">🏛️</span> ' + t('sector', 'Sector') + ': ' + esc(holderSector) + '</div>';
            }
            html += '</div>';
        }

        var isNotAvailable = !isAvailable;
        var canReturn = false;
        if (opts.isPrivate) {
            canReturn = isCheckedByMe;
        } else {
            canReturn = isNotAvailable && hasAdminMovementPermission;
        }
        if (!opts.isPrivate && !isNextTurn) {
            canPickup = false;
        }
        html += '<div class="mv-v-actions">';
        if (canPickup) {
            html += '<button class="btn mv-btn-pickup" onclick="MyVehiclesFragment.pickup(\'' + esc(v.vehicle_code) + '\')">';
            html += '<span>🚗</span> ' + t('pickup', 'Pickup');
            html += '</button>';
        } else if (canReturn) {
            html += '<button class="btn mv-btn-return" onclick="MyVehiclesFragment.returnVehicle(\'' + esc(v.vehicle_code) + '\')">';
            html += '<span>↩️</span> ' + t('return', 'Return');
            html += '</button>';
        }
        html += '</div>';
        html += '</div>';
        return html;
    }

    async function loadMyVehicles() {
        try {
            var res = await API.get('/vehicles/my-vehicles');
            if (!res || res.success === false) {
                var errMsg = (res && res.message) || t('load_failed', 'Failed to load vehicle data');
                if (typeof UI !== 'undefined' && UI.showToast) UI.showToast(errMsg, 'error');
                renderError('mvPrivateGrid');
                renderError('mvShiftGrid');
                renderError('mvDeptGrid');
                return;
            }

            /* Fetch users to resolve holder names & sectors */
            try {
                var uRes = await API.get('/users');
                var users = (uRes && uRes.data) || (Array.isArray(uRes) ? uRes : []);
                userMap = {};
                users.forEach(function(u) {
                    if (!u.emp_id) return;
                    userMap[u.emp_id] = {
                        name: u.username || u.email || u.emp_id,
                        sector_name: u.sector_name || u.sector_name_en || '',
                        department_name: u.department_name_ar || u.department_name || ''
                    };
                });
            } catch (ue) {
                console.warn('my_vehicles: Could not load users for holder names', ue);
            }

            var data = (res && res.data) || res || {};
            renderPrivate(data.private || []);
            renderShift(data.shift_vehicles || [], data.shift_total || 0);
            renderDepartment(data.department_vehicles || [], data.dept_total || 0);
        } catch (e) {
            var errMsg = (e && e.message) || '';
            if (typeof UI !== 'undefined' && UI.showToast) {
                UI.showToast(t('load_failed', 'Failed to load vehicle data') + (errMsg ? ': ' + errMsg : ''), 'error');
            }
            renderError('mvPrivateGrid');
            renderError('mvShiftGrid');
            renderError('mvDeptGrid');
        }
    }

    function renderError(containerId) {
        var container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = '<div class="mv-empty-state">' +
            '<div class="empty-icon">⚠️</div>' +
            '<p>' + t('error_loading', 'An error occurred while loading data') + '</p>' +
            '<button class="btn mv-btn-pickup" onclick="MyVehiclesFragment.reload()" style="margin-top:12px">' +
            t('retry', 'Retry') + '</button></div>';
    }

    function renderPrivate(vehicles) {
        var container = document.getElementById('mvPrivateGrid');
        if (!container) return;
        if (!vehicles.length) {
            container.innerHTML = '<div class="mv-empty-state"><div class="empty-icon">🔒</div><p>' +
                t('no_private_vehicles', 'No private vehicles assigned to you') + '</p></div>';
            return;
        }
        container.innerHTML = vehicles.map(function(v) { return buildCard(v, { isPrivate: true }); }).join('');
    }

    function renderShift(vehicles, totalShift) {
        var container = document.getElementById('mvShiftGrid');
        if (!container) return;

        if (!vehicles || !vehicles.length) {
            var msg = totalShift > 0
                ? t('all_shift_checked_out', 'All shift vehicles are currently checked out')
                : t('no_shift_vehicles', 'No shift vehicles available');
            container.innerHTML = '<div class="mv-empty-state"><div class="empty-icon">🔄</div><p>' + msg + '</p></div>';
            return;
        }

        var displayVehicles = vehicles;
        if (!hasAdminMovementPermission) {
            displayVehicles = vehicles.filter(function(v) {
                return v.is_next_turn || v.is_my_current;
            });
            if (!displayVehicles.length) {
                container.innerHTML = '<div class="mv-empty-state"><div class="empty-icon">🔄</div><p>' +
                    t('no_shift_turn', 'No shift vehicle is assigned to your turn currently') + '</p></div>';
                return;
            }
        }

        container.innerHTML = displayVehicles.map(function(v) {
            return buildCard(v, {
                turnOrder: v.turn_order || null,
                isNextTurn: v.is_next_turn || false,
                isMyCurrentVehicle: v.is_my_current || false
            });
        }).join('');
    }

    function renderDepartment(vehicles, totalDept) {
        var container = document.getElementById('mvDeptGrid');
        if (!container) return;

        if (!vehicles || !vehicles.length) {
            var msg = totalDept > 0
                ? t('all_dept_checked_out', 'All department vehicles are currently checked out')
                : t('no_dept_vehicles', 'No vehicles available in your department');
            container.innerHTML = '<div class="mv-empty-state"><div class="empty-icon">🏢</div><p>' + msg + '</p></div>';
            return;
        }

        var displayVehicles = vehicles;
        if (!hasAdminMovementPermission) {
            displayVehicles = vehicles.filter(function(v) {
                return v.is_next_turn || v.is_my_current;
            });
            if (!displayVehicles.length) {
                container.innerHTML = '<div class="mv-empty-state"><div class="empty-icon">🏢</div><p>' +
                    t('no_dept_turn', 'No department vehicle is assigned to your turn currently') + '</p></div>';
                return;
            }
        }

        container.innerHTML = displayVehicles.map(function(v) {
            return buildCard(v, {
                turnOrder: v.turn_order || null,
                isNextTurn: v.is_next_turn || false,
                isMyCurrentVehicle: v.is_my_current || false
            });
        }).join('');
    }

    async function pickup(vehicleCode) {
        var msg = t('confirm_pickup', 'Do you want to pick up vehicle') + ' ' + vehicleCode + '?';
        if (!confirm(msg)) return;

        try {
            await API.post('/vehicles/self-service', {
                vehicle_code: vehicleCode,
                operation_type: 'pickup'
            });
            if (typeof UI !== 'undefined' && UI.showToast) {
                UI.showToast(t('vehicle_picked_up', 'Vehicle picked up successfully'), 'success');
            }
            loadMyVehicles();
        } catch (e) {
            if (typeof UI !== 'undefined' && UI.showToast) {
                UI.showToast(e.message || t('error', 'Error'), 'error');
            }
        }
    }

    async function returnVehicle(vehicleCode) {
        var msg = t('return_vehicle', 'Return Vehicle') + ' ' + vehicleCode + '?';
        if (!confirm(msg)) return;

        try {
            await API.post('/vehicles/self-service', {
                vehicle_code: vehicleCode,
                operation_type: 'return'
            });
            if (typeof UI !== 'undefined' && UI.showToast) {
                UI.showToast(t('vehicle_returned_success', 'Vehicle returned successfully'), 'success');
            }
            loadMyVehicles();
        } catch (e) {
            if (typeof UI !== 'undefined' && UI.showToast) {
                UI.showToast(e.message || t('error', 'Error'), 'error');
            }
        }
    }

    window.MyVehiclesFragment = { pickup: pickup, returnVehicle: returnVehicle, reload: loadMyVehicles };

    var _initAttempts = 0;
    (function init() {
        if (window.__pageDenied) return;
        var user = Auth.getUser();
        if (!user) {
            _initAttempts++;
            if (_initAttempts > 50) {
                renderError('mvPrivateGrid');
                renderError('mvShiftGrid');
                renderError('mvDeptGrid');
                return;
            }
            setTimeout(init, 100);
            return;
        }
        currentUser = user;
        perms = (currentUser && currentUser.permissions) || [];
        hasMovementPermission = true;
        hasAdminMovementPermission = perms.includes('manage_movements') || perms.includes('*');

        applyFragmentLang();
        loadMyVehicles();
    })();
})();
</script>
<?php $pageScripts = ob_get_clean(); ?>
<?php
/**
 * My Vehicles Fragment — Employee Self-Service
 *
 * ROTATION LOGIC (frontend, based on DB schema):
 * ─────────────────────────────────────────────────────────────
 * Pool = vehicles WHERE vehicle_mode='shift'
 *               AND sector_id = currentUser.sector_id
 *               AND gender    = currentUser.gender   (or NULL)
 *
 * Next-in-turn = the vehicle in that pool whose last pickup
 *                movement_datetime is the OLDEST (or never picked up).
 *                Round-robin: after all have been used once, the one
 *                returned longest ago gets the next turn.
 *
 * Pickup allowed when:
 *   • Vehicle IS the next-in-turn
 *   • Vehicle IS available (not checked out)
 *   • Vehicle IS operational
 *   • Current user does NOT already hold a rotation vehicle
 *
 * Return allowed ONLY to the user currently holding the vehicle
 *   (last movement = pickup by currentUser.emp_id)
 *
 * Admin (manage_movements / *): sees ALL shift vehicles,
 *   full pickup + return, no restrictions.
 * ─────────────────────────────────────────────────────────────
 */
?>
<style>
html[dir="ltr"] body{direction:ltr;font-family:var(--font-en)}
html[dir="ltr"] .app-sidebar{right:auto;left:0}
html[dir="ltr"] .app-main{margin-right:0;margin-left:var(--sidebar-width)}
html[dir="ltr"] .app-footer{margin-right:0;margin-left:var(--sidebar-width)}
html[dir="ltr"] .app-sidebar.collapsed~.app-main{margin-right:0;margin-left:var(--sidebar-collapsed-width)}

.mv-section-card{background:var(--bg-card);border-radius:12px;box-shadow:var(--card-shadow);border:1px solid var(--border-default);padding:20px;margin-bottom:28px}
.mv-section-title{font-size:1.1rem;font-weight:700;color:var(--text-primary);margin-bottom:16px;display:flex;align-items:center;gap:8px}
.mv-section-count{font-size:.82rem;color:var(--text-secondary);font-weight:400;margin-inline-start:6px}

.mv-vehicles-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px}
@media(max-width:768px){.mv-vehicles-grid{grid-template-columns:1fr}}

.mv-v-card{background:var(--bg-card);border-radius:12px;padding:18px;box-shadow:var(--card-shadow);border:1px solid var(--border-default);transition:transform .25s,box-shadow .25s}
.mv-v-card:hover{transform:translateY(-3px);box-shadow:0 6px 20px rgba(0,0,0,.1)}
.mv-v-card.next-turn{border:2px solid var(--primary-main);box-shadow:0 0 14px rgba(59,130,246,.22)}
.mv-v-card.my-current{border:2px solid var(--status-warning);box-shadow:0 0 14px rgba(255,193,7,.22)}

.mv-v-card-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;flex-wrap:wrap;gap:6px}
.mv-v-code{font-size:1.1rem;font-weight:700;color:var(--text-primary)}
.mv-v-badge{padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:600;color:#fff}
.mv-v-badge.available{background:var(--status-success)}
.mv-v-badge.checked_out{background:var(--status-danger)}
.mv-v-badge.operational{background:var(--status-success)}
.mv-v-badge.maintenance{background:var(--status-warning);color:#1a1a2e}
.mv-v-badge.out_of_service{background:var(--status-danger)}

.mv-v-type{font-size:.9rem;color:var(--text-primary);margin-bottom:8px;font-weight:500}
.mv-v-detail{font-size:.8rem;color:var(--text-secondary);display:flex;align-items:center;gap:6px;margin-top:4px}
.mv-v-mode-badge{display:inline-block;padding:2px 8px;border-radius:4px;font-size:.75rem;font-weight:500;margin-top:8px}
.mv-v-mode-badge.private{background:rgba(212,175,55,.15);color:#a88a1e}
.mv-v-mode-badge.rotation{background:rgba(111,66,193,.12);color:#6f42c1}

.mv-order-badge{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:50%;background:var(--primary-main);color:var(--text-light);font-weight:700;font-size:.8rem;margin-inline-end:6px;flex-shrink:0}
.mv-next-label{display:inline-block;padding:2px 8px;border-radius:4px;font-size:.72rem;font-weight:600;background:var(--primary-main);color:var(--text-light);margin-inline-start:4px}
.mv-my-label{display:inline-block;padding:2px 8px;border-radius:4px;font-size:.72rem;font-weight:600;background:var(--status-warning);color:#1a1a2e;margin-inline-start:4px}

.mv-holder-info{background:rgba(220,53,69,.06);border-radius:8px;padding:8px 12px;margin-top:8px;font-size:.8rem}
.mv-blocked-msg{margin-top:10px;padding:8px 12px;background:rgba(255,193,7,.1);border:1px solid rgba(255,193,7,.3);border-radius:8px;font-size:.8rem;color:var(--text-secondary);display:flex;align-items:flex-start;gap:6px}

.mv-v-actions{margin-top:14px;display:flex;gap:8px;flex-wrap:wrap}
.mv-btn-pickup{background:var(--primary-main);color:var(--text-light);padding:8px 18px;font-size:.85rem;border-radius:8px;border:none;cursor:pointer;font-weight:600;display:inline-flex;align-items:center;gap:6px;transition:opacity .2s}
.mv-btn-pickup:hover:not(:disabled){opacity:.88}
.mv-btn-pickup:disabled{opacity:.42;cursor:not-allowed}
.mv-btn-return{background:var(--status-warning);color:#1a1a2e;padding:8px 18px;font-size:.85rem;border-radius:8px;border:none;cursor:pointer;font-weight:600;display:inline-flex;align-items:center;gap:6px;transition:opacity .2s}
.mv-btn-return:hover{opacity:.88}

.mv-info-banner{background:rgba(23,162,184,.08);border:1px solid rgba(23,162,184,.2);border-radius:10px;padding:14px 20px;margin-bottom:20px;color:var(--text-primary);font-size:.88rem;display:flex;align-items:flex-start;gap:10px;line-height:1.5}
.mv-empty-state{text-align:center;padding:40px 24px;color:var(--text-secondary)}
.mv-empty-state .empty-icon{font-size:2.5rem;margin-bottom:10px;opacity:.5}
.mv-debug-note{font-size:.75rem;color:var(--text-secondary);margin-top:6px;opacity:.7}
</style>

<div class="page-header">
    <h2 id="mvPageTitle">My Vehicles</h2>
</div>

<div class="mv-info-banner">
    <span style="font-size:1.2rem;flex-shrink:0">ℹ️</span>
    <span id="mvInfoText"></span>
</div>

<!-- PRIVATE VEHICLES -->
<div class="mv-section-card" id="mvPrivateSection">
    <div class="mv-section-title">
        <span>🔒</span>
        <span id="mvPrivateTitle">My Private Vehicles</span>
    </div>
    <div id="mvPrivateGrid" class="mv-vehicles-grid">
        <div class="mv-empty-state"><div class="spinner spinner-sm"></div></div>
    </div>
</div>

<!-- ROTATION VEHICLES -->
<div class="mv-section-card" id="mvRotationSection">
    <div class="mv-section-title">
        <span>🔄</span>
        <span id="mvRotationTitle">Rotation Vehicles</span>
        <span class="mv-section-count" id="mvRotationCount"></span>
    </div>
    <div id="mvRotationGrid" class="mv-vehicles-grid">
        <div class="mv-empty-state"><div class="spinner spinner-sm"></div></div>
    </div>
</div>

<?php ob_start(); ?>
<script>
(function () {
    'use strict';

    var currentUser   = null;
    var isAdmin       = false;
    var userMap       = {};
    var _initAttempts = 0;

    /* ── Helpers ─────────────────────────────────────────────────────── */
    function esc(s) { var d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
    function el(id) { return document.getElementById(id); }
    function setText(id, val) { var e = el(id); if (e) e.textContent = val; }
    function t(key, fb) {
        if (typeof i18n === 'undefined' || !i18n.t) return fb || key;
        var v = i18n.t(key);
        return (v && v !== key) ? v : (fb || key);
    }

    /* ── isAvailable helper — handles all API response variants ── */
    function isAvail(v) {
        var a = v.available;
        if (a === false || a === 0 || a === '0' || a === null || a === undefined) return false;
        return true;
    }

    /* ── Date formatter ── */
    function formatDT(dt) {
        try {
            var d = new Date(dt);
            if (isNaN(d.getTime())) return dt || '—';
            var loc = (i18n && i18n.lang === 'en') ? 'en-US' : 'ar-SA';
            return d.toLocaleDateString(loc) + ' ' + d.toLocaleTimeString(loc, { hour: '2-digit', minute: '2-digit' });
        } catch(e) { return dt || '—'; }
    }

    /* ── Static labels ─────────────────────────────────────────────── */
    function applyLang() {
        if (!i18n.strings || !Object.keys(i18n.strings).length) {
            setTimeout(applyLang, 100); return;
        }
        setText('mvPageTitle',     t('my_vehicles',         'My Vehicles'));
        setText('mvPrivateTitle',  t('my_private_vehicles', 'My Private Vehicles'));
        setText('mvRotationTitle', t('rotation_vehicles',   'Rotation Vehicles'));
        setText('mvInfoText', isAdmin
            ? t('admin_vehicles_info', 'Admin view — all rotation vehicles for all sectors shown with full control')
            : t('my_vehicles_info',
                'Shows your private vehicle and the rotation vehicle that is next in turn for your sector and gender. '
              + 'You can only pick up when it is your turn and you are not already holding a rotation vehicle.')
        );
    }

    /* ── Badges ─────────────────────────────────────────────────────── */
    function availBadge(available) {
        return available
            ? '<span class="mv-v-badge available">'   + esc(t('available',   'Available'))   + '</span>'
            : '<span class="mv-v-badge checked_out">' + esc(t('checked_out', 'Checked Out')) + '</span>';
    }
    function statusBadge(s) {
        var m = { operational:['operational',t('operational','Operational')], maintenance:['maintenance',t('maintenance','Maintenance')], out_of_service:['out_of_service',t('out_of_service','Out of Service')] };
        var x = m[s] || m.operational;
        return '<span class="mv-v-badge ' + x[0] + '">' + esc(x[1]) + '</span>';
    }

    /* ══════════════════════════════════════════════════════════════════
       ROTATION — determine next-in-turn from movement history
       ──────────────────────────────────────────────────────────────────
       Algorithm:
         1. Filter pool: vehicle_mode='shift', sector_id & gender match user
         2. For each vehicle, find its latest pickup datetime from movements
         3. Sort ascending by that datetime (null/never = oldest = first)
         4. First vehicle in sorted list = next in turn
         5. If that vehicle is currently checked out, it stays first until returned
       ══════════════════════════════════════════════════════════════════ */
    function determineNextTurn(vehicles, movements) {
        /*
         * If the server already provides is_next_turn flags AND no movements
         * are embedded (regular user), trust the server ordering directly.
         */
        var hasServerFlags = vehicles.some(function(v) { return v.is_next_turn; });
        if (hasServerFlags && !movements.length) {
            /* Put is_next_turn=true first, preserve original order otherwise */
            return vehicles.slice().sort(function(a, b) {
                return (b.is_next_turn ? 1 : 0) - (a.is_next_turn ? 1 : 0);
            });
        }

        /* Build map: vehicle_code → latest pickup datetime from movement history */
        var lastPickup = {};
        movements.forEach(function(m) {
            if (m.operation_type !== 'pickup') return;
            var code = m.vehicle_code;
            var dt   = m.movement_datetime || m.created_at || '';
            if (!lastPickup[code] || dt > lastPickup[code]) {
                lastPickup[code] = dt;
            }
        });

        /* Sort ascending by last pickup: never-picked-up vehicles come first */
        var sorted = vehicles.slice().sort(function(a, b) {
            var dtA = lastPickup[a.vehicle_code] || '';
            var dtB = lastPickup[b.vehicle_code] || '';
            if (!dtA && !dtB) return 0;
            if (!dtA) return -1;
            if (!dtB) return  1;
            return dtA < dtB ? -1 : dtA > dtB ? 1 : 0;
        });

        return sorted; /* sorted[0] = next in turn */
    }

    /* ── Load all data ─────────────────────────────────────────────── */
    async function loadMyVehicles() {
        ['mvPrivateGrid','mvRotationGrid'].forEach(function(id) {
            var c = el(id);
            if (c) c.innerHTML = '<div class="mv-empty-state"><div class="spinner spinner-sm"></div></div>';
        });

        try {
            var res = await API.get('/vehicles/my-vehicles');
            if (!res || res.success === false) {
                UI.showToast((res && res.message) || t('load_failed','Failed to load'), 'error');
                renderError('mvPrivateGrid'); renderError('mvRotationGrid'); return;
            }
            var data = (res.data || res) || {};

            var allVehicles = [];
            var movements   = [];

            if (isAdmin) {
                /* Admin has manage_movements — safe to call restricted endpoints */
                try { var avr = await API.get('/vehicles'); allVehicles = (avr && avr.data) || []; } catch(e) {}
                try { var mr  = await API.get('/movements'); movements = (mr && mr.data) || (Array.isArray(mr) ? mr : []); } catch(e) {}
                try {
                    var uRes  = await API.get('/users');
                    var users = (uRes && uRes.data) || (Array.isArray(uRes) ? uRes : []);
                    userMap   = {};
                    users.forEach(function(u) {
                        if (u.emp_id) userMap[u.emp_id] = { name: u.username || u.email || u.emp_id, sector_name: u.sector_name || '' };
                    });
                } catch(e) {}
            } else {
                /*
                 * Regular user — use ONLY what /my-vehicles returns.
                 * No calls to /vehicles, /movements, or /users (all require admin perms).
                 * The server embeds the rotation pool + movements in the response.
                 * Fall back to is_next_turn flag if no movements are embedded.
                 */
                allVehicles = [].concat(data.shift_vehicles || [], data.department_vehicles || []);
                movements   = data.movements || [];
                var holders = data.holders || data.users || [];
                userMap = {};
                holders.forEach(function(u) {
                    if (u.emp_id) userMap[u.emp_id] = { name: u.username || u.name || u.emp_id, sector_name: u.sector_name || '' };
                });
            }

            /* ── Debug: log raw API response to diagnose holder detection ── */
            if (console && console.log) {
                var shiftSample = (data.shift_vehicles || []).slice(0, 5);
                console.log('[my_vehicles] emp_id:', currentUser.emp_id, '| gender:', currentUser.gender, '| sector_id:', currentUser.sector_id);
                console.log('[my_vehicles] shift_vehicles:', JSON.stringify(shiftSample.map(function(v){
                    return {
                        code:         v.vehicle_code,
                        available:    v.available,
                        last_holder:  v.last_holder,
                        performed_by: v.performed_by,
                        held_by:      v.held_by,
                        is_my_current:v.is_my_current,
                        is_next_turn: v.is_next_turn,
                        sector_id:    v.sector_id,
                        gender:       v.gender
                    };
                })));
            }

            /* ── Private vehicles ── */
            renderPrivate(data.private || []);

            /* ── Rotation pool ──────────────────────────────────────────
               Filter allVehicles to this user's sector + gender pool.
               vehicle.gender = 'men'|'women'|NULL
               NULL gender vehicles are available to everyone in the sector.
               ─────────────────────────────────────────────────────────── */
            var userSectorId = String(currentUser.sector_id || '');
            var userGender   = currentUser.gender || '';

            var rotationPool = allVehicles.filter(function(v) {
                if (v.vehicle_mode !== 'shift') return false;
                if (String(v.sector_id || '') !== userSectorId) return false;
                /* Gender filter: match user gender OR vehicle has no gender restriction */
                if (v.gender && userGender && v.gender !== userGender) return false;
                return true;
            });

            /* For admin show all shift vehicles regardless of sector/gender */
            if (isAdmin) {
                rotationPool = allVehicles.filter(function(v) {
                    return v.vehicle_mode === 'shift';
                });
            }

            setText('mvRotationCount', '(' + rotationPool.length + ')');

            /* Determine turn order */
            var sortedPool = determineNextTurn(rotationPool, movements);

            renderRotation(sortedPool);

        } catch(e) {
            console.error('my_vehicles load error:', e);
            UI.showToast((e && e.message) || t('load_failed','Failed to load'), 'error');
            renderError('mvPrivateGrid'); renderError('mvRotationGrid');
        }
    }

    /* ── Render private ─────────────────────────────────────────────── */
    function renderPrivate(vehicles) {
        var container = el('mvPrivateGrid');
        var section   = el('mvPrivateSection');
        if (!container) return;

        if (!vehicles.length) {
            if (section && !isAdmin) section.style.display = 'none';
            else container.innerHTML = '<div class="mv-empty-state"><div class="empty-icon">🔒</div><p>'
                + t('no_private_vehicles','No private vehicles assigned to you') + '</p></div>';
            return;
        }
        if (section) section.style.display = '';
        container.innerHTML = vehicles.map(buildPrivateCard).join('');
    }

    /* ── Build private card ─────────────────────────────────────────── */
    function buildPrivateCard(v) {
        var available   = isAvail(v);
        var operational = v.status === 'operational';
        var isMine      = (v.emp_id || '') === (currentUser.emp_id || '');
        var myEmpIdP   = String(currentUser.emp_id || '').trim();
        var holderP    = String(v.last_holder || v.performed_by || v.held_by || '').trim();
        var isHeldByMe = !available && holderP !== '' && holderP === myEmpIdP;

        var html = '<div class="mv-v-card' + (isHeldByMe ? ' my-current' : '') + '">';
        html += '<div class="mv-v-card-head">';
        html += '<span class="mv-v-code">' + esc(v.vehicle_code) + '</span>';
        html += availBadge(available);
        html += '</div>';
        html += '<div class="mv-v-type">' + esc(v.type || '—') + '</div>';
        html += statusBadge(v.status);
        html += ' <span class="mv-v-mode-badge private">' + t('private','Private') + '</span>';
        html += '<div class="mv-v-detail"><span>👤</span> ' + esc(v.driver_name || '—') + '</div>';
        if (v.vehicle_category) html += '<div class="mv-v-detail"><span>🚗</span> ' + esc(v.vehicle_category) + '</div>';

        if (!available && v.last_holder) {
            var hi = userMap[v.last_holder] || {};
            html += '<div class="mv-holder-info"><div class="mv-v-detail" style="margin-top:0"><span>👤</span> '
                  + t('held_by','Held by') + ': <strong>' + esc(hi.name || v.last_holder) + '</strong></div></div>';
        }

        html += '<div class="mv-v-actions">';
        if (available && operational && isMine) {
            html += '<button class="mv-btn-pickup" onclick="MyVehiclesFragment.pickup(\'' + esc(v.vehicle_code) + '\')">'
                  + '<span>🚗</span> ' + t('pickup','Pickup') + '</button>';
        }
        /* Return button removed for all regular users */
        html += '</div></div>';
        return html;
    }

    /* ── Render rotation ────────────────────────────────────────────────
       sortedPool[0] = next in turn
       ─────────────────────────────────────────────────────────────────── */
    function renderRotation(sortedPool) {
        var container = el('mvRotationGrid');
        if (!container) return;

        if (!sortedPool.length) {
            container.innerHTML = '<div class="mv-empty-state"><div class="empty-icon">🔄</div><p>'
                + t('no_rotation_vehicles','No rotation vehicles available for your sector') + '</p></div>';
            return;
        }

        /* Vehicle currently held by this user (if any) */
        var myEmpId_ = String(currentUser.emp_id || '').trim();
        var myHeldVehicle = sortedPool.find(function(v) {
            if (isAvail(v)) return false;
            var holder = String(v.last_holder || v.performed_by || v.held_by || '').trim();
            return holder !== '' && holder === myEmpId_;
        });

        /* User is blocked from a new pickup if they already hold one */
        var iHoldOne = !!myHeldVehicle;

        if (isAdmin) {
            /* Admin: show all with turn numbers */
            container.innerHTML = sortedPool.map(function(v, idx) {
                return buildRotationCard(v, {
                    turnOrder:   idx + 1,
                    isNextTurn:  idx === 0,
                    iHoldOne:    false
                });
            }).join('');
            return;
        }

        /* Regular user — show only what is relevant to them */
        var toShow = [];

        /* 1. If user holds a vehicle right now, always show it */
        if (myHeldVehicle) {
            toShow.push({ v: myHeldVehicle, isNextTurn: false, iHoldOne: true });
        }

        /* 2. Show the next-in-turn vehicle (sortedPool[0])
              — but only if it is available (not checked out by someone else)
              — skip if it's already shown above (user holds it) */
        var nextTurn = sortedPool[0];
        if (nextTurn && isAvail(nextTurn) && (!myHeldVehicle || myHeldVehicle.vehicle_code !== nextTurn.vehicle_code)) {
            toShow.push({ v: nextTurn, isNextTurn: true, iHoldOne: iHoldOne });
        }

        /* 3. If nextTurn is checked out by someone else, tell user to wait */
        if (!toShow.length && sortedPool.length > 0) {
            var who = (userMap[nextTurn.last_holder] || {}).name || nextTurn.last_holder || '?';
            container.innerHTML = '<div class="mv-empty-state"><div class="empty-icon">🔄</div>'
                + '<p>' + t('rotation_in_use','The next rotation vehicle is currently checked out. Please wait.') + '</p>'
                + '<p class="mv-debug-note">(' + esc(nextTurn.vehicle_code) + ' — ' + esc(who) + ')</p></div>';
            return;
        }

        container.innerHTML = toShow.map(function(item) {
            return buildRotationCard(item.v, {
                isNextTurn: item.isNextTurn,
                iHoldOne:   item.iHoldOne
            });
        }).join('');
    }

    /* ── Build rotation card ────────────────────────────────────────────
       iHoldOne = current user already holds a rotation vehicle
                  → disable pickup button with reason
       ─────────────────────────────────────────────────────────────────── */
    function buildRotationCard(v, opts) {
        opts = opts || {};
        var available   = isAvail(v);
        var operational = v.status === 'operational';
        var isNextTurn  = !!opts.isNextTurn;
        /*
         * isHeldByMe: vehicle is checked out AND the holder is this user.
         * We check every field the API might use for the current holder.
         * is_my_current is intentionally NOT used — it may be set by the
         * server based on incomplete logic and could show Return to everyone.
         */
        var myEmpId    = String(currentUser.emp_id || '').trim();
        var holderField = String(v.last_holder || v.performed_by || v.held_by || '').trim();
        var isHeldByMe = !available && holderField !== '' && holderField === myEmpId;
        var iHoldOne    = !!opts.iHoldOne;

        var cardClass = 'mv-v-card';
        if (isNextTurn && available) cardClass += ' next-turn';
        if (isHeldByMe)              cardClass += ' my-current';

        var html = '<div class="' + cardClass + '">';

        /* Head */
        html += '<div class="mv-v-card-head">';
        if (opts.turnOrder) {
            html += '<span>'
                  + '<span class="mv-order-badge">' + opts.turnOrder + '</span>'
                  + '<span class="mv-v-code">' + esc(v.vehicle_code) + '</span>';
            if (isNextTurn && available) html += '<span class="mv-next-label">' + t('next_turn','Next Turn') + '</span>';
            if (isHeldByMe)             html += '<span class="mv-my-label">' + t('my_vehicle','Mine') + '</span>';
            html += '</span>';
        } else {
            html += '<span class="mv-v-code">' + esc(v.vehicle_code) + '</span>';
            if (isNextTurn && available) html += '<span class="mv-next-label">' + t('next_turn','Next Turn') + '</span>';
            if (isHeldByMe)             html += '<span class="mv-my-label">' + t('my_vehicle','Mine') + '</span>';
        }
        html += availBadge(available);
        html += '</div>';

        /* Body */
        html += '<div class="mv-v-type">' + esc(v.type || '—') + '</div>';
        html += statusBadge(v.status);
        html += ' <span class="mv-v-mode-badge rotation">' + t('rotation','Rotation') + '</span>';
        if (v.vehicle_category) html += '<div class="mv-v-detail"><span>🚗</span> ' + esc(v.vehicle_category) + '</div>';
        html += '<div class="mv-v-detail"><span>👤</span> ' + esc(v.driver_name || '—') + '</div>';
        if (v.gender) html += '<div class="mv-v-detail"><span>' + (v.gender==='men'?'👨':'👩') + '</span> '
                             + t(v.gender, v.gender) + '</div>';
        if (v.manufacture_year) html += '<div class="mv-v-detail"><span>📅</span> ' + esc(v.manufacture_year) + '</div>';

        /* Holder info */
        if (!available && v.last_holder) {
            var hi   = userMap[v.last_holder] || {};
            var name = hi.name || v.last_holder;
            html += '<div class="mv-holder-info">';
            html += '<div class="mv-v-detail" style="margin-top:0"><span>👤</span> '
                  + t('held_by','Held by') + ': <strong>' + esc(name) + '</strong></div>';
            if (v.movement_datetime) html += '<div class="mv-v-detail"><span>🕐</span> ' + esc(formatDT(v.movement_datetime)) + '</div>';
            html += '</div>';
        }

        /* Actions */
        html += '<div class="mv-v-actions">';

        if (isAdmin) {
            if (available && operational) {
                html += '<button class="mv-btn-pickup" onclick="MyVehiclesFragment.pickup(\'' + esc(v.vehicle_code) + '\')">'
                      + '<span>🚗</span> ' + t('pickup','Pickup') + '</button>';
            }
            if (!available) {
                html += '<button class="mv-btn-return" onclick="MyVehiclesFragment.returnVehicle(\'' + esc(v.vehicle_code) + '\')">'
                      + '<span>↩️</span> ' + t('return','Return') + '</button>';
            }
        } else {
            /* Regular user pickup: only next-turn + available + not already holding one */
            if (isNextTurn && available && operational) {
                if (iHoldOne) {
                    /* Already holding — show disabled button */
                    html += '<button class="mv-btn-pickup" disabled title="'
                          + esc(t('return_first','Return your current vehicle first')) + '">'
                          + '<span>🚗</span> ' + t('pickup','Pickup') + '</button>';
                    html += '<div class="mv-blocked-msg"><span>⚠️</span>'
                          + t('return_first','Please return your current vehicle before picking up a new one')
                          + '</div>';
                } else {
                    html += '<button class="mv-btn-pickup" onclick="MyVehiclesFragment.pickup(\'' + esc(v.vehicle_code) + '\')">'
                          + '<span>🚗</span> ' + t('pickup','Pickup') + '</button>';
                }
            }

            /* Return button: regular users cannot return rotation vehicles from this page */
        }

        html += '</div></div>';
        return html;
    }

    /* ── Error state ── */
    function renderError(id) {
        var c = el(id);
        if (!c) return;
        c.innerHTML = '<div class="mv-empty-state"><div class="empty-icon">⚠️</div>'
            + '<p>' + t('error_loading','An error occurred') + '</p>'
            + '<button class="mv-btn-pickup" onclick="MyVehiclesFragment.reload()" style="margin-top:12px">'
            + t('retry','Retry') + '</button></div>';
    }

    /* ── Pickup ── */
    async function pickup(vehicleCode) {
        if (!confirm(t('confirm_pickup','Pick up vehicle') + ' ' + vehicleCode + '?')) return;
        try {
            await API.post('/vehicles/self-service', { vehicle_code: vehicleCode, operation_type: 'pickup' });
            UI.showToast(t('vehicle_picked_up','Vehicle picked up successfully'), 'success');
            loadMyVehicles();
        } catch(e) { UI.showToast((e && e.message) || t('error','Error'), 'error'); }
    }

    /* ── Return ── */
    async function returnVehicle(vehicleCode) {
        if (!confirm(t('confirm_return','Return vehicle') + ' ' + vehicleCode + '?')) return;
        try {
            await API.post('/vehicles/self-service', { vehicle_code: vehicleCode, operation_type: 'return' });
            UI.showToast(t('vehicle_returned_success','Vehicle returned successfully'), 'success');
            loadMyVehicles();
        } catch(e) { UI.showToast((e && e.message) || t('error','Error'), 'error'); }
    }

    window.MyVehiclesFragment = { pickup: pickup, returnVehicle: returnVehicle, reload: loadMyVehicles };

    /* ── Init ── */
    (function init() {
        if (window.__pageDenied) return;
        var user = Auth.getUser();
        if (!user) {
            if (++_initAttempts > 50) { renderError('mvPrivateGrid'); renderError('mvRotationGrid'); return; }
            setTimeout(init, 100);
            return;
        }
        currentUser = user;
        var perms   = user.permissions || [];
        isAdmin     = perms.includes('manage_movements') || perms.includes('*');

        applyLang();
        loadMyVehicles();
    })();

})();
</script>
<?php $pageScripts = ob_get_clean(); ?>
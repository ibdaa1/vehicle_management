<?php
/**
 * Maintenance Fragment — Vehicle Maintenance Records
 * Full CRUD inside the dashboard.
 * FIX: translateStatic() uses safe setter — no more "Cannot set properties of null"
 */
?>
<style>
/* Fix LTR layout flash */
html[dir="ltr"] body{direction:ltr;font-family:var(--font-en)}
html[dir="ltr"] .app-sidebar{right:auto;left:0}
html[dir="ltr"] .app-main{margin-right:0;margin-left:var(--sidebar-width)}
html[dir="ltr"] .app-footer{margin-right:0;margin-left:var(--sidebar-width)}
html[dir="ltr"] .app-sidebar.collapsed~.app-main{margin-right:0;margin-left:var(--sidebar-collapsed-width)}
.mt-stats{display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap}
.mt-stat{flex:1;min-width:140px;background:var(--bg-card,#fff);border-radius:12px;padding:16px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.mt-stat .num{font-size:1.8rem;font-weight:700;color:var(--primary-main,#1a5276)}
.mt-stat .lbl{font-size:.85rem;color:var(--text-secondary,#666);margin-top:4px}
.mt-stat.routine .num{color:#007bff}
.mt-stat.emergency .num{color:#dc3545}
.mt-stat.overdue .num{color:#ff9800}
.mt-toolbar{display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;align-items:center}
.mt-toolbar .search-box{flex:1;min-width:200px;position:relative}
.mt-toolbar .search-box input{width:100%;padding:10px 12px;padding-inline-end:36px;border:1px solid var(--border-default,#ddd);border-radius:8px;font-size:.95rem}
.mt-toolbar .search-box .ico{position:absolute;inset-inline-end:12px;top:50%;transform:translateY(-50%);color:#999}
.mt-toolbar select{padding:10px;border:1px solid var(--border-default,#ddd);border-radius:8px;font-size:.9rem}
.mt-toolbar .btn-add{margin-inline-start:auto}
.mt-table{width:100%;border-collapse:separate;border-spacing:0;background:var(--bg-card,#fff);border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.mt-table th{background:var(--primary-dark,#1a5276);color:#fff;padding:12px 14px;font-size:.85rem;white-space:nowrap}
.mt-table td{padding:10px 14px;border-bottom:1px solid var(--border-default,#eee);font-size:.9rem}
.mt-table tr:hover td{background:rgba(26,82,118,.04)}
.mt-badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.8rem;font-weight:600}
.mt-badge.routine{background:#cce5ff;color:#004085}
.mt-badge.emergency{background:#f8d7da;color:#721c24}
.mt-badge.technical{background:#d4edda;color:#155724}
.mt-badge.overdue{background:#fff3cd;color:#856404}
.mt-actions button{background:none;border:none;cursor:pointer;font-size:1.1rem;padding:4px}
.mt-empty{text-align:center;padding:60px 20px;color:#999}
.mt-empty .ico{font-size:3rem;margin-bottom:12px}
.mt-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center}
.mt-modal-bg.show{display:flex}
.mt-modal{background:var(--bg-card,#fff);border-radius:16px;width:95%;max-width:640px;max-height:90vh;overflow-y:auto;padding:0}
.mt-modal .modal-hd{display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid var(--border-default,#eee);position:sticky;top:0;background:var(--bg-card,#fff);z-index:1}
.mt-modal .modal-hd h3{margin:0;font-size:1.1rem}
.mt-modal .modal-hd .close{background:none;border:none;font-size:1.5rem;cursor:pointer;color:#999}
.mt-modal .modal-bd{padding:20px}
.mt-form .fg{margin-bottom:16px}
.mt-form label{display:block;font-weight:600;font-size:.85rem;margin-bottom:6px;color:var(--text-secondary,#555)}
.mt-form input,.mt-form select,.mt-form textarea{width:100%;padding:10px 12px;border:1px solid var(--border-default,#ddd);border-radius:8px;font-size:.95rem;box-sizing:border-box}
.mt-form textarea{resize:vertical;min-height:70px}
.mt-form .row2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.mt-form .modal-ft{display:flex;gap:12px;padding:16px 20px;border-top:1px solid var(--border-default,#eee);justify-content:flex-end;position:sticky;bottom:0;background:var(--bg-card,#fff)}
.mt-detail .d-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border-default,#f0f0f0)}
.mt-detail .d-row .d-lbl{color:var(--text-secondary,#777);font-size:.85rem}
.mt-detail .d-row .d-val{font-weight:600}
/* Skeleton */
.skel-row td{padding:10px 14px}
.skel-cell{height:14px;border-radius:6px;background:linear-gradient(90deg,#e8e8e8 25%,#f5f5f5 50%,#e8e8e8 75%);background-size:200% 100%;animation:mt-shimmer 1.5s infinite}
@keyframes mt-shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
</style>

<div class="page-header"><h2 id="mtPageTitle">Maintenance</h2></div>

<!-- Stats -->
<div class="mt-stats">
    <div class="mt-stat"><div class="num" id="mtStatTotal">0</div><div class="lbl" id="mtLblTotal">Total Records</div></div>
    <div class="mt-stat routine"><div class="num" id="mtStatRoutine">0</div><div class="lbl" id="mtLblRoutine">Routine Maintenance</div></div>
    <div class="mt-stat emergency"><div class="num" id="mtStatEmergency">0</div><div class="lbl" id="mtLblEmergency">Emergency</div></div>
    <div class="mt-stat overdue"><div class="num" id="mtStatOverdue">0</div><div class="lbl" id="mtLblOverdue">Overdue</div></div>
</div>

<!-- Toolbar -->
<div class="mt-toolbar">
    <div class="search-box">
        <span class="ico">🔍</span>
        <input type="text" id="mtSearch" placeholder="Search maintenance...">
    </div>
    <select id="mtFilterType">
        <option value=""  id="mtOptAllTypes">All Types</option>
        <option value="Routine"        id="mtOptRoutine">Routine Maintenance</option>
        <option value="Emergency"      id="mtOptEmergency">Emergency</option>
        <option value="Technical Check" id="mtOptTechnical">Technical Check</option>
        <option value="Mechanical"     id="mtOptMechanical">Mechanical</option>
    </select>
    <button class="btn btn-primary btn-sm btn-add" id="mtBtnAdd">➕ <span id="mtBtnAddText">Add Record</span></button>
</div>

<!-- Table -->
<div class="table-responsive">
<table class="mt-table data-table">
    <thead><tr>
        <th>#</th>
        <th id="mtThVehicle">Vehicle Code</th>
        <th id="mtThVisitDate">Visit Date</th>
        <th id="mtThNextVisit">Next Visit Date</th>
        <th id="mtThType">Maintenance Type</th>
        <th id="mtThLocation">Location</th>
        <th id="mtThCreatedBy">Created By</th>
        <th id="mtThNotes">Notes</th>
        <th id="mtThActions">Actions</th>
    </tr></thead>
    <tbody id="mtTableBody"></tbody>
</table>
</div>

<!-- Add/Edit Modal -->
<div class="mt-modal-bg" id="mtModal">
    <div class="mt-modal">
        <div class="modal-hd">
            <h3 id="mtModalTitle">➕ Add Maintenance Record</h3>
            <button class="close" id="mtModalClose">&times;</button>
        </div>
        <div class="modal-bd">
            <form class="mt-form" id="mtForm" onsubmit="return false">
                <input type="hidden" id="mtId">
                <div class="row2">
                    <div class="fg">
                        <label id="mtLblVehicleCode">Vehicle Code *</label>
                        <input type="text" id="mtVehicleCode" required placeholder="e.g.: SHJ-1234">
                    </div>
                    <div class="fg">
                        <label id="mtLblType">Maintenance Type</label>
                        <select id="mtType">
                            <option value="Routine"         id="mtFormOptRoutine">Routine Maintenance</option>
                            <option value="Emergency"       id="mtFormOptEmergency">Emergency</option>
                            <option value="Technical Check" id="mtFormOptTechnical">Technical Check</option>
                            <option value="Mechanical"      id="mtFormOptMechanical">Mechanical</option>
                        </select>
                    </div>
                </div>
                <div class="row2">
                    <div class="fg">
                        <label id="mtLblVisitDate">Visit Date *</label>
                        <input type="date" id="mtVisitDate" required>
                    </div>
                    <div class="fg">
                        <label id="mtLblNextVisit">Next Visit Date</label>
                        <input type="date" id="mtNextVisitDate">
                    </div>
                </div>
                <div class="fg">
                    <label id="mtLblLocation">Location</label>
                    <input type="text" id="mtLocation" placeholder="Maintenance location">
                </div>
                <div class="fg">
                    <label id="mtLblNotes">Notes</label>
                    <textarea id="mtNotes" rows="3" placeholder="Add additional notes..."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-ft">
            <button class="btn btn-ghost"   id="mtCancelBtn">Cancel</button>
            <button class="btn btn-primary" id="mtSaveBtn">💾 Save</button>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div class="mt-modal-bg" id="mtDetailModal">
    <div class="mt-modal">
        <div class="modal-hd">
            <h3 id="mtDetailTitle">Maintenance Details</h3>
            <button class="close" id="mtDetailClose">&times;</button>
        </div>
        <div class="modal-bd mt-detail" id="mtDetailBody"></div>
    </div>
</div>

<?php ob_start(); ?>
<script>
(function(){
    'use strict';

    const $ = id => document.getElementById(id);
    const esc = s => { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; };

    let allRecords = [], filteredRecords = [];
    let mtCanEdit = false, mtCanDelete = false;

    /* ─────────────────────────────────────────────
       SAFE TEXT SETTER
       Prevents "Cannot set properties of null" by
       silently skipping missing elements.
       ───────────────────────────────────────────── */
    function setText(id, value) {
        const el = $(id);
        if (el) el.textContent = value;
    }
    function setPlaceholder(id, value) {
        const el = $(id);
        if (el) el.placeholder = value;
    }

    /* ---- Skeleton loader ---- */
    function showSkeleton() {
        const cell = '<td><div class="skel-cell"></div></td>';
        const row  = '<tr class="skel-row">' + Array(9).fill(cell).join('') + '</tr>';
        $('mtTableBody').innerHTML = Array(5).fill(row).join('');
    }

    /* ---- Type helpers ---- */
    function typeLabel(t) {
        const map = {
            'Routine':        i18n.t('type_routine'),
            'Emergency':      i18n.t('type_emergency'),
            'Technical Check':i18n.t('type_technical'),
            'Mechanical':     i18n.t('type_mechanical')
        };
        return map[t] || t || '—';
    }

    function typeBadge(t) {
        const clsMap = { 'Emergency': 'emergency', 'Technical Check': 'technical', 'Mechanical': 'routine' };
        const cls = clsMap[t] || 'routine';
        return '<span class="mt-badge ' + esc(cls) + '">' + esc(typeLabel(t)) + '</span>';
    }

    function isOverdue(d) {
        if (!d) return false;
        return new Date(d) < new Date(new Date().toISOString().slice(0, 10));
    }

    /* ---- Load records ---- */
    async function loadRecords() {
        showSkeleton();
        try {
            const res = await API.get('/maintenance');
            allRecords = (res.data || res) || [];
        } catch(e) {
            allRecords = [];
            UI.showToast(i18n.t('load_failed') || 'Failed to load', 'error');
        }
        applyFilters();
        loadStats();
    }

    async function loadStats() {
        try {
            const res = await API.get('/maintenance/stats');
            const s = res.data || res;
            setText('mtStatTotal',    s.total     || 0);
            setText('mtStatRoutine',  s.routine   || 0);
            setText('mtStatEmergency',s.emergency || 0);
            setText('mtStatOverdue',  s.overdue   || 0);
        } catch(e) {}
    }

    function applyFilters() {
        const q  = ($('mtSearch').value || '').toLowerCase();
        const tp = $('mtFilterType').value;
        filteredRecords = allRecords.filter(r => {
            if (tp && r.maintenance_type !== tp) return false;
            if (q && ![r.vehicle_code, r.location, r.notes].some(f => (f || '').toLowerCase().includes(q))) return false;
            return true;
        });
        renderTable();
    }

    function renderTable() {
        const tbody = $('mtTableBody');
        if (!filteredRecords.length) {
            tbody.innerHTML = '<tr><td colspan="9"><div class="mt-empty"><div class="ico">🔧</div><div>' + esc(i18n.t('no_maintenance') || 'No maintenance records') + '</div></div></td></tr>';
            return;
        }
        let html = '';
        filteredRecords.forEach((r, i) => {
            const overdue = isOverdue(r.next_visit_date);
            const nextStyle = overdue ? ' style="color:#dc3545;font-weight:600"' : '';
            const nextVal   = r.next_visit_date
                ? r.next_visit_date + (overdue ? ' ⚠️' : '')
                : (overdue ? '<span class="mt-badge overdue">' + esc(i18n.t('overdue') || 'Overdue') + '</span>' : '-');
            html += '<tr>'
                + '<td>' + (i + 1) + '</td>'
                + '<td><strong>' + esc(r.vehicle_code) + '</strong></td>'
                + '<td>' + esc(r.visit_date || '-') + '</td>'
                + '<td' + nextStyle + '>' + nextVal + '</td>'
                + '<td>' + typeBadge(r.maintenance_type) + '</td>'
                + '<td>' + esc(r.location || '-') + '</td>'
                + '<td>' + esc(r.created_by || '-') + '</td>'
                + '<td>' + esc((r.notes || '').substring(0, 40)) + (r.notes && r.notes.length > 40 ? '…' : '') + '</td>'
                + '<td class="mt-actions">'
                + '<button title="' + esc(i18n.t('view') || 'View') + '" onclick="window.__mtView(' + r.id + ')">👁️</button>';
            if (mtCanEdit)   html += '<button title="' + esc(i18n.t('edit') || 'Edit') + '" onclick="window.__mtEdit(' + r.id + ')">✏️</button>';
            if (mtCanDelete) html += '<button title="' + esc(i18n.t('delete') || 'Delete') + '" onclick="window.__mtDelete(' + r.id + ')" style="color:var(--status-danger)">🗑️</button>';
            html += '</td></tr>';
        });
        tbody.innerHTML = html;
    }

    /* ---- Modal open/close ---- */
    function openModal(record) {
        setText('mtId', record ? record.id : '');
        const vc = $('mtVehicleCode'); if (vc) vc.value = record ? record.vehicle_code : '';
        const ty = $('mtType');        if (ty) ty.value = record ? (record.maintenance_type || 'Routine') : 'Routine';
        const vd = $('mtVisitDate');   if (vd) vd.value = record ? (record.visit_date || '') : '';
        const nv = $('mtNextVisitDate');if (nv) nv.value = record ? (record.next_visit_date || '') : '';
        const lo = $('mtLocation');    if (lo) lo.value = record ? (record.location || '') : '';
        const no = $('mtNotes');       if (no) no.value = record ? (record.notes || '') : '';
        setText('mtModalTitle', record
            ? ('✏️ ' + (i18n.t('edit_maintenance') || 'Edit Record'))
            : ('➕ ' + (i18n.t('add_maintenance')  || 'Add Record')));
        const modal = $('mtModal');
        if (modal) modal.classList.add('show');
    }

    function closeModal()  { const m = $('mtModal');       if (m) m.classList.remove('show'); }
    function closeDetail() { const m = $('mtDetailModal'); if (m) m.classList.remove('show'); }

    /* ---- Save ---- */
    async function saveRecord() {
        const idEl = $('mtId');
        const id   = idEl ? idEl.value : '';
        const data = {
            vehicle_code:    ($('mtVehicleCode')    || {}).value?.trim()  || '',
            visit_date:      ($('mtVisitDate')       || {}).value          || '',
            next_visit_date: ($('mtNextVisitDate')   || {}).value          || null,
            maintenance_type:($('mtType')            || {}).value          || 'Routine',
            location:        ($('mtLocation')        || {}).value?.trim()  || '',
            notes:           ($('mtNotes')           || {}).value?.trim()  || ''
        };
        if (!data.vehicle_code || !data.visit_date) {
            UI.showToast(i18n.t('required_fields') || 'Please fill required fields', 'warning');
            return;
        }
        try {
            if (id) {
                await API.put('/maintenance/' + id, data);
            } else {
                await API.post('/maintenance', data);
            }
            UI.showToast(i18n.t('success') || 'Saved successfully', 'success');
            closeModal();
            loadRecords();
        } catch(e) {
            UI.showToast(e.message || i18n.t('error') || 'Error', 'error');
        }
    }

    /* ---- Global action handlers ---- */
    window.__mtView = async function(id) {
        try {
            const res = await API.get('/maintenance/' + id);
            const r   = res.data || res;
            const overdue = isOverdue(r.next_visit_date);
            let html = '';
            const row = (lbl, val) => '<div class="d-row"><span class="d-lbl">' + esc(lbl) + '</span><span class="d-val">' + val + '</span></div>';
            html += row(i18n.t('vehicle_code')    || 'Vehicle Code',    esc(r.vehicle_code));
            if (r.vehicle_type) html += row(i18n.t('vehicle_type') || 'Vehicle Type', esc(r.vehicle_type));
            html += row(i18n.t('visit_date')      || 'Visit Date',      esc(r.visit_date || '-'));
            html += row(i18n.t('next_visit')      || 'Next Visit',
                '<span' + (overdue ? ' style="color:#dc3545"' : '') + '>' + esc(r.next_visit_date || '-') + (overdue ? ' ⚠️' : '') + '</span>');
            html += row(i18n.t('maintenance_type')|| 'Type',            typeBadge(r.maintenance_type));
            html += row(i18n.t('location')        || 'Location',        esc(r.location || '-'));
            html += row(i18n.t('created_by')      || 'Created By',      esc(r.created_by || '-'));
            html += row(i18n.t('notes')           || 'Notes',           esc(r.notes || '-'));
            html += row(i18n.t('created_at')      || 'Created At',      esc(r.created_at || '-'));
            if (r.updated_at) html += row(i18n.t('updated_at') || 'Updated At', esc(r.updated_at));
            const body  = $('mtDetailBody');  if (body)  body.innerHTML = html;
            const title = $('mtDetailTitle'); if (title) title.textContent = i18n.t('maintenance_details') || 'Maintenance Details';
            const modal = $('mtDetailModal'); if (modal) modal.classList.add('show');
        } catch(e) {
            UI.showToast(e.message || i18n.t('error') || 'Error', 'error');
        }
    };

    window.__mtEdit = async function(id) {
        try {
            const res = await API.get('/maintenance/' + id);
            openModal(res.data || res);
        } catch(e) {
            UI.showToast(e.message || i18n.t('error') || 'Error', 'error');
        }
    };

    window.__mtDelete = async function(id) {
        if (!confirm(i18n.t('confirm_delete') || 'Delete this record?')) return;
        try {
            await API.del('/maintenance/' + id);
            UI.showToast(i18n.t('success') || 'Deleted', 'success');
            loadRecords();
        } catch(e) {
            UI.showToast(e.message || i18n.t('error') || 'Error', 'error');
        }
    };

    /* ---- Event listeners ---- */
    function bind(id, evt, fn) { const el = $(id); if (el) el.addEventListener(evt, fn); }

    bind('mtBtnAdd',     'click',  () => openModal(null));
    bind('mtModalClose', 'click',  closeModal);
    bind('mtCancelBtn',  'click',  closeModal);
    bind('mtSaveBtn',    'click',  saveRecord);
    bind('mtDetailClose','click',  closeDetail);
    bind('mtSearch',     'input',  applyFilters);
    bind('mtFilterType', 'change', applyFilters);

    const mtModal = $('mtModal');
    if (mtModal) mtModal.addEventListener('click', e => { if (e.target === mtModal) closeModal(); });
    const mtDM = $('mtDetailModal');
    if (mtDM) mtDM.addEventListener('click', e => { if (e.target === mtDM) closeDetail(); });

    /* ─────────────────────────────────────────────
       TRANSLATE STATIC LABELS
       Uses setText() which silently skips null —
       this is what fixes the TypeError on line 208.
       ───────────────────────────────────────────── */
    function translateStatic() {
        if (!i18n.strings || !Object.keys(i18n.strings).length) {
            setTimeout(translateStatic, 100);
            return;
        }
        const t = k => i18n.t(k) || k;

        /* Page header */
        setText('mtPageTitle',   t('maintenance'));

        /* Stats labels */
        setText('mtLblTotal',    t('maintenance_records'));
        setText('mtLblRoutine',  t('type_routine'));
        setText('mtLblEmergency',t('type_emergency'));
        setText('mtLblOverdue',  t('overdue'));

        /* Toolbar */
        setPlaceholder('mtSearch', t('search_maintenance'));
        setText('mtBtnAddText',  t('add_maintenance'));

        /* Filter dropdown */
        setText('mtOptAllTypes', t('all_maintenance_types'));
        setText('mtOptRoutine',  t('type_routine'));
        setText('mtOptEmergency',t('type_emergency'));
        setText('mtOptTechnical',t('type_technical'));
        setText('mtOptMechanical',t('type_mechanical'));

        /* Table headers */
        setText('mtThVehicle',   t('vehicle_code'));
        setText('mtThVisitDate', t('visit_date'));
        setText('mtThNextVisit', t('next_visit'));
        setText('mtThType',      t('maintenance_type'));
        setText('mtThLocation',  t('location'));
        setText('mtThCreatedBy', t('created_by'));
        setText('mtThNotes',     t('notes'));
        setText('mtThActions',   t('actions'));

        /* Form labels */
        setText('mtLblVehicleCode', t('vehicle_code') + ' *');
        setText('mtLblType',        t('maintenance_type'));
        setText('mtLblVisitDate',   t('visit_date') + ' *');
        setText('mtLblNextVisit',   t('next_visit'));
        setText('mtLblLocation',    t('location'));
        setText('mtLblNotes',       t('notes'));

        /* Form select options */
        setText('mtFormOptRoutine',   t('type_routine'));
        setText('mtFormOptEmergency', t('type_emergency'));
        setText('mtFormOptTechnical', t('type_technical'));
        setText('mtFormOptMechanical',t('type_mechanical'));

        /* Modal / buttons */
        setText('mtCancelBtn',   t('cancel'));
        setText('mtSaveBtn',     '💾 ' + t('save'));
        setText('mtDetailTitle', t('maintenance_details'));
    }

    /* ---- Init ---- */
    (function init() {
        if (window.__pageDenied) return;
        const user = Auth.getUser();
        if (!user) { setTimeout(init, 100); return; }

        const perms = user.permissions || [];
        const isAdmin = perms.includes('manage_maintenance') || perms.includes('*');
        mtCanEdit   = isAdmin;
        mtCanDelete = isAdmin;

        if (!isAdmin) {
            const ab = $('mtBtnAdd');
            if (ab) ab.style.display = 'none';
        }

        translateStatic();
        loadRecords();
    })();
})();
</script>
<?php $pageScripts = ob_get_clean(); ?>
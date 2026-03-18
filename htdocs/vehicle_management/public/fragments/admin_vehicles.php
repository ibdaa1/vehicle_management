<?php
/**
 * Admin Vehicles Fragment — Super Admin / Admin Vehicle Management
 * Loaded inside dashboard.php shell.
 * Shows ALL vehicles separated by type:
 *   - Private vehicles
 *   - Shift vehicles
 *   - Department/Rotation vehicles
 * Admin can pickup and return any vehicle.
 * Requires: manage_movements permission.
 */
?>
<style>
/* Fix LTR layout flash: html[dir] is set before CSS renders, body[dir] after */
html[dir="ltr"] body{direction:ltr;font-family:var(--font-en)}
html[dir="ltr"] .app-sidebar{right:auto;left:0}
html[dir="ltr"] .app-main{margin-right:0;margin-left:var(--sidebar-width)}
html[dir="ltr"] .app-footer{margin-right:0;margin-left:var(--sidebar-width)}
html[dir="ltr"] .app-sidebar.collapsed~.app-main{margin-right:0;margin-left:var(--sidebar-collapsed-width)}
/* === Admin Vehicles Fragment Styles === */
.av-section-title{font-size:1.1rem;font-weight:700;color:var(--text-primary);margin-bottom:16px;display:flex;align-items:center;gap:8px}
.av-section-card{background:var(--bg-card);border-radius:12px;box-shadow:var(--card-shadow);border:1px solid var(--border-default);padding:20px;margin-bottom:28px}
.av-vehicles-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px}
.av-v-card{background:var(--bg-card);border-radius:12px;padding:18px;box-shadow:var(--card-shadow);border:1px solid var(--border-default);transition:transform .25s,box-shadow .25s}
.av-v-card:hover{transform:translateY(-3px);box-shadow:0 6px 20px rgba(0,0,0,.1)}
.av-v-card-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
.av-v-code{font-size:1.1rem;font-weight:700;color:var(--text-primary)}
.av-v-badge{padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:600;color:#fff}
.av-v-badge.available{background:var(--status-success)}
.av-v-badge.checked_out{background:var(--status-danger)}
.av-v-badge.operational{background:var(--status-success)}
.av-v-badge.maintenance{background:var(--status-warning);color:#1a1a2e}
.av-v-badge.out_of_service{background:var(--status-danger)}
.av-v-type{font-size:.9rem;color:var(--text-primary);margin-bottom:8px;font-weight:500}
.av-v-detail{font-size:.8rem;color:var(--text-secondary);display:flex;align-items:center;gap:6px;margin-top:4px}
.av-v-detail .icon{font-size:.9rem}
.av-v-mode-badge{display:inline-block;padding:2px 8px;border-radius:4px;font-size:.75rem;font-weight:500;margin-top:8px}
.av-v-mode-badge.private{background:rgba(212,175,55,.15);color:#a88a1e}
.av-v-mode-badge.shift{background:rgba(23,162,184,.12);color:#17a2b8}
.av-v-mode-badge.dept{background:rgba(111,66,193,.12);color:#6f42c1}
.av-v-actions{margin-top:14px;display:flex;gap:8px}
.av-v-actions .btn{padding:8px 16px;font-size:.85rem;border-radius:8px;border:none;cursor:pointer;font-weight:600;display:inline-flex;align-items:center;gap:6px;transition:background .2s}
.av-btn-pickup{background:var(--primary-main);color:var(--text-light)}
.av-btn-pickup:hover{opacity:.9}
.av-btn-pickup:disabled{opacity:.5;cursor:not-allowed}
.av-btn-return{background:var(--status-warning);color:#1a1a2e}
.av-btn-return:hover{opacity:.9}
.av-empty-state{text-align:center;padding:40px 24px;color:var(--text-secondary)}
.av-empty-state .empty-icon{font-size:2.5rem;margin-bottom:10px;opacity:.5}
.av-info-banner{background:rgba(23,162,184,.08);border:1px solid rgba(23,162,184,.2);border-radius:10px;padding:14px 20px;margin-bottom:20px;color:var(--text-primary);font-size:.9rem;display:flex;align-items:center;gap:10px}
.av-info-banner .icon{font-size:1.3rem}
.av-order-badge{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;background:var(--primary-main);color:var(--text-light);font-weight:700;font-size:.85rem;margin-inline-end:8px}
.av-v-card.next-turn{border:2px solid var(--primary-main);box-shadow:0 0 12px rgba(var(--primary-main-rgb,59,130,246),.25)}
.av-next-label{display:inline-block;padding:2px 8px;border-radius:4px;font-size:.72rem;font-weight:600;background:var(--primary-main);color:var(--text-light);margin-inline-start:6px}
.av-v-card.checked-out{border:2px solid var(--status-danger);box-shadow:0 0 8px rgba(220,53,69,.15)}
.av-btn-details{padding:8px 16px;font-size:.85rem;border-radius:8px;border:1px solid rgba(111,66,193,.3);cursor:pointer;font-weight:600;display:inline-flex;align-items:center;gap:6px;transition:background .2s;background:rgba(111,66,193,.12);color:#6f42c1}
.av-btn-details:hover{background:rgba(111,66,193,.22)}
.av-modal-overlay{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px}
.av-modal-content{background:var(--bg-card);border-radius:16px;width:90%;max-width:600px;max-height:85vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.3)}
.av-modal-header{display:flex;justify-content:space-between;align-items:center;padding:20px 24px;border-bottom:1px solid var(--border-default)}
.av-modal-header h3{margin:0;font-size:1.1rem;color:var(--text-primary)}
.av-modal-close{border:none;background:none;font-size:1.5rem;cursor:pointer;color:var(--text-secondary);padding:0 4px;line-height:1}
.av-modal-close:hover{color:var(--status-danger)}
.av-modal-body{padding:24px}
.av-detail-section{margin-bottom:20px}
.av-detail-section-title{font-size:.95rem;font-weight:700;color:var(--text-primary);margin-bottom:10px;display:flex;align-items:center;gap:6px}
.av-detail-row{display:flex;align-items:center;gap:8px;padding:8px 0;font-size:.85rem;color:var(--text-secondary);border-bottom:1px solid rgba(200,200,200,.2)}
.av-detail-row .dlabel{font-weight:600;color:var(--text-primary);min-width:130px}
.av-detail-photos{display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:10px;margin-top:10px}
.av-detail-photos img{width:100%;border-radius:8px;object-fit:cover;aspect-ratio:1;cursor:pointer;border:1px solid var(--border-default)}
.av-detail-photos img:hover{opacity:.85}
.av-rotation-banner{background:rgba(111,66,193,.06);border:1px solid rgba(111,66,193,.15);border-radius:10px;padding:12px 18px;margin-bottom:16px;font-size:.85rem;color:var(--text-primary);display:flex;align-items:center;gap:10px}
.av-holder-info{background:rgba(220,53,69,.06);border-radius:8px;padding:8px 12px;margin-top:8px;font-size:.8rem}
.av-stats-bar{display:flex;flex-wrap:wrap;gap:16px;margin-bottom:24px}
.av-stat-card{background:var(--bg-card);border-radius:10px;padding:14px 20px;box-shadow:var(--card-shadow);border:1px solid var(--border-default);display:flex;align-items:center;gap:12px;min-width:160px;flex:1}
.av-stat-icon{font-size:1.5rem}
.av-stat-info{display:flex;flex-direction:column}
.av-stat-value{font-size:1.3rem;font-weight:700;color:var(--text-primary)}
.av-stat-label{font-size:.78rem;color:var(--text-secondary)}
.av-section-count{font-size:.85rem;color:var(--text-secondary);font-weight:400;margin-inline-start:8px}
.av-filter-bar{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:10px;align-items:center}
.av-filter-bar select,.av-filter-bar input{padding:8px 12px;border:1px solid var(--border-default);border-radius:8px;background:var(--bg-card);color:var(--text-primary);font-size:.85rem}
.av-filter-bar select{min-width:140px}
.av-filter-bar input[type="text"]{min-width:200px}
.av-filter-row2{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:18px;align-items:center}
.av-filter-row2 select{padding:8px 12px;border:1px solid var(--border-default);border-radius:8px;background:var(--bg-card);color:var(--text-primary);font-size:.85rem;min-width:140px}
.av-dept-group-title{font-size:.95rem;font-weight:700;color:var(--text-primary);margin:16px 0 10px;display:flex;align-items:center;gap:8px;padding:6px 12px;background:rgba(var(--primary-main-rgb,59,130,246),.06);border-radius:8px;border-inline-start:3px solid var(--primary-main)}
.av-btn-clear-filters{padding:8px 16px;border:1px solid var(--status-danger);border-radius:8px;background:rgba(220,53,69,.08);color:var(--status-danger);font-size:.85rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:background .2s}
.av-btn-clear-filters:hover{background:rgba(220,53,69,.18)}
.av-view-toggle{display:flex;gap:0;margin-bottom:20px;border:1px solid var(--border-default);border-radius:10px;overflow:hidden;width:fit-content}
.av-toggle-btn{padding:10px 24px;border:none;background:var(--bg-card);color:var(--text-secondary);font-size:.9rem;font-weight:600;cursor:pointer;transition:background .2s,color .2s}
.av-toggle-btn.active{background:var(--primary-main);color:var(--text-light)}
.av-toggle-btn:hover:not(.active){background:rgba(var(--primary-main-rgb,59,130,246),.08)}
@media(max-width:768px){
    .av-vehicles-grid{grid-template-columns:1fr}
    .av-stats-bar{flex-direction:column}
    .av-filter-bar,.av-filter-row2{flex-direction:column;align-items:stretch}
    .av-view-toggle{width:100%}
    .av-toggle-btn{flex:1;text-align:center}
}
</style>

<div class="page-header">
    <h2 id="avPageTitle" data-label-ar="إدارة جميع المركبات" data-label-en="All Vehicles Management">All Vehicles Management</h2>
</div>

<!-- Info Banner -->
<div class="av-info-banner" id="avInfoBanner">
    <span class="icon">🔑</span>
    <span id="avInfoText" data-label-ar="صفحة المسؤول - عرض جميع المركبات مصنفة حسب النوع مع إمكانية التسليم والإرجاع" data-label-en="Admin page — All vehicles displayed by type with pickup and return capabilities">Admin page — All vehicles displayed by type with pickup and return capabilities</span>
</div>

<!-- Stats Bar -->
<div class="av-stats-bar" id="avStatsBar">
    <div class="av-stat-card">
        <span class="av-stat-icon">🚗</span>
        <div class="av-stat-info">
            <span class="av-stat-value" id="avStatTotal">—</span>
            <span class="av-stat-label" data-label-ar="إجمالي المركبات" data-label-en="Total Vehicles">Total Vehicles</span>
        </div>
    </div>
    <div class="av-stat-card">
        <span class="av-stat-icon">🔒</span>
        <div class="av-stat-info">
            <span class="av-stat-value" id="avStatPrivate">—</span>
            <span class="av-stat-label" data-label-ar="خاصة" data-label-en="Private">Private</span>
        </div>
    </div>
    <div class="av-stat-card">
        <span class="av-stat-icon">🔄</span>
        <div class="av-stat-info">
            <span class="av-stat-value" id="avStatDept">—</span>
            <span class="av-stat-label" data-label-ar="بالدور" data-label-en="Rotation">Rotation</span>
        </div>
    </div>
    <div class="av-stat-card">
        <span class="av-stat-icon">✅</span>
        <div class="av-stat-info">
            <span class="av-stat-value" id="avStatAvailable">—</span>
            <span class="av-stat-label" data-label-ar="متاحة" data-label-en="Available">Available</span>
        </div>
    </div>
    <div class="av-stat-card">
        <span class="av-stat-icon">🚫</span>
        <div class="av-stat-info">
            <span class="av-stat-value" id="avStatCheckedOut">—</span>
            <span class="av-stat-label" data-label-ar="مستلمة" data-label-en="Checked Out">Checked Out</span>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="av-filter-bar" id="avFilterBar">
    <input type="text" id="avSearchInput" placeholder="Search by code or vehicle type..." data-placeholder-ar="بحث بكود أو نوع المركبة..." data-placeholder-en="Search by code or vehicle type...">
    <select id="avFilterStatus">
        <option value="" data-label-ar="جميع الحالات" data-label-en="All Status">All Statuses</option>
        <option value="operational" data-label-ar="تعمل" data-label-en="Operational">Operational</option>
        <option value="maintenance" data-label-ar="صيانة" data-label-en="Maintenance">Under Maintenance</option>
        <option value="out_of_service" data-label-ar="خارج الخدمة" data-label-en="Out of Service">Out of Service</option>
    </select>
    <select id="avFilterAvailability">
        <option value="" data-label-ar="الكل" data-label-en="All">All</option>
        <option value="available" data-label-ar="متاحة" data-label-en="Available">Available</option>
        <option value="checked_out" data-label-ar="مستلمة" data-label-en="Checked Out">Checked Out</option>
    </select>
</div>

<!-- Advanced Filter Row -->
<div class="av-filter-row2" id="avFilterRow2">
    <select id="avFilterSector">
        <option value="" data-label-ar="كل القطاعات" data-label-en="All Sectors">All Sectors</option>
    </select>
    <select id="avFilterDepartment">
        <option value="" data-label-ar="كل الإدارات" data-label-en="All Departments">All Departments</option>
    </select>
    <select id="avFilterSection">
        <option value="" data-label-ar="كل الأقسام" data-label-en="All Sections">All Sections</option>
    </select>
    <select id="avFilterDivision">
        <option value="" data-label-ar="كل الشعب" data-label-en="All Divisions">All Divisions</option>
    </select>
    <select id="avFilterGender">
        <option value="" data-label-ar="كل الجنس" data-label-en="All Genders">All Genders</option>
        <option value="men" data-label-ar="رجال" data-label-en="Men">Men</option>
        <option value="women" data-label-ar="نساء" data-label-en="Women">Women</option>
    </select>
    <button type="button" class="btn av-btn-clear-filters" id="avClearFilters">
        <span>🗑️</span> <span data-label-ar="إلغاء الفلاتر" data-label-en="Clear Filters">Clear Filters</span>
    </button>
</div>

<!-- View Toggle -->
<div class="av-view-toggle" id="avViewToggle">
    <button type="button" class="av-toggle-btn active" id="avTogglePrivate" data-label-ar="🔒 خاصة" data-label-en="🔒 Private">🔒 Private</button>
    <button type="button" class="av-toggle-btn" id="avToggleShift" data-label-ar="🔄 بالدور" data-label-en="🔄 Rotation">🔄 Rotation</button>
</div>

<!-- ===== PRIVATE VEHICLES SECTION ===== -->
<div class="av-section-card" id="avPrivateSection">
    <div class="av-section-title">
        <span>🔒</span>
        <span id="avPrivateTitle" data-label-ar="المركبات الخاصة" data-label-en="Private Vehicles">Private Vehicles</span>
        <span class="av-section-count" id="avPrivateCount"></span>
    </div>
    <div id="avPrivateGrid" class="av-vehicles-grid">
        <div class="av-empty-state"><div class="spinner spinner-sm"></div><span data-label-ar="جاري التحميل..." data-label-en="Loading...">Loading...</span></div>
    </div>
</div>

<!-- ===== SHIFT VEHICLES SECTION ===== -->
<div class="av-section-card" id="avShiftSection">
    <div class="av-section-title">
        <span>🔄</span>
        <span id="avShiftTitle" data-label-ar="مركبات الورديات" data-label-en="Shift Vehicles">Shift Vehicles</span>
        <span class="av-section-count" id="avShiftCount"></span>
    </div>
    <div id="avShiftGrid" class="av-vehicles-grid">
        <div class="av-empty-state"><div class="spinner spinner-sm"></div><span data-label-ar="جاري التحميل..." data-label-en="Loading...">Loading...</span></div>
    </div>
</div>

<!-- ===== DEPARTMENT VEHICLES SECTION (Rotation) ===== -->
<div class="av-section-card" id="avDeptSection">
    <div class="av-section-title">
        <span>🔄</span>
        <span id="avDeptTitle" data-label-ar="مركبات بالدور" data-label-en="Rotation Vehicles">Rotation Vehicles</span>
        <span class="av-section-count" id="avDeptCount"></span>
    </div>
    <div class="av-rotation-banner" id="avRotationBanner">
        <span>🔁</span>
        <span id="avRotationText" data-label-ar="يتم التسليم بالدور كحلقة — المركبة التي تم تسليمها يأتي دورها بعد تسليم جميع المركبات الأخرى" data-label-en="Round-robin delivery — a vehicle's next turn comes after all other vehicles have been delivered">Round-robin delivery — a vehicle's next turn comes after all other vehicles have been delivered</span>
    </div>
    <div id="avDeptGrid" class="av-vehicles-grid">
        <div class="av-empty-state"><div class="spinner spinner-sm"></div><span data-label-ar="جاري التحميل..." data-label-en="Loading...">Loading...</span></div>
    </div>
</div>

<!-- ===== Vehicle Detail Modal ===== -->
<div id="avDetailModal" class="av-modal-overlay" style="display:none">
    <div class="av-modal-content">
        <div class="av-modal-header">
            <h3 id="avDetailTitle" data-label-ar="تفاصيل المركبة" data-label-en="Vehicle Details">Vehicle Details</h3>
            <button class="av-modal-close" onclick="AdminVehiclesFragment.closeDetails()">&times;</button>
        </div>
        <div class="av-modal-body" id="avDetailBody">
            <div class="av-empty-state"><div class="spinner spinner-sm"></div><span data-label-ar="جاري التحميل..." data-label-en="Loading...">Loading...</span></div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
/* ============================================
   Admin Vehicles Fragment
   For Super Admin / Admin only.
   Shows ALL vehicles categorized:
   - Private
   - Shift
   - Department/Rotation
   Admin can pickup/return any vehicle.
   ============================================ */
(function () {
    'use strict';

    var currentUser = null;
    var allVehiclesData = [];
    var allRefs = { sectors: [], departments: [], sections: [], divisions: [] };
    var userMap = {}; /* emp_id → { name, sector_name } */
    var activeView = 'private'; /* 'private' or 'shift' */

    /* ---------- Helpers ---------- */
    function esc(s){var d=document.createElement('div');d.textContent=s||'';return d.innerHTML;}

    function statusBadge(status) {
        var map = {
            operational:    { cls: 'operational',    key: 'operational' },
            maintenance:    { cls: 'maintenance',    key: 'under_maintenance' },
            out_of_service: { cls: 'out_of_service', key: 'out_of_service' }
        };
        var s = map[status] || map.operational;
        return '<span class="av-v-badge ' + s.cls + '">' + esc(i18n.t(s.key)) + '</span>';
    }

    function availBadge(available) {
        if (available) {
            return '<span class="av-v-badge available">' + esc(i18n.t('available')) + '</span>';
        }
        return '<span class="av-v-badge checked_out">' + esc(i18n.t('checked_out')) + '</span>';
    }

    function modeBadge(mode) {
        if (mode === 'private') {
            return '<span class="av-v-mode-badge private">' + esc(i18n.t('private')) + '</span>';
        }
        return '<span class="av-v-mode-badge dept">' + esc(i18n.t('av_rotation')) + '</span>';
    }

    /* ---------- Load reference data for filters ---------- */
    async function loadReferences() {
        try {
            var res = await API.get('/references');
            allRefs = (res && res.data) || res || { sectors: [], departments: [], sections: [], divisions: [] };
            populateSectorFilter();
            populateDepartmentFilter();
        } catch (e) {
            console.warn('admin_vehicles: Failed to load references', e);
        }
    }

    function populateSectorFilter() {
        var sel = document.getElementById('avFilterSector');
        if (!sel) return;
        var first = '<option value="" data-label-ar="كل القطاعات" data-label-en="All Sectors">' + esc(i18n.t('all_sectors')) + '</option>';
        var isEn = (i18n.lang === 'en');
        var opts = (allRefs.sectors || []).map(function(s) {
            var label = isEn ? (s.name_en || s.name || s.sector_name || '') : (s.name || s.sector_name || '');
            return '<option value="' + esc(String(s.id)) + '">' + esc(label) + '</option>';
        }).join('');
        sel.innerHTML = first + opts;
    }

    function populateDepartmentFilter(sectorId) {
        var sel = document.getElementById('avFilterDepartment');
        if (!sel) return;
        var isEn = (i18n.lang === 'en');
        var first = '<option value="" data-label-ar="كل الإدارات" data-label-en="All Departments">' + esc(i18n.t('all_departments')) + '</option>';
        var depts = allRefs.departments || [];
        /* Departments don't have sector_id; filter via vehicle data when sector is chosen */
        if (sectorId) {
            var deptIdsInSector = {};
            allVehiclesData.forEach(function(v) {
                if (String(v.sector_id || '') === String(sectorId) && v.department_id) {
                    deptIdsInSector[String(v.department_id)] = true;
                }
            });
            depts = depts.filter(function(d) {
                var did = String(d.department_id || d.id || '');
                return deptIdsInSector[did];
            });
        }
        var opts = depts.map(function(d) {
            var did = d.department_id || d.id || '';
            var label = isEn ? (d.name_en || d.name_ar || d.name || '') : (d.name_ar || d.name || d.name_en || '');
            return '<option value="' + esc(String(did)) + '">' + esc(label) + '</option>';
        }).join('');
        sel.innerHTML = first + opts;
        /* Reset cascading */
        populateSectionFilter('');
    }

    function populateSectionFilter(deptId) {
        var sel = document.getElementById('avFilterSection');
        if (!sel) return;
        var isEn = (i18n.lang === 'en');
        var first = '<option value="" data-label-ar="كل الأقسام" data-label-en="All Sections">' + esc(i18n.t('all_sections')) + '</option>';
        var secs = allRefs.sections || [];
        if (deptId) {
            secs = secs.filter(function(s) {
                return String(s.department_id) === String(deptId);
            });
        }
        var opts = secs.map(function(s) {
            var sid = s.section_id || s.id || '';
            var label = isEn ? (s.name_en || s.name_ar || s.name || '') : (s.name_ar || s.name || s.name_en || '');
            return '<option value="' + esc(String(sid)) + '">' + esc(label) + '</option>';
        }).join('');
        sel.innerHTML = first + opts;
        populateDivisionFilter('');
    }

    function populateDivisionFilter(sectionId) {
        var sel = document.getElementById('avFilterDivision');
        if (!sel) return;
        var isEn = (i18n.lang === 'en');
        var first = '<option value="" data-label-ar="كل الشعب" data-label-en="All Divisions">' + esc(i18n.t('all_divisions')) + '</option>';
        var divs = allRefs.divisions || [];
        if (sectionId) {
            divs = divs.filter(function(d) { return String(d.section_id) === String(sectionId); });
        }
        var opts = divs.map(function(d) {
            var did = d.division_id || d.id || '';
            var label = isEn ? (d.name_en || d.name_ar || d.name || '') : (d.name_ar || d.name || d.name_en || '');
            return '<option value="' + esc(String(did)) + '">' + esc(label) + '</option>';
        }).join('');
        sel.innerHTML = first + opts;
    }

    /* ---------- Build a vehicle card ---------- */
    function buildCard(v, opts) {
        opts = opts || {};
        var isAvailable = v.available !== false && v.available !== 0 && v.available !== '0';
        var isOperational = v.status === 'operational';
        var canPickup = isAvailable && isOperational;
        var canReturn = !isAvailable;
        var cardClass = 'av-v-card';
        if (opts.isNextTurn) cardClass += ' next-turn';
        if (!isAvailable) cardClass += ' checked-out';

        var html = '<div class="' + cardClass + '">';
        html += '<div class="av-v-card-head">';
        if (opts.turnOrder) {
            html += '<span><span class="av-order-badge">' + opts.turnOrder + '</span><span class="av-v-code">' + esc(v.vehicle_code) + '</span>';
            if (opts.isNextTurn) {
                html += '<span class="av-next-label">' + esc(i18n.t('av_next_turn')) + '</span>';
            }
            html += '</span>';
        } else {
            html += '<span class="av-v-code">' + esc(v.vehicle_code) + '</span>';
        }
        html += availBadge(isAvailable);
        html += '</div>';

        html += '<div class="av-v-type">' + esc(v.type || v.vehicle_type || '—') + '</div>';
        html += statusBadge(v.status);
        html += modeBadge(opts.displayMode || v.vehicle_mode);

        html += '<div class="av-v-detail"><span class="icon">👤</span> ' + esc(i18n.t('driver')) + ': ' + esc(v.driver_name || '—') + '</div>';
        if (v.emp_id) {
            html += '<div class="av-v-detail"><span class="icon">🆔</span> ' + esc(i18n.t('emp_id')) + ': ' + esc(v.emp_id) + '</div>';
        }
        if (v.vehicle_category) {
            html += '<div class="av-v-detail"><span class="icon">🚗</span> ' + esc(i18n.t('vehicle_category')) + ': ' + esc(v.vehicle_category) + '</div>';
        }
        if (v.sector_name) {
            html += '<div class="av-v-detail"><span class="icon">🏛️</span> ' + esc(i18n.t('sector')) + ': ' + esc(v.sector_name) + '</div>';
        }
        if (v.department_name) {
            html += '<div class="av-v-detail"><span class="icon">🏢</span> ' + esc(i18n.t('department')) + ': ' + esc(v.department_name) + '</div>';
        }
        if (!isAvailable && v.last_holder) {
            var holderInfo = userMap[v.last_holder] || {};
            var holderName = holderInfo.name || v.last_holder;
            var holderSector = holderInfo.sector_name || '';
            html += '<div class="av-holder-info">';
            html += '<div class="av-v-detail" style="margin-top:0"><span class="icon">👤</span> ' + esc(i18n.t('av_recipient')) + ': <strong>' + esc(holderName) + '</strong></div>';
            if (holderSector) {
                html += '<div class="av-v-detail"><span class="icon">🏛️</span> ' + esc(i18n.t('sector')) + ': ' + esc(holderSector) + '</div>';
            }
            if (v.movement_datetime) {
                html += '<div class="av-v-detail"><span class="icon">🕐</span> ' + esc(i18n.t('av_pickup_time')) + ': ' + esc(formatDateTime(v.movement_datetime)) + '</div>';
            }
            html += '</div>';
        }

        /* Admin Pickup/Return buttons */
        html += '<div class="av-v-actions">';
        if (canPickup) {
            html += '<button class="btn av-btn-pickup" onclick="AdminVehiclesFragment.pickup(\'' + esc(v.vehicle_code) + '\')">';
            html += '<span>🚗</span> ' + esc(i18n.t('av_pickup_btn'));
            html += '</button>';
        }
        if (canReturn) {
            html += '<button class="btn av-btn-return" onclick="AdminVehiclesFragment.returnVehicle(\'' + esc(v.vehicle_code) + '\')">';
            html += '<span>↩️</span> ' + esc(i18n.t('av_return_btn'));
            html += '</button>';
        }
        if (!isAvailable) {
            html += '<button class="btn av-btn-details" onclick="AdminVehiclesFragment.showDetails(\'' + esc(v.vehicle_code) + '\')">';
            html += '<span>📋</span> ' + esc(i18n.t('av_view_details'));
            html += '</button>';
        }
        html += '</div>';

        html += '</div>';
        return html;
    }

    /* ---------- Load all vehicles ---------- */
    async function loadAllVehicles() {
        try {
            var res = await API.get('/vehicles');
            if (!res || res.success === false) {
                var errMsg = (res && res.message) || i18n.t('load_failed');
                console.error('vehicles API error:', errMsg);
                if (typeof UI !== 'undefined' && UI.showToast) {
                    UI.showToast(errMsg, 'error');
                }
                renderError('avPrivateGrid');
                renderError('avShiftGrid');
                renderError('avDeptGrid');
                return;
            }
            allVehiclesData = (res && res.data) || [];

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
                console.warn('admin_vehicles: Could not load users for holder names', ue);
            }

            renderAll();
        } catch (e) {
            console.error('Failed to load vehicles:', e);
            if (typeof UI !== 'undefined' && UI.showToast) {
                UI.showToast(i18n.t('load_failed'), 'error');
            }
            renderError('avPrivateGrid');
            renderError('avShiftGrid');
            renderError('avDeptGrid');
        }
    }

    /* ---------- Categorize & render ---------- */
    function renderAll() {
        /* Clear stale next-turn marks before re-rendering */
        allVehiclesData.forEach(function(v) { delete v._markedNextTurn; });

        var search = (document.getElementById('avSearchInput') || {}).value || '';
        search = search.trim().toLowerCase();
        var statusFilter = (document.getElementById('avFilterStatus') || {}).value || '';
        var availFilter = (document.getElementById('avFilterAvailability') || {}).value || '';
        var sectorFilter = (document.getElementById('avFilterSector') || {}).value || '';
        var deptFilter = (document.getElementById('avFilterDepartment') || {}).value || '';
        var sectionFilter = (document.getElementById('avFilterSection') || {}).value || '';
        var divisionFilter = (document.getElementById('avFilterDivision') || {}).value || '';
        var genderFilter = (document.getElementById('avFilterGender') || {}).value || '';

        var filtered = allVehiclesData.filter(function(v) {
            if (search) {
                var code = (v.vehicle_code || '').toLowerCase();
                var type = (v.type || v.vehicle_type || '').toLowerCase();
                var driver = (v.driver_name || '').toLowerCase();
                var empId = (v.emp_id || '').toLowerCase();
                if (code.indexOf(search) === -1 && type.indexOf(search) === -1 && driver.indexOf(search) === -1 && empId.indexOf(search) === -1) {
                    return false;
                }
            }
            if (statusFilter && v.status !== statusFilter) return false;
            if (availFilter === 'available' && !v.available) return false;
            if (availFilter === 'checked_out' && v.available) return false;
            if (sectorFilter && String(v.sector_id || '') !== sectorFilter) return false;
            if (deptFilter && String(v.department_id || '') !== deptFilter) return false;
            if (sectionFilter && String(v.section_id || '') !== sectionFilter) return false;
            if (divisionFilter && String(v.division_id || '') !== divisionFilter) return false;
            if (genderFilter && (v.gender || '') !== genderFilter) return false;
            return true;
        });

        var privateVehicles = [];
        var shiftVehicles = [];
        var deptVehicles = [];

        filtered.forEach(function(v) {
            var mode = v.vehicle_mode || '';
            if (mode === 'private') {
                privateVehicles.push(v);
            } else {
                /* All non-private vehicles are rotation (round-robin) */
                deptVehicles.push(v);
            }
        });

        /* Sort shift and dept vehicles by department then vehicle_code for grouped display */
        var deptSorter = function(a, b) {
            var dA = (a.department_name || a.department_name_ar || '').toLowerCase();
            var dB = (b.department_name || b.department_name_ar || '').toLowerCase();
            if (dA < dB) return -1;
            if (dA > dB) return 1;
            return (a.vehicle_code || '').localeCompare(b.vehicle_code || '');
        };
        shiftVehicles.sort(deptSorter);
        deptVehicles.sort(deptSorter);

        renderSection('avPrivateGrid', privateVehicles, 'private');
        renderGroupedSection('avDeptGrid', deptVehicles, 'dept');
        updateStats(privateVehicles, shiftVehicles, deptVehicles);
        updateCounts(privateVehicles.length, shiftVehicles.length, deptVehicles.length);

        /* Toggle view visibility */
        var privSection = document.getElementById('avPrivateSection');
        var shiftSection = document.getElementById('avShiftSection');
        var deptSection = document.getElementById('avDeptSection');
        if (privSection) privSection.style.display = (activeView === 'private') ? '' : 'none';
        if (shiftSection) shiftSection.style.display = 'none'; /* merged into rotation */
        if (deptSection) deptSection.style.display = (activeView === 'shift') ? '' : 'none';
    }

    /* ---------- Render section ---------- */
    function renderSection(containerId, vehicles, sectionType) {
        var container = document.getElementById(containerId);
        if (!container) return;
        if (!vehicles.length) {
            var emptyIcon = sectionType === 'private' ? '🔒' : (sectionType === 'shift' ? '🔄' : '🏢');
            var emptyMsg = i18n.t('no_vehicles');
            container.innerHTML = '<div class="av-empty-state"><div class="empty-icon">' + emptyIcon + '</div><p>' + esc(emptyMsg) + '</p></div>';
            return;
        }

        var showTurnOrder = (sectionType === 'shift' || sectionType === 'dept');
        var cards = vehicles.map(function(v, idx) {
            var opts = { displayMode: sectionType === 'dept' ? 'dept' : v.vehicle_mode };
            if (showTurnOrder) {
                opts.turnOrder = idx + 1;
                /* First available operational vehicle is next in turn */
                if (!v._markedNextTurn) {
                    var isFirst = true;
                    for (var j = 0; j < idx; j++) {
                        if (vehicles[j].available && vehicles[j].status === 'operational') {
                            isFirst = false;
                            break;
                        }
                    }
                    if (isFirst && v.available && v.status === 'operational') {
                        opts.isNextTurn = true;
                        v._markedNextTurn = true;
                    }
                }
            }
            return buildCard(v, opts);
        }).join('');
        container.innerHTML = cards;
    }

    /* ---------- Render grouped section (by department) ---------- */
    function renderGroupedSection(containerId, vehicles, sectionType) {
        var container = document.getElementById(containerId);
        if (!container) return;
        if (!vehicles.length) {
            var emptyIcon = sectionType === 'shift' ? '🔄' : '🏢';
            var emptyMsg = i18n.t('no_vehicles');
            container.innerHTML = '<div class="av-empty-state"><div class="empty-icon">' + emptyIcon + '</div><p>' + esc(emptyMsg) + '</p></div>';
            return;
        }

        /* Group vehicles by department */
        var groups = {};
        var groupOrder = [];
        vehicles.forEach(function(v) {
            var deptKey = v.department_name || v.department_name_ar || i18n.t('av_unassigned');
            if (!groups[deptKey]) {
                groups[deptKey] = [];
                groupOrder.push(deptKey);
            }
            groups[deptKey].push(v);
        });

        var html = '';
        var globalIdx = 0;
        groupOrder.forEach(function(deptName) {
            var groupVehicles = groups[deptName];
            html += '<div class="av-dept-group-title"><span>🏢</span> ' + esc(deptName) + ' <span style="font-size:.8rem;font-weight:400;color:var(--text-secondary)">(' + groupVehicles.length + ')</span></div>';
            html += '<div class="av-vehicles-grid">';
            groupVehicles.forEach(function(v) {
                globalIdx++;
                var opts = { displayMode: sectionType === 'dept' ? 'dept' : v.vehicle_mode, turnOrder: globalIdx };
                /* First available operational vehicle in each group is next in turn */
                if (!v._markedNextTurn && v.available && v.status === 'operational') {
                    var alreadyMarked = false;
                    for (var j = 0; j < groupVehicles.indexOf(v); j++) {
                        if (groupVehicles[j]._markedNextTurn) { alreadyMarked = true; break; }
                    }
                    if (!alreadyMarked) {
                        opts.isNextTurn = true;
                        v._markedNextTurn = true;
                    }
                }
                html += buildCard(v, opts);
            });
            html += '</div>';
        });
        container.innerHTML = html;
    }

    /* ---------- Update stats bar ---------- */
    function updateStats(priv, shift, dept) {
        var all = priv.concat(dept);
        var total = all.length;
        var availCount = 0;
        var checkedCount = 0;
        all.forEach(function(v) {
            if (v.available) availCount++;
            else checkedCount++;
        });
        setText('avStatTotal', total);
        setText('avStatPrivate', priv.length);
        setText('avStatDept', dept.length);
        setText('avStatAvailable', availCount);
        setText('avStatCheckedOut', checkedCount);
    }

    function updateCounts(privCount, shiftCount, deptCount) {
        setText('avPrivateCount', '(' + privCount + ' ' + i18n.t('av_vehicles_count') + ')');
        setText('avDeptCount', '(' + deptCount + ' ' + i18n.t('av_vehicles_count') + ')');
    }

    function setText(id, val) {
        var el = document.getElementById(id);
        if (el) el.textContent = val;
    }

    /* ---------- Render error state with retry ---------- */
    function renderError(containerId) {
        var container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = '<div class="av-empty-state">' +
            '<div class="empty-icon">⚠️</div>' +
            '<p>' + esc(i18n.t('av_error_loading')) + '</p>' +
            '<button class="btn av-btn-pickup" onclick="AdminVehiclesFragment.reload()" style="margin-top:12px">' +
            esc(i18n.t('av_retry')) + '</button></div>';
    }

    /* ---------- Pickup action (admin self-service) ---------- */
    async function pickup(vehicleCode) {
        var msg = i18n.t('av_confirm_pickup') + ' ' + vehicleCode + '?';
        if (!confirm(msg)) return;

        try {
            await API.post('/vehicles/self-service', {
                vehicle_code: vehicleCode,
                operation_type: 'pickup'
            });
            if (typeof UI !== 'undefined' && UI.showToast) {
                UI.showToast(i18n.t('pickup_success'), 'success');
            }
            loadAllVehicles();
        } catch (e) {
            if (typeof UI !== 'undefined' && UI.showToast) {
                UI.showToast(e.message || i18n.t('error'), 'error');
            }
        }
    }

    /* ---------- Return action (admin self-service) ---------- */
    async function returnVehicle(vehicleCode) {
        var msg = i18n.t('av_confirm_return') + ' ' + vehicleCode + '?';
        if (!confirm(msg)) return;

        try {
            await API.post('/vehicles/self-service', {
                vehicle_code: vehicleCode,
                operation_type: 'return'
            });
            if (typeof UI !== 'undefined' && UI.showToast) {
                UI.showToast(i18n.t('vehicle_returned_success'), 'success');
            }
            loadAllVehicles();
        } catch (e) {
            if (typeof UI !== 'undefined' && UI.showToast) {
                UI.showToast(e.message || i18n.t('error'), 'error');
            }
        }
    }

    /* ---------- Apply language to fragment labels ---------- */
    function applyFragmentLang() {
        /* Wait until i18n strings are loaded (i18n.load is async) */
        if (!i18n.strings || !Object.keys(i18n.strings).length) {
            setTimeout(applyFragmentLang, 100);
            return;
        }
        var isEn = (i18n.lang === 'en');
        document.querySelectorAll('[data-label-ar]').forEach(function(el) {
            el.textContent = el.getAttribute(isEn ? 'data-label-en' : 'data-label-ar') || el.textContent;
        });
        document.querySelectorAll('[data-placeholder-ar]').forEach(function(el) {
            el.setAttribute('placeholder', el.getAttribute(isEn ? 'data-placeholder-en' : 'data-placeholder-ar') || el.getAttribute('placeholder'));
        });
        /* Re-render cards to update language */
        if (allVehiclesData.length) {
            renderAll();
        }
    }

    /* ---------- Filter listeners ---------- */
    function setupFilters() {
        var searchInput = document.getElementById('avSearchInput');
        var statusSelect = document.getElementById('avFilterStatus');
        var availSelect = document.getElementById('avFilterAvailability');
        var sectorSelect = document.getElementById('avFilterSector');
        var deptSelect = document.getElementById('avFilterDepartment');
        var sectionSelect = document.getElementById('avFilterSection');
        var divisionSelect = document.getElementById('avFilterDivision');
        var genderSelect = document.getElementById('avFilterGender');
        var debounceTimer = null;

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() {
                    if (allVehiclesData.length) renderAll();
                }, 300);
            });
        }
        if (statusSelect) {
            statusSelect.addEventListener('change', function() {
                if (allVehiclesData.length) renderAll();
            });
        }
        if (availSelect) {
            availSelect.addEventListener('change', function() {
                if (allVehiclesData.length) renderAll();
            });
        }
        if (sectorSelect) {
            sectorSelect.addEventListener('change', function() {
                populateDepartmentFilter(this.value);
                if (allVehiclesData.length) renderAll();
            });
        }
        if (deptSelect) {
            deptSelect.addEventListener('change', function() {
                populateSectionFilter(this.value);
                if (allVehiclesData.length) renderAll();
            });
        }
        if (sectionSelect) {
            sectionSelect.addEventListener('change', function() {
                populateDivisionFilter(this.value);
                if (allVehiclesData.length) renderAll();
            });
        }
        if (divisionSelect) {
            divisionSelect.addEventListener('change', function() {
                if (allVehiclesData.length) renderAll();
            });
        }
        if (genderSelect) {
            genderSelect.addEventListener('change', function() {
                if (allVehiclesData.length) renderAll();
            });
        }

        /* View toggle buttons */
        var togglePrivate = document.getElementById('avTogglePrivate');
        var toggleShift = document.getElementById('avToggleShift');
        if (togglePrivate) {
            togglePrivate.addEventListener('click', function() {
                activeView = 'private';
                togglePrivate.classList.add('active');
                if (toggleShift) toggleShift.classList.remove('active');
                if (allVehiclesData.length) renderAll();
            });
        }
        if (toggleShift) {
            toggleShift.addEventListener('click', function() {
                activeView = 'shift';
                toggleShift.classList.add('active');
                if (togglePrivate) togglePrivate.classList.remove('active');
                if (allVehiclesData.length) renderAll();
            });
        }

        /* Clear all filters button */
        var clearBtn = document.getElementById('avClearFilters');
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                if (searchInput) searchInput.value = '';
                if (statusSelect) statusSelect.value = '';
                if (availSelect) availSelect.value = '';
                if (sectorSelect) sectorSelect.value = '';
                if (genderSelect) genderSelect.value = '';
                populateDepartmentFilter('');
                if (allVehiclesData.length) renderAll();
            });
        }
    }

    /* ---------- Detail modal helpers ---------- */
    function detailRow(label, value) {
        return '<div class="av-detail-row"><span class="dlabel">' + esc(label) + '</span><span>' + esc(value || '—') + '</span></div>';
    }

    function statusLabel(status) {
        var map = { operational: 'operational', maintenance: 'under_maintenance', out_of_service: 'out_of_service' };
        return map[status] ? i18n.t(map[status]) : (status || '—');
    }

    function formatDateTime(dt) {
        try {
            var d = new Date(dt);
            if (isNaN(d.getTime())) return dt;
            var lang = (i18n.lang === 'en') ? 'en-US' : 'ar-SA';
            return d.toLocaleDateString(lang) + ' ' + d.toLocaleTimeString(lang, { hour: '2-digit', minute: '2-digit' });
        } catch (e) {
            return dt;
        }
    }

    /* ---------- Show vehicle details modal ---------- */
    async function showDetails(vehicleCode) {
        var modal = document.getElementById('avDetailModal');
        var body = document.getElementById('avDetailBody');
        var title = document.getElementById('avDetailTitle');
        if (!modal || !body) return;

        var vehicle = null;
        for (var i = 0; i < allVehiclesData.length; i++) {
            if (allVehiclesData[i].vehicle_code === vehicleCode) { vehicle = allVehiclesData[i]; break; }
        }
        if (!vehicle) return;

        title.textContent = i18n.t('vehicle_details') + ': ' + vehicleCode;
        body.innerHTML = '<div class="av-empty-state"><div class="spinner spinner-sm"></div><span>' + esc(i18n.t('loading')) + '</span></div>';
        modal.style.display = 'flex';

        /* Build vehicle info */
        var html = '<div class="av-detail-section">';
        html += '<div class="av-detail-section-title">🚗 ' + esc(i18n.t('av_vehicle_info')) + '</div>';
        html += detailRow(i18n.t('av_code'), vehicle.vehicle_code);
        html += detailRow(i18n.t('av_type'), vehicle.type || vehicle.vehicle_type);
        html += detailRow(i18n.t('vehicle_category'), vehicle.vehicle_category);
        html += detailRow(i18n.t('status'), statusLabel(vehicle.status));
        html += detailRow(i18n.t('av_availability'), vehicle.available ? i18n.t('available') : i18n.t('checked_out'));
        html += detailRow(i18n.t('driver'), vehicle.driver_name);
        if (vehicle.emp_id) html += detailRow(i18n.t('emp_id'), vehicle.emp_id);
        if (vehicle.sector_name) html += detailRow(i18n.t('sector'), vehicle.sector_name);
        var deptName = vehicle.department_name || vehicle.department_name_ar;
        if (deptName) html += detailRow(i18n.t('department'), deptName);
        if (vehicle.gender) html += detailRow(i18n.t('av_gender'), vehicle.gender === 'men' ? i18n.t('men') : i18n.t('women'));
        if (vehicle.notes) html += detailRow(i18n.t('notes'), vehicle.notes);
        html += '</div>';

        /* Show holder info if checked out — placeholder, will be enriched with movement data */
        var holderSectionHtml = '';
        if (!vehicle.available && vehicle.last_holder) {
            var holderInfo = userMap[vehicle.last_holder] || {};
            var holderName = holderInfo.name || vehicle.last_holder;
            var holderSector = holderInfo.sector_name || '';
            var holderDept = holderInfo.department_name || '';
            holderSectionHtml += '<div class="av-detail-section">';
            holderSectionHtml += '<div class="av-detail-section-title">👤 ' + esc(i18n.t('current_holder')) + '</div>';
            holderSectionHtml += detailRow(i18n.t('av_holder_id'), vehicle.last_holder);
            holderSectionHtml += detailRow(i18n.t('av_name'), holderName);
            if (holderSector) {
                holderSectionHtml += detailRow(i18n.t('sector'), holderSector);
            }
            if (holderDept) {
                holderSectionHtml += detailRow(i18n.t('department'), holderDept);
            }
            /* pickup time placeholder — will be filled from movement data below */
            holderSectionHtml += '<!--PICKUP_TIME_PLACEHOLDER-->';
            holderSectionHtml += '</div>';
        }

        /* Try to fetch movement data for this vehicle */
        try {
            var movRes = await API.get('/movements');
            var movements = [];
            if (movRes && Array.isArray(movRes.data)) {
                movements = movRes.data;
            } else if (Array.isArray(movRes)) {
                movements = movRes;
            }

            var vehicleMovements = movements.filter(function(m) {
                return m.vehicle_code === vehicleCode;
            }).sort(function(a, b) {
                return new Date(b.movement_datetime || b.created_at || 0) - new Date(a.movement_datetime || a.created_at || 0);
            });

            /* Enrich holder section with pickup time from latest pickup movement */
            if (vehicleMovements.length > 0 && holderSectionHtml) {
                var latestPickup = null;
                for (var mi = 0; mi < vehicleMovements.length; mi++) {
                    if (vehicleMovements[mi].operation_type === 'pickup') {
                        latestPickup = vehicleMovements[mi];
                        break;
                    }
                }
                if (latestPickup && latestPickup.movement_datetime) {
                    holderSectionHtml = holderSectionHtml.replace('<!--PICKUP_TIME_PLACEHOLDER-->',
                        detailRow(i18n.t('av_pickup_time'), formatDateTime(latestPickup.movement_datetime)));
                } else {
                    holderSectionHtml = holderSectionHtml.replace('<!--PICKUP_TIME_PLACEHOLDER-->', '');
                }
            } else {
                holderSectionHtml = holderSectionHtml.replace('<!--PICKUP_TIME_PLACEHOLDER-->', '');
            }
            html += holderSectionHtml;

            if (vehicleMovements.length > 0) {
                var lastMov = vehicleMovements[0];
                html += '<div class="av-detail-section">';
                html += '<div class="av-detail-section-title">📋 ' + esc(i18n.t('av_last_movement')) + '</div>';
                var opLabel = lastMov.operation_type === 'pickup' ? i18n.t('av_pickup_btn') : i18n.t('av_return_btn');
                html += detailRow(i18n.t('av_operation'), opLabel);
                var performedByName = (userMap[lastMov.performed_by] || {}).name || lastMov.performed_by;
                html += detailRow(i18n.t('av_performed_by'), performedByName + (lastMov.performed_by !== performedByName ? ' (' + lastMov.performed_by + ')' : ''));
                if (lastMov.movement_datetime) {
                    html += detailRow(i18n.t('av_datetime'), formatDateTime(lastMov.movement_datetime));
                }
                if (lastMov.vehicle_condition) {
                    var condMap = { clean: i18n.t('clean'), acceptable: i18n.t('acceptable'), damaged: i18n.t('damaged') };
                    html += detailRow(i18n.t('condition'), condMap[lastMov.vehicle_condition] || lastMov.vehicle_condition);
                }
                if (lastMov.fuel_level) {
                    var fuelMap = { full: i18n.t('fuel_full'), three_quarter: '3/4', half: i18n.t('fuel_half'), quarter: '1/4', empty: i18n.t('fuel_empty') };
                    html += detailRow(i18n.t('fuel_level'), fuelMap[lastMov.fuel_level] || lastMov.fuel_level);
                }
                if (lastMov.notes) {
                    html += detailRow(i18n.t('notes'), lastMov.notes);
                }
                html += '</div>';

                /* Fetch photos for this movement */
                if (lastMov.id) {
                    try {
                        var photoRes = await API.get('/movements/' + lastMov.id + '/photos');
                        var photos = [];
                        if (photoRes && Array.isArray(photoRes.data)) photos = photoRes.data;
                        else if (Array.isArray(photoRes)) photos = photoRes;
                        if (photos.length > 0) {
                            html += '<div class="av-detail-section">';
                            html += '<div class="av-detail-section-title">📷 ' + esc(i18n.t('av_photos')) + ' (' + photos.length + ')</div>';
                            html += '<div class="av-detail-photos">';
                            photos.forEach(function(p) {
                                var url = p.photo_url || p.url || '';
                                if (url) {
                                    html += '<img src="' + esc(url) + '" alt="' + esc(i18n.t('av_photo')) + '" onclick="window.open(this.src,\'_blank\')">';
                                }
                            });
                            html += '</div></div>';
                        }
                    } catch (pe) {
                        console.warn('admin_vehicles: Failed to load movement photos', pe);
                    }
                }
            }
        } catch (e) {
            console.warn('admin_vehicles: Failed to load movements', e);
            /* Still show holder section without pickup time */
            if (holderSectionHtml) {
                holderSectionHtml = holderSectionHtml.replace('<!--PICKUP_TIME_PLACEHOLDER-->', '');
                html += holderSectionHtml;
            }
            html += '<div class="av-detail-section"><p style="color:var(--text-secondary);font-size:.85rem">' + esc(i18n.t('av_movement_load_error')) + '</p></div>';
        }

        body.innerHTML = html;
    }

    /* ---------- Close detail modal ---------- */
    function closeDetails() {
        var modal = document.getElementById('avDetailModal');
        if (modal) modal.style.display = 'none';
    }

    /* ---------- Expose globally ---------- */
    window.AdminVehiclesFragment = { pickup: pickup, returnVehicle: returnVehicle, reload: loadAllVehicles, showDetails: showDetails, closeDetails: closeDetails };

    /* ---------- Init with retry for Auth ---------- */
    var _initAttempts = 0;
    (function init() {
        if (window.__pageDenied) return;
        var user = Auth.getUser();
        if (!user) {
            _initAttempts++;
            if (_initAttempts > 50) {
                console.warn('admin_vehicles: Auth not available after 5s');
                renderError('avPrivateGrid');
                renderError('avShiftGrid');
                renderError('avDeptGrid');
                return;
            }
            setTimeout(init, 100);
            return;
        }
        currentUser = user;

        applyFragmentLang();
        setupFilters();
        loadReferences();
        loadAllVehicles();

        /* Close detail modal on overlay click */
        var modal = document.getElementById('avDetailModal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) closeDetails();
            });
        }
    })();
})();
</script>
<?php $pageScripts = ob_get_clean(); ?>
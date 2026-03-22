<?php
/**
 * Movements Fragment — Vehicle Movement Tracking
 * Full CRUD with geolocation, photos, vehicle condition & fuel tracking.
 */
?>
<style>
/* Fix LTR layout flash: html[dir] is set before CSS renders, body[dir] after */
html[dir="ltr"] body{direction:ltr;font-family:var(--font-en)}
html[dir="ltr"] .app-sidebar{right:auto;left:0}
html[dir="ltr"] .app-main{margin-right:0;margin-left:var(--sidebar-width)}
html[dir="ltr"] .app-footer{margin-right:0;margin-left:var(--sidebar-width)}
html[dir="ltr"] .app-sidebar.collapsed~.app-main{margin-right:0;margin-left:var(--sidebar-collapsed-width)}
.mv-stats{display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap}
.mv-stat{flex:1;min-width:140px;background:var(--bg-card,#fff);border-radius:12px;padding:16px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.mv-stat{cursor:pointer;transition:transform .15s,box-shadow .15s}
.mv-stat:hover{transform:translateY(-2px);box-shadow:0 4px 16px rgba(0,0,0,.12)}
.mv-stat .num{font-size:1.8rem;font-weight:700;color:var(--primary-main,#1a5276)}
.mv-stat .lbl{font-size:.85rem;color:var(--text-secondary,#666);margin-top:4px}
.mv-stat .print-hint{font-size:.7rem;color:var(--text-secondary,#999);margin-top:2px;opacity:0;transition:opacity .2s}
.mv-stat:hover .print-hint{opacity:1}
.mv-toolbar{display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;align-items:center}
.mv-toolbar .search-box{flex:1;min-width:200px;max-width:360px;position:relative}
.mv-toolbar .search-box input{width:100%;padding:10px 14px;padding-inline-end:36px;border:1.5px solid var(--border-default,#ddd);border-radius:10px;font-size:.875rem;background:var(--bg-card,#fff);color:var(--text-primary,#333);min-height:42px;box-sizing:border-box;transition:border-color .2s,box-shadow .2s}
.mv-toolbar .search-box input:focus{outline:none;border-color:var(--primary-main,#1a5276);box-shadow:0 0 0 3px rgba(26,82,118,.12)}
.mv-toolbar .search-box .ico{position:absolute;inset-inline-end:12px;top:50%;transform:translateY(-50%);color:#999;pointer-events:none}
.mv-toolbar .btn-add{margin-inline-start:auto}
.mv-filters-row{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;margin-bottom:16px;align-items:end}
.mv-filters-row select{width:100%;padding:10px 12px;border:1.5px solid var(--border-default,#ddd);border-radius:10px;font-size:.875rem;background:var(--bg-card,#fff);color:var(--text-primary,#333);transition:border-color .2s,box-shadow .2s;cursor:pointer;min-height:42px;box-sizing:border-box}
.mv-filters-row select:focus{outline:none;border-color:var(--primary-main,#1a5276);box-shadow:0 0 0 3px rgba(26,82,118,.12)}
.mv-date-group{display:flex;align-items:center;gap:8px;grid-column:span 2}
.mv-date-group input[type="date"]{flex:1;padding:10px 12px;border:1.5px solid var(--border-default,#ddd);border-radius:10px;font-size:.875rem;background:var(--bg-card,#fff);color:var(--text-primary,#333);min-height:42px;box-sizing:border-box;transition:border-color .2s,box-shadow .2s}
.mv-date-group input[type="date"]:focus{outline:none;border-color:var(--primary-main,#1a5276);box-shadow:0 0 0 3px rgba(26,82,118,.12)}
.mv-date-group .date-sep{font-size:.85rem;color:var(--text-secondary,#666);white-space:nowrap;font-weight:600}
@media(max-width:768px){.mv-filters-row{grid-template-columns:repeat(auto-fill,minmax(140px,1fr))}.mv-date-group{grid-column:span 1;flex-wrap:wrap}}
.mv-table{width:100%;border-collapse:separate;border-spacing:0;background:var(--bg-card,#fff);border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.mv-table th{background:var(--primary-dark,#1a5276);color:#fff;padding:12px 14px;font-size:.85rem;white-space:nowrap}
.mv-table td{padding:10px 14px;border-bottom:1px solid var(--border-default,#eee);font-size:.9rem;word-break:break-word}
.mv-table tr:hover td{background:rgba(26,82,118,.04)}
.mv-badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.8rem;font-weight:600}
.mv-badge.pickup{background:#d4edda;color:#155724}
.mv-badge.return{background:#fff3cd;color:#856404}
.mv-badge.clean{background:#d4edda;color:#155724}
.mv-badge.acceptable{background:#fff3cd;color:#856404}
.mv-badge.damaged{background:#f8d7da;color:#721c24}
.mv-actions button{background:none;border:none;cursor:pointer;font-size:1.1rem;padding:4px}
.mv-empty{text-align:center;padding:60px 20px;color:#999}
.mv-empty .ico{font-size:3rem;margin-bottom:12px}
/* Modal */
.mv-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center}
.mv-modal-bg.show{display:flex}
.mv-modal{background:var(--bg-card,#fff);border-radius:16px;width:95%;max-width:640px;max-height:90vh;overflow-y:auto;padding:0}
.mv-modal .modal-hd{display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid var(--border-default,#eee);position:sticky;top:0;background:var(--bg-card,#fff);z-index:1}
.mv-modal .modal-hd h3{margin:0;font-size:1.1rem}
.mv-modal .modal-hd .close{background:none;border:none;font-size:1.5rem;cursor:pointer;color:#999}
.mv-modal .modal-bd{padding:20px}
.mv-form .fg{margin-bottom:16px}
.mv-form label{display:block;font-weight:600;font-size:.85rem;margin-bottom:6px;color:var(--text-secondary,#555)}
.mv-form input,.mv-form select,.mv-form textarea{width:100%;padding:10px 12px;border:1px solid var(--border-default,#ddd);border-radius:8px;font-size:.95rem;box-sizing:border-box}
.mv-form textarea{resize:vertical;min-height:70px}
.mv-form .row2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.mv-form .loc-btns{display:flex;gap:8px;margin-bottom:8px}
.mv-form .loc-btns button{padding:8px 14px;border:none;border-radius:8px;cursor:pointer;font-size:.85rem}
.mv-form .loc-btns .get-loc{background:var(--primary-main,#1a5276);color:#fff}
.mv-form .loc-btns .open-map{background:var(--accent-gold,#c9a961);color:#fff}
.mv-form .loc-btns .clear-loc{background:#e74c3c;color:#fff}
#mvPhotosPreview{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
#mvPhotosPreview .thumb{width:80px;height:80px;border-radius:8px;object-fit:cover;border:2px solid var(--border-default,#ddd)}
#mvPhotoInput{display:none}
.mv-form .photo-area{border:2px dashed var(--border-default,#ccc);border-radius:12px;padding:20px;text-align:center;cursor:pointer;transition:border-color .2s}
.mv-form .photo-area:hover{border-color:var(--primary-main,#1a5276)}
.mv-form .modal-ft{display:flex;gap:12px;padding:16px 20px;border-top:1px solid var(--border-default,#eee);justify-content:flex-end;position:sticky;bottom:0;background:var(--bg-card,#fff)}
/* Detail modal */
.mv-detail .d-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border-default,#f0f0f0)}
.mv-detail .d-row .d-lbl{color:var(--text-secondary,#777);font-size:.85rem}
.mv-detail .d-row .d-val{font-weight:600}
.mv-detail .d-photos{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}
.mv-detail .d-photos img{width:100px;height:80px;border-radius:8px;object-fit:cover;cursor:pointer}
.mv-page{display:flex;justify-content:center;gap:8px;margin-top:16px;flex-wrap:wrap;align-items:center}
.mv-page button{padding:6px 14px;border:1px solid var(--border-default,#ddd);border-radius:6px;background:#fff;cursor:pointer;transition:all .3s}
.mv-page button:hover:not(:disabled){background:var(--primary-main,#1a5276);color:#fff}
.mv-page button.active{background:var(--primary-main,#1a5276);color:#fff;border-color:var(--primary-main)}
.mv-page button:disabled{opacity:.4;cursor:not-allowed}
.mv-page-info{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-top:8px;font-size:.85rem;color:var(--text-secondary,#666)}
.mv-page-info .pg-goto{display:flex;align-items:center;gap:6px}
.mv-page-info .pg-goto input{width:60px;height:30px;text-align:center;border:1px solid var(--border-default,#ddd);border-radius:6px;font-size:.85rem}
.mv-page-info .pg-goto button{height:30px;padding:0 10px;border:1px solid var(--primary-main,#1a5276);background:var(--primary-main,#1a5276);color:#fff;border-radius:6px;cursor:pointer;font-size:.8rem}
.loc-status{font-size:.8rem;color:var(--text-secondary,#777);margin-top:4px}
/* Skeleton */
.skel-row td{padding:10px 14px}
.skel-cell{height:14px;border-radius:6px;background:linear-gradient(90deg,#e8e8e8 25%,#f5f5f5 50%,#e8e8e8 75%);background-size:200% 100%;animation:mv-shimmer 1.5s infinite}
@keyframes mv-shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
</style>

<div class="page-header"><h2 data-lang-key="movements">Vehicle Movements</h2></div>

<!-- Stats -->
<div class="mv-stats">
    <div class="mv-stat" data-stat="total_vehicles"><div class="num" id="mvStatTotal">0</div><div class="lbl" data-lang-key="total_vehicles">Total Vehicles</div><div class="print-hint">🖨️</div></div>
    <div class="mv-stat" data-stat="private_vehicles"><div class="num" id="mvStatPrivate">0</div><div class="lbl" data-lang-key="private_vehicles">Private</div><div class="print-hint">🖨️</div></div>
    <div class="mv-stat" data-stat="shift_vehicles"><div class="num" id="mvStatShift">0</div><div class="lbl" data-lang-key="shift_vehicles">Shift</div><div class="print-hint">🖨️</div></div>
    <div class="mv-stat" data-stat="checked_out"><div class="num" id="mvStatCheckedOut">0</div><div class="lbl" data-lang-key="checked_out">Handed Over</div><div class="print-hint">🖨️</div></div>
    <div class="mv-stat" data-stat="available"><div class="num" id="mvStatAvailable">0</div><div class="lbl" data-lang-key="available_vehicles">Available</div><div class="print-hint">🖨️</div></div>
    <div class="mv-stat" data-stat="used_in_period"><div class="num" id="mvStatUsedToday">0</div><div class="lbl" data-lang-key="used_in_period">Used in Period</div><div class="print-hint">🖨️</div></div>
    <div class="mv-stat" data-stat="unused_in_period"><div class="num" id="mvStatUnused">0</div><div class="lbl" data-lang-key="unused_vehicles">Unused</div><div class="print-hint">🖨️</div></div>
    <div class="mv-stat" data-stat="private_not_returned"><div class="num" id="mvStatPrivateNotReturned">0</div><div class="lbl" data-lang-key="private_not_returned">Private Not Returned</div><div class="print-hint">🖨️</div></div>
</div>
<div class="mv-stats">
    <div class="mv-stat" data-stat="total_movements"><div class="num" id="mvStatMovements">0</div><div class="lbl" data-lang-key="total_movements">Total Movements</div><div class="print-hint">🖨️</div></div>
    <div class="mv-stat" data-stat="pickups"><div class="num" id="mvStatPickup">0</div><div class="lbl" data-lang-key="operation_type_pickup">Pickup</div><div class="print-hint">🖨️</div></div>
    <div class="mv-stat" data-stat="returns"><div class="num" id="mvStatReturn">0</div><div class="lbl" data-lang-key="operation_type_return">Return</div><div class="print-hint">🖨️</div></div>
    <div class="mv-stat" data-stat="employees"><div class="num" id="mvStatEmployees">0</div><div class="lbl" data-lang-key="employee_count">Employees</div><div class="print-hint">🖨️</div></div>
    <div class="mv-stat" data-stat="sedan"><div class="num" id="mvStatSedan">0</div><div class="lbl" data-lang-key="sedan">Sedan</div><div class="print-hint">🖨️</div></div>
    <div class="mv-stat" data-stat="pickup_cat"><div class="num" id="mvStatPickupCat">0</div><div class="lbl" data-lang-key="pickup_category">Pickup</div><div class="print-hint">🖨️</div></div>
    <div class="mv-stat" data-stat="bus"><div class="num" id="mvStatBus">0</div><div class="lbl" data-lang-key="bus">Bus</div><div class="print-hint">🖨️</div></div>
    <div class="mv-stat" data-stat="operational"><div class="num" id="mvStatOperational">0</div><div class="lbl" data-lang-key="operational">Operational</div><div class="print-hint">🖨️</div></div>
</div>
<!-- Sector Stats (dynamic) -->
<div class="mv-stats" id="mvSectorStats" style="display:none"></div>

<!-- Toolbar: Search + Actions -->
<div class="mv-toolbar">
    <div class="search-box">
        <span class="ico">🔍</span>
        <input type="text" id="mvSearch" placeholder="Search movement">
    </div>
    <button class="btn btn-outline btn-sm" id="mvBtnPrint" title="Print Report">🖨️ <span id="mvBtnPrintText">Print Report</span></button>
    <button class="btn btn-primary btn-sm btn-add" id="mvBtnAdd">➕ <span id="mvBtnAddText">Add Movement</span></button>
</div>

<!-- Filters -->
<div class="mv-filters-row">
    <select id="mvFilterSector">
        <option value="" id="mvOptAllSectors">All Sectors</option>
    </select>
    <select id="mvFilterDept">
        <option value="" id="mvOptAllDepts">All Departments</option>
    </select>
    <select id="mvFilterSection">
        <option value="" id="mvOptAllSections">All Sections</option>
    </select>
    <select id="mvFilterDivision">
        <option value="" id="mvOptAllDivisions">All Divisions</option>
    </select>
    <select id="mvFilterGender">
        <option value="" id="mvOptAllGenders">All Genders</option>
        <option value="men" id="mvOptMen">Men</option>
        <option value="women" id="mvOptWomen">Women</option>
    </select>
    <select id="mvFilterVehicleMode">
        <option value="" id="mvOptAllModes">All Modes</option>
        <option value="private" id="mvOptPrivateMode">Private</option>
        <option value="shift" id="mvOptShiftMode">Shift</option>
    </select>
    <select id="mvFilterType">
        <option value="" id="mvOptAllTypes">All Types</option>
        <option value="pickup" id="mvOptPickup">Pickup</option>
        <option value="return" id="mvOptReturn">Return</option>
    </select>
    <select id="mvFilterCondition">
        <option value="" id="mvOptAllConditions">All Conditions</option>
        <option value="clean" id="mvOptClean">Clean</option>
        <option value="acceptable" id="mvOptAcceptable">Acceptable</option>
        <option value="damaged" id="mvOptDamaged">Damaged</option>
    </select>
    <select id="mvFilterVehicleStatus">
        <option value="" id="mvOptAllVehicleStatuses">All Vehicle Statuses</option>
        <option value="operational" id="mvOptOperational">Operational</option>
        <option value="maintenance" id="mvOptMaintenance">Under Maintenance</option>
        <option value="out_of_service" id="mvOptOutOfService">Out of Service</option>
    </select>
    <select id="mvFilterVehicle">
        <option value="" id="mvOptAllVehicles">All Vehicles</option>
    </select>
    <div class="mv-date-group">
        <span class="date-sep" id="mvLabelFrom">From:</span>
        <input type="date" id="mvDateFrom">
        <span class="date-sep" id="mvLabelTo">To:</span>
        <input type="date" id="mvDateTo">
    </div>
</div>

<!-- Table -->
<div class="table-responsive">
<table class="mv-table data-table">
    <thead><tr>
        <th>#</th>
        <th id="mvThCode">Vehicle Code</th>
        <th id="mvThType">Type</th>
        <th id="mvThBy">By</th>
        <th id="mvThDate">Date</th>
        <th id="mvThCondition">Condition</th>
        <th id="mvThFuel">Fuel Level</th>
        <th>📍</th>
        <th id="mvThActions">Actions</th>
    </tr></thead>
    <tbody id="mvTableBody"></tbody>
</table>
</div>
<div class="mv-page" id="mvPagination"></div>
<div class="mv-page-info" id="mvPaginationInfo"></div>

<!-- Add/Edit Modal -->
<div class="mv-modal-bg" id="mvModal">
    <div class="mv-modal">
        <div class="modal-hd">
            <h3 id="mvModalTitle">➕ Add Movement</h3>
            <button class="close" id="mvModalClose">&times;</button>
        </div>
        <div class="modal-bd">
            <form class="mv-form" id="mvForm">
                <input type="hidden" id="mvId">
                <div class="row2">
                    <div class="fg">
                        <label data-lang-key="vehicle_code_label">Vehicle Code *</label>
                        <input type="text" id="mvVehicleCode" required>
                    </div>
                    <div class="fg">
                        <label data-lang-key="operation_type_label">Operation Type *</label>
                        <select id="mvOperationType" required>
                            <option value="">-- Select --</option>
                            <option value="pickup">Pickup</option>
                            <option value="return">Return</option>
                        </select>
                    </div>
                </div>
                <div class="fg">
                    <label data-lang-key="performed_by_label">Performed By *</label>
                    <select id="mvPerformedBy" required>
                        <option value="">-- Select --</option>
                    </select>
                </div>
                <div class="row2">
                    <div class="fg">
                        <label data-lang-key="vehicle_condition_label">Vehicle Condition</label>
                        <select id="mvCondition">
                            <option value="">-- Select --</option>
                            <option value="clean">Clean</option>
                            <option value="acceptable">Acceptable</option>
                            <option value="damaged">Damaged</option>
                        </select>
                    </div>
                    <div class="fg">
                        <label data-lang-key="fuel_level_label">Fuel Level</label>
                        <select id="mvFuel">
                            <option value="">-- Select --</option>
                            <option value="full">Full</option>
                            <option value="three_quarter">3/4</option>
                            <option value="half">Half</option>
                            <option value="quarter">1/4</option>
                            <option value="empty">Empty</option>
                        </select>
                    </div>
                </div>
                <!-- Location -->
                <div class="fg">
                    <label data-lang-key="location_label">📍 Location</label>
                    <div class="loc-btns">
                        <button type="button" class="get-loc" id="mvGetLoc">📍 Get Location</button>
                        <button type="button" class="open-map" id="mvOpenMap">🗺️ Map</button>
                        <button type="button" class="clear-loc" id="mvClearLoc">🗑️ Clear</button>
                    </div>
                    <div class="row2">
                        <div><input type="number" id="mvLat" step="0.00000001" placeholder="Latitude"></div>
                        <div><input type="number" id="mvLng" step="0.00000001" placeholder="Longitude"></div>
                    </div>
                    <div class="loc-status" id="mvLocStatus"></div>
                </div>
                <div class="fg">
                    <label data-lang-key="notes_label">Notes</label>
                    <textarea id="mvNotes" rows="3"></textarea>
                </div>
                <!-- Photos -->
                <div class="fg">
                    <label data-lang-key="photos_label">📷 Photos (max 6)</label>
                    <div class="photo-area" id="mvPhotoArea">
                        <p id="mvPhotosAreaText">Click or drag photos here</p>
                        <input type="file" id="mvPhotoInput" accept="image/jpeg,image/png" multiple>
                    </div>
                    <div id="mvPhotosPreview"></div>
                </div>
            </form>
        </div>
        <div class="modal-ft">
            <button class="btn btn-ghost" id="mvCancelBtn">Cancel</button>
            <button class="btn btn-primary" id="mvSaveBtn">💾 Save</button>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div class="mv-modal-bg" id="mvDetailModal">
    <div class="mv-modal">
        <div class="modal-hd">
            <h3 id="mvDetailTitle">Movement Details</h3>
            <button class="close" id="mvDetailClose">&times;</button>
        </div>
        <div class="modal-bd mv-detail" id="mvDetailBody"></div>
    </div>
</div>

<?php ob_start(); ?>
<script>
(function(){
    const $=id=>document.getElementById(id);
    const esc=s=>{const d=document.createElement('div');d.textContent=s||'';return d.innerHTML;};
    let allMovements=[], filteredMovements=[], currentPage=1, perPage=100, pendingPhotos=[];
    let vehicleMap={}, latestByVehicle={};
    var allRefs={};
    var lastStats={};
    var mvUser=null;
    var mvPerms=[];
    var mvCanCreate=false;
    var mvCanEdit=false;
    var mvCanDelete=false;

    /* ---- Skeleton loader ---- */
    function showSkeleton(){
        const cols=9, rows=6;
        const cell='<td><div class="skel-cell"></div></td>';
        const row='<tr class="skel-row">'+Array(cols).fill(cell).join('')+'</tr>';
        document.getElementById('mvTableBody').innerHTML=Array(rows).fill(row).join('');
    }

    /* ---- Helper: get selected data-id from a select element ---- */
    function getSelId(sel){
        if(!sel||!sel.value) return '';
        var opt=sel.options[sel.selectedIndex];
        return opt?opt.getAttribute('data-id')||'':'';
    }

    /* ---- Load vehicles & references for cross-filters ---- */
    async function loadReferences(){
        // Load each resource independently so a failure in one doesn't block others
        try{
            var vRes=await API.get('/vehicles/list');
            var vehicles=(vRes.data||vRes)||[];
            vehicles.forEach(function(v){vehicleMap[v.vehicle_code]=v;});
            // Populate vehicle code dropdown
            var sel=$('mvFilterVehicle');
            vehicles.forEach(function(v){
                var o=document.createElement('option');o.value=v.vehicle_code;o.textContent=v.vehicle_code;sel.appendChild(o);
            });
        }catch(e){console.error('loadReferences: vehicles',e);}

        try{
            var rRes=await API.get('/references');
            var refs=rRes.data||rRes;
            allRefs=refs;
            // Populate sector dropdown
            var sSel=$('mvFilterSector');
            (refs.sectors||[]).forEach(function(s){
                var o=document.createElement('option');o.value=s.id;o.textContent=s.name||s.name_en;sSel.appendChild(o);
            });
            // Populate department dropdown
            var dSel=$('mvFilterDept');
            (refs.departments||[]).forEach(function(d){
                var o=document.createElement('option');o.value=d.name_ar;o.textContent=d.name_ar;o.setAttribute('data-id',d.department_id||d.id||'');dSel.appendChild(o);
            });
            // Populate section dropdown (all sections initially)
            populateSections('');
            // Populate division dropdown (all divisions initially)
            populateDivisions('');
        }catch(e){console.error('loadReferences: references',e);}

        try{
            var uRes=await API.get('/users');
            var users=(uRes.data||uRes)||[];
            var pbSel=$('mvPerformedBy');
            users.forEach(function(u){
                if(!u.emp_id) return;
                var o=document.createElement('option');
                o.value=u.emp_id;
                o.textContent=u.emp_id+' - '+(u.username||u.email||'');
                pbSel.appendChild(o);
            });
            // Default to current user's emp_id
            if(mvUser&&mvUser.emp_id) pbSel.value=mvUser.emp_id;
        }catch(e){console.error('loadReferences: users',e);}
    }

    /* ---- Populate section dropdown (cascade on department) ---- */
    function populateSections(deptId){
        var secSel=$('mvFilterSection');
        while(secSel.options.length>1) secSel.remove(1);
        var sections=allRefs.sections||[];
        sections.forEach(function(s){
            var secDeptId=String(s.department_id||'');
            if(deptId && secDeptId!==String(deptId)) return;
            var o=document.createElement('option');
            o.value=s.name_ar;o.textContent=s.name_ar;
            o.setAttribute('data-id',s.section_id||s.id||'');
            secSel.appendChild(o);
        });
        // Reset division dropdown too
        populateDivisions('');
    }

    /* ---- Populate division dropdown (cascade on section) ---- */
    function populateDivisions(sectionId){
        var dvSel=$('mvFilterDivision');
        // Keep the first "All Divisions" option
        while(dvSel.options.length>1) dvSel.remove(1);
        var divisions=allRefs.divisions||[];
        divisions.forEach(function(d){
            var divSecId=String(d.section_id||'');
            if(sectionId && divSecId!==String(sectionId)) return;
            var o=document.createElement('option');
            o.value=d.name_ar;o.textContent=d.name_ar;
            o.setAttribute('data-id',d.division_id||d.id||'');
            dvSel.appendChild(o);
        });
    }

    /* ---- Build latest movement per vehicle ---- */
    function buildLatestMap(){
        latestByVehicle={};
        // Sort by datetime desc to find latest per vehicle
        const sorted=[...allMovements].sort((a,b)=>(b.movement_datetime||'').localeCompare(a.movement_datetime||''));
        sorted.forEach(m=>{
            if(!latestByVehicle[m.vehicle_code]) latestByVehicle[m.vehicle_code]=m;
        });
    }

    /* ---- Load ---- */
    async function loadMovements(){
        showSkeleton();
        try{
            const res=await API.get('/movements');
            allMovements=(res.data||res)||[];
        }catch(e){allMovements=[];}
        buildLatestMap();
        applyFilters();
        loadStats();
    }

    async function loadStats(){
        try{
            var params=[];
            var sectorSel=$('mvFilterSector');
            var deptSel=$('mvFilterDept');
            var secSel=$('mvFilterSection');
            var divSel=$('mvFilterDivision');
            var dateFrom=$('mvDateFrom').value;
            var dateTo=$('mvDateTo').value;
            var gender=$('mvFilterGender').value;
            // Get sector_id from the selected sector
            if(sectorSel&&sectorSel.value){
                params.push('sector_id='+encodeURIComponent(sectorSel.value));
            }
            // Get department_id from the selected department name
            if(deptSel&&deptSel.value){
                var deptOpt=deptSel.options[deptSel.selectedIndex];
                var deptId=deptOpt?deptOpt.getAttribute('data-id'):'';
                if(deptId) params.push('department_id='+encodeURIComponent(deptId));
            }
            if(secSel&&secSel.value){
                var secOpt=secSel.options[secSel.selectedIndex];
                var secId=secOpt?secOpt.getAttribute('data-id'):'';
                if(secId) params.push('section_id='+encodeURIComponent(secId));
            }
            if(divSel&&divSel.value){
                var divOpt=divSel.options[divSel.selectedIndex];
                var divId=divOpt?divOpt.getAttribute('data-id'):'';
                if(divId) params.push('division_id='+encodeURIComponent(divId));
            }
            if(dateFrom) params.push('date_from='+encodeURIComponent(dateFrom));
            if(dateTo) params.push('date_to='+encodeURIComponent(dateTo));
            if(gender) params.push('gender='+encodeURIComponent(gender));
            var vehicleMode=$('mvFilterVehicleMode').value;
            if(vehicleMode) params.push('vehicle_mode='+encodeURIComponent(vehicleMode));
            var url='/movements/stats'+(params.length?'?'+params.join('&'):'');
            var res=await API.get(url);
            var s=res.data||res;
            lastStats=s;
            $('mvStatTotal').textContent=s.total_vehicles||0;
            $('mvStatPrivate').textContent=s.private_vehicles||0;
            $('mvStatShift').textContent=s.shift_vehicles||0;
            $('mvStatCheckedOut').textContent=s.checked_out||0;
            $('mvStatAvailable').textContent=s.available||0;
            $('mvStatUsedToday').textContent=s.used_in_period||0;
            $('mvStatUnused').textContent=s.unused_in_period||0;
            $('mvStatPrivateNotReturned').textContent=s.private_not_returned||0;
            $('mvStatMovements').textContent=s.total_movements||0;
            $('mvStatPickup').textContent=s.pickups||0;
            $('mvStatReturn').textContent=s.returns||0;
            $('mvStatEmployees').textContent=s.employee_count||0;
            var cats=s.categories||{};
            $('mvStatSedan').textContent=cats.sedan||0;
            $('mvStatPickupCat').textContent=cats.pickup||0;
            $('mvStatBus').textContent=cats.bus||0;
            var sts=s.statuses||{};
            $('mvStatOperational').textContent=sts.operational||0;
            // Build per-sector stats from client-side vehicle data
            renderSectorStats();
        }catch(e){console.error('loadStats',e);}
    }

    function renderSectorStats(){
        var container=$('mvSectorStats');
        var sectors=allRefs.sectors||[];
        if(!sectors.length){container.style.display='none';return;}
        var vehicles=getFilteredVehicles();
        var sectorCounts={};
        vehicles.forEach(function(v){
            var sid=v.sector_id||0;
            if(!sectorCounts[sid]) sectorCounts[sid]=0;
            sectorCounts[sid]++;
        });
        var html='';
        sectors.forEach(function(s){
            var count=sectorCounts[s.id]||0;
            html+='<div class="mv-stat"><div class="num">'+count+'</div><div class="lbl">'+(s.name||s.name_en)+'</div></div>';
        });
        if(html){
            container.innerHTML=html;
            container.style.display='flex';
        }else{
            container.style.display='none';
        }
    }

    function updateStats(){
        loadStats();
    }

    function applyFilters(){
        const q=($('mvSearch').value||'').toLowerCase();
        const t=$('mvFilterType').value;
        const c=$('mvFilterCondition').value;
        const dateFrom=$('mvDateFrom').value;
        const dateTo=$('mvDateTo').value;
        const vStatus=$('mvFilterVehicleStatus').value;
        const sectorId=$('mvFilterSector').value;
        const deptId=getSelId($('mvFilterDept'));
        const secId=getSelId($('mvFilterSection'));
        const divId=getSelId($('mvFilterDivision'));
        const gender=$('mvFilterGender').value;
        const vCode=$('mvFilterVehicle').value;
        const vMode=$('mvFilterVehicleMode').value;
        filteredMovements=allMovements.filter(m=>{
            if(t && m.operation_type!==t) return false;
            if(c && m.vehicle_condition!==c) return false;
            if(q && !((m.vehicle_code||'').toLowerCase().includes(q)||(m.performed_by||'').toLowerCase().includes(q))) return false;
            if(dateFrom){
                const md=(m.movement_datetime||'').substring(0,10);
                if(md<dateFrom) return false;
            }
            if(dateTo){
                const md=(m.movement_datetime||'').substring(0,10);
                if(md>dateTo) return false;
            }
            // Cross-reference vehicle data
            const v=vehicleMap[m.vehicle_code];
            const hasVehicleFilter=vStatus||sectorId||deptId||secId||divId||gender||vMode;
            if(hasVehicleFilter && !v) return false;
            if(vStatus && v && (v.status||'')!==vStatus) return false;
            if(sectorId && v && String(v.sector_id||'')!==String(sectorId)) return false;
            if(deptId && v && String(v.department_id||'')!==String(deptId)) return false;
            if(secId && v && String(v.section_id||'')!==String(secId)) return false;
            if(divId && v && String(v.division_id||'')!==String(divId)) return false;
            if(gender && v && (v.gender||'')!==gender) return false;
            if(vMode && v && (v.vehicle_mode||'')!==vMode) return false;
            if(vCode && m.vehicle_code!==vCode) return false;
            return true;
        });
        currentPage=1;
        render();
    }

    function render(){
        const start=(currentPage-1)*perPage;
        const page=filteredMovements.slice(start,start+perPage);
        const tbody=$('mvTableBody');
        if(!page.length){
            tbody.innerHTML='<tr><td colspan="9"><div class="mv-empty"><div class="ico">🔄</div><p>'+i18n.t('no_movements')+'</p></div></td></tr>';
            $('mvPagination').innerHTML='';
            return;
        }
        const condLabel=c=>c==='clean'?i18n.t('clean'):c==='acceptable'?i18n.t('acceptable'):c==='damaged'?i18n.t('damaged'):'—';
        const fuelLabel=f=>{const m={full:i18n.t('fuel_full'),three_quarter:i18n.t('fuel_three_quarter'),half:i18n.t('fuel_half'),quarter:i18n.t('fuel_quarter'),empty:i18n.t('fuel_empty')};return m[f]||'—';};
        const typeLabel=t=>t==='pickup'?i18n.t('pickup_operation'):i18n.t('return_operation');
        let h='';
        page.forEach((m,i)=>{
            const hasLoc=m.latitude&&m.longitude;
            // Check if vehicle is currently checked out (latest movement is pickup)
            const latest=latestByVehicle[m.vehicle_code];
            const isCheckedOut=latest && latest.operation_type==='pickup';
            h+='<tr>';
            h+='<td data-label="#">'+(start+i+1)+'</td>';
            h+='<td data-label="'+i18n.t('vehicle_code')+'"><strong>'+esc(m.vehicle_code)+'</strong></td>';
            h+='<td data-label="'+i18n.t('vehicle_type')+'"><span class="mv-badge '+m.operation_type+'">'+typeLabel(m.operation_type)+'</span></td>';
            h+='<td data-label="'+i18n.t('by')+'">'+esc(m.performed_by)+'</td>';
            h+='<td data-label="'+i18n.t('date')+'">'+esc((m.movement_datetime||'').replace('T',' ').substring(0,16))+'</td>';
            h+='<td data-label="'+i18n.t('condition')+'">'+(m.vehicle_condition?'<span class="mv-badge '+m.vehicle_condition+'">'+condLabel(m.vehicle_condition)+'</span>':'—')+'</td>';
            h+='<td data-label="'+i18n.t('fuel_level')+'">'+fuelLabel(m.fuel_level)+'</td>';
            h+='<td data-label="'+i18n.t('location')+'">'+(hasLoc?'<a href="https://www.google.com/maps?q='+m.latitude+','+m.longitude+'" target="_blank" title="Open Map">📍</a>':'—')+'</td>';
            h+='<td data-label="'+i18n.t('actions')+'" class="mv-actions">';
            if(isCheckedOut && m.id===latest.id && mvCanCreate){
                h+='<button onclick="MvPage.quickReturn(\''+esc(m.vehicle_code)+'\',\''+esc(m.performed_by)+'\')" title="'+i18n.t('return_vehicle')+'" style="color:#d63031;font-weight:700">↩️</button>';
            }
            h+='<button onclick="MvPage.view('+m.id+')" title="View">👁</button>';
            if(mvCanEdit) h+='<button onclick="MvPage.edit('+m.id+')" title="Edit">✏️</button>';
            if(mvCanDelete) h+='<button onclick="MvPage.del('+m.id+')" title="Delete">🗑️</button>';
            h+='</td></tr>';
        });
        tbody.innerHTML=h;
        renderPagination();
    }

    function renderPagination(){
        const totalItems=filteredMovements.length;
        const totalPg=Math.ceil(totalItems/perPage);
        const pg=$('mvPagination'),info=$('mvPaginationInfo');
        if(totalPg<=1){pg.innerHTML='';info.innerHTML=totalItems?'<span>'+i18n.t('total_records')+': '+totalItems+'</span>':'';return;}
        let h='<button '+(currentPage<=1?'disabled':'')+' onclick="MvPage.goPage('+(currentPage-1)+')">'+i18n.t('previous')+'</button>';
        let start=Math.max(1,currentPage-3),end=Math.min(totalPg,currentPage+3);
        if(start>1){h+='<button onclick="MvPage.goPage(1)">1</button>';if(start>2)h+='<span style="padding:0 4px">…</span>';}
        for(let i=start;i<=end;i++){
            h+='<button class="'+(i===currentPage?'active':'')+'" onclick="MvPage.goPage('+i+')">'+i+'</button>';
        }
        if(end<totalPg){if(end<totalPg-1)h+='<span style="padding:0 4px">…</span>';h+='<button onclick="MvPage.goPage('+totalPg+')">'+totalPg+'</button>';}
        h+='<button '+(currentPage>=totalPg?'disabled':'')+' onclick="MvPage.goPage('+(currentPage+1)+')">'+i18n.t('next')+'</button>';
        pg.innerHTML=h;
        info.innerHTML='<span>'+i18n.t('total_records')+': '+totalItems+' | '+i18n.t('page')+' '+currentPage+' '+i18n.t('of')+' '+totalPg+'</span>'+
            '<div class="pg-goto"><label>'+i18n.t('go_to_page')+':</label><input type="number" min="1" max="'+totalPg+'" id="mvGotoInput" value="'+currentPage+'"><button onclick="MvPage.gotoPage()">↵</button></div>';
    }

    /* ---- Modal ---- */
    function openModal(title){
        $('mvModalTitle').textContent=title||'Add Movement';
        $('mvModal').classList.add('show');
        pendingPhotos=[];
        existingPhotosHtml='';
        $('mvPhotosPreview').innerHTML='';
    }
    function closeModal(){$('mvModal').classList.remove('show');}

    $('mvBtnAdd').addEventListener('click',()=>{
        $('mvForm').reset();$('mvId').value='';
        if(mvUser&&mvUser.emp_id) $('mvPerformedBy').value=mvUser.emp_id;
        openModal('➕ Add Movement');
    });
    $('mvModalClose').addEventListener('click',closeModal);
    $('mvCancelBtn').addEventListener('click',closeModal);

    /* ---- Geolocation ---- */
    $('mvGetLoc').addEventListener('click',()=>{
        if(!navigator.geolocation){$('mvLocStatus').textContent='Geolocation not supported';return;}
        $('mvLocStatus').textContent='Getting location...';
        navigator.geolocation.getCurrentPosition(pos=>{
            $('mvLat').value=pos.coords.latitude.toFixed(8);
            $('mvLng').value=pos.coords.longitude.toFixed(8);
            $('mvLocStatus').textContent='✅ Location acquired';
        },err=>{
            $('mvLocStatus').textContent='❌ '+err.message;
        },{enableHighAccuracy:true,timeout:15000});
    });
    $('mvOpenMap').addEventListener('click',()=>{
        const lat=$('mvLat').value,lng=$('mvLng').value;
        if(lat&&lng) window.open('https://www.google.com/maps?q='+lat+','+lng,'_blank');
    });
    $('mvClearLoc').addEventListener('click',()=>{$('mvLat').value='';$('mvLng').value='';$('mvLocStatus').textContent='';});

    /* ---- Photos ---- */
    $('mvPhotoArea').addEventListener('click',()=>$('mvPhotoInput').click());
    $('mvPhotoArea').addEventListener('dragover',e=>{e.preventDefault();e.currentTarget.style.borderColor='var(--primary-main,#1a5276)';});
    $('mvPhotoArea').addEventListener('dragleave',e=>{e.currentTarget.style.borderColor='';});
    $('mvPhotoArea').addEventListener('drop',e=>{
        e.preventDefault();e.currentTarget.style.borderColor='';
        handleFiles(e.dataTransfer.files);
    });
    $('mvPhotoInput').addEventListener('change',e=>handleFiles(e.target.files));

    function handleFiles(files){
        const remaining=6-pendingPhotos.length;
        const arr=Array.from(files).slice(0,remaining);
        arr.forEach(f=>{
            if(!/^image\/(jpeg|png)$/.test(f.type))return;
            const reader=new FileReader();
            reader.onload=e=>{
                pendingPhotos.push(e.target.result);
                renderPhotosPrev();
            };
            reader.readAsDataURL(f);
        });
    }
    var existingPhotosHtml='';
    function renderPhotosPrev(){
        var html=existingPhotosHtml;
        if(pendingPhotos.length){
            if(html) html+='<div style="margin-bottom:8px;margin-top:8px"><strong>'+i18n.t('new_photos')+':</strong></div>';
            html+=pendingPhotos.map((p,i)=>{
                var safe=p.replace(/[^a-zA-Z0-9+/=:;,]/g,'');
                return '<img src="'+safe+'" class="thumb" onclick="MvPage.removePhoto('+i+')">'; 
            }).join('');
        }
        $('mvPhotosPreview').innerHTML=html;
    }

    /* ---- Filters ---- */
    function applyFiltersAndStats(){applyFilters();loadStats();}
    var _dateDebounce=null;
    function debouncedApplyFiltersAndStats(){
        clearTimeout(_dateDebounce);
        _dateDebounce=setTimeout(applyFiltersAndStats,300);
    }
    $('mvSearch').addEventListener('input',applyFilters);
    $('mvFilterType').addEventListener('change',applyFilters);
    $('mvFilterCondition').addEventListener('change',applyFilters);
    $('mvDateFrom').addEventListener('change',applyFiltersAndStats);
    $('mvDateTo').addEventListener('change',applyFiltersAndStats);
    $('mvDateFrom').addEventListener('input',debouncedApplyFiltersAndStats);
    $('mvDateTo').addEventListener('input',debouncedApplyFiltersAndStats);
    $('mvFilterVehicleStatus').addEventListener('change',applyFilters);
    $('mvFilterSector').addEventListener('change',applyFiltersAndStats);
    $('mvFilterDept').addEventListener('change',function(){
        // Cascade: repopulate sections based on selected department
        var deptId=getSelId($('mvFilterDept'));
        populateSections(deptId);
        applyFiltersAndStats();
    });
    $('mvFilterSection').addEventListener('change',function(){
        // Cascade: repopulate divisions based on selected section
        var secId=getSelId($('mvFilterSection'));
        populateDivisions(secId);
        applyFiltersAndStats();
    });
    $('mvFilterDivision').addEventListener('change',applyFiltersAndStats);
    $('mvFilterGender').addEventListener('change',applyFiltersAndStats);
    $('mvFilterVehicleMode').addEventListener('change',applyFiltersAndStats);
    $('mvFilterVehicle').addEventListener('change',applyFilters);

    /* ---- Print individual stat ---- */
    function getFilteredVehicles(){
        var allVehicles=Object.values(vehicleMap);
        var sectorId=$('mvFilterSector').value;
        var deptId=getSelId($('mvFilterDept'));
        var secId=getSelId($('mvFilterSection'));
        var divId=getSelId($('mvFilterDivision'));
        var gender=$('mvFilterGender').value;
        var vMode=$('mvFilterVehicleMode').value;
        return allVehicles.filter(function(v){
            if(sectorId && String(v.sector_id||'')!==String(sectorId)) return false;
            if(deptId && String(v.department_id||'')!==String(deptId)) return false;
            if(secId && String(v.section_id||'')!==String(secId)) return false;
            if(divId && String(v.division_id||'')!==String(divId)) return false;
            if(gender && (v.gender||'')!==gender) return false;
            if(vMode && (v.vehicle_mode||'')!==vMode) return false;
            return true;
        });
    }
    function getUsedVehicleCodes(){
        var dateFrom=$('mvDateFrom').value;
        var dateTo=$('mvDateTo').value;
        var codes={};
        allMovements.forEach(function(m){
            var md=(m.movement_datetime||'').substring(0,10);
            if((!dateFrom||md>=dateFrom)&&(!dateTo||md<=dateTo)){
                codes[m.vehicle_code]=true;
            }
        });
        return codes;
    }
    function getFilteredMovements(typeFilter){
        var dateFrom=$('mvDateFrom').value;
        var dateTo=$('mvDateTo').value;
        var deptId=getSelId($('mvFilterDept'));
        var secId=getSelId($('mvFilterSection'));
        var divId=getSelId($('mvFilterDivision'));
        var gender=$('mvFilterGender').value;
        var vMode=$('mvFilterVehicleMode').value;
        return allMovements.filter(function(m){
            if(typeFilter && m.operation_type!==typeFilter) return false;
            var md=(m.movement_datetime||'').substring(0,10);
            if(dateFrom && md<dateFrom) return false;
            if(dateTo && md>dateTo) return false;
            var v=vehicleMap[m.vehicle_code];
            if((deptId||secId||divId||gender||vMode) && !v) return false;
            if(deptId && v && String(v.department_id||'')!==String(deptId)) return false;
            if(secId && v && String(v.section_id||'')!==String(secId)) return false;
            if(divId && v && String(v.division_id||'')!==String(divId)) return false;
            if(gender && v && (v.gender||'')!==gender) return false;
            if(vMode && v && (v.vehicle_mode||'')!==vMode) return false;
            return true;
        });
    }
    function printVehicleList(title,vehicles){
        var modeLabel=function(m){return m==='private'?i18n.t('private_vehicles'):m==='shift'?i18n.t('shift_vehicles'):(m||'—');};
        var catLabel=function(c){return c==='sedan'?i18n.t('sedan'):c==='pickup'?i18n.t('pickup_category'):c==='bus'?i18n.t('bus'):(c||'—');};
        var statusLabel=function(s){return s==='operational'?i18n.t('operational'):s==='maintenance'?i18n.t('under_maintenance'):s==='out_of_service'?i18n.t('out_of_service'):(s||'—');};
        var genderLabel=function(g){return g==='men'?i18n.t('men'):g==='women'?i18n.t('women'):'—';};
        var html='<html dir="rtl"><head><meta charset="utf-8"><title>'+esc(title)+'</title>';
        html+='<style>body{font-family:Arial,sans-serif;direction:rtl;margin:20px}table{width:100%;border-collapse:collapse;font-size:12px;margin-bottom:20px}th,td{border:1px solid #ccc;padding:6px 8px;text-align:right}th{background:#f0f0f0}h2{text-align:center;margin:10px 0}.info{text-align:center;color:#666;margin-bottom:16px}@media print{body{margin:10px}}</style>';
        html+='</head><body>';
        html+='<h2>'+esc(title)+'</h2>';
        var s=lastStats||{};
        if(s.date_from||s.date_to) html+='<p class="info">'+i18n.t('from')+' '+(s.date_from||'—')+' — '+i18n.t('to')+' '+(s.date_to||'—')+'</p>';
        html+='<p class="info">'+i18n.t('total_records')+': '+vehicles.length+'</p>';
        html+='<table><thead><tr><th>#</th><th>'+i18n.t('vehicle_code')+'</th><th>'+i18n.t('vehicle_type')+'</th><th>'+i18n.t('vehicle_category')+'</th><th>'+i18n.t('vehicle_mode')+'</th><th>'+i18n.t('vehicle_status')+'</th><th>'+i18n.t('department')+'</th><th>'+i18n.t('section')+'</th><th>'+i18n.t('division')+'</th><th>'+i18n.t('gender')+'</th></tr></thead><tbody>';
        vehicles.forEach(function(v,i){
            html+='<tr><td>'+(i+1)+'</td><td>'+esc(v.vehicle_code)+'</td><td>'+esc(v.type||v.vehicle_type||'')+'</td><td>'+catLabel(v.vehicle_category)+'</td><td>'+modeLabel(v.vehicle_mode)+'</td><td>'+statusLabel(v.status)+'</td><td>'+esc(v.department_name||'')+'</td><td>'+esc(v.section_name||'')+'</td><td>'+esc(v.division_name||'')+'</td><td>'+genderLabel(v.gender)+'</td></tr>';
        });
        html+='</tbody></table></body></html>';
        var w=window.open('','_blank');w.document.write(html);w.document.close();w.print();
    }
    function printMovementList(title,movements){
        var typeLabel=function(t){return t==='pickup'?i18n.t('pickup_operation'):i18n.t('return_operation');};
        var condLabel=function(c){return c==='clean'?i18n.t('clean'):c==='acceptable'?i18n.t('acceptable'):c==='damaged'?i18n.t('damaged'):'—';};
        var genderLabel=function(g){return g==='men'?i18n.t('men'):g==='women'?i18n.t('women'):'—';};
        var html='<html dir="rtl"><head><meta charset="utf-8"><title>'+esc(title)+'</title>';
        html+='<style>body{font-family:Arial,sans-serif;direction:rtl;margin:20px}table{width:100%;border-collapse:collapse;font-size:12px;margin-bottom:20px}th,td{border:1px solid #ccc;padding:6px 8px;text-align:right}th{background:#f0f0f0}h2{text-align:center;margin:10px 0}.info{text-align:center;color:#666;margin-bottom:16px}@media print{body{margin:10px}}</style>';
        html+='</head><body>';
        html+='<h2>'+esc(title)+'</h2>';
        var s=lastStats||{};
        if(s.date_from||s.date_to) html+='<p class="info">'+i18n.t('from')+' '+(s.date_from||'—')+' — '+i18n.t('to')+' '+(s.date_to||'—')+'</p>';
        html+='<p class="info">'+i18n.t('total_records')+': '+movements.length+'</p>';
        html+='<table><thead><tr><th>#</th><th>'+i18n.t('vehicle_code')+'</th><th>'+i18n.t('operation_type')+'</th><th>'+i18n.t('by')+'</th><th>'+i18n.t('date')+'</th><th>'+i18n.t('condition')+'</th><th>'+i18n.t('fuel_level')+'</th><th>'+i18n.t('department')+'</th><th>'+i18n.t('section')+'</th><th>'+i18n.t('division')+'</th><th>'+i18n.t('gender')+'</th></tr></thead><tbody>';
        movements.forEach(function(m,i){
            var v=vehicleMap[m.vehicle_code]||{};
            html+='<tr><td>'+(i+1)+'</td><td>'+esc(m.vehicle_code)+'</td><td>'+typeLabel(m.operation_type)+'</td><td>'+esc(m.performed_by||'')+'</td><td>'+esc((m.movement_datetime||'').replace('T',' ').substring(0,16))+'</td><td>'+condLabel(m.vehicle_condition)+'</td><td>'+(m.fuel_level||'—')+'</td><td>'+esc(v.department_name||'')+'</td><td>'+esc(v.section_name||'')+'</td><td>'+esc(v.division_name||'')+'</td><td>'+genderLabel(v.gender)+'</td></tr>';
        });
        html+='</tbody></table></body></html>';
        var w=window.open('','_blank');w.document.write(html);w.document.close();w.print();
    }
    function printEmployeeList(title,movements){
        var empMap={};
        movements.forEach(function(m){
            var key=m.performed_by||'';
            if(!key) return;
            if(!empMap[key]) empMap[key]={emp_id:key,pickups:0,returns:0,total:0};
            empMap[key].total++;
            if(m.operation_type==='pickup') empMap[key].pickups++;
            else if(m.operation_type==='return') empMap[key].returns++;
        });
        var employees=Object.values(empMap).sort(function(a,b){return b.total-a.total;});
        var html='<html dir="rtl"><head><meta charset="utf-8"><title>'+esc(title)+'</title>';
        html+='<style>body{font-family:Arial,sans-serif;direction:rtl;margin:20px}table{width:100%;border-collapse:collapse;font-size:12px;margin-bottom:20px}th,td{border:1px solid #ccc;padding:6px 8px;text-align:right}th{background:#f0f0f0}h2{text-align:center;margin:10px 0}.info{text-align:center;color:#666;margin-bottom:16px}@media print{body{margin:10px}}</style>';
        html+='</head><body>';
        html+='<h2>'+esc(title)+'</h2>';
        var s=lastStats||{};
        if(s.date_from||s.date_to) html+='<p class="info">'+i18n.t('from')+' '+(s.date_from||'—')+' — '+i18n.t('to')+' '+(s.date_to||'—')+'</p>';
        html+='<p class="info">'+i18n.t('employee_count')+': '+employees.length+'</p>';
        html+='<table><thead><tr><th>#</th><th>'+i18n.t('employee_id')+'</th><th>'+i18n.t('total_movements')+'</th><th>'+i18n.t('operation_type_pickup')+'</th><th>'+i18n.t('operation_type_return')+'</th></tr></thead><tbody>';
        employees.forEach(function(e,i){
            html+='<tr><td>'+(i+1)+'</td><td>'+esc(e.emp_id)+'</td><td>'+e.total+'</td><td>'+e.pickups+'</td><td>'+e.returns+'</td></tr>';
        });
        html+='</tbody></table></body></html>';
        var w=window.open('','_blank');w.document.write(html);w.document.close();w.print();
    }
    function printStatDetail(statKey){
        var vehicles=getFilteredVehicles();
        var usedCodes=getUsedVehicleCodes();
        switch(statKey){
            case 'total_vehicles':
                printVehicleList(i18n.t('total_vehicles'),vehicles);
                break;
            case 'private_vehicles':
                printVehicleList(i18n.t('private_vehicles'),vehicles.filter(function(v){return v.vehicle_mode==='private';}));
                break;
            case 'shift_vehicles':
                printVehicleList(i18n.t('shift_vehicles'),vehicles.filter(function(v){return v.vehicle_mode==='shift';}));
                break;
            case 'checked_out':
                printVehicleList(i18n.t('checked_out'),vehicles.filter(function(v){
                    var latest=latestByVehicle[v.vehicle_code];
                    return latest&&latest.operation_type==='pickup';
                }));
                break;
            case 'available':
                printVehicleList(i18n.t('available_vehicles'),vehicles.filter(function(v){
                    var latest=latestByVehicle[v.vehicle_code];
                    return !latest||latest.operation_type!=='pickup';
                }));
                break;
            case 'used_in_period':
                printVehicleList(i18n.t('used_in_period'),vehicles.filter(function(v){return usedCodes[v.vehicle_code];}));
                break;
            case 'unused_in_period':
                printVehicleList(i18n.t('unused_vehicles'),vehicles.filter(function(v){return !usedCodes[v.vehicle_code];}));
                break;
            case 'private_not_returned':
                printVehicleList(i18n.t('private_not_returned'),vehicles.filter(function(v){
                    if(v.vehicle_mode!=='private') return false;
                    var latest=latestByVehicle[v.vehicle_code];
                    return latest&&latest.operation_type==='pickup';
                }));
                break;
            case 'total_movements':
                printMovementList(i18n.t('total_movements'),getFilteredMovements(null));
                break;
            case 'pickups':
                printMovementList(i18n.t('pickup_operation'),getFilteredMovements('pickup'));
                break;
            case 'returns':
                printMovementList(i18n.t('return_operation'),getFilteredMovements('return'));
                break;
            case 'employees':
                printEmployeeList(i18n.t('employee_count'),getFilteredMovements(null));
                break;
            case 'sedan':
                printVehicleList(i18n.t('sedan'),vehicles.filter(function(v){return v.vehicle_category==='sedan';}));
                break;
            case 'pickup_cat':
                printVehicleList(i18n.t('pickup_category'),vehicles.filter(function(v){return v.vehicle_category==='pickup';}));
                break;
            case 'bus':
                printVehicleList(i18n.t('bus'),vehicles.filter(function(v){return v.vehicle_category==='bus';}));
                break;
            case 'operational':
                printVehicleList(i18n.t('operational'),vehicles.filter(function(v){return v.status==='operational';}));
                break;
            default:
                return;
        }
    }
    // Attach click handlers to all stat cards
    document.querySelectorAll('.mv-stat[data-stat]').forEach(function(el){
        el.addEventListener('click',function(){
            var key=this.getAttribute('data-stat');
            if(key) printStatDetail(key);
        });
    });

    /* ---- Print Report ---- */
    $('mvBtnPrint').addEventListener('click',function(){
        const data=filteredMovements;
        if(!data.length){UI.showToast(i18n.t('no_data_for_print'),'error');return;}
        const typeLabel=t=>t==='pickup'?i18n.t('pickup_operation'):i18n.t('return_operation');
        const condLabel=c=>c==='clean'?i18n.t('clean'):c==='acceptable'?i18n.t('acceptable'):c==='damaged'?i18n.t('damaged'):'—';
        const modeLabel=m=>m==='private'?i18n.t('private_vehicles'):m==='shift'?i18n.t('shift_vehicles'):'—';
        const catLabel=c=>c==='sedan'?i18n.t('sedan'):c==='pickup'?i18n.t('pickup_category'):c==='bus'?i18n.t('bus'):(c||'—');
        const statusLabel=s=>s==='operational'?i18n.t('operational'):s==='maintenance'?i18n.t('under_maintenance'):s==='out_of_service'?i18n.t('out_of_service'):(s||'—');
        var s=lastStats||{};
        var cats=s.categories||{};
        var sts=s.statuses||{};
        let html='<html dir="rtl"><head><meta charset="utf-8"><title>'+i18n.t('movements_report')+'</title><style>body{font-family:Arial,sans-serif;direction:rtl;margin:20px}table{width:100%;border-collapse:collapse;font-size:12px;margin-bottom:20px}th,td{border:1px solid #ccc;padding:6px 8px;text-align:right}th{background:#f0f0f0}h2,h3{text-align:center;margin:10px 0}.stats-grid{display:flex;flex-wrap:wrap;gap:8px;margin:12px 0;justify-content:center}.stat-box{border:1px solid #ccc;border-radius:8px;padding:10px 16px;text-align:center;min-width:120px}.stat-box .snum{font-size:1.4rem;font-weight:700;color:#1a5276}.stat-box .slbl{font-size:.8rem;color:#666;margin-top:2px}@media print{body{margin:10px}.stats-grid{page-break-inside:avoid}}</style></head><body>';
        html+='<h2>'+i18n.t('movements_report')+'</h2>';
        if(s.date_from||s.date_to) html+='<p style="text-align:center;color:#666">'+i18n.t('from')+': '+(s.date_from||'—')+' — '+i18n.t('to')+': '+(s.date_to||'—')+'</p>';

        // Statistics summary section
        html+='<h3>📊 '+i18n.t('statistics')+'</h3>';
        html+='<div class="stats-grid">';
        html+='<div class="stat-box"><div class="snum">'+(s.total_vehicles||0)+'</div><div class="slbl">'+i18n.t('total_vehicles')+'</div></div>';
        html+='<div class="stat-box"><div class="snum">'+(s.private_vehicles||0)+'</div><div class="slbl">'+i18n.t('private_vehicles')+'</div></div>';
        html+='<div class="stat-box"><div class="snum">'+(s.shift_vehicles||0)+'</div><div class="slbl">'+i18n.t('shift_vehicles')+'</div></div>';
        html+='<div class="stat-box"><div class="snum">'+(s.checked_out||0)+'</div><div class="slbl">'+i18n.t('checked_out')+'</div></div>';
        html+='<div class="stat-box"><div class="snum">'+(s.available||0)+'</div><div class="slbl">'+i18n.t('available_vehicles')+'</div></div>';
        html+='<div class="stat-box"><div class="snum">'+(s.used_in_period||0)+'</div><div class="slbl">'+i18n.t('used_in_period')+'</div></div>';
        html+='<div class="stat-box"><div class="snum">'+(s.unused_in_period||0)+'</div><div class="slbl">'+i18n.t('unused_vehicles')+'</div></div>';
        html+='<div class="stat-box"><div class="snum">'+(s.private_not_returned||0)+'</div><div class="slbl">'+i18n.t('private_not_returned')+'</div></div>';
        html+='<div class="stat-box"><div class="snum">'+(s.employee_count||0)+'</div><div class="slbl">'+i18n.t('employee_count')+'</div></div>';
        html+='</div>';

        // Category & status breakdown
        html+='<table><thead><tr><th>'+i18n.t('vehicle_category')+'</th><th>'+i18n.t('count')+'</th></tr></thead><tbody>';
        Object.entries(cats).forEach(function(e){html+='<tr><td>'+catLabel(e[0])+'</td><td>'+e[1]+'</td></tr>';});
        html+='</tbody></table>';
        html+='<table><thead><tr><th>'+i18n.t('vehicle_status')+'</th><th>'+i18n.t('count')+'</th></tr></thead><tbody>';
        Object.entries(sts).forEach(function(e){html+='<tr><td>'+statusLabel(e[0])+'</td><td>'+e[1]+'</td></tr>';});
        html+='</tbody></table>';

        // Movement counts
        html+='<table><thead><tr><th>'+i18n.t('total_movements')+'</th><th>'+i18n.t('operation_type_pickup')+'</th><th>'+i18n.t('operation_type_return')+'</th></tr></thead><tbody>';
        html+='<tr><td>'+(s.total_movements||0)+'</td><td>'+(s.pickups||0)+'</td><td>'+(s.returns||0)+'</td></tr>';
        html+='</tbody></table>';

        // Movements detail table
        html+='<h3>📋 '+i18n.t('movements')+'</h3>';
        html+='<p>'+i18n.t('total_records')+': '+data.length+'</p>';
        html+='<table><thead><tr><th>#</th><th>'+i18n.t('vehicle_code')+'</th><th>'+i18n.t('vehicle_type')+'</th><th>'+i18n.t('vehicle_mode')+'</th><th>'+i18n.t('vehicle_category')+'</th><th>'+i18n.t('by')+'</th><th>'+i18n.t('date')+'</th><th>'+i18n.t('condition')+'</th><th>'+i18n.t('fuel_level')+'</th></tr></thead><tbody>';
        data.forEach((m,i)=>{
            const v=vehicleMap[m.vehicle_code]||{};
            html+='<tr><td>'+(i+1)+'</td><td>'+esc(m.vehicle_code)+'</td><td>'+typeLabel(m.operation_type)+'</td>';
            html+='<td>'+modeLabel(v.vehicle_mode)+'</td><td>'+catLabel(v.vehicle_category)+'</td>';
            html+='<td>'+esc(m.performed_by)+'</td><td>'+esc((m.movement_datetime||'').replace('T',' ').substring(0,16))+'</td>';
            html+='<td>'+condLabel(m.vehicle_condition)+'</td><td>'+(m.fuel_level||'—')+'</td></tr>';
        });
        html+='</tbody></table></body></html>';
        const w=window.open('','_blank');w.document.write(html);w.document.close();w.print();
    });

    /* ---- Save ---- */
    $('mvSaveBtn').addEventListener('click',async()=>{
        const id=$('mvId').value;
        const data={
            vehicle_code:$('mvVehicleCode').value.trim(),
            operation_type:$('mvOperationType').value,
            performed_by:$('mvPerformedBy').value.trim(),
            vehicle_condition:$('mvCondition').value||null,
            fuel_level:$('mvFuel').value||null,
            latitude:$('mvLat').value||null,
            longitude:$('mvLng').value||null,
            notes:$('mvNotes').value.trim(),
        };
        if(!data.vehicle_code||!data.operation_type||!data.performed_by){
            UI.showToast('Please fill required fields','error');return;
        }
        try{
            let res;
            if(id){
                res=await API.put('/movements/'+id,data);
                // Upload new photos if any
                if(pendingPhotos.length){
                    try{await API.post('/movements/'+id+'/photos',{photos:pendingPhotos});}catch(pe){console.error(pe);}
                }
                UI.showToast('Movement updated','success');
            }else{
                res=await API.post('/movements',data);
                UI.showToast('Movement created','success');
                // Upload photos if any
                const newId=(res.data||res).id;
                if(pendingPhotos.length&&newId){
                    try{await API.post('/movements/'+newId+'/photos',{photos:pendingPhotos});}catch(pe){console.error(pe);}
                }
            }
            closeModal();
            loadMovements();
        }catch(e){UI.showToast(e.message||'Error','error');}
    });

    /* ---- Global methods ---- */
    window.MvPage={
        goPage(p){const totalPg=Math.ceil(filteredMovements.length/perPage);if(p<1)p=1;if(p>totalPg)p=totalPg;currentPage=p;render();window.scrollTo({top:0,behavior:'smooth'});},
        gotoPage(){const inp=$('mvGotoInput');if(inp){const p=parseInt(inp.value);if(p&&p>=1)this.goPage(p);}},
        removePhoto(i){pendingPhotos.splice(i,1);renderPhotosPrev();},
        async view(id){
            try{
                const res=await API.get('/movements/'+id);
                const m=res.data||res;
                const condL=c=>c==='clean'?i18n.t('clean'):c==='acceptable'?i18n.t('acceptable'):c==='damaged'?i18n.t('damaged'):'—';
                const fuelL=f=>{const mp={full:i18n.t('fuel_full'),three_quarter:i18n.t('fuel_three_quarter'),half:i18n.t('fuel_half'),quarter:i18n.t('fuel_quarter'),empty:i18n.t('fuel_empty')};return mp[f]||'—';};
                const typeL=t=>t==='pickup'?i18n.t('pickup_operation'):i18n.t('return_operation');
                let h='<div class="d-row"><span class="d-lbl">'+i18n.t('vehicle_code')+'</span><span class="d-val">'+esc(m.vehicle_code)+'</span></div>';
                h+='<div class="d-row"><span class="d-lbl">'+i18n.t('operation_type')+'</span><span class="d-val"><span class="mv-badge '+m.operation_type+'">'+typeL(m.operation_type)+'</span></span></div>';
                h+='<div class="d-row"><span class="d-lbl">'+i18n.t('by')+'</span><span class="d-val">'+esc(m.performed_by)+'</span></div>';
                h+='<div class="d-row"><span class="d-lbl">'+i18n.t('date')+'</span><span class="d-val">'+esc(m.movement_datetime)+'</span></div>';
                h+='<div class="d-row"><span class="d-lbl">'+i18n.t('condition')+'</span><span class="d-val">'+condL(m.vehicle_condition)+'</span></div>';
                h+='<div class="d-row"><span class="d-lbl">'+i18n.t('fuel_level')+'</span><span class="d-val">'+fuelL(m.fuel_level)+'</span></div>';
                if(m.latitude&&m.longitude){
                    h+='<div class="d-row"><span class="d-lbl">'+i18n.t('location')+'</span><span class="d-val"><a href="https://www.google.com/maps?q='+m.latitude+','+m.longitude+'" target="_blank">📍 '+m.latitude+', '+m.longitude+'</a></span></div>';
                }
                h+='<div class="d-row"><span class="d-lbl">'+i18n.t('notes')+'</span><span class="d-val">'+esc(m.notes||'—')+'</span></div>';
                h+='<div class="d-row"><span class="d-lbl">'+i18n.t('added_by')+'</span><span class="d-val">'+esc(m.created_by||'—')+'</span></div>';
                if(m.updated_by) h+='<div class="d-row"><span class="d-lbl" data-label-ar="آخر تعديل بواسطة" data-label-en="Updated By">Updated By</span><span class="d-val">'+esc(m.updated_by)+'</span></div>';
                if(m.created_at) h+='<div class="d-row"><span class="d-lbl" data-label-ar="تاريخ الإنشاء" data-label-en="Created At">Created At</span><span class="d-val">'+esc((m.created_at||'').replace('T',' ').substring(0,19))+'</span></div>';
                if(m.updated_at && m.updated_at!==m.created_at) h+='<div class="d-row"><span class="d-lbl" data-label-ar="تاريخ التحديث" data-label-en="Updated At">Updated At</span><span class="d-val">'+esc((m.updated_at||'').replace('T',' ').substring(0,19))+'</span></div>';
                // Photos
                const photos=m.photos||[];
                if(photos.length){
                    h+='<h4 style="margin-top:16px">📷 Photos</h4><div class="d-photos">';
                    photos.forEach(p=>{
                        var pu=p.photo_url||'';
                        // Fix legacy URLs missing base path (e.g. /public/uploads/... → /vehicle_management/public/uploads/...)
                        var basePath='';
                        try{basePath=(new URL(API.baseUrl)).pathname;}catch(e){basePath=API.baseUrl||'';}
                        basePath=basePath.replace(/\/+$/,'');
                        if(basePath && basePath!=='/' && pu.indexOf('/public/uploads/')===0 && pu.indexOf(basePath)!==0) pu=basePath+pu;
                        h+='<img src="'+esc(pu)+'" onclick="window.open(this.src,\'_blank\')">';
                    });
                    h+='</div>';
                }
                $('mvDetailBody').innerHTML=h;
                $('mvDetailTitle').textContent='Movement #'+id;
                $('mvDetailModal').classList.add('show');
            }catch(e){UI.showToast('Failed to load details','error');}
        },
        async edit(id){
            try{
                const res=await API.get('/movements/'+id);
                const m=res.data||res;
                $('mvId').value=m.id;
                $('mvVehicleCode').value=m.vehicle_code||'';
                $('mvOperationType').value=m.operation_type||'';
                $('mvPerformedBy').value=m.performed_by||'';
                $('mvCondition').value=m.vehicle_condition||'';
                $('mvFuel').value=m.fuel_level||'';
                $('mvLat').value=m.latitude||'';
                $('mvLng').value=m.longitude||'';
                $('mvNotes').value=m.notes||'';
                openModal('✏️ Edit Movement #'+id);
                // Show existing photos in the edit form (after openModal which clears them)
                var ePhotos=m.photos||[];
                existingPhotosHtml='';
                if(ePhotos.length){
                    existingPhotosHtml='<div style="margin-bottom:8px"><strong data-label-ar="الصور الحالية" data-label-en="Current Photos">Current Photos:</strong></div><div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:8px">';
                    ePhotos.forEach(function(p){
                        var pu=p.photo_url||'';
                        var basePath='';
                        try{basePath=(new URL(API.baseUrl)).pathname;}catch(e){basePath=API.baseUrl||'';}
                        basePath=basePath.replace(/\/+$/,'');
                        if(basePath && basePath!=='/' && pu.indexOf('/public/uploads/')===0 && pu.indexOf(basePath)!==0) pu=basePath+pu;
                        existingPhotosHtml+='<img src="'+esc(pu)+'" style="width:80px;height:80px;object-fit:cover;border-radius:6px;border:2px solid var(--border-default,#ddd)" onclick="window.open(this.src,\'_blank\')">';
                    });
                    existingPhotosHtml+='</div>';
                }
                renderPhotosPrev();
            }catch(e){UI.showToast('Failed to load','error');}
        },
        async del(id){
            if(!confirm('Delete this movement?'))return;
            try{
                await API.del('/movements/'+id);
                UI.showToast('Movement deleted','success');
                loadMovements();
            }catch(e){UI.showToast(e.message||'Error','error');}
        },
        async quickReturn(vehicleCode, performedBy){
            if(!confirm(i18n.t('return_vehicle')+' '+vehicleCode+'?'))return;
            try{
                await API.post('/movements',{
                    vehicle_code: vehicleCode,
                    operation_type: 'return',
                    performed_by: (mvUser&&mvUser.emp_id)||performedBy
                });
                UI.showToast(i18n.t('vehicle_returned_success'),'success');
                loadMovements();
            }catch(e){UI.showToast(e.message||'Error','error');}
        }
    };

    $('mvDetailClose').addEventListener('click',()=>$('mvDetailModal').classList.remove('show'));

    /* ---- Translate static HTML elements ---- */
    function translateStatic(){
        // Retry if i18n translations are not loaded yet
        if(!i18n.strings || !Object.keys(i18n.strings).length){
            setTimeout(translateStatic,100);
            return;
        }
        const txt={
            mvOptAllTypes:'all_types', mvOptPickup:'pickup_operation', mvOptReturn:'return_operation',
            mvOptAllConditions:'all_conditions', mvOptClean:'clean', mvOptAcceptable:'acceptable', mvOptDamaged:'damaged',
            mvOptAllVehicleStatuses:'all_vehicle_statuses', mvOptOperational:'operational', mvOptMaintenance:'under_maintenance', mvOptOutOfService:'out_of_service',
            mvOptAllDepts:'all_departments', mvOptAllSections:'all_sections', mvOptAllDivisions:'all_divisions',
            mvOptAllSectors:'all_sectors',
            mvOptAllGenders:'all_genders', mvOptMen:'men', mvOptWomen:'women',
            mvOptAllModes:'all_modes', mvOptPrivateMode:'private_vehicles', mvOptShiftMode:'shift_vehicles',
            mvOptAllVehicles:'all_vehicles',
            mvBtnPrintText:'print_report', mvBtnAddText:'add_movement',
            mvThCode:'vehicle_code', mvThType:'vehicle_type', mvThBy:'by', mvThDate:'date',
            mvThCondition:'condition', mvThFuel:'fuel_level', mvThActions:'actions'
        };
        Object.entries(txt).forEach(function(e){var el=$(e[0]);if(el)el.textContent=i18n.t(e[1]);});
        var lblFrom=$('mvLabelFrom');if(lblFrom)lblFrom.textContent=i18n.t('from')+':';
        var lblTo=$('mvLabelTo');if(lblTo)lblTo.textContent=i18n.t('to')+':';
        var search=$('mvSearch');if(search)search.placeholder=i18n.t('search_movement');
        var printBtn=$('mvBtnPrint');if(printBtn)printBtn.title=i18n.t('print_report');
        // Translate all elements with data-lang-key attribute
        document.querySelectorAll('[data-lang-key]').forEach(function(el){
            var key=el.getAttribute('data-lang-key');
            if(key) el.textContent=i18n.t(key);
        });
    }

    // Init with retry for Auth (matches working fragment pattern)
    (function mvInit(){
        if(window.__pageDenied) return;
        var user=Auth.getUser();
        if(!user){setTimeout(mvInit,100);return;}
        mvUser=user;
        mvPerms=(mvUser&&mvUser.permissions)||[];
        mvCanCreate=mvPerms.includes('manage_movements')||mvPerms.includes('*');
        mvCanEdit=mvPerms.includes('manage_movements')||mvPerms.includes('*');
        mvCanDelete=mvPerms.includes('manage_movements')||mvPerms.includes('*');
        if(!mvCanCreate){var addBtn=$('mvBtnAdd');if(addBtn)addBtn.style.display='none';}
        translateStatic();
        // Await loadReferences so vehicleMap is populated before loadMovements applies filters
        loadReferences().then(function(){ loadMovements(); });
    })();
})();
</script>
<?php $pageScripts = ob_get_clean(); ?>
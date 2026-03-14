<?php
/**
 * Vehicles Fragment — Table view, CRUD, Handover/Receive
 * Loaded inside dashboard.php shell.
 */
?>
<style>
.toolbar{display:flex;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px}
.toolbar .search-box{position:relative;flex:1;min-width:200px;max-width:360px}
.toolbar .search-box input{width:100%;padding-inline-end:36px}
.toolbar .search-box .search-icon{position:absolute;top:50%;inset-inline-end:12px;transform:translateY(-50%);color:var(--text-secondary);pointer-events:none}
.toolbar .filters{display:flex;gap:10px;flex-wrap:wrap}
.toolbar .filters select{min-width:140px}
.toolbar-end{display:flex;align-items:center;gap:10px;margin-inline-start:auto}
.v-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
@media(max-width:768px){.v-stats{grid-template-columns:repeat(2,1fr)}}
@media(max-width:480px){.v-stats{grid-template-columns:1fr 1fr}}
.v-stat{background:var(--bg-card);padding:16px;border-radius:12px;box-shadow:var(--card-shadow);border:1px solid var(--border-default);text-align:center}
.v-stat .v-stat-val{font-size:1.5rem;font-weight:700;color:var(--text-primary)}
.v-stat .v-stat-lbl{font-size:.8rem;color:var(--text-secondary);margin-top:4px}
.badge-available{background:#d4edda;color:#155724;display:inline-block;padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:600}
.badge-checked-out{background:#fff3cd;color:#856404;display:inline-block;padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:600}
.data-table .table-actions .btn-icon{width:30px;height:30px;font-size:.8rem}
.pagination{display:flex;align-items:center;justify-content:center;gap:6px;margin-top:24px;flex-wrap:wrap}
.pagination button{min-width:36px;height:36px;border:1px solid var(--border-default);background:var(--bg-card);color:var(--text-primary);border-radius:8px;cursor:pointer;font-size:.85rem;transition:all .3s}
.pagination button:hover:not(:disabled){background:var(--primary-main);color:var(--text-light)}
.pagination button.active{background:var(--primary-main);color:var(--text-light);border-color:var(--primary-main)}
.pagination button:disabled{opacity:.4;cursor:not-allowed}
.pagination .pg-prev,.pagination .pg-next{min-width:auto;padding:0 12px;font-weight:600}
.pagination-info{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-top:12px;font-size:.85rem;color:var(--text-secondary)}
.pagination-info .pg-goto{display:flex;align-items:center;gap:6px}
.pagination-info .pg-goto input{width:60px;height:32px;text-align:center;border:1px solid var(--border-default);border-radius:6px;background:var(--bg-card);color:var(--text-primary);font-size:.85rem}
.pagination-info .pg-goto button{height:32px;padding:0 10px;border:1px solid var(--primary-main);background:var(--primary-main);color:var(--text-light);border-radius:6px;cursor:pointer;font-size:.8rem}
.modal-lg .modal-content{max-width:720px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:0 16px}
@media(max-width:600px){.form-grid{grid-template-columns:1fr}}
.form-grid .full-width{grid-column:1/-1}
.empty-state{text-align:center;padding:48px 24px;color:var(--text-secondary)}
.empty-state .empty-icon{font-size:3rem;margin-bottom:12px;opacity:.5}
@media(max-width:768px){.toolbar{flex-direction:column;align-items:stretch}.toolbar-end{margin-inline-start:0;justify-content:space-between}.toolbar .search-box{max-width:100%}}
</style>

<div class="page-header">
    <h2 id="pageTitle">Vehicle Management</h2>
</div>

<!-- Stats -->
<div class="v-stats">
    <div class="v-stat"><div class="v-stat-val" id="sTotal">—</div><div class="v-stat-lbl" id="lblTotal">Total</div></div>
    <div class="v-stat"><div class="v-stat-val" id="sOp" style="color:var(--status-success)">—</div><div class="v-stat-lbl" id="lblOp">Operational</div></div>
    <div class="v-stat"><div class="v-stat-val" id="sMaint" style="color:var(--status-warning)">—</div><div class="v-stat-lbl" id="lblMaint">Maintenance</div></div>
    <div class="v-stat"><div class="v-stat-val" id="sOos" style="color:var(--status-danger)">—</div><div class="v-stat-lbl" id="lblOos">Out of Service</div></div>
</div>

<!-- Toolbar -->
<div class="toolbar">
    <div class="search-box">
        <input type="text" class="form-control" id="searchInput" placeholder="Search vehicle...">
        <span class="search-icon">🔍</span>
    </div>
    <div class="filters">
        <select class="form-select" id="filterStatus">
            <option value="">All Statuses</option>
            <option value="operational">Operational</option>
            <option value="maintenance">Maintenance</option>
            <option value="out_of_service">Out of Service</option>
        </select>
        <select class="form-select" id="filterAvailability">
            <option value="">All Availability</option>
            <option value="available">Available Only</option>
            <option value="checked_out">Checked Out Only</option>
        </select>
        <select class="form-select" id="filterDept"><option value="">All Departments</option></select>
        <select class="form-select" id="filterSection"><option value="">All Sections</option></select>
        <select class="form-select" id="filterMode">
            <option value="">All Modes</option>
            <option value="private">Private</option>
            <option value="shift">Shift</option>
        </select>
        <select class="form-select" id="filterGender">
            <option value="">All Genders</option>
            <option value="men">Men</option>
            <option value="women">Women</option>
        </select>
    </div>
    <div class="toolbar-end">
        <button class="btn btn-outline btn-sm" id="btnPrintReport" title="Print Report">🖨️ <span id="lblPrint">Print Report</span></button>
        <button class="btn btn-outline btn-sm" id="btnExportExcel" title="Export Excel">📥 <span id="lblExport">Export Excel</span></button>
        <button class="btn btn-primary" id="btnAddVehicle">➕ <span id="lblAddVehicle">Add Vehicle</span></button>
    </div>
</div>

<!-- Table View -->
<div id="vehicleTable">
    <div class="table-wrapper table-responsive">
        <table class="data-table" id="vehiclesDataTable">
            <thead><tr>
                <th>#</th><th id="thCode">Vehicle Code</th><th id="thType">Type</th><th id="thCategory">Category</th><th id="thAvail">Availability</th><th id="thDriver">Driver</th><th id="thPhone">Phone</th><th id="thDept">Department</th><th id="thStatus">Status</th><th id="thMode">Mode</th><th id="thGender">Gender</th><th id="thYear">Year</th><th id="thActions">Actions</th>
            </tr></thead>
            <tbody id="tableBody"></tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<div class="pagination" id="pagination"></div>
<div class="pagination-info" id="paginationInfo"></div>

<!-- ===== ADD/EDIT VEHICLE MODAL ===== -->
<div class="modal" id="vehicleModal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3 id="modalTitle">Add Vehicle</h3>
            <button class="modal-close" data-action="close-modal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="vehicleForm">
                <input type="hidden" id="fId">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" id="lblFCode">Vehicle Code *</label>
                        <input type="text" class="form-control" id="fCode" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" id="lblFType">Vehicle Type *</label>
                        <input type="text" class="form-control" id="fType" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" id="lblFCategory">Vehicle Category</label>
                        <select class="form-select" id="fCategory">
                            <option value="">— Choose —</option>
                            <option value="sedan">Sedan</option>
                            <option value="pickup">Pickup</option>
                            <option value="bus">Bus</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" id="lblFYear">Manufacture Year *</label>
                        <input type="number" class="form-control" id="fYear" min="1990" max="2030" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" id="lblFMode">Vehicle Mode</label>
                        <select class="form-select" id="fMode">
                            <option value="">— Choose —</option>
                            <option value="private">Private</option>
                            <option value="shift">Shift</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" id="lblFGender">Gender</label>
                        <select class="form-select" id="fGender">
                            <option value="">— Unspecified —</option>
                            <option value="men">Men</option>
                            <option value="women">Women</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" id="lblFEmpId">Driver Employee ID</label>
                        <input type="text" class="form-control" id="fEmpId">
                    </div>
                    <div class="form-group">
                        <label class="form-label" id="lblFDriverName">Driver Name</label>
                        <input type="text" class="form-control" id="fDriverName">
                    </div>
                    <div class="form-group">
                        <label class="form-label" id="lblFDriverPhone">Driver Phone</label>
                        <input type="text" class="form-control" id="fDriverPhone">
                    </div>
                    <div class="form-group">
                        <label class="form-label" id="lblFStatus">Status</label>
                        <select class="form-select" id="fStatus">
                            <option value="operational">Operational</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="out_of_service">Out of Service</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" id="lblFDept">Department</label>
                        <select class="form-select" id="fDept"><option value="">— Choose —</option></select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" id="lblFSection">Section</label>
                        <select class="form-select" id="fSection"><option value="">— Choose —</option></select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" id="lblFDivision">Division</label>
                        <select class="form-select" id="fDivision"><option value="">— Choose —</option></select>
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label" id="lblFNotes">Notes</label>
                        <textarea class="form-control" id="fNotes" rows="2"></textarea>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" data-action="close-modal" id="btnCancelVehicle">Cancel</button>
            <button class="btn btn-primary" id="btnSaveVehicle">Save</button>
        </div>
    </div>
</div>

<!-- ===== VEHICLE DETAILS MODAL ===== -->
<div class="modal" id="detailsModal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3 id="detailsModalTitle">Vehicle Details</h3>
            <button class="modal-close" data-action="close-modal">&times;</button>
        </div>
        <div class="modal-body" id="detailsBody"></div>
    </div>
</div>

<!-- ===== DELETE CONFIRMATION ===== -->
<div class="modal" id="deleteModal">
    <div class="modal-content" style="max-width:400px">
        <div class="modal-body" style="padding:32px;text-align:center">
            <div style="font-size:2.5rem;margin-bottom:16px">⚠️</div>
            <p style="margin-bottom:24px;font-size:1rem" id="deleteConfirmText">Are you sure you want to delete this vehicle?</p>
            <input type="hidden" id="deleteVehicleId">
            <div style="display:flex;gap:12px;justify-content:center">
                <button class="btn btn-danger" id="btnConfirmDelete">Delete</button>
                <button class="btn btn-ghost" data-action="close-modal" id="btnCancelDelete">Cancel</button>
            </div>
        </div>
    </div>
</div>

<?php
$pageScripts = <<<'SCRIPT'
<script>
(function(){
    'use strict';
    const $=id=>document.getElementById(id);
    let allVehicles=[], filteredVehicles=[], currentPage=1, perPage=100, refs={departments:[],sections:[],divisions:[]};
    let canCreate=false, canEdit=false, canDelete=false;

    const STATUS={operational:{key:'operational',cls:'badge-success'},maintenance:{key:'under_maintenance',cls:'badge-warning'},out_of_service:{key:'out_of_service',cls:'badge-danger'}};
    function badge(s){const m=STATUS[s]||{key:'operational',cls:'badge-info'};return '<span class="badge '+m.cls+'">'+i18n.t(m.key)+'</span>';}
    function esc(s){return UI._escapeHtml(s||'—');}
    function categoryLabel(c){const m={pickup:'pickup',bus:'bus',sedan:'sedan'};return m[c]?i18n.t(m[c]):'—';}

    /* --- Translate all static HTML text --- */
    function translateStatic(){
        const t=k=>i18n.t(k);
        // Page header
        $('pageTitle').textContent=t('vehicle_management');
        // Stats labels
        $('lblTotal').textContent=t('total');
        $('lblOp').textContent=t('operational');
        $('lblMaint').textContent=t('under_maintenance');
        $('lblOos').textContent=t('out_of_service');
        // Search
        $('searchInput').placeholder=t('search_vehicle');
        // Filter dropdowns
        const fs=$('filterStatus');fs.options[0].text=t('all_statuses');fs.options[1].text=t('operational');fs.options[2].text=t('under_maintenance');fs.options[3].text=t('out_of_service');
        const fa=$('filterAvailability');fa.options[0].text=t('all_availability');fa.options[1].text=t('available_only');fa.options[2].text=t('checked_out_only');
        $('filterDept').options[0].text=t('all_departments');
        $('filterSection').options[0].text=t('all_sections');
        const fm=$('filterMode');fm.options[0].text=t('all_modes');fm.options[1].text=t('private');fm.options[2].text=t('shift');
        const fg=$('filterGender');fg.options[0].text=t('all_genders');fg.options[1].text=t('men');fg.options[2].text=t('women');
        // Toolbar buttons
        $('lblPrint').textContent=t('print_report');
        $('lblExport').textContent=t('export_excel');
        $('lblAddVehicle').textContent=t('add_vehicle');
        // Table headers
        $('thCode').textContent=t('vehicle_code');
        $('thType').textContent=t('vehicle_type');
        $('thCategory').textContent=t('vehicle_category');
        $('thAvail').textContent=t('availability');
        $('thDriver').textContent=t('driver');
        $('thPhone').textContent=t('phone');
        $('thDept').textContent=t('department');
        $('thStatus').textContent=t('status');
        $('thMode').textContent=t('vehicle_mode');
        $('thGender').textContent=t('gender');
        $('thYear').textContent=t('manufacture_year');
        $('thActions').textContent=t('actions');
        // Modal form labels
        $('lblFCode').textContent=t('vehicle_code')+' *';
        $('lblFType').textContent=t('vehicle_type')+' *';
        $('lblFCategory').textContent=t('vehicle_category');
        $('lblFYear').textContent=t('manufacture_year')+' *';
        $('lblFMode').textContent=t('vehicle_mode');
        $('lblFGender').textContent=t('gender');
        $('lblFEmpId').textContent=t('emp_id');
        $('lblFDriverName').textContent=t('driver_name');
        $('lblFDriverPhone').textContent=t('driver_phone');
        $('lblFStatus').textContent=t('status');
        $('lblFDept').textContent=t('department');
        $('lblFSection').textContent=t('section');
        $('lblFDivision').textContent=t('division');
        $('lblFNotes').textContent=t('notes');
        // Modal form selects
        const fCat=$('fCategory');fCat.options[0].text='— '+t('choose')+' —';fCat.options[1].text=t('sedan');fCat.options[2].text=t('pickup');fCat.options[3].text=t('bus');
        const fMd=$('fMode');fMd.options[0].text='— '+t('choose')+' —';fMd.options[1].text=t('private');fMd.options[2].text=t('shift');
        const fGn=$('fGender');fGn.options[0].text='— '+t('unspecified')+' —';fGn.options[1].text=t('men');fGn.options[2].text=t('women');
        const fSt=$('fStatus');fSt.options[0].text=t('operational');fSt.options[1].text=t('under_maintenance');fSt.options[2].text=t('out_of_service');
        $('fDept').options[0].text='— '+t('choose')+' —';
        $('fSection').options[0].text='— '+t('choose')+' —';
        $('fDivision').options[0].text='— '+t('choose')+' —';
        // Modal buttons
        $('btnCancelVehicle').textContent=t('cancel');
        $('btnSaveVehicle').textContent=t('save');
        // Details modal
        $('detailsModalTitle').textContent=t('vehicle_details');
        // Delete modal
        $('deleteConfirmText').textContent=t('confirm_delete');
        $('btnConfirmDelete').textContent=t('delete');
        $('btnCancelDelete').textContent=t('cancel');
    }

    /* --- Load references for dropdowns --- */
    async function loadRefs(){
        try{
            const res=await API.get('/references');
            refs=res.data||res;
            const dd=$('filterDept'),fd=$('fDept'),fs=$('filterSection');
            (refs.departments||[]).forEach(d=>{
                const o1=new Option(d.name_ar||d.name_en,d.department_id);
                const o2=new Option(d.name_ar||d.name_en,d.department_id);
                dd.appendChild(o1);fd.appendChild(o2);
            });
            (refs.sections||[]).forEach(s=>{
                fs.appendChild(new Option(s.name_ar||s.name_en,s.section_id));
            });
        }catch(e){}
    }

    /* Cascading department -> section -> division */
    $('fDept').addEventListener('change',()=>{
        const did=parseInt($('fDept').value);
        const s=$('fSection');s.innerHTML='<option value="">— '+i18n.t('choose')+' —</option>';
        $('fDivision').innerHTML='<option value="">— '+i18n.t('choose')+' —</option>';
        (refs.sections||[]).filter(sc=>sc.department_id==did).forEach(sc=>{
            s.appendChild(new Option(sc.name_ar||sc.name_en,sc.section_id));
        });
    });
    $('fSection').addEventListener('change',()=>{
        const sid=parseInt($('fSection').value);
        const d=$('fDivision');d.innerHTML='<option value="">— '+i18n.t('choose')+' —</option>';
        (refs.divisions||[]).filter(dv=>dv.section_id==sid).forEach(dv=>{
            d.appendChild(new Option(dv.name_ar||dv.name_en,dv.division_id));
        });
    });

    /* --- Load stats --- */
    async function loadStats(){
        try{
            const res=await API.get('/vehicles/stats');
            const d=res.data||res;
            $('sTotal').textContent=d.total||0;
            $('sOp').textContent=d.operational||0;
            $('sMaint').textContent=d.maintenance||0;
            $('sOos').textContent=d.out_of_service||0;
        }catch(e){}
    }

    /* --- Load vehicles --- */
    async function loadVehicles(){
        try{
            const params=[];
            const st=$('filterStatus').value;if(st)params.push('status='+st);
            const dp=$('filterDept').value;if(dp)params.push('department_id='+dp);
            const md=$('filterMode').value;if(md)params.push('vehicle_mode='+md);
            const gn=$('filterGender').value;if(gn)params.push('gender='+gn);
            const q=params.length?'?'+params.join('&'):'';
            const res=await API.get('/vehicles'+q);
            allVehicles=Array.isArray(res.data)?res.data:(Array.isArray(res)?res:[]);
        }catch(e){allVehicles=[];}
        applySearch();
    }

    function applySearch(){
        const q=$('searchInput').value.trim().toLowerCase();
        const avail=$('filterAvailability').value;
        filteredVehicles=allVehicles.filter(v=>{
            if(q && !((v.vehicle_code||'').toLowerCase().includes(q)||(v.driver_name||'').toLowerCase().includes(q))) return false;
            if(avail==='available' && !v.available) return false;
            if(avail==='checked_out' && v.available) return false;
            return true;
        });
        currentPage=1;
        render();
    }

    function render(){
        const start=(currentPage-1)*perPage, end=start+perPage;
        const page=filteredVehicles.slice(start,end);
        renderTable(page);
        renderPagination();
    }

    function availBadge(v){return v.available?'<span class="badge-available">'+i18n.t('available_for_handover')+' ✅</span>':'<span class="badge-checked-out">'+i18n.t('currently_checked_out')+' 🔒</span>';}

    function renderTable(list){
        const tb=$('tableBody');
        if(!list.length){tb.innerHTML='<tr><td colspan="13" class="text-center" style="padding:32px;color:var(--text-secondary)">'+i18n.t('no_vehicles')+'</td></tr>';return;}
        const genderLabel=g=>g==='men'?i18n.t('men'):g==='women'?i18n.t('women'):'—';
        const modeLabel=m=>m==='private'?i18n.t('private'):m==='shift'?i18n.t('shift'):'—';
        let h='';
        list.forEach((v,i)=>{
            h+='<tr>';
            h+='<td>'+((currentPage-1)*perPage+i+1)+'</td>';
            h+='<td><strong>'+esc(v.vehicle_code)+'</strong></td>';
            h+='<td>'+esc(v.type)+'</td>';
            h+='<td>'+categoryLabel(v.vehicle_category)+'</td>';
            h+='<td>'+availBadge(v)+'</td>';
            h+='<td>'+esc(v.driver_name)+'</td>';
            h+='<td>'+esc(v.driver_phone)+'</td>';
            h+='<td>'+esc(v.department_name_ar)+'</td>';
            h+='<td>'+badge(v.status)+'</td>';
            h+='<td>'+modeLabel(v.vehicle_mode)+'</td>';
            h+='<td>'+genderLabel(v.gender)+'</td>';
            h+='<td>'+(v.manufacture_year||'—')+'</td>';
            h+='<td class="table-actions">';
            h+='<button class="btn btn-ghost btn-icon" onclick="VPage.view('+v.id+')" title="'+i18n.t('view')+'">👁️</button>';
            if(canEdit) h+='<button class="btn btn-ghost btn-icon" onclick="VPage.edit('+v.id+')" title="'+i18n.t('edit')+'">✏️</button>';
            if(canDelete) h+='<button class="btn btn-ghost btn-icon" onclick="VPage.del('+v.id+')" title="'+i18n.t('delete')+'" style="color:var(--status-danger)">🗑️</button>';
            h+='</td></tr>';
        });
        tb.innerHTML=h;
    }

    function renderPagination(){
        const totalItems=filteredVehicles.length;
        const totalPg=Math.ceil(totalItems/perPage);
        const pg=$('pagination');
        const info=$('paginationInfo');
        if(totalPg<=1){pg.innerHTML='';info.innerHTML='<span>'+i18n.t('total_records')+': '+totalItems+'</span>';return;}
        let h='<button class="pg-prev" '+(currentPage<=1?'disabled':'')+' onclick="VPage.goPage('+(currentPage-1)+')">'+i18n.t('previous')+'</button>';
        let start=Math.max(1,currentPage-3), end=Math.min(totalPg,currentPage+3);
        if(start>1){h+='<button onclick="VPage.goPage(1)">1</button>';if(start>2)h+='<span style="padding:0 4px">…</span>';}
        for(let i=start;i<=end;i++){
            h+='<button class="'+(i===currentPage?'active':'')+'" onclick="VPage.goPage('+i+')">'+i+'</button>';
        }
        if(end<totalPg){if(end<totalPg-1)h+='<span style="padding:0 4px">…</span>';h+='<button onclick="VPage.goPage('+totalPg+')">'+totalPg+'</button>';}
        h+='<button class="pg-next" '+(currentPage>=totalPg?'disabled':'')+' onclick="VPage.goPage('+(currentPage+1)+')">'+i18n.t('next')+'</button>';
        pg.innerHTML=h;
        info.innerHTML='<span>'+i18n.t('total_records')+': '+totalItems+' | '+i18n.t('page')+' '+currentPage+' '+i18n.t('of')+' '+totalPg+'</span>'+
            '<div class="pg-goto"><label>'+i18n.t('go_to_page')+':</label><input type="number" min="1" max="'+totalPg+'" id="gotoPageInput" value="'+currentPage+'"><button onclick="VPage.gotoPage()">↵</button></div>';
    }

    /* --- Filters --- */
    $('filterStatus').addEventListener('change',loadVehicles);
    $('filterAvailability').addEventListener('change',applySearch);
    $('filterDept').addEventListener('change',loadVehicles);
    $('filterSection').addEventListener('change',loadVehicles);
    $('filterMode').addEventListener('change',loadVehicles);
    $('filterGender').addEventListener('change',loadVehicles);
    let searchTimer;
    $('searchInput').addEventListener('input',()=>{clearTimeout(searchTimer);searchTimer=setTimeout(applySearch,300);});

    /* --- Print Report --- */
    $('btnPrintReport').addEventListener('click',function(){
        const data=filteredVehicles;
        if(!data.length){UI.showToast(i18n.t('no_data_for_print'),'error');return;}
        const statusLabel=s=>s==='operational'?i18n.t('operational'):s==='maintenance'?i18n.t('under_maintenance'):s==='out_of_service'?i18n.t('out_of_service'):'';
        let html='<html dir="rtl"><head><meta charset="utf-8"><title>'+i18n.t('vehicles_report')+'</title><style>body{font-family:Arial,sans-serif;direction:rtl}table{width:100%;border-collapse:collapse;font-size:12px}th,td{border:1px solid #ccc;padding:6px 8px;text-align:right}th{background:#f0f0f0}h2{text-align:center}.avail{color:green}.noavail{color:#b8860b}@media print{body{margin:0}}</style></head><body>';
        html+='<h2>'+i18n.t('vehicles_report')+'</h2><p>'+i18n.t('total')+': '+data.length+'</p>';
        html+='<table><thead><tr><th>#</th><th>'+i18n.t('vehicle_code')+'</th><th>'+i18n.t('vehicle_type')+'</th><th>'+i18n.t('vehicle_category')+'</th><th>'+i18n.t('availability')+'</th><th>'+i18n.t('driver')+'</th><th>'+i18n.t('phone')+'</th><th>'+i18n.t('department')+'</th><th>'+i18n.t('status')+'</th><th>'+i18n.t('manufacture_year')+'</th></tr></thead><tbody>';
        data.forEach((v,i)=>{
            html+='<tr><td>'+(i+1)+'</td><td>'+esc(v.vehicle_code)+'</td><td>'+esc(v.type)+'</td><td>'+categoryLabel(v.vehicle_category)+'</td>';
            html+='<td class="'+(v.available?'avail':'noavail')+'">'+(v.available?i18n.t('available_for_handover'):i18n.t('currently_checked_out'))+'</td>';
            html+='<td>'+esc(v.driver_name)+'</td><td>'+esc(v.driver_phone)+'</td><td>'+esc(v.department_name_ar)+'</td>';
            html+='<td>'+statusLabel(v.status)+'</td><td>'+(v.manufacture_year||'—')+'</td></tr>';
        });
        html+='</tbody></table></body></html>';
        const w=window.open('','_blank');w.document.write(html);w.document.close();w.print();
    });

    /* --- Export Excel --- */
    $('btnExportExcel').addEventListener('click',function(){
        const data=filteredVehicles;
        if(!data.length){UI.showToast(i18n.t('no_data_for_export'),'error');return;}
        const genderLabel=g=>g==='men'?i18n.t('men'):g==='women'?i18n.t('women'):'';
        const modeLabel=m=>m==='private'?i18n.t('private'):m==='shift'?i18n.t('shift'):'';
        const statusLabel=s=>s==='operational'?i18n.t('operational'):s==='maintenance'?i18n.t('under_maintenance'):s==='out_of_service'?i18n.t('out_of_service'):'';
        const headers=['#',i18n.t('vehicle_code'),i18n.t('vehicle_type'),i18n.t('vehicle_category'),i18n.t('availability'),i18n.t('driver_name'),i18n.t('driver_phone'),i18n.t('department'),i18n.t('status'),i18n.t('vehicle_mode'),i18n.t('gender'),i18n.t('manufacture_year'),i18n.t('emp_id'),i18n.t('notes')];
        let csv='\uFEFF'+headers.join(',')+'\n';
        data.forEach((v,i)=>{
            const row=[
                i+1,
                '"'+(v.vehicle_code||'').replace(/"/g,'""')+'"',
                '"'+(v.type||'').replace(/"/g,'""')+'"',
                '"'+categoryLabel(v.vehicle_category)+'"',
                '"'+(v.available?i18n.t('available_for_handover'):i18n.t('currently_checked_out'))+'"',
                '"'+(v.driver_name||'').replace(/"/g,'""')+'"',
                '"'+(v.driver_phone||'').replace(/"/g,'""')+'"',
                '"'+(v.department_name_ar||'').replace(/"/g,'""')+'"',
                '"'+statusLabel(v.status)+'"',
                '"'+modeLabel(v.vehicle_mode)+'"',
                '"'+genderLabel(v.gender)+'"',
                v.manufacture_year||'',
                '"'+(v.emp_id||'').replace(/"/g,'""')+'"',
                '"'+(v.notes||'').replace(/"/g,'""')+'"'
            ];
            csv+=row.join(',')+'\n';
        });
        const blob=new Blob([csv],{type:'text/csv;charset=utf-8;'});
        const link=document.createElement('a');
        link.href=URL.createObjectURL(blob);
        link.download='vehicles_export_'+new Date().toISOString().slice(0,10)+'.csv';
        link.click();
        UI.showToast(i18n.t('export_success'),'success');
    });

    /* --- Add Vehicle --- */
    $('btnAddVehicle').addEventListener('click',()=>{
        $('modalTitle').textContent=i18n.t('add_vehicle');
        $('vehicleForm').reset();$('fId').value='';
        UI.showModal('vehicleModal');
    });

    /* --- Save Vehicle --- */
    $('btnSaveVehicle').addEventListener('click',async()=>{
        const id=$('fId').value;
        const data={
            vehicle_code:$('fCode').value.trim(),
            type:$('fType').value.trim(),
            vehicle_category:$('fCategory').value||null,
            manufacture_year:parseInt($('fYear').value)||null,
            vehicle_mode:$('fMode').value,
            gender:$('fGender').value||null,
            emp_id:$('fEmpId').value.trim(),
            driver_name:$('fDriverName').value.trim(),
            driver_phone:$('fDriverPhone').value.trim(),
            status:$('fStatus').value,
            department_id:$('fDept').value?parseInt($('fDept').value):null,
            section_id:$('fSection').value?parseInt($('fSection').value):null,
            division_id:$('fDivision').value?parseInt($('fDivision').value):null,
            notes:$('fNotes').value.trim()
        };
        if(!data.vehicle_code||!data.type||!data.manufacture_year){UI.showToast(i18n.t('required_fields'),'error');return;}
        try{
            if(id){await API.put('/vehicles/'+id,data);UI.showToast(i18n.t('vehicle_updated'),'success');}
            else{await API.post('/vehicles',data);UI.showToast(i18n.t('vehicle_added'),'success');}
            UI.hideModal('vehicleModal');
            loadVehicles();loadStats();
        }catch(e){UI.showToast(e.message||i18n.t('error'),'error');}
    });

    /* --- Global methods for onclick --- */
    window.VPage={
        goPage(p){const totalPg=Math.ceil(filteredVehicles.length/perPage);if(p<1)p=1;if(p>totalPg)p=totalPg;currentPage=p;render();window.scrollTo({top:0,behavior:'smooth'});},

        gotoPage(){const inp=$('gotoPageInput');if(inp){const p=parseInt(inp.value);if(p&&p>=1)this.goPage(p);}},

        async view(id){
            try{
                const res=await API.get('/vehicles/'+id);
                const v=res.data||res;
                let h='<div class="form-grid">';
                h+='<div class="v-row"><span class="v-label">'+i18n.t('vehicle_code')+'</span><span class="v-val">'+esc(v.vehicle_code)+'</span></div>';
                h+='<div class="v-row"><span class="v-label">'+i18n.t('vehicle_type')+'</span><span class="v-val">'+esc(v.type)+'</span></div>';
                h+='<div class="v-row"><span class="v-label">'+i18n.t('vehicle_category')+'</span><span class="v-val">'+categoryLabel(v.vehicle_category)+'</span></div>';
                h+='<div class="v-row"><span class="v-label">'+i18n.t('manufacture_year')+'</span><span class="v-val">'+(v.manufacture_year||'—')+'</span></div>';
                h+='<div class="v-row"><span class="v-label">'+i18n.t('status')+'</span><span class="v-val">'+badge(v.status)+'</span></div>';
                const gl=v.gender==='men'?i18n.t('men'):v.gender==='women'?i18n.t('women'):'—';
                const ml=v.vehicle_mode==='private'?i18n.t('private'):v.vehicle_mode==='shift'?i18n.t('shift'):'—';
                h+='<div class="v-row"><span class="v-label">'+i18n.t('vehicle_mode')+'</span><span class="v-val">'+ml+'</span></div>';
                h+='<div class="v-row"><span class="v-label">'+i18n.t('gender')+'</span><span class="v-val">'+gl+'</span></div>';
                h+='<div class="v-row"><span class="v-label">'+i18n.t('driver')+'</span><span class="v-val">'+esc(v.driver_name)+'</span></div>';
                h+='<div class="v-row"><span class="v-label">'+i18n.t('phone')+'</span><span class="v-val">'+esc(v.driver_phone)+'</span></div>';
                h+='<div class="v-row"><span class="v-label">'+i18n.t('emp_id')+'</span><span class="v-val">'+esc(v.emp_id)+'</span></div>';
                h+='<div class="v-row"><span class="v-label">'+i18n.t('department')+'</span><span class="v-val">'+esc(v.department_name_ar)+'</span></div>';
                h+='<div class="v-row"><span class="v-label">'+i18n.t('section')+'</span><span class="v-val">'+esc(v.section_name_ar)+'</span></div>';
                h+='<div class="v-row"><span class="v-label">'+i18n.t('division')+'</span><span class="v-val">'+esc(v.division_name_ar)+'</span></div>';
                h+='<div class="v-row"><span class="v-label">'+i18n.t('notes')+'</span><span class="v-val">'+esc(v.notes)+'</span></div>';
                h+='<div class="v-row"><span class="v-label">'+i18n.t('created_at')+'</span><span class="v-val">'+esc(v.created_at)+'</span></div>';
                if(v.updated_at) h+='<div class="v-row"><span class="v-label">'+i18n.t('updated_at')+'</span><span class="v-val">'+esc(v.updated_at)+'</span></div>';
                h+='</div>';
                $('detailsBody').innerHTML=h;
                UI.showModal('detailsModal');
            }catch(e){UI.showToast(i18n.t('load_failed'),'error');}
        },

        async edit(id){
            try{
                const res=await API.get('/vehicles/'+id);
                const v=res.data||res;
                $('modalTitle').textContent=i18n.t('edit')+' '+i18n.t('vehicle_management');
                $('fId').value=v.id;
                $('fCode').value=v.vehicle_code||'';
                $('fType').value=v.type||'';
                $('fCategory').value=v.vehicle_category||'';
                $('fYear').value=v.manufacture_year||'';
                $('fMode').value=v.vehicle_mode||'';
                $('fGender').value=v.gender||'';
                $('fEmpId').value=v.emp_id||'';
                $('fDriverName').value=v.driver_name||'';
                $('fDriverPhone').value=v.driver_phone||'';
                $('fStatus').value=v.status||'operational';
                $('fDept').value=v.department_id||'';
                $('fDept').dispatchEvent(new Event('change'));
                setTimeout(()=>{$('fSection').value=v.section_id||'';$('fSection').dispatchEvent(new Event('change'));setTimeout(()=>{$('fDivision').value=v.division_id||'';},50);},50);
                $('fNotes').value=v.notes||'';
                UI.showModal('vehicleModal');
            }catch(e){UI.showToast(i18n.t('load_failed'),'error');}
        },

        del(id){
            $('deleteVehicleId').value=id;
            UI.showModal('deleteModal');
        }
    };

    /* --- Confirm Delete --- */
    $('btnConfirmDelete').addEventListener('click',async()=>{
        const id=$('deleteVehicleId').value;
        try{
            await API.del('/vehicles/'+id);
            UI.showToast(i18n.t('vehicle_deleted'),'success');
            UI.hideModal('deleteModal');
            loadVehicles();loadStats();
        }catch(e){UI.showToast(e.message||i18n.t('error'),'error');}
    });

    /* --- Init --- */
    document.addEventListener('DOMContentLoaded',async()=>{
        await new Promise(r=>setTimeout(r,150));
        // Load user permissions
        var user=Auth.getUser();
        var perms=(user&&user.permissions)||[];
        canCreate=perms.includes('manage_vehicles')||perms.includes('*');
        canEdit=perms.includes('manage_vehicles')||perms.includes('*');
        canDelete=perms.includes('manage_vehicles')||perms.includes('*');
        if(!canCreate) $('btnAddVehicle').style.display='none';
        translateStatic();
        await loadRefs();
        loadStats();
        loadVehicles();
        if(new URLSearchParams(location.search).get('action')==='add'&&canCreate){
            $('btnAddVehicle').click();
        }
    });
})();
</script>
SCRIPT;
?>

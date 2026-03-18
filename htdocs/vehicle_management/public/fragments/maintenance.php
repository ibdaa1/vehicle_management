<?php
/**
 * Maintenance Fragment — Vehicle Maintenance Records
 * Full CRUD inside the dashboard.
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

<div class="page-header"><h2 id="mtPageTitle" data-label-en="Maintenance" data-label-ar="الصيانة">Maintenance</h2></div>

<!-- Stats -->
<div class="mt-stats">
    <div class="mt-stat"><div class="num" id="mtStatTotal">0</div><div class="lbl" id="mtLblTotal" data-label-en="Total Records" data-label-ar="إجمالي السجلات">Total Records</div></div>
    <div class="mt-stat routine"><div class="num" id="mtStatRoutine">0</div><div class="lbl" id="mtLblRoutine" data-label-en="Routine Maintenance" data-label-ar="صيانة دورية">Routine Maintenance</div></div>
    <div class="mt-stat emergency"><div class="num" id="mtStatEmergency">0</div><div class="lbl" id="mtLblEmergency" data-label-en="Emergency" data-label-ar="طوارئ">Emergency</div></div>
    <div class="mt-stat overdue"><div class="num" id="mtStatOverdue">0</div><div class="lbl" id="mtLblOverdue" data-label-en="Overdue" data-label-ar="متأخرة">Overdue</div></div>
</div>

<!-- Toolbar -->
<div class="mt-toolbar">
    <div class="search-box">
        <span class="ico">🔍</span>
        <input type="text" id="mtSearch" placeholder="Search maintenance..." data-placeholder-en="Search maintenance..." data-placeholder-ar="بحث في الصيانة...">
    </div>
    <select id="mtFilterType">
        <option value="" id="mtOptAllTypes" data-label-en="All Types" data-label-ar="كل الأنواع">All Types</option>
        <option value="Routine" id="mtOptRoutine" data-label-en="Routine Maintenance" data-label-ar="صيانة دورية">Routine Maintenance</option>
        <option value="Emergency" id="mtOptEmergency" data-label-en="Emergency" data-label-ar="طوارئ">Emergency</option>
        <option value="Technical Check" id="mtOptTechnical" data-label-en="Technical Check" data-label-ar="فحص فني">Technical Check</option>
        <option value="Mechanical" id="mtOptMechanical" data-label-en="Mechanical" data-label-ar="ميكانيكي">Mechanical</option>
    </select>
    <button class="btn btn-primary btn-sm btn-add" id="mtBtnAdd" data-label-en="➕ Add Maintenance Record" data-label-ar="➕ إضافة سجل صيانة">➕ <span id="mtBtnAddText">Add Record</span></button>
</div>

<!-- Table -->
<div class="table-responsive">
<table class="mt-table data-table">
    <thead><tr>
        <th>#</th>
        <th id="mtThVehicle" data-label-en="Vehicle Code" data-label-ar="رقم المركبة">Vehicle Code</th>
        <th id="mtThVisitDate" data-label-en="Visit Date" data-label-ar="تاريخ الزيارة">Visit Date</th>
        <th id="mtThNextVisit" data-label-en="Next Visit Date" data-label-ar="تاريخ الزيارة القادمة">Next Visit Date</th>
        <th id="mtThType" data-label-en="Maintenance Type" data-label-ar="نوع الصيانة">Maintenance Type</th>
        <th id="mtThLocation" data-label-en="Location" data-label-ar="الموقع">Location</th>
        <th id="mtThCreatedBy" data-label-en="Created By" data-label-ar="أنشأ بواسطة">Created By</th>
        <th id="mtThNotes" data-label-en="Notes" data-label-ar="ملاحظات">Notes</th>
        <th id="mtThActions" data-label-en="Actions" data-label-ar="الإجراءات">Actions</th>
    </tr></thead>
    <tbody id="mtTableBody"></tbody>
</table>
</div>

<!-- Add/Edit Modal -->
<div class="mt-modal-bg" id="mtModal">
    <div class="mt-modal">
        <div class="modal-hd">
            <h3 id="mtModalTitle" data-label-en="➕ Add Maintenance Record" data-label-ar="➕ إضافة سجل صيانة">➕ Add Maintenance Record</h3>
            <button class="close" id="mtModalClose">&times;</button>
        </div>
        <div class="modal-bd">
            <form class="mt-form" id="mtForm">
                <input type="hidden" id="mtId">
                <div class="row2">
                    <div class="fg">
                        <label id="mtLblVehicleCode" data-label-en="Vehicle Code *" data-label-ar="رقم المركبة *">Vehicle Code *</label>
                        <input type="text" id="mtVehicleCode" required placeholder="e.g.: SHJ-1234">
                    </div>
                    <div class="fg">
                        <label id="mtLblType" data-label-en="Maintenance Type" data-label-ar="نوع الصيانة">Maintenance Type</label>
                        <select id="mtType">
                            <option value="Routine" id="mtFormOptRoutine" data-label-en="Routine Maintenance" data-label-ar="صيانة دورية">Routine Maintenance</option>
                            <option value="Emergency" id="mtFormOptEmergency" data-label-en="Emergency" data-label-ar="طوارئ">Emergency</option>
                            <option value="Technical Check" id="mtFormOptTechnical" data-label-en="Technical Check" data-label-ar="فحص فني">Technical Check</option>
                            <option value="Mechanical" id="mtFormOptMechanical" data-label-en="Mechanical" data-label-ar="ميكانيكي">Mechanical</option>
                        </select>
                    </div>
                </div>
                <div class="row2">
                    <div class="fg">
                        <label id="mtLblVisitDate" data-label-en="Visit Date *" data-label-ar="تاريخ الزيارة *">Visit Date *</label>
                        <input type="date" id="mtVisitDate" required>
                    </div>
                    <div class="fg">
                        <label id="mtLblNextVisit" data-label-en="Next Visit Date" data-label-ar="تاريخ الزيارة القادمة">Next Visit Date</label>
                        <input type="date" id="mtNextVisitDate">
                    </div>
                </div>
                <div class="fg">
                    <label id="mtLblLocation" data-label-en="Location" data-label-ar="الموقع">Location</label>
                    <input type="text" id="mtLocation" placeholder="Maintenance location" data-placeholder-en="Maintenance location" data-placeholder-ar="موقع الصيانة">
                </div>
                <div class="fg">
                    <label id="mtLblNotes" data-label-en="Notes" data-label-ar="ملاحظات">Notes</label>
                    <textarea id="mtNotes" rows="3" placeholder="Add additional notes..." data-placeholder-en="Add additional notes..." data-placeholder-ar="أضف ملاحظات إضافية..."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-ft">
            <button class="btn btn-ghost" id="mtCancelBtn" data-label-en="Cancel" data-label-ar="إلغاء">Cancel</button>
            <button class="btn btn-primary" id="mtSaveBtn" data-label-en="💾 Save" data-label-ar="💾 حفظ">💾 Save</button>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div class="mt-modal-bg" id="mtDetailModal">
    <div class="mt-modal">
        <div class="modal-hd">
            <h3 id="mtDetailTitle" data-label-en="Maintenance Details" data-label-ar="تفاصيل الصيانة">Maintenance Details</h3>
            <button class="close" id="mtDetailClose">&times;</button>
        </div>
        <div class="modal-bd mt-detail" id="mtDetailBody"></div>
    </div>
</div>

<?php ob_start(); ?>
<script>
(function(){
    const $=id=>document.getElementById(id);
    const esc=s=>{const d=document.createElement('div');d.textContent=s||'';return d.innerHTML;};
    let allRecords=[], filteredRecords=[];
    var mtCanCreate=false, mtCanEdit=false, mtCanDelete=false;

    /* ---- Skeleton loader ---- */
    function showSkeleton(){
        const cols=9, rows=5;
        const cell='<td><div class="skel-cell"></div></td>';
        const row='<tr class="skel-row">'+Array(cols).fill(cell).join('')+'</tr>';
        $('mtTableBody').innerHTML=Array(rows).fill(row).join('');
    }

    /* ---- Type helpers ---- */
    function typeLabel(t){
        const map={
            'Routine':i18n.t('type_routine'),
            'Emergency':i18n.t('type_emergency'),
            'Technical Check':i18n.t('type_technical'),
            'Mechanical':i18n.t('type_mechanical')
        };
        return map[t]||t||'—';
    }

    function typeBadge(t){
        let cls='routine';
        if(t==='Emergency')cls='emergency';
        else if(t==='Technical Check')cls='technical';
        return '<span class="mt-badge '+esc(cls)+'">'+esc(typeLabel(t))+'</span>';
    }

    function isOverdue(d){
        if(!d)return false;
        return new Date(d)<new Date(new Date().toISOString().slice(0,10));
    }

    /* ---- Load ---- */
    async function loadRecords(){
        showSkeleton();
        try{
            const res=await API.get('/maintenance');
            allRecords=(res.data||res)||[];
        }catch(e){allRecords=[];}
        applyFilters();
        loadStats();
    }

    async function loadStats(){
        try{
            const res=await API.get('/maintenance/stats');
            const s=res.data||res;
            $('mtStatTotal').textContent=s.total||0;
            $('mtStatRoutine').textContent=s.routine||0;
            $('mtStatEmergency').textContent=s.emergency||0;
            $('mtStatOverdue').textContent=s.overdue||0;
        }catch(e){}
    }

    function applyFilters(){
        const q=($('mtSearch').value||'').toLowerCase();
        const tp=$('mtFilterType').value;
        filteredRecords=allRecords.filter(r=>{
            if(tp&&r.maintenance_type!==tp)return false;
            if(q&&!((r.vehicle_code||'').toLowerCase().includes(q)||(r.location||'').toLowerCase().includes(q)||(r.notes||'').toLowerCase().includes(q)))return false;
            return true;
        });
        renderTable();
    }

    function renderTable(){
        const tbody=$('mtTableBody');
        if(!filteredRecords.length){
            tbody.innerHTML='<tr><td colspan="9" class="mt-empty"><div class="ico">🔧</div><div>'+esc(i18n.t('no_maintenance'))+'</div></td></tr>';
            return;
        }
        let html='';
        filteredRecords.forEach((r,i)=>{
            const overdue=isOverdue(r.next_visit_date);
            const nextDateClass=overdue?' style="color:#dc3545;font-weight:600"':'';
            html+='<tr>'
                +'<td>'+(i+1)+'</td>'
                +'<td><strong>'+esc(r.vehicle_code)+'</strong></td>'
                +'<td>'+(r.visit_date||'-')+'</td>'
                +'<td'+nextDateClass+'>'+(r.next_visit_date||(overdue?'<span class="mt-badge overdue">'+esc(i18n.t('overdue'))+'</span>':'-'))+'</td>'
                +'<td>'+typeBadge(r.maintenance_type)+'</td>'
                +'<td>'+esc(r.location||'-')+'</td>'
                +'<td>'+esc(r.created_by||'-')+'</td>'
                +'<td>'+esc((r.notes||'').substring(0,40))+(r.notes&&r.notes.length>40?'...':'')+'</td>'
                +'<td class="mt-actions">'
                +'<button title="'+esc(i18n.t('view'))+'" onclick="window.__mtView('+r.id+')">👁️</button>';
            if(mtCanEdit) html+='<button title="'+esc(i18n.t('edit'))+'" onclick="window.__mtEdit('+r.id+')">✏️</button>';
            if(mtCanDelete) html+='<button title="'+esc(i18n.t('delete'))+'" onclick="window.__mtDelete('+r.id+')" style="color:var(--status-danger)">🗑️</button>';
            html+='</td></tr>';
        });
        tbody.innerHTML=html;
    }

    /* ---- CRUD ---- */
    function openModal(record){
        $('mtId').value=record?record.id:'';
        $('mtVehicleCode').value=record?record.vehicle_code:'';
        $('mtType').value=record?record.maintenance_type:'Routine';
        $('mtVisitDate').value=record?record.visit_date:'';
        $('mtNextVisitDate').value=record?(record.next_visit_date||''):'';
        $('mtLocation').value=record?(record.location||''):'';
        $('mtNotes').value=record?(record.notes||''):'';
        $('mtModalTitle').textContent=record?('✏️ '+i18n.t('edit_maintenance')):('➕ '+i18n.t('add_maintenance'));
        $('mtModal').classList.add('show');
    }

    function closeModal(){$('mtModal').classList.remove('show');}
    function closeDetail(){$('mtDetailModal').classList.remove('show');}

    async function saveRecord(){
        const id=$('mtId').value;
        const data={
            vehicle_code:$('mtVehicleCode').value.trim(),
            visit_date:$('mtVisitDate').value,
            next_visit_date:$('mtNextVisitDate').value||null,
            maintenance_type:$('mtType').value,
            location:$('mtLocation').value.trim(),
            notes:$('mtNotes').value.trim()
        };
        if(!data.vehicle_code||!data.visit_date){
            UI.showToast(i18n.t('required_fields'),'warning');
            return;
        }
        try{
            if(id){
                await API.put('/maintenance/'+id,data);
                UI.showToast(i18n.t('success'),'success');
            }else{
                await API.post('/maintenance',data);
                UI.showToast(i18n.t('success'),'success');
            }
            closeModal();
            loadRecords();
        }catch(e){
            UI.showToast(e.message||i18n.t('error'),'danger');
        }
    }

    window.__mtView=async function(id){
        try{
            const res=await API.get('/maintenance/'+id);
            const r=res.data||res;
            const overdue=isOverdue(r.next_visit_date);
            let html='';
            html+='<div class="d-row"><span class="d-lbl">'+esc(i18n.t('vehicle_code'))+'</span><span class="d-val">'+esc(r.vehicle_code)+'</span></div>';
            if(r.vehicle_type) html+='<div class="d-row"><span class="d-lbl">'+esc(i18n.t('vehicle_type'))+'</span><span class="d-val">'+esc(r.vehicle_type)+'</span></div>';
            html+='<div class="d-row"><span class="d-lbl">'+esc(i18n.t('visit_date'))+'</span><span class="d-val">'+(r.visit_date||'-')+'</span></div>';
            html+='<div class="d-row"><span class="d-lbl">'+esc(i18n.t('next_visit'))+'</span><span class="d-val"'+(overdue?' style="color:#dc3545"':'')+'>'+( r.next_visit_date||'-')+(overdue?' ⚠️':'')+'</span></div>';
            html+='<div class="d-row"><span class="d-lbl">'+esc(i18n.t('maintenance_type'))+'</span><span class="d-val">'+typeBadge(r.maintenance_type)+'</span></div>';
            html+='<div class="d-row"><span class="d-lbl">'+esc(i18n.t('location'))+'</span><span class="d-val">'+esc(r.location||'-')+'</span></div>';
            html+='<div class="d-row"><span class="d-lbl">'+esc(i18n.t('created_by'))+'</span><span class="d-val">'+esc(r.created_by||'-')+'</span></div>';
            html+='<div class="d-row"><span class="d-lbl">'+esc(i18n.t('notes'))+'</span><span class="d-val">'+esc(r.notes||'-')+'</span></div>';
            html+='<div class="d-row"><span class="d-lbl">'+esc(i18n.t('created_at'))+'</span><span class="d-val">'+(r.created_at||'-')+'</span></div>';
            if(r.updated_at) html+='<div class="d-row"><span class="d-lbl">'+esc(i18n.t('updated_at'))+'</span><span class="d-val">'+(r.updated_at||'-')+'</span></div>';
            $('mtDetailBody').innerHTML=html;
            $('mtDetailTitle').textContent=i18n.t('maintenance_details');
            $('mtDetailModal').classList.add('show');
        }catch(e){UI.showToast(e.message||i18n.t('error'),'danger');}
    };

    window.__mtEdit=async function(id){
        try{
            const res=await API.get('/maintenance/'+id);
            openModal(res.data||res);
        }catch(e){UI.showToast(e.message||i18n.t('error'),'danger');}
    };

    window.__mtDelete=async function(id){
        if(!confirm(i18n.t('confirm_delete')))return;
        try{
            await API.delete('/maintenance/'+id);
            UI.showToast(i18n.t('success'),'success');
            loadRecords();
        }catch(e){UI.showToast(e.message||i18n.t('error'),'danger');}
    };

    /* ---- Events ---- */
    $('mtBtnAdd').addEventListener('click',()=>openModal(null));
    $('mtModalClose').addEventListener('click',closeModal);
    $('mtCancelBtn').addEventListener('click',closeModal);
    $('mtSaveBtn').addEventListener('click',saveRecord);
    $('mtDetailClose').addEventListener('click',closeDetail);
    $('mtSearch').addEventListener('input',applyFilters);
    $('mtFilterType').addEventListener('change',applyFilters);
    $('mtModal').addEventListener('click',e=>{if(e.target===$('mtModal'))closeModal();});
    $('mtDetailModal').addEventListener('click',e=>{if(e.target===$('mtDetailModal'))closeDetail();});

    /* ---- Translate static labels using global i18n ---- */
    function translateStatic(){
        // Retry if i18n translations are not loaded yet
        if(!i18n.strings || !Object.keys(i18n.strings).length){
            setTimeout(translateStatic,100);
            return;
        }
        const t=k=>i18n.t(k);
        $('mtPageTitle').textContent=t('maintenance');
        $('mtLblTotal').textContent=t('maintenance_records');
        $('mtLblRoutine').textContent=t('type_routine');
        $('mtLblEmergency').textContent=t('type_emergency');
        $('mtLblOverdue').textContent=t('overdue');
        $('mtSearch').placeholder=t('search_maintenance');
        $('mtBtnAddText').textContent=t('add_maintenance');
        $('mtThVehicle').textContent=t('vehicle_code');
        $('mtThVisitDate').textContent=t('visit_date');
        $('mtThNextVisit').textContent=t('next_visit');
        $('mtThType').textContent=t('maintenance_type');
        $('mtThLocation').textContent=t('location');
        $('mtThCreatedBy').textContent=t('created_by');
        $('mtThNotes').textContent=t('notes');
        $('mtThActions').textContent=t('actions');
        $('mtLblVehicleCode').textContent=t('vehicle_code')+' *';
        $('mtLblType').textContent=t('maintenance_type');
        $('mtLblVisitDate').textContent=t('visit_date')+' *';
        $('mtLblNextVisit').textContent=t('next_visit');
        $('mtLblLocation').textContent=t('location');
        $('mtLblNotes').textContent=t('notes');
        $('mtCancelBtn').textContent=t('cancel');
        $('mtSaveBtn').textContent='💾 '+t('save');
        $('mtDetailTitle').textContent=t('maintenance_details');
        // Filter dropdown options
        $('mtOptAllTypes').textContent=t('all_maintenance_types');
        $('mtOptRoutine').textContent=t('type_routine');
        $('mtOptEmergency').textContent=t('type_emergency');
        $('mtOptTechnical').textContent=t('type_technical');
        $('mtOptMechanical').textContent=t('type_mechanical');
        // Form type select
        $('mtFormOptRoutine').textContent=t('type_routine');
        $('mtFormOptEmergency').textContent=t('type_emergency');
        $('mtFormOptTechnical').textContent=t('type_technical');
        $('mtFormOptMechanical').textContent=t('type_mechanical');
    }

    /* ---- Init with permission check ---- */
    (function initPerms(){
        if(window.__pageDenied)return;
        var user=Auth.getUser();
        if(!user){setTimeout(initPerms,100);return;}
        var perms=(user.permissions)||[];
        mtCanCreate=perms.includes('manage_maintenance')||perms.includes('*');
        mtCanEdit=perms.includes('manage_maintenance')||perms.includes('*');
        mtCanDelete=perms.includes('manage_maintenance')||perms.includes('*');
        if(!mtCanCreate){var ab=$('mtBtnAdd');if(ab)ab.style.display='none';}
        translateStatic();
        loadRecords();
    })();
})();
</script>
<?php $pageScripts = ob_get_clean(); ?>
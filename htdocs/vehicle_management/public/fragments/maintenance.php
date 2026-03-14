<?php
/**
 * Maintenance Fragment — Vehicle Maintenance Records
 * Full CRUD inside the dashboard.
 */
?>
<style>
.mt-stats{display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap}
.mt-stat{flex:1;min-width:140px;background:var(--bg-card,#fff);border-radius:12px;padding:16px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.mt-stat .num{font-size:1.8rem;font-weight:700;color:var(--primary-main,#1a5276)}
.mt-stat .lbl{font-size:.85rem;color:var(--text-secondary,#666);margin-top:4px}
.mt-stat.routine .num{color:#007bff}
.mt-stat.emergency .num{color:#dc3545}
.mt-stat.overdue .num{color:#ff9800}
.mt-toolbar{display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;align-items:center}
.mt-toolbar .search-box{flex:1;min-width:200px;position:relative}
.mt-toolbar .search-box input{width:100%;padding:10px 12px 10px 36px;border:1px solid var(--border-default,#ddd);border-radius:8px;font-size:.95rem}
.mt-toolbar .search-box .ico{position:absolute;right:12px;top:50%;transform:translateY(-50%);color:#999}
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
</style>

<div class="page-header"><h2 id="mtPageTitle">الصيانة</h2></div>

<!-- Stats -->
<div class="mt-stats">
    <div class="mt-stat"><div class="num" id="mtStatTotal">0</div><div class="lbl" id="mtLblTotal">إجمالي السجلات</div></div>
    <div class="mt-stat routine"><div class="num" id="mtStatRoutine">0</div><div class="lbl" id="mtLblRoutine">صيانة دورية</div></div>
    <div class="mt-stat emergency"><div class="num" id="mtStatEmergency">0</div><div class="lbl" id="mtLblEmergency">طوارئ</div></div>
    <div class="mt-stat overdue"><div class="num" id="mtStatOverdue">0</div><div class="lbl" id="mtLblOverdue">متأخرة</div></div>
</div>

<!-- Toolbar -->
<div class="mt-toolbar">
    <div class="search-box">
        <span class="ico">🔍</span>
        <input type="text" id="mtSearch" placeholder="بحث في الصيانة...">
    </div>
    <select id="mtFilterType">
        <option value="">كل الأنواع</option>
        <option value="Routine">صيانة دورية</option>
        <option value="Emergency">طوارئ</option>
        <option value="Technical Check">فحص فني</option>
        <option value="Mechanical">ميكانيكي</option>
    </select>
    <button class="btn btn-primary btn-sm btn-add" id="mtBtnAdd">➕ إضافة سجل</button>
</div>

<!-- Table -->
<div class="table-responsive">
<table class="mt-table data-table">
    <thead><tr>
        <th>#</th>
        <th id="mtThVehicle">كود المركبة</th>
        <th id="mtThVisitDate">تاريخ الزيارة</th>
        <th id="mtThNextVisit">الزيارة القادمة</th>
        <th id="mtThType">نوع الصيانة</th>
        <th id="mtThLocation">الموقع</th>
        <th id="mtThCreatedBy">أنشأ بواسطة</th>
        <th id="mtThNotes">ملاحظات</th>
        <th id="mtThActions">إجراءات</th>
    </tr></thead>
    <tbody id="mtTableBody"></tbody>
</table>
</div>

<!-- Add/Edit Modal -->
<div class="mt-modal-bg" id="mtModal">
    <div class="mt-modal">
        <div class="modal-hd">
            <h3 id="mtModalTitle">➕ إضافة سجل صيانة</h3>
            <button class="close" id="mtModalClose">&times;</button>
        </div>
        <div class="modal-bd">
            <form class="mt-form" id="mtForm">
                <input type="hidden" id="mtId">
                <div class="row2">
                    <div class="fg">
                        <label id="mtLblVehicleCode">كود المركبة *</label>
                        <input type="text" id="mtVehicleCode" required placeholder="مثال: SHJ-1234">
                    </div>
                    <div class="fg">
                        <label id="mtLblType">نوع الصيانة</label>
                        <select id="mtType">
                            <option value="Routine">صيانة دورية</option>
                            <option value="Emergency">طوارئ</option>
                            <option value="Technical Check">فحص فني</option>
                            <option value="Mechanical">ميكانيكي</option>
                        </select>
                    </div>
                </div>
                <div class="row2">
                    <div class="fg">
                        <label id="mtLblVisitDate">تاريخ الزيارة *</label>
                        <input type="date" id="mtVisitDate" required>
                    </div>
                    <div class="fg">
                        <label id="mtLblNextVisit">تاريخ الزيارة القادمة</label>
                        <input type="date" id="mtNextVisitDate">
                    </div>
                </div>
                <div class="fg">
                    <label id="mtLblLocation">الموقع</label>
                    <input type="text" id="mtLocation" placeholder="موقع الصيانة">
                </div>
                <div class="fg">
                    <label id="mtLblNotes">ملاحظات</label>
                    <textarea id="mtNotes" rows="3" placeholder="أضف ملاحظات إضافية..."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-ft">
            <button class="btn btn-ghost" id="mtCancelBtn">إلغاء</button>
            <button class="btn btn-primary" id="mtSaveBtn">💾 حفظ</button>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div class="mt-modal-bg" id="mtDetailModal">
    <div class="mt-modal">
        <div class="modal-hd">
            <h3 id="mtDetailTitle">تفاصيل الصيانة</h3>
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

    /* ---- i18n ---- */
    const labels={
        ar:{
            page_title:'الصيانة',total_records:'إجمالي السجلات',routine:'صيانة دورية',
            emergency:'طوارئ',overdue:'متأخرة',search_placeholder:'بحث في الصيانة...',
            all_types:'كل الأنواع',type_routine:'صيانة دورية',type_emergency:'طوارئ',
            type_technical:'فحص فني',type_mechanical:'ميكانيكي',
            add_record:'إضافة سجل',vehicle_code:'كود المركبة',visit_date:'تاريخ الزيارة',
            next_visit:'الزيارة القادمة',maintenance_type:'نوع الصيانة',location:'الموقع',
            created_by:'أنشأ بواسطة',notes:'ملاحظات',actions:'إجراءات',
            save:'حفظ',cancel:'إلغاء',add_maintenance:'إضافة سجل صيانة',
            edit_maintenance:'تعديل سجل صيانة',detail_title:'تفاصيل الصيانة',
            no_records:'لا توجد سجلات صيانة بعد',confirm_delete:'هل أنت متأكد من حذف هذا السجل؟',
            vehicle_type:'نوع المركبة',created_at:'تاريخ الإنشاء',updated_at:'تاريخ التحديث',
            na:'غير متوفر'
        },
        en:{
            page_title:'Maintenance',total_records:'Total Records',routine:'Routine',
            emergency:'Emergency',overdue:'Overdue',search_placeholder:'Search maintenance...',
            all_types:'All Types',type_routine:'Routine',type_emergency:'Emergency',
            type_technical:'Technical Check',type_mechanical:'Mechanical',
            add_record:'Add Record',vehicle_code:'Vehicle Code',visit_date:'Visit Date',
            next_visit:'Next Visit',maintenance_type:'Maintenance Type',location:'Location',
            created_by:'Created By',notes:'Notes',actions:'Actions',
            save:'Save',cancel:'Cancel',add_maintenance:'Add Maintenance Record',
            edit_maintenance:'Edit Maintenance Record',detail_title:'Maintenance Details',
            no_records:'No maintenance records yet',confirm_delete:'Are you sure you want to delete this record?',
            vehicle_type:'Vehicle Type',created_at:'Created At',updated_at:'Updated At',
            na:'N/A'
        }
    };
    const lang=()=>localStorage.getItem('lang')||'ar';
    const i18n={t:(k)=>(labels[lang()]||labels.ar)[k]||k};

    function typeLabel(t){
        const map={'Routine':i18n.t('type_routine'),'Emergency':i18n.t('type_emergency'),'Technical Check':i18n.t('type_technical'),'Mechanical':i18n.t('type_mechanical')};
        return map[t]||t||i18n.t('na');
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
            if(tp && r.maintenance_type!==tp)return false;
            if(q && !((r.vehicle_code||'').toLowerCase().includes(q)||(r.location||'').toLowerCase().includes(q)||(r.notes||'').toLowerCase().includes(q)))return false;
            return true;
        });
        renderTable();
    }

    function renderTable(){
        const tbody=$('mtTableBody');
        if(!filteredRecords.length){
            tbody.innerHTML='<tr><td colspan="9" class="mt-empty"><div class="ico">🔧</div><div>'+esc(i18n.t('no_records'))+'</div></td></tr>';
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
                +'<button title="View" onclick="window.__mtView('+r.id+')">👁️</button>';
            if(mtCanEdit) html+='<button title="Edit" onclick="window.__mtEdit('+r.id+')">✏️</button>';
            if(mtCanDelete) html+='<button title="Delete" onclick="window.__mtDelete('+r.id+')">🗑️</button>';
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
            UI.toast(lang()==='en'?'Please fill required fields':'يرجى ملء الحقول المطلوبة','warning');
            return;
        }
        try{
            if(id){
                await API.put('/maintenance/'+id,data);
                UI.toast(lang()==='en'?'Updated successfully':'تم التحديث بنجاح','success');
            }else{
                await API.post('/maintenance',data);
                UI.toast(lang()==='en'?'Created successfully':'تم الإنشاء بنجاح','success');
            }
            closeModal();
            loadRecords();
        }catch(e){
            UI.toast(e.message||'Error','danger');
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
            html+='<div class="d-row"><span class="d-lbl">'+esc(i18n.t('next_visit'))+'</span><span class="d-val"'+(overdue?' style="color:#dc3545"':'')+'>'+(r.next_visit_date||'-')+(overdue?' ⚠️':'')+'</span></div>';
            html+='<div class="d-row"><span class="d-lbl">'+esc(i18n.t('maintenance_type'))+'</span><span class="d-val">'+typeBadge(r.maintenance_type)+'</span></div>';
            html+='<div class="d-row"><span class="d-lbl">'+esc(i18n.t('location'))+'</span><span class="d-val">'+esc(r.location||'-')+'</span></div>';
            html+='<div class="d-row"><span class="d-lbl">'+esc(i18n.t('created_by'))+'</span><span class="d-val">'+esc(r.created_by||'-')+'</span></div>';
            html+='<div class="d-row"><span class="d-lbl">'+esc(i18n.t('notes'))+'</span><span class="d-val">'+esc(r.notes||'-')+'</span></div>';
            html+='<div class="d-row"><span class="d-lbl">'+esc(i18n.t('created_at'))+'</span><span class="d-val">'+(r.created_at||'-')+'</span></div>';
            if(r.updated_at) html+='<div class="d-row"><span class="d-lbl">'+esc(i18n.t('updated_at'))+'</span><span class="d-val">'+(r.updated_at||'-')+'</span></div>';
            $('mtDetailBody').innerHTML=html;
            $('mtDetailModal').classList.add('show');
        }catch(e){UI.toast(e.message||'Error','danger');}
    };

    window.__mtEdit=async function(id){
        try{
            const res=await API.get('/maintenance/'+id);
            openModal(res.data||res);
        }catch(e){UI.toast(e.message||'Error','danger');}
    };

    window.__mtDelete=async function(id){
        if(!confirm(i18n.t('confirm_delete')))return;
        try{
            await API.delete('/maintenance/'+id);
            UI.toast(lang()==='en'?'Deleted successfully':'تم الحذف بنجاح','success');
            loadRecords();
        }catch(e){UI.toast(e.message||'Error','danger');}
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

    /* ---- i18n translate static labels ---- */
    function translateStatic(){
        $('mtPageTitle').textContent=i18n.t('page_title');
        $('mtLblTotal').textContent=i18n.t('total_records');
        $('mtLblRoutine').textContent=i18n.t('routine');
        $('mtLblEmergency').textContent=i18n.t('emergency');
        $('mtLblOverdue').textContent=i18n.t('overdue');
        $('mtSearch').placeholder=i18n.t('search_placeholder');
        $('mtBtnAdd').textContent='➕ '+i18n.t('add_record');
        $('mtThVehicle').textContent=i18n.t('vehicle_code');
        $('mtThVisitDate').textContent=i18n.t('visit_date');
        $('mtThNextVisit').textContent=i18n.t('next_visit');
        $('mtThType').textContent=i18n.t('maintenance_type');
        $('mtThLocation').textContent=i18n.t('location');
        $('mtThCreatedBy').textContent=i18n.t('created_by');
        $('mtThNotes').textContent=i18n.t('notes');
        $('mtThActions').textContent=i18n.t('actions');
        $('mtLblVehicleCode').textContent=i18n.t('vehicle_code')+' *';
        $('mtLblType').textContent=i18n.t('maintenance_type');
        $('mtLblVisitDate').textContent=i18n.t('visit_date')+' *';
        $('mtLblNextVisit').textContent=i18n.t('next_visit');
        $('mtLblLocation').textContent=i18n.t('location');
        $('mtLblNotes').textContent=i18n.t('notes');
        $('mtCancelBtn').textContent=i18n.t('cancel');
        $('mtSaveBtn').textContent='💾 '+i18n.t('save');
    }

    // Init with permission check (retry-based pattern)
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

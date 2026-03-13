<?php
/**
 * Vehicles Fragment — Card + Table view, CRUD, Handover/Receive
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
.v-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px}
.v-card{background:var(--bg-card);border-radius:12px;box-shadow:var(--card-shadow);border:1px solid var(--border-default);overflow:hidden;transition:transform .3s,box-shadow .3s}
.v-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.12)}
.v-card-head{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;background:linear-gradient(135deg,var(--primary-dark),var(--primary-main));color:var(--text-light)}
.v-card-head .v-code{font-size:1.1rem;font-weight:700}
.v-card-body{padding:16px 20px}
.v-card-body .v-row{display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px dashed var(--border-default);font-size:.875rem}
.v-card-body .v-row:last-child{border-bottom:none}
.v-card-body .v-row .v-label{color:var(--text-secondary)}
.v-card-body .v-row .v-val{font-weight:600;color:var(--text-primary)}
.v-card-actions{display:flex;gap:8px;padding:12px 20px;border-top:1px solid var(--border-default);background:var(--bg-main)}
.v-card-actions .btn{flex:1}
.badge-available{background:#d4edda;color:#155724;display:inline-block;padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:600}
.badge-checked-out{background:#fff3cd;color:#856404;display:inline-block;padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:600}
#vehicleTable{display:none}
.data-table .table-actions .btn-icon{width:30px;height:30px;font-size:.8rem}
.view-toggle .btn.active{background:var(--primary-main);color:var(--text-light)}
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
@media(max-width:768px){.toolbar{flex-direction:column;align-items:stretch}.toolbar-end{margin-inline-start:0;justify-content:space-between}.toolbar .search-box{max-width:100%}.v-cards{grid-template-columns:1fr}}
</style>

<div class="page-header">
    <h2>إدارة المركبات</h2>
</div>

<!-- Stats -->
<div class="v-stats">
    <div class="v-stat"><div class="v-stat-val" id="sTotal">—</div><div class="v-stat-lbl">الإجمالي</div></div>
    <div class="v-stat"><div class="v-stat-val" id="sOp" style="color:var(--status-success)">—</div><div class="v-stat-lbl">تعمل</div></div>
    <div class="v-stat"><div class="v-stat-val" id="sMaint" style="color:var(--status-warning)">—</div><div class="v-stat-lbl">صيانة</div></div>
    <div class="v-stat"><div class="v-stat-val" id="sOos" style="color:var(--status-danger)">—</div><div class="v-stat-lbl">خارج الخدمة</div></div>
</div>

<!-- Toolbar -->
<div class="toolbar">
    <div class="search-box">
        <input type="text" class="form-control" id="searchInput" placeholder="بحث برقم المركبة أو اسم السائق...">
        <span class="search-icon">🔍</span>
    </div>
    <div class="filters">
        <select class="form-select" id="filterStatus">
            <option value="">كل الحالات</option>
            <option value="operational">تعمل</option>
            <option value="maintenance">صيانة</option>
            <option value="out_of_service">خارج الخدمة</option>
        </select>
        <select class="form-select" id="filterAvailability">
            <option value="">كل التوفر</option>
            <option value="available">متاحة للتسليم</option>
            <option value="checked_out">مُستلمة</option>
        </select>
        <select class="form-select" id="filterDept"><option value="">كل الإدارات</option></select>
        <select class="form-select" id="filterSection"><option value="">كل الأقسام</option></select>
        <select class="form-select" id="filterMode">
            <option value="">كل الأنماط</option>
            <option value="private">خاصة</option>
            <option value="shift">وردية</option>
        </select>
        <select class="form-select" id="filterGender">
            <option value="">كل الجنس</option>
            <option value="men">رجال</option>
            <option value="women">نساء</option>
        </select>
    </div>
    <div class="toolbar-end">
        <div class="view-toggle">
            <button class="btn btn-ghost btn-icon active" id="viewCards" title="بطاقات">🗂️</button>
            <button class="btn btn-ghost btn-icon" id="viewTable" title="جدول">📋</button>
        </div>
        <button class="btn btn-outline btn-sm" id="btnPrintReport" title="طباعة التقرير">🖨️ طباعة</button>
        <button class="btn btn-outline btn-sm" id="btnExportExcel" title="تصدير إكسيل">📥 تصدير</button>
        <button class="btn btn-primary" id="btnAddVehicle">➕ إضافة مركبة</button>
    </div>
</div>

<!-- Card View -->
<div id="vehicleCards"><div class="loading-placeholder"><div class="spinner spinner-sm"></div><span>جارٍ التحميل...</span></div></div>

<!-- Table View -->
<div id="vehicleTable" style="display:none">
    <div class="table-wrapper">
        <table class="data-table" id="vehiclesDataTable">
            <thead><tr>
                <th>#</th><th>رقم المركبة</th><th>النوع</th><th>الفئة</th><th>التوفر</th><th>السائق</th><th>الهاتف</th><th>الإدارة</th><th>الحالة</th><th>النمط</th><th>الجنس</th><th>السنة</th><th>الإجراءات</th>
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
            <h3 id="modalTitle">إضافة مركبة</h3>
            <button class="modal-close" data-action="close-modal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="vehicleForm">
                <input type="hidden" id="fId">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">رقم المركبة *</label>
                        <input type="text" class="form-control" id="fCode" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">نوع المركبة *</label>
                        <input type="text" class="form-control" id="fType" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">فئة المركبة</label>
                        <select class="form-select" id="fCategory">
                            <option value="">— اختر —</option>
                            <option value="sedan">سيدان</option>
                            <option value="pickup">بيك أب</option>
                            <option value="bus">باص</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">سنة الصنع *</label>
                        <input type="number" class="form-control" id="fYear" min="1990" max="2030" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">نمط المركبة</label>
                        <select class="form-select" id="fMode">
                            <option value="">— اختر —</option>
                            <option value="private">خاصة</option>
                            <option value="shift">وردية</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">الجنس</label>
                        <select class="form-select" id="fGender">
                            <option value="">— غير محدد —</option>
                            <option value="men">رجال</option>
                            <option value="women">نساء</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">رقم الموظف (السائق)</label>
                        <input type="text" class="form-control" id="fEmpId">
                    </div>
                    <div class="form-group">
                        <label class="form-label">اسم السائق</label>
                        <input type="text" class="form-control" id="fDriverName">
                    </div>
                    <div class="form-group">
                        <label class="form-label">هاتف السائق</label>
                        <input type="text" class="form-control" id="fDriverPhone">
                    </div>
                    <div class="form-group">
                        <label class="form-label">الحالة</label>
                        <select class="form-select" id="fStatus">
                            <option value="operational">تعمل</option>
                            <option value="maintenance">صيانة</option>
                            <option value="out_of_service">خارج الخدمة</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">الإدارة</label>
                        <select class="form-select" id="fDept"><option value="">— اختر —</option></select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">القسم</label>
                        <select class="form-select" id="fSection"><option value="">— اختر —</option></select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">الشعبة</label>
                        <select class="form-select" id="fDivision"><option value="">— اختر —</option></select>
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">ملاحظات</label>
                        <textarea class="form-control" id="fNotes" rows="2"></textarea>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" data-action="close-modal">إلغاء</button>
            <button class="btn btn-primary" id="btnSaveVehicle">حفظ</button>
        </div>
    </div>
</div>

<!-- ===== VEHICLE DETAILS MODAL ===== -->
<div class="modal" id="detailsModal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3>تفاصيل المركبة</h3>
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
            <p style="margin-bottom:24px;font-size:1rem">هل أنت متأكد من حذف هذه المركبة؟</p>
            <input type="hidden" id="deleteVehicleId">
            <div style="display:flex;gap:12px;justify-content:center">
                <button class="btn btn-danger" id="btnConfirmDelete">حذف</button>
                <button class="btn btn-ghost" data-action="close-modal">إلغاء</button>
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
    let allVehicles=[], filteredVehicles=[], currentPage=1, perPage=12, viewMode='cards', refs={departments:[],sections:[],divisions:[]};

    const STATUS={operational:{ar:'تعمل',cls:'badge-success'},maintenance:{ar:'صيانة',cls:'badge-warning'},out_of_service:{ar:'خارج الخدمة',cls:'badge-danger'}};
    function badge(s){const m=STATUS[s]||{ar:'—',cls:'badge-info'};return '<span class="badge '+m.cls+'">'+m.ar+'</span>';}
    function esc(s){return UI._escapeHtml(s||'—');}
    function categoryLabel(c){const m={pickup:'بيك أب',bus:'باص',sedan:'سيدان'};return m[c]||'—';}

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
        const s=$('fSection');s.innerHTML='<option value="">— اختر —</option>';
        $('fDivision').innerHTML='<option value="">— اختر —</option>';
        (refs.sections||[]).filter(sc=>sc.department_id==did).forEach(sc=>{
            s.appendChild(new Option(sc.name_ar||sc.name_en,sc.section_id));
        });
    });
    $('fSection').addEventListener('change',()=>{
        const sid=parseInt($('fSection').value);
        const d=$('fDivision');d.innerHTML='<option value="">— اختر —</option>';
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
        if(viewMode==='cards')renderCards(page);else renderTable(page);
        renderPagination();
    }

    function availBadge(v){return v.available?'<span class="badge-available">متاحة للتسليم ✅</span>':'<span class="badge-checked-out">مُستلمة 🔒</span>';}

    function renderCards(list){
        const c=$('vehicleCards');
        if(!list.length){c.innerHTML='<div class="empty-state"><div class="empty-icon">🚗</div><p>لا توجد مركبات</p></div>';return;}
        let h='<div class="v-cards">';
        list.forEach(v=>{
            h+='<div class="v-card">';
            h+='<div class="v-card-head"><span class="v-code">'+esc(v.vehicle_code)+'</span>'+badge(v.status)+'</div>';
            h+='<div class="v-card-body">';
            h+='<div class="v-row"><span class="v-label">التوفر</span><span class="v-val">'+availBadge(v)+'</span></div>';
            h+='<div class="v-row"><span class="v-label">النوع</span><span class="v-val">'+esc(v.type)+'</span></div>';
            h+='<div class="v-row"><span class="v-label">الفئة</span><span class="v-val">'+categoryLabel(v.vehicle_category)+'</span></div>';
            h+='<div class="v-row"><span class="v-label">السائق</span><span class="v-val">'+esc(v.driver_name)+'</span></div>';
            h+='<div class="v-row"><span class="v-label">الهاتف</span><span class="v-val">'+esc(v.driver_phone)+'</span></div>';
            h+='<div class="v-row"><span class="v-label">الإدارة</span><span class="v-val">'+esc(v.department_name_ar)+'</span></div>';
            h+='<div class="v-row"><span class="v-label">السنة</span><span class="v-val">'+(v.manufacture_year||'—')+'</span></div>';
            h+='</div>';
            h+='<div class="v-card-actions">';
            h+='<button class="btn btn-outline btn-sm" onclick="VPage.view('+v.id+')">👁️ عرض</button>';
            h+='<button class="btn btn-outline btn-sm" onclick="VPage.edit('+v.id+')">✏️ تعديل</button>';
            h+='<button class="btn btn-danger btn-sm" onclick="VPage.del('+v.id+')">🗑️</button>';
            h+='</div></div>';
        });
        h+='</div>';
        c.innerHTML=h;
    }

    function renderTable(list){
        const tb=$('tableBody');
        if(!list.length){tb.innerHTML='<tr><td colspan="13" class="text-center" style="padding:32px;color:var(--text-secondary)">لا توجد مركبات</td></tr>';return;}
        const genderLabel=g=>g==='men'?'رجال':g==='women'?'نساء':'—';
        const modeLabel=m=>m==='private'?'خاصة':m==='shift'?'وردية':'—';
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
            h+='<button class="btn btn-ghost btn-icon" onclick="VPage.view('+v.id+')" title="عرض">👁️</button>';
            h+='<button class="btn btn-ghost btn-icon" onclick="VPage.edit('+v.id+')" title="تعديل">✏️</button>';
            h+='<button class="btn btn-ghost btn-icon" onclick="VPage.del('+v.id+')" title="حذف" style="color:var(--status-danger)">🗑️</button>';
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
        // Show max 7 page buttons with ellipsis
        let start=Math.max(1,currentPage-3), end=Math.min(totalPg,currentPage+3);
        if(start>1){h+='<button onclick="VPage.goPage(1)">1</button>';if(start>2)h+='<span style="padding:0 4px">…</span>';}
        for(let i=start;i<=end;i++){
            h+='<button class="'+(i===currentPage?'active':'')+'" onclick="VPage.goPage('+i+')">'+i+'</button>';
        }
        if(end<totalPg){if(end<totalPg-1)h+='<span style="padding:0 4px">…</span>';h+='<button onclick="VPage.goPage('+totalPg+')">'+totalPg+'</button>';}
        h+='<button class="pg-next" '+(currentPage>=totalPg?'disabled':'')+' onclick="VPage.goPage('+(currentPage+1)+')">'+i18n.t('next')+'</button>';
        pg.innerHTML=h;
        // Pagination info with go-to-page
        info.innerHTML='<span>'+i18n.t('total_records')+': '+totalItems+' | '+i18n.t('page')+' '+currentPage+' '+i18n.t('of')+' '+totalPg+'</span>'+
            '<div class="pg-goto"><label>'+i18n.t('go_to_page')+':</label><input type="number" min="1" max="'+totalPg+'" id="gotoPageInput" value="'+currentPage+'"><button onclick="VPage.gotoPage()">↵</button></div>';
    }

    /* --- View toggle --- */
    $('viewCards').addEventListener('click',()=>{viewMode='cards';$('viewCards').classList.add('active');$('viewTable').classList.remove('active');$('vehicleCards').style.display='';$('vehicleTable').style.display='none';render();});
    $('viewTable').addEventListener('click',()=>{viewMode='table';$('viewTable').classList.add('active');$('viewCards').classList.remove('active');$('vehicleTable').style.display='';$('vehicleCards').style.display='none';render();});

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
        if(!data.length){UI.showToast('لا توجد بيانات للطباعة','error');return;}
        const statusLabel=s=>s==='operational'?'تعمل':s==='maintenance'?'صيانة':s==='out_of_service'?'خارج الخدمة':'';
        let html='<html dir="rtl"><head><meta charset="utf-8"><title>تقرير المركبات</title><style>body{font-family:Arial,sans-serif;direction:rtl}table{width:100%;border-collapse:collapse;font-size:12px}th,td{border:1px solid #ccc;padding:6px 8px;text-align:right}th{background:#f0f0f0}h2{text-align:center}.avail{color:green}.noavail{color:#b8860b}@media print{body{margin:0}}</style></head><body>';
        html+='<h2>تقرير المركبات</h2><p>العدد: '+data.length+'</p>';
        html+='<table><thead><tr><th>#</th><th>رقم المركبة</th><th>النوع</th><th>الفئة</th><th>التوفر</th><th>السائق</th><th>الهاتف</th><th>الإدارة</th><th>الحالة</th><th>السنة</th></tr></thead><tbody>';
        data.forEach((v,i)=>{
            html+='<tr><td>'+(i+1)+'</td><td>'+esc(v.vehicle_code)+'</td><td>'+esc(v.type)+'</td><td>'+categoryLabel(v.vehicle_category)+'</td>';
            html+='<td class="'+(v.available?'avail':'noavail')+'">'+(v.available?'متاحة للتسليم':'مُستلمة')+'</td>';
            html+='<td>'+esc(v.driver_name)+'</td><td>'+esc(v.driver_phone)+'</td><td>'+esc(v.department_name_ar)+'</td>';
            html+='<td>'+statusLabel(v.status)+'</td><td>'+(v.manufacture_year||'—')+'</td></tr>';
        });
        html+='</tbody></table></body></html>';
        const w=window.open('','_blank');w.document.write(html);w.document.close();w.print();
    });

    /* --- Export Excel --- */
    $('btnExportExcel').addEventListener('click',function(){
        const data=filteredVehicles;
        if(!data.length){UI.showToast('لا توجد بيانات للتصدير','error');return;}
        const genderLabel=g=>g==='men'?'رجال':g==='women'?'نساء':'';
        const modeLabel=m=>m==='private'?'خاصة':m==='shift'?'وردية':'';
        const statusLabel=s=>s==='operational'?'تعمل':s==='maintenance'?'صيانة':s==='out_of_service'?'خارج الخدمة':'';
        const headers=['#','رقم المركبة','النوع','الفئة','التوفر','اسم السائق','هاتف السائق','الإدارة','الحالة','النمط','الجنس','سنة الصنع','رقم الموظف','ملاحظات'];
        let csv='\uFEFF'+headers.join(',')+'\n';
        data.forEach((v,i)=>{
            const row=[
                i+1,
                '"'+(v.vehicle_code||'').replace(/"/g,'""')+'"',
                '"'+(v.type||'').replace(/"/g,'""')+'"',
                '"'+categoryLabel(v.vehicle_category)+'"',
                '"'+(v.available?'متاحة':'مُستلمة')+'"',
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
        UI.showToast('تم التصدير بنجاح','success');
    });

    /* --- Add Vehicle --- */
    $('btnAddVehicle').addEventListener('click',()=>{
        $('modalTitle').textContent='إضافة مركبة';
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
        if(!data.vehicle_code||!data.type||!data.manufacture_year){UI.showToast('يرجى ملء الحقول المطلوبة','error');return;}
        try{
            if(id){await API.put('/vehicles/'+id,data);UI.showToast('تم تحديث المركبة','success');}
            else{await API.post('/vehicles',data);UI.showToast('تم إضافة المركبة','success');}
            UI.hideModal('vehicleModal');
            loadVehicles();loadStats();
        }catch(e){UI.showToast(e.message||'حدث خطأ','error');}
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
                h+='<div class="v-row"><span class="v-label">رقم المركبة</span><span class="v-val">'+esc(v.vehicle_code)+'</span></div>';
                h+='<div class="v-row"><span class="v-label">النوع</span><span class="v-val">'+esc(v.type)+'</span></div>';
                h+='<div class="v-row"><span class="v-label">الفئة</span><span class="v-val">'+categoryLabel(v.vehicle_category)+'</span></div>';
                h+='<div class="v-row"><span class="v-label">السنة</span><span class="v-val">'+(v.manufacture_year||'—')+'</span></div>';
                h+='<div class="v-row"><span class="v-label">الحالة</span><span class="v-val">'+badge(v.status)+'</span></div>';
                const genderLabel=v.gender==='men'?'رجال':v.gender==='women'?'نساء':'—';
                const modeLabel=v.vehicle_mode==='private'?'خاصة':v.vehicle_mode==='shift'?'وردية':'—';
                h+='<div class="v-row"><span class="v-label">النمط</span><span class="v-val">'+modeLabel+'</span></div>';
                h+='<div class="v-row"><span class="v-label">الجنس</span><span class="v-val">'+genderLabel+'</span></div>';
                h+='<div class="v-row"><span class="v-label">السائق</span><span class="v-val">'+esc(v.driver_name)+'</span></div>';
                h+='<div class="v-row"><span class="v-label">الهاتف</span><span class="v-val">'+esc(v.driver_phone)+'</span></div>';
                h+='<div class="v-row"><span class="v-label">الموظف</span><span class="v-val">'+esc(v.emp_id)+'</span></div>';
                h+='<div class="v-row"><span class="v-label">الإدارة</span><span class="v-val">'+esc(v.department_name_ar)+'</span></div>';
                h+='<div class="v-row"><span class="v-label">القسم</span><span class="v-val">'+esc(v.section_name_ar)+'</span></div>';
                h+='<div class="v-row"><span class="v-label">الشعبة</span><span class="v-val">'+esc(v.division_name_ar)+'</span></div>';
                h+='<div class="v-row"><span class="v-label">ملاحظات</span><span class="v-val">'+esc(v.notes)+'</span></div>';
                h+='<div class="v-row"><span class="v-label">تاريخ الإنشاء</span><span class="v-val">'+esc(v.created_at)+'</span></div>';
                if(v.updated_at) h+='<div class="v-row"><span class="v-label">آخر تحديث</span><span class="v-val">'+esc(v.updated_at)+'</span></div>';
                h+='</div>';
                $('detailsBody').innerHTML=h;
                UI.showModal('detailsModal');
            }catch(e){UI.showToast('تعذر تحميل البيانات','error');}
        },

        async edit(id){
            try{
                const res=await API.get('/vehicles/'+id);
                const v=res.data||res;
                $('modalTitle').textContent='تعديل مركبة';
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
            }catch(e){UI.showToast('تعذر تحميل البيانات','error');}
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
            UI.showToast('تم حذف المركبة','success');
            UI.hideModal('deleteModal');
            loadVehicles();loadStats();
        }catch(e){UI.showToast(e.message||'حدث خطأ','error');}
    });

    /* --- Apply i18n labels to dynamic elements --- */
    function applyI18nLabels(){
        if(!i18n.strings || !Object.keys(i18n.strings).length) return;
        const t=i18n.t.bind(i18n);
        $('searchInput').placeholder=t('search_vehicle');
        // Filter dropdowns
        const fs=$('filterStatus');fs.options[0].text=t('all_statuses');fs.options[1].text=t('operational');fs.options[2].text=t('under_maintenance');fs.options[3].text=t('out_of_service');
        const fa=$('filterAvailability');fa.options[0].text=t('all_availability');fa.options[1].text=t('available_only');fa.options[2].text=t('checked_out_only');
        $('filterDept').options[0].text=t('all_departments');
        $('filterSection').options[0].text=t('all_sections');
        const fm=$('filterMode');fm.options[0].text=t('all_modes');fm.options[1].text=t('private');fm.options[2].text=t('shift');
        const fg=$('filterGender');fg.options[0].text=t('all_genders');fg.options[1].text=t('men');fg.options[2].text=t('women');
        // Buttons and titles
        $('viewCards').title=t('card_view');$('viewTable').title=t('table_view');
        $('btnPrintReport').innerHTML='🖨️ '+t('print');
        $('btnExportExcel').innerHTML='📥 '+t('export');
        $('btnAddVehicle').innerHTML='➕ '+t('add_vehicle');
    }

    /* --- Init --- */
    document.addEventListener('DOMContentLoaded',async()=>{
        await new Promise(r=>setTimeout(r,150));
        applyI18nLabels();
        await loadRefs();
        loadStats();
        loadVehicles();
        // Check if action=add in URL
        if(new URLSearchParams(location.search).get('action')==='add'){
            $('btnAddVehicle').click();
        }
    });
})();
</script>
SCRIPT;
?>

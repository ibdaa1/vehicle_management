<?php
/**
 * Vehicle List Fragment — Vehicle Management
 * Full CRUD inside the dashboard.
 * Uses ob_start/ob_get_clean pattern for deferred scripts.
 */
?>
<style>
.vl-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
.vl-stat{background:var(--bg-card);padding:16px;border-radius:12px;box-shadow:var(--card-shadow);border:1px solid var(--border-default);text-align:center}
.vl-stat .num{font-size:1.5rem;font-weight:700;color:var(--text-primary)}
.vl-stat .lbl{font-size:.8rem;color:var(--text-secondary);margin-top:4px}
.vl-toolbar{display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;align-items:center}
.vl-toolbar .search-box{flex:1;min-width:200px;max-width:360px;position:relative}
.vl-toolbar .search-box input{width:100%;padding:10px 14px;padding-inline-end:36px;border:1px solid var(--border-default);border-radius:8px;background:var(--bg-card);color:var(--text-primary);font-size:.9rem}
.vl-toolbar .search-box input:focus{outline:none;border-color:var(--primary-main);box-shadow:0 0 0 3px color-mix(in srgb,var(--primary-main) 15%,transparent)}
.vl-toolbar .search-box .ico{position:absolute;inset-inline-end:12px;top:50%;transform:translateY(-50%);color:var(--text-secondary);pointer-events:none}
.vl-toolbar select{padding:10px 14px;border:1px solid var(--border-default);border-radius:8px;background:var(--bg-card);color:var(--text-primary);font-size:.9rem}
.vl-toolbar .btn-add{margin-inline-start:auto}
.vl-filters{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px}
.vl-filters select{min-width:140px;padding:10px 14px;border:1px solid var(--border-default);border-radius:8px;background:var(--bg-card);color:var(--text-primary);font-size:.9rem}
.vl-table-wrap{overflow-x:auto;border-radius:12px;border:1px solid var(--border-default)}
.vl-table{width:100%;border-collapse:collapse;background:var(--bg-card);border-radius:12px;overflow:hidden;box-shadow:var(--card-shadow)}
.vl-table th,.vl-table td{padding:12px 16px;text-align:start;border-bottom:1px solid var(--border-default);font-size:.875rem}
.vl-table th{background:var(--primary-dark);color:var(--text-light);font-weight:600;white-space:nowrap}
.vl-table tr:hover{background:var(--bg-main)}
.vl-table .vl-actions{display:flex;gap:6px;justify-content:center}
.vl-table .vl-actions .btn-icon{width:32px;height:32px;border-radius:8px;border:none;cursor:pointer;font-size:.85rem;display:inline-flex;align-items:center;justify-content:center;transition:all .3s}
.btn-icon.btn-edit{background:var(--status-info);color:var(--text-light)}
.btn-icon.btn-edit:hover{opacity:.85}
.btn-icon.btn-delete{background:var(--status-danger);color:var(--text-light)}
.btn-icon.btn-delete:hover{opacity:.85}
.vl-badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:600}
.vl-badge.operational{background:color-mix(in srgb,var(--status-success) 18%,var(--bg-card));color:var(--status-success)}
.vl-badge.maintenance{background:color-mix(in srgb,var(--status-warning) 22%,var(--bg-card));color:var(--status-warning)}
.vl-badge.out_of_service{background:color-mix(in srgb,var(--status-danger) 18%,var(--bg-card));color:var(--status-danger)}
.vl-empty{text-align:center;padding:48px 24px;color:var(--text-secondary)}
.vl-empty .ico{font-size:3rem;margin-bottom:12px;opacity:.5}
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;justify-content:center;align-items:center}
.modal-overlay.active{display:flex}
.modal-box{background:var(--bg-card);border-radius:16px;width:90%;max-width:640px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.3)}
.modal-box h3{margin:0;font-size:1.1rem;color:var(--text-primary);padding:20px 24px;border-bottom:1px solid var(--border-default)}
.modal-box .modal-body{padding:24px}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:.85rem;font-weight:600;margin-bottom:6px;color:var(--text-secondary)}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:10px 14px;border:1px solid var(--border-default);border-radius:8px;background:var(--bg-card);color:var(--text-primary);font-size:.9rem}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{outline:none;border-color:var(--primary-main);box-shadow:0 0 0 3px color-mix(in srgb,var(--primary-main) 15%,transparent)}
.form-actions{display:flex;gap:12px;justify-content:flex-end;padding:16px 24px;border-top:1px solid var(--border-default)}
.btn{padding:10px 20px;border:none;border-radius:8px;cursor:pointer;font-size:.9rem;font-weight:600;transition:all .3s}
.btn-primary{background:var(--primary-main);color:var(--text-light)}
.btn-primary:hover{opacity:.9}
.btn-secondary{background:var(--bg-main);color:var(--text-primary);border:1px solid var(--border-default)}
.btn-danger{background:var(--status-danger);color:var(--text-light)}
.btn-danger:hover{opacity:.9}
.btn-sm{padding:6px 12px;font-size:.8rem}
@media(max-width:768px){
    .vl-toolbar{flex-direction:column;align-items:stretch}
    .vl-toolbar .search-box{max-width:100%;min-width:auto}
    .vl-toolbar .btn-add{margin-inline-start:0}
    .vl-filters{flex-direction:column}
    .vl-filters select{min-width:auto;width:100%}
    .vl-stats{grid-template-columns:1fr 1fr;gap:8px}
    .vl-stat .num{font-size:1.2rem}
    .vl-table th,.vl-table td{padding:8px 10px;font-size:.8rem}
}
@media(max-width:480px){
    .vl-stat .num{font-size:1.1rem}
    .vl-stat .lbl{font-size:.75rem}
}
</style>

<div class="page-header">
    <h2 id="vlPageTitle" data-label-ar="إدارة المركبات" data-label-en="Vehicle Management">إدارة المركبات</h2>
</div>

<!-- Stats -->
<div class="vl-stats">
    <div class="vl-stat"><div class="num" id="vlStatTotal">—</div><div class="lbl" id="vlLblTotal" data-label-ar="الإجمالي" data-label-en="Total">الإجمالي</div></div>
    <div class="vl-stat"><div class="num" id="vlStatOp" style="color:var(--status-success)">—</div><div class="lbl" id="vlLblOp" data-label-ar="تعمل" data-label-en="Operational">تعمل</div></div>
    <div class="vl-stat"><div class="num" id="vlStatMaint" style="color:var(--status-warning)">—</div><div class="lbl" id="vlLblMaint" data-label-ar="صيانة" data-label-en="Maintenance">صيانة</div></div>
    <div class="vl-stat"><div class="num" id="vlStatOos" style="color:var(--status-danger)">—</div><div class="lbl" id="vlLblOos" data-label-ar="خارج الخدمة" data-label-en="Out of Service">خارج الخدمة</div></div>
</div>

<!-- Toolbar -->
<div class="vl-toolbar">
    <div class="search-box">
        <input type="text" id="vlSearch" placeholder="بحث..." data-placeholder-ar="بحث عن مركبة..." data-placeholder-en="Search vehicles...">
        <span class="ico">🔍</span>
    </div>
    <select id="vlFilterStatus">
        <option value="" data-label-ar="كل الحالات" data-label-en="All Statuses">كل الحالات</option>
        <option value="operational" data-label-ar="تعمل" data-label-en="Operational">تعمل</option>
        <option value="maintenance" data-label-ar="صيانة" data-label-en="Maintenance">صيانة</option>
        <option value="out_of_service" data-label-ar="خارج الخدمة" data-label-en="Out of Service">خارج الخدمة</option>
    </select>
    <button class="btn btn-primary btn-add" id="vlBtnAdd" style="display:none" onclick="VLForm.showAdd()">➕ <span data-label-ar="إضافة مركبة" data-label-en="Add Vehicle">إضافة مركبة</span></button>
</div>

<!-- Filters Row -->
<div class="vl-filters">
    <select id="vlFilterSector">
        <option value="" data-label-ar="كل القطاعات" data-label-en="All Sectors">كل القطاعات</option>
    </select>
    <select id="vlFilterDept">
        <option value="" data-label-ar="كل الإدارات" data-label-en="All Departments">كل الإدارات</option>
    </select>
    <select id="vlFilterSection">
        <option value="" data-label-ar="كل الأقسام" data-label-en="All Sections">كل الأقسام</option>
    </select>
    <select id="vlFilterDivision">
        <option value="" data-label-ar="كل الشعب" data-label-en="All Divisions">كل الشعب</option>
    </select>
</div>

<!-- Data Table -->
<div class="vl-table-wrap" id="vlTableWrap">
    <table class="vl-table" id="vlTable">
        <thead>
            <tr>
                <th>#</th>
                <th data-label-ar="كود المركبة" data-label-en="Vehicle Code">كود المركبة</th>
                <th data-label-ar="النوع" data-label-en="Type">النوع</th>
                <th data-label-ar="الرقم الإداري" data-label-en="Emp ID">الرقم الإداري</th>
                <th data-label-ar="اسم السائق" data-label-en="Driver Name">اسم السائق</th>
                <th data-label-ar="هاتف السائق" data-label-en="Driver Phone">هاتف السائق</th>
                <th data-label-ar="القطاع" data-label-en="Sector">القطاع</th>
                <th data-label-ar="الإدارة" data-label-en="Department">الإدارة</th>
                <th data-label-ar="القسم" data-label-en="Section">القسم</th>
                <th data-label-ar="الشعبة" data-label-en="Division">الشعبة</th>
                <th data-label-ar="الحالة" data-label-en="Status">الحالة</th>
                <th data-label-ar="النمط" data-label-en="Mode">النمط</th>
                <th data-label-ar="الإجراءات" data-label-en="Actions">الإجراءات</th>
            </tr>
        </thead>
        <tbody id="vlBody"></tbody>
    </table>
</div>
<div class="vl-empty" id="vlEmpty" style="display:none">
    <div class="ico">🚗</div>
    <p data-label-ar="لا توجد مركبات" data-label-en="No vehicles found">لا توجد مركبات</p>
</div>

<!-- Add/Edit Modal -->
<div class="modal-overlay" id="vlModal">
    <div class="modal-box">
        <h3 id="vlModalTitle" data-label-ar="إضافة مركبة" data-label-en="Add Vehicle">إضافة مركبة</h3>
        <div class="modal-body">
        <input type="hidden" id="vlEditId">
        <div class="form-group">
            <label id="vlLblCode" data-label-ar="كود المركبة" data-label-en="Vehicle Code">كود المركبة *</label>
            <input type="text" id="vlFldCode">
        </div>
        <div class="form-group">
            <label id="vlLblType" data-label-ar="النوع" data-label-en="Type">النوع</label>
            <input type="text" id="vlFldType">
        </div>
        <div class="form-group">
            <label id="vlLblCategory" data-label-ar="الفئة" data-label-en="Category">الفئة</label>
            <select id="vlFldCategory">
                <option value="sedan">سيدان / Sedan</option>
                <option value="pickup">بيك أب / Pickup</option>
                <option value="bus">باص / Bus</option>
            </select>
        </div>
        <div class="form-group">
            <label id="vlLblStatus" data-label-ar="الحالة" data-label-en="Status">الحالة</label>
            <select id="vlFldStatus">
                <option value="operational">تعمل / Operational</option>
                <option value="maintenance">صيانة / Maintenance</option>
                <option value="out_of_service">خارج الخدمة / Out of Service</option>
            </select>
        </div>
        <div class="form-group">
            <label id="vlLblMode" data-label-ar="النمط" data-label-en="Mode">النمط</label>
            <select id="vlFldMode">
                <option value="private">خاص / Private</option>
                <option value="shift">وردية / Shift</option>
            </select>
        </div>
        <div class="form-group">
            <label id="vlLblGender" data-label-ar="الجنس" data-label-en="Gender">الجنس</label>
            <select id="vlFldGender">
                <option value="men">ذكر / Male</option>
                <option value="women">أنثى / Female</option>
            </select>
        </div>
        <div class="form-group">
            <label id="vlLblSector" data-label-ar="القطاع" data-label-en="Sector">القطاع</label>
            <select id="vlFldSector"><option value="">—</option></select>
        </div>
        <div class="form-group">
            <label id="vlLblDept" data-label-ar="الإدارة" data-label-en="Department">الإدارة</label>
            <select id="vlFldDept"><option value="">—</option></select>
        </div>
        <div class="form-group">
            <label id="vlLblSection" data-label-ar="القسم" data-label-en="Section">القسم</label>
            <select id="vlFldSection"><option value="">—</option></select>
        </div>
        <div class="form-group">
            <label id="vlLblDivision" data-label-ar="الشعبة" data-label-en="Division">الشعبة</label>
            <select id="vlFldDivision"><option value="">—</option></select>
        </div>
        <div class="form-group">
            <label id="vlLblYear" data-label-ar="سنة الصنع" data-label-en="Year">سنة الصنع</label>
            <input type="number" id="vlFldYear" min="2000" max="2099">
        </div>
        <div class="form-group">
            <label id="vlLblEmpId" data-label-ar="الرقم الإداري" data-label-en="Emp ID">الرقم الإداري</label>
            <input type="text" id="vlFldEmpId">
        </div>
        <div class="form-group">
            <label id="vlLblDriverName" data-label-ar="اسم السائق" data-label-en="Driver Name">اسم السائق</label>
            <input type="text" id="vlFldDriverName">
        </div>
        <div class="form-group">
            <label id="vlLblDriverPhone" data-label-ar="هاتف السائق" data-label-en="Driver Phone">هاتف السائق</label>
            <input type="text" id="vlFldDriverPhone">
        </div>
        <div class="form-group">
            <label id="vlLblNotes" data-label-ar="ملاحظات" data-label-en="Notes">ملاحظات</label>
            <textarea id="vlFldNotes" rows="3"></textarea>
        </div>
        </div><!-- end modal-body -->
        <div class="form-actions">
            <button class="btn btn-secondary" id="vlCancelBtn" onclick="VLForm.hide()">إلغاء</button>
            <button class="btn btn-primary" id="vlSaveBtn" onclick="VLForm.save()">💾 حفظ</button>
        </div>
    </div>
</div>

<!-- Delete Confirm Modal -->
<div class="modal-overlay" id="vlDeleteModal">
    <div class="modal-box" style="max-width:400px;text-align:center">
        <div style="font-size:3rem;margin-bottom:12px">⚠️</div>
        <h3 id="vlDeleteTitle" data-label-ar="تأكيد الحذف" data-label-en="Confirm Delete">تأكيد الحذف</h3>
        <p id="vlDeleteMsg" data-label-ar="هل أنت متأكد من حذف هذه المركبة؟" data-label-en="Are you sure you want to delete this vehicle?">هل أنت متأكد من حذف هذه المركبة؟</p>
        <input type="hidden" id="vlDeleteId">
        <div class="form-actions" style="justify-content:center">
            <button class="btn btn-secondary" onclick="document.getElementById('vlDeleteModal').classList.remove('active')">إلغاء</button>
            <button class="btn btn-danger" id="vlConfirmDeleteBtn" onclick="VLForm.confirmDelete()">🗑️ حذف</button>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
(function(){
    'use strict';
    if(window.__pageDenied) return;

    var $=function(id){return document.getElementById(id);};
    var allVehicles=[], canCreate=false, canEdit=false, canDelete=false;
    var vlRefs={sectors:[],departments:[],sections:[],divisions:[]};

    /* --- Load references --- */
    function loadAllRefs(){
        return API.get('/references').then(function(res){
            vlRefs=(res&&res.data)||res||{sectors:[],departments:[],sections:[],divisions:[]};
            populateFormDropdowns();
            populateFilterDropdowns();
        }).catch(function(e){
            console.error('Load references error',e);
            vlRefs={sectors:[],departments:[],sections:[],divisions:[]};
        });
    }

    function populateFormDropdowns(){
        var lang=localStorage.getItem('lang')||'ar';
        var isEn=(lang==='en');
        var sel=$('vlFldSector');
        sel.innerHTML='<option value="">—</option>';
        (vlRefs.sectors||[]).forEach(function(s){
            sel.innerHTML+='<option value="'+(s.id||'')+'">'+((isEn?(s.name_en||s.name):(s.name||s.name_en))||'—')+'</option>';
        });
        var dd=$('vlFldDept');
        dd.innerHTML='<option value="">—</option>';
        (vlRefs.departments||[]).forEach(function(d){
            dd.innerHTML+='<option value="'+(d.department_id||'')+'">'+((isEn?(d.name_en||d.name_ar):(d.name_ar||d.name_en))||'—')+'</option>';
        });
        $('vlFldSection').innerHTML='<option value="">—</option>';
        $('vlFldDivision').innerHTML='<option value="">—</option>';
    }

    function populateFilterDropdowns(){
        var lang=localStorage.getItem('lang')||'ar';
        var isEn=(lang==='en');
        var sf=$('vlFilterSector');
        var prevSector=sf.value;
        sf.innerHTML='<option value="">'+(isEn?'All Sectors':'كل القطاعات')+'</option>';
        (vlRefs.sectors||[]).forEach(function(s){
            sf.innerHTML+='<option value="'+(s.id||'')+'">'+((isEn?(s.name_en||s.name):(s.name||s.name_en))||'—')+'</option>';
        });
        if(prevSector) sf.value=prevSector;
        var df=$('vlFilterDept');
        var prevDept=df.value;
        df.innerHTML='<option value="">'+(isEn?'All Departments':'كل الإدارات')+'</option>';
        (vlRefs.departments||[]).forEach(function(d){
            df.innerHTML+='<option value="'+(d.department_id||'')+'">'+((isEn?(d.name_en||d.name_ar):(d.name_ar||d.name_en))||'—')+'</option>';
        });
        if(prevDept) df.value=prevDept;
        cascadeFilterSections(prevDept);
    }

    function cascadeFilterSections(deptId){
        var lang=localStorage.getItem('lang')||'ar';
        var isEn=(lang==='en');
        var sf=$('vlFilterSection');
        var prevVal=sf.value;
        sf.innerHTML='<option value="">'+(isEn?'All Sections':'كل الأقسام')+'</option>';
        if(deptId){
            (vlRefs.sections||[]).filter(function(s){return s.department_id==deptId;}).forEach(function(s){
                sf.innerHTML+='<option value="'+(s.section_id||'')+'">'+((isEn?(s.name_en||s.name_ar):(s.name_ar||s.name_en))||'—')+'</option>';
            });
        }
        if(prevVal) sf.value=prevVal;
        cascadeFilterDivisions(sf.value);
    }

    function cascadeFilterDivisions(sectionId){
        var lang=localStorage.getItem('lang')||'ar';
        var isEn=(lang==='en');
        var df=$('vlFilterDivision');
        var prevVal=df.value;
        df.innerHTML='<option value="">'+(isEn?'All Divisions':'كل الشعب')+'</option>';
        if(sectionId){
            (vlRefs.divisions||[]).filter(function(d){return d.section_id==sectionId;}).forEach(function(d){
                df.innerHTML+='<option value="'+(d.division_id||'')+'">'+((isEn?(d.name_en||d.name_ar):(d.name_ar||d.name_en))||'—')+'</option>';
            });
        }
        if(prevVal) df.value=prevVal;
    }

    /* Form cascading */
    function cascadeFormSection(deptId){
        var lang=localStorage.getItem('lang')||'ar';
        var isEn=(lang==='en');
        var s=$('vlFldSection');
        s.innerHTML='<option value="">—</option>';
        if(deptId){
            (vlRefs.sections||[]).filter(function(sc){return sc.department_id==deptId;}).forEach(function(sc){
                s.innerHTML+='<option value="'+(sc.section_id||'')+'">'+((isEn?(sc.name_en||sc.name_ar):(sc.name_ar||sc.name_en))||'—')+'</option>';
            });
        }
        cascadeFormDivision('');
    }
    function cascadeFormDivision(sectionId){
        var lang=localStorage.getItem('lang')||'ar';
        var isEn=(lang==='en');
        var d=$('vlFldDivision');
        d.innerHTML='<option value="">—</option>';
        if(sectionId){
            (vlRefs.divisions||[]).filter(function(dv){return dv.section_id==sectionId;}).forEach(function(dv){
                d.innerHTML+='<option value="'+(dv.division_id||'')+'">'+((isEn?(dv.name_en||dv.name_ar):(dv.name_ar||dv.name_en))||'—')+'</option>';
            });
        }
    }

    /* --- Load vehicles --- */
    function loadVehicles(){
        API.get('/vehicles').then(function(res){
            allVehicles=(res.data||res||[]);
            renderTable();
            updateStats();
        }).catch(function(e){
            console.error('Load vehicles error',e);
            var errMsg=(e&&e.message)||'';
            if(typeof UI!=='undefined'&&UI.showToast){
                UI.showToast((localStorage.getItem('lang')==='en'?'Failed to load vehicles':'تعذر تحميل المركبات')+(errMsg?': '+errMsg:''),'error');
            }
            allVehicles=[];
            renderTable();
            updateStats();
        });
    }

    /* --- Stats --- */
    function updateStats(){
        var total=allVehicles.length;
        var op=0, maint=0, oos=0;
        allVehicles.forEach(function(v){
            if(v.status==='operational') op++;
            else if(v.status==='maintenance') maint++;
            else if(v.status==='out_of_service') oos++;
        });
        $('vlStatTotal').textContent=total;
        $('vlStatOp').textContent=op;
        $('vlStatMaint').textContent=maint;
        $('vlStatOos').textContent=oos;
    }

    /* --- Render table --- */
    function renderTable(){
        var search=($('vlSearch').value||'').toLowerCase();
        var statusFilter=$('vlFilterStatus').value;
        var sectorFilter=$('vlFilterSector').value;
        var deptFilter=$('vlFilterDept').value;
        var sectionFilter=$('vlFilterSection').value;
        var divisionFilter=$('vlFilterDivision').value;
        var filtered=allVehicles.filter(function(v){
            if(statusFilter && v.status!==statusFilter) return false;
            if(sectorFilter && String(v.sector_id||'')!==sectorFilter) return false;
            if(deptFilter && String(v.department_id||'')!==deptFilter) return false;
            if(sectionFilter && String(v.section_id||'')!==sectionFilter) return false;
            if(divisionFilter && String(v.division_id||'')!==divisionFilter) return false;
            if(search){
                var code=(v.vehicle_code||'').toLowerCase();
                var type=(v.type||v.vehicle_type||'').toLowerCase();
                var empId=(v.emp_id||'').toLowerCase();
                var driverName=(v.driver_name||'').toLowerCase();
                var driverPhone=(v.driver_phone||'').toLowerCase();
                if(code.indexOf(search)<0 && type.indexOf(search)<0 && empId.indexOf(search)<0 && driverName.indexOf(search)<0 && driverPhone.indexOf(search)<0) return false;
            }
            return true;
        });

        var body=$('vlBody');
        if(!filtered.length){
            body.innerHTML='';
            $('vlTableWrap').style.display='none';
            $('vlEmpty').style.display='block';
            return;
        }
        $('vlTableWrap').style.display='';
        $('vlEmpty').style.display='none';

        var lang=localStorage.getItem('lang')||'ar';
        var isEn=(lang==='en');
        var html='';
        filtered.forEach(function(v,i){
            var statusCls=v.status==='operational'?'operational':(v.status==='maintenance'?'maintenance':'out_of_service');
            var statusTxt=v.status==='operational'?(isEn?'Operational':'تعمل'):(v.status==='maintenance'?(isEn?'Maintenance':'صيانة'):(isEn?'Out of Service':'خارج الخدمة'));
            var modeTxt=(v.vehicle_mode||v.usage_mode)==='private'?(isEn?'Private':'خاص'):(isEn?'Shift':'وردية');
            var sectorName='—', deptName='—', sectName='—', divName='—';
            if(v.sector_id){var sc=(vlRefs.sectors||[]).find(function(s){return s.id==v.sector_id;});if(sc) sectorName=(isEn?(sc.name_en||sc.name):(sc.name||sc.name_en))||'—';}
            if(v.department_id){var dd=(vlRefs.departments||[]).find(function(d){return d.department_id==v.department_id;});if(dd) deptName=(isEn?(dd.name_en||dd.name_ar):(dd.name_ar||dd.name_en))||'—';}
            if(v.section_id){var ss=(vlRefs.sections||[]).find(function(s){return s.section_id==v.section_id;});if(ss) sectName=(isEn?(ss.name_en||ss.name_ar):(ss.name_ar||ss.name_en))||'—';}
            if(v.division_id){var dv=(vlRefs.divisions||[]).find(function(d){return d.division_id==v.division_id;});if(dv) divName=(isEn?(dv.name_en||dv.name_ar):(dv.name_ar||dv.name_en))||'—';}
            var actions='';
            if(canEdit) actions+='<button class="btn-icon btn-edit" onclick="VLForm.showEdit('+v.id+')" title="Edit">✏️</button>';
            if(canDelete) actions+='<button class="btn-icon btn-delete" onclick="VLForm.showDelete('+v.id+')" title="Delete">🗑️</button>';
            html+='<tr>'
                +'<td>'+(i+1)+'</td>'
                +'<td>'+(v.vehicle_code||'—')+'</td>'
                +'<td>'+(v.type||v.vehicle_type||'—')+'</td>'
                +'<td>'+(v.emp_id||'—')+'</td>'
                +'<td>'+(v.driver_name||'—')+'</td>'
                +'<td>'+(v.driver_phone||'—')+'</td>'
                +'<td>'+sectorName+'</td>'
                +'<td>'+deptName+'</td>'
                +'<td>'+sectName+'</td>'
                +'<td>'+divName+'</td>'
                +'<td><span class="vl-badge '+statusCls+'">'+statusTxt+'</span></td>'
                +'<td>'+modeTxt+'</td>'
                +'<td class="vl-actions">'+actions+'</td>'
                +'</tr>';
        });
        body.innerHTML=html;
    }

    /* --- Search & Filter events --- */
    $('vlSearch').addEventListener('input', renderTable);
    $('vlFilterStatus').addEventListener('change', renderTable);
    $('vlFilterSector').addEventListener('change', renderTable);
    $('vlFilterDept').addEventListener('change', function(){
        cascadeFilterSections(this.value);
        renderTable();
    });
    $('vlFilterSection').addEventListener('change', function(){
        cascadeFilterDivisions(this.value);
        renderTable();
    });
    $('vlFilterDivision').addEventListener('change', renderTable);

    /* Form cascading events */
    $('vlFldDept').addEventListener('change', function(){ cascadeFormSection(this.value); });
    $('vlFldSection').addEventListener('change', function(){ cascadeFormDivision(this.value); });

    /* --- Form object (exposed globally for onclick) --- */
    window.VLForm = {
        showAdd: function(){
            $('vlEditId').value='';
            $('vlFldCode').value='';
            $('vlFldType').value='';
            $('vlFldCategory').value='sedan';
            $('vlFldStatus').value='operational';
            $('vlFldMode').value='private';
            $('vlFldGender').value='men';
            $('vlFldSector').value='';
            $('vlFldDept').value='';
            $('vlFldSection').innerHTML='<option value="">—</option>';
            $('vlFldDivision').innerHTML='<option value="">—</option>';
            $('vlFldYear').value='';
            $('vlFldEmpId').value='';
            $('vlFldDriverName').value='';
            $('vlFldDriverPhone').value='';
            $('vlFldNotes').value='';
            $('vlModalTitle').textContent='إضافة مركبة';
            $('vlModal').classList.add('active');
        },
        showEdit: function(id){
            var v=allVehicles.find(function(x){return x.id==id;});
            if(!v) return;
            $('vlEditId').value=v.id;
            $('vlFldCode').value=v.vehicle_code||'';
            $('vlFldType').value=v.type||v.vehicle_type||'';
            $('vlFldCategory').value=v.vehicle_category||v.category||'sedan';
            $('vlFldStatus').value=v.status||'operational';
            $('vlFldMode').value=v.vehicle_mode||'private';
            $('vlFldGender').value=v.gender||'men';
            $('vlFldSector').value=v.sector_id||'';
            $('vlFldDept').value=v.department_id||'';
            cascadeFormSection(v.department_id||'');
            setTimeout(function(){
                $('vlFldSection').value=v.section_id||'';
                cascadeFormDivision(v.section_id||'');
                setTimeout(function(){ $('vlFldDivision').value=v.division_id||''; },50);
            },50);
            $('vlFldYear').value=v.manufacture_year||v.year||'';
            $('vlFldEmpId').value=v.emp_id||'';
            $('vlFldDriverName').value=v.driver_name||'';
            $('vlFldDriverPhone').value=v.driver_phone||'';
            $('vlFldNotes').value=v.notes||'';
            $('vlModalTitle').textContent='تعديل مركبة';
            $('vlModal').classList.add('active');
        },
        hide: function(){
            $('vlModal').classList.remove('active');
        },
        save: function(){
            var id=$('vlEditId').value;
            var data={
                vehicle_code: $('vlFldCode').value.trim(),
                type: $('vlFldType').value.trim(),
                vehicle_category: $('vlFldCategory').value,
                status: $('vlFldStatus').value,
                vehicle_mode: $('vlFldMode').value,
                gender: $('vlFldGender').value,
                sector_id: $('vlFldSector').value||null,
                department_id: $('vlFldDept').value||null,
                section_id: $('vlFldSection').value||null,
                division_id: $('vlFldDivision').value||null,
                manufacture_year: $('vlFldYear').value||null,
                emp_id: $('vlFldEmpId').value.trim()||null,
                driver_name: $('vlFldDriverName').value.trim()||null,
                driver_phone: $('vlFldDriverPhone').value.trim()||null,
                notes: $('vlFldNotes').value.trim()||null
            };
            if(!data.vehicle_code){
                UI.showToast('كود المركبة مطلوب','error');
                return;
            }
            var promise = id ? API.put('/vehicles/'+id, data) : API.post('/vehicles', data);
            promise.then(function(){
                UI.showToast(id?'تم التحديث':'تمت الإضافة','success');
                VLForm.hide();
                loadVehicles();
            }).catch(function(e){
                UI.showToast(e.message||'خطأ','error');
            });
        },
        showDelete: function(id){
            $('vlDeleteId').value=id;
            $('vlDeleteModal').classList.add('active');
        },
        confirmDelete: function(){
            var id=$('vlDeleteId').value;
            API.del('/vehicles/'+id).then(function(){
                UI.showToast('تم الحذف','success');
                $('vlDeleteModal').classList.remove('active');
                loadVehicles();
            }).catch(function(e){
                UI.showToast(e.message||'خطأ','error');
            });
        }
    };

    /* --- Apply language --- */
    function applyLang(){
        var lang=localStorage.getItem('lang')||'ar';
        var isEn=(lang==='en');
        document.querySelectorAll('#pageContent [data-label-ar]').forEach(function(el){
            if(el.tagName==='OPTION'){
                el.textContent=el.getAttribute(isEn?'data-label-en':'data-label-ar')||el.textContent;
            } else {
                el.textContent=el.getAttribute(isEn?'data-label-en':'data-label-ar')||el.textContent;
            }
        });
        var s=$('vlSearch');
        if(s) s.placeholder=s.getAttribute(isEn?'data-placeholder-en':'data-placeholder-ar')||s.placeholder;
    }

    /* --- Init with permission check --- */
    (function initPerms(){
        if(window.__pageDenied) return;
        var user=Auth.getUser();
        if(!user){setTimeout(initPerms,100);return;}
        var perms=(user.permissions)||[];
        canCreate=perms.includes('manage_vehicles')||perms.includes('*');
        canEdit=perms.includes('manage_vehicles')||perms.includes('*');
        canDelete=perms.includes('manage_vehicles')||perms.includes('*');
        if(canCreate){$('vlBtnAdd').style.display='';}
        applyLang();
        loadAllRefs().then(function(){ loadVehicles(); });
    })();
})();
</script>
<?php $pageScripts = ob_get_clean(); ?>

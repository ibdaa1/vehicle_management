<?php
/**
 * Vehicle List Fragment — Vehicle Management
 * Full CRUD inside the dashboard.
 * Uses ob_start/ob_get_clean pattern for deferred scripts.
 */
?>
<style>
.vl-stats{display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap}
.vl-stat{flex:1;min-width:140px;background:var(--bg-card,#fff);border-radius:12px;padding:16px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.vl-stat .num{font-size:1.8rem;font-weight:700;color:var(--primary-main,#1a5276)}
.vl-stat .lbl{font-size:.85rem;color:var(--text-secondary,#666);margin-top:4px}
.vl-toolbar{display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;align-items:center}
.vl-toolbar .search-box{flex:1;min-width:200px;position:relative}
.vl-toolbar .search-box input{width:100%;padding:10px 12px 10px 36px;border:1px solid var(--border-default,#ddd);border-radius:8px;font-size:.95rem}
.vl-toolbar .search-box .ico{position:absolute;right:12px;top:50%;transform:translateY(-50%);color:#999}
.vl-toolbar select{padding:10px;border:1px solid var(--border-default,#ddd);border-radius:8px;font-size:.9rem}
.vl-toolbar .btn-add{margin-inline-start:auto}
.vl-table{width:100%;border-collapse:separate;border-spacing:0;background:var(--bg-card,#fff);border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.vl-table th{background:var(--primary-dark,#1a5276);color:#fff;padding:12px 14px;font-size:.85rem;white-space:nowrap}
.vl-table td{padding:10px 14px;border-bottom:1px solid var(--border-default,#eee);font-size:.9rem}
.vl-table tr:hover td{background:rgba(26,82,118,.04)}
.vl-badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.8rem;font-weight:600}
.vl-badge.operational{background:#d4edda;color:#155724}
.vl-badge.maintenance{background:#fff3cd;color:#856404}
.vl-badge.out_of_service{background:#f8d7da;color:#721c24}
.vl-actions button{background:none;border:none;cursor:pointer;font-size:1.1rem;padding:4px}
.vl-empty{text-align:center;padding:60px 20px;color:#999}
.vl-empty .ico{font-size:3rem;margin-bottom:12px}
.modal-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.5);z-index:9000;align-items:center;justify-content:center}
.modal-overlay.active{display:flex}
.modal-box{background:var(--bg-card,#fff);border-radius:16px;padding:24px;width:90%;max-width:560px;max-height:85vh;overflow-y:auto;box-shadow:0 8px 32px rgba(0,0,0,.2)}
.modal-box h3{margin:0 0 16px;font-size:1.2rem}
.form-group{margin-bottom:14px}
.form-group label{display:block;font-size:.85rem;font-weight:600;margin-bottom:4px;color:var(--text-secondary,#555)}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:10px;border:1px solid var(--border-default,#ddd);border-radius:8px;font-size:.95rem}
.form-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:18px}
.btn{padding:10px 20px;border:none;border-radius:8px;cursor:pointer;font-size:.9rem;font-weight:600}
.btn-primary{background:var(--primary-main,#1a5276);color:#fff}
.btn-secondary{background:var(--bg-secondary,#e9ecef);color:var(--text-primary,#333)}
.btn-danger{background:#dc3545;color:#fff}
.btn-sm{padding:6px 12px;font-size:.8rem}
</style>

<div class="page-header">
    <h2 id="vlPageTitle" data-label-ar="إدارة المركبات" data-label-en="Vehicle Management">إدارة المركبات</h2>
</div>

<!-- Stats -->
<div class="vl-stats">
    <div class="vl-stat"><div class="num" id="vlStatTotal">—</div><div class="lbl" id="vlLblTotal" data-label-ar="الإجمالي" data-label-en="Total">الإجمالي</div></div>
    <div class="vl-stat"><div class="num" id="vlStatOp" style="color:#28a745">—</div><div class="lbl" id="vlLblOp" data-label-ar="تعمل" data-label-en="Operational">تعمل</div></div>
    <div class="vl-stat"><div class="num" id="vlStatMaint" style="color:#ffc107">—</div><div class="lbl" id="vlLblMaint" data-label-ar="صيانة" data-label-en="Maintenance">صيانة</div></div>
    <div class="vl-stat"><div class="num" id="vlStatOos" style="color:#dc3545">—</div><div class="lbl" id="vlLblOos" data-label-ar="خارج الخدمة" data-label-en="Out of Service">خارج الخدمة</div></div>
</div>

<!-- Toolbar -->
<div class="vl-toolbar">
    <div class="search-box">
        <input type="text" id="vlSearch" placeholder="بحث..." data-placeholder-ar="بحث عن مركبة..." data-placeholder-en="Search vehicles...">
        <span class="ico">🔍</span>
    </div>
    <select id="vlFilterStatus">
        <option value="">كل الحالات</option>
        <option value="operational">تعمل</option>
        <option value="maintenance">صيانة</option>
        <option value="out_of_service">خارج الخدمة</option>
    </select>
    <button class="btn btn-primary btn-add" id="vlBtnAdd" style="display:none" onclick="VLForm.showAdd()">➕ <span data-label-ar="إضافة مركبة" data-label-en="Add Vehicle">إضافة مركبة</span></button>
</div>

<!-- Data Table -->
<div id="vlTableWrap">
    <table class="vl-table" id="vlTable">
        <thead>
            <tr>
                <th>#</th>
                <th data-label-ar="كود المركبة" data-label-en="Vehicle Code">كود المركبة</th>
                <th data-label-ar="النوع" data-label-en="Type">النوع</th>
                <th data-label-ar="القسم" data-label-en="Department">القسم</th>
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
            <label id="vlLblDept" data-label-ar="القسم" data-label-en="Department">القسم</label>
            <select id="vlFldDept"><option value="">—</option></select>
        </div>
        <div class="form-group">
            <label id="vlLblYear" data-label-ar="سنة الصنع" data-label-en="Year">سنة الصنع</label>
            <input type="number" id="vlFldYear" min="2000" max="2099">
        </div>
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

    /* --- Load vehicles --- */
    function loadVehicles(){
        API.get('/vehicles').then(function(res){
            allVehicles=(res.data||res||[]);
            renderTable();
            updateStats();
        }).catch(function(e){ console.error('Load vehicles error',e); });
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
        var filtered=allVehicles.filter(function(v){
            if(statusFilter && v.status!==statusFilter) return false;
            if(search){
                var code=(v.vehicle_code||'').toLowerCase();
                var type=(v.type||v.vehicle_type||'').toLowerCase();
                if(code.indexOf(search)<0 && type.indexOf(search)<0) return false;
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

        var html='';
        filtered.forEach(function(v,i){
            var statusCls=v.status==='operational'?'operational':(v.status==='maintenance'?'maintenance':'out_of_service');
            var statusTxt=v.status==='operational'?'تعمل':(v.status==='maintenance'?'صيانة':'خارج الخدمة');
            var modeTxt=(v.vehicle_mode||v.usage_mode)==='private'?'خاص':'وردية';
            var actions='';
            if(canEdit) actions+='<button onclick="VLForm.showEdit('+v.id+')" title="Edit">✏️</button>';
            if(canDelete) actions+='<button onclick="VLForm.showDelete('+v.id+')" title="Delete">🗑️</button>';
            html+='<tr>'
                +'<td>'+(i+1)+'</td>'
                +'<td>'+(v.vehicle_code||'—')+'</td>'
                +'<td>'+(v.type||v.vehicle_type||'—')+'</td>'
                +'<td>'+(v.department_name_ar||v.department_name||'—')+'</td>'
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

    /* --- Load departments for dropdown --- */
    function loadDepartments(){
        API.get('/references/departments').then(function(res){
            var depts=res.data||res||[];
            var sel=$('vlFldDept');
            sel.innerHTML='<option value="">—</option>';
            depts.forEach(function(d){
                sel.innerHTML+='<option value="'+d.id+'">'+d.name+'</option>';
            });
        }).catch(function(){});
    }

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
            $('vlFldDept').value='';
            $('vlFldYear').value='';
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
            $('vlFldDept').value=v.department_id||'';
            $('vlFldYear').value=v.manufacture_year||v.year||'';
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
                department_id: $('vlFldDept').value||null,
                manufacture_year: $('vlFldYear').value||null
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
            el.textContent=el.getAttribute(isEn?'data-label-en':'data-label-ar')||el.textContent;
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
        loadDepartments();
        loadVehicles();
    })();
})();
</script>
<?php $pageScripts = ob_get_clean(); ?>

<?php
/**
 * Violations Fragment — Vehicle Violations Management
 * Full CRUD inside the dashboard with vehicle-holder lookup at violation time.
 */
?>
<style>
/* Fix LTR layout flash: html[dir] is set before CSS renders, body[dir] after */
html[dir="ltr"] body{direction:ltr;font-family:var(--font-en)}
html[dir="ltr"] .app-sidebar{right:auto;left:0}
html[dir="ltr"] .app-main{margin-right:0;margin-left:var(--sidebar-width)}
html[dir="ltr"] .app-footer{margin-right:0;margin-left:var(--sidebar-width)}
html[dir="ltr"] .app-sidebar.collapsed~.app-main{margin-right:0;margin-left:var(--sidebar-collapsed-width)}
.vl-stats{display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap}
.vl-stat{flex:1;min-width:140px;background:var(--bg-card,#fff);border-radius:12px;padding:16px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.vl-stat .num{font-size:1.8rem;font-weight:700;color:var(--primary-main,#1a5276)}
.vl-stat .lbl{font-size:.85rem;color:var(--text-secondary,#666);margin-top:4px}
.vl-stat .amt{font-size:.85rem;color:var(--text-secondary,#888);margin-top:2px}
.vl-stat.paid .num{color:#28a745}
.vl-stat.unpaid .num{color:#dc3545}
.vl-toolbar{display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;align-items:center}
.vl-toolbar .search-box{flex:1;min-width:200px;position:relative}
.vl-toolbar .search-box input{width:100%;padding:10px 12px;padding-inline-end:36px;border:1px solid var(--border-default,#ddd);border-radius:8px;font-size:.95rem}
.vl-toolbar .search-box .ico{position:absolute;inset-inline-end:12px;top:50%;transform:translateY(-50%);color:#999}
.vl-toolbar select{padding:10px;border:1px solid var(--border-default,#ddd);border-radius:8px;font-size:.9rem}
.vl-toolbar .btn-add{margin-inline-start:auto}
.vl-table{width:100%;border-collapse:separate;border-spacing:0;background:var(--bg-card,#fff);border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.vl-table th{background:var(--primary-dark,#1a5276);color:#fff;padding:12px 14px;font-size:.85rem;white-space:nowrap}
.vl-table td{padding:10px 14px;border-bottom:1px solid var(--border-default,#eee);font-size:.9rem}
.vl-table tr:hover td{background:rgba(26,82,118,.04)}
.vl-badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.8rem;font-weight:600}
.vl-badge.paid{background:#d4edda;color:#155724}
.vl-badge.unpaid{background:#f8d7da;color:#721c24}
.vl-actions button{background:none;border:none;cursor:pointer;font-size:1.1rem;padding:4px}
.vl-empty{text-align:center;padding:60px 20px;color:#999}
.vl-empty .ico{font-size:3rem;margin-bottom:12px}
/* Modal */
.vl-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center}
.vl-modal-bg.show{display:flex}
.vl-modal{background:var(--bg-card,#fff);border-radius:16px;width:95%;max-width:640px;max-height:90vh;overflow-y:auto;padding:0}
.vl-modal .modal-hd{display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid var(--border-default,#eee);position:sticky;top:0;background:var(--bg-card,#fff);z-index:1}
.vl-modal .modal-hd h3{margin:0;font-size:1.1rem}
.vl-modal .modal-hd .close{background:none;border:none;font-size:1.5rem;cursor:pointer;color:#999}
.vl-modal .modal-bd{padding:20px}
.vl-form .fg{margin-bottom:16px}
.vl-form label{display:block;font-weight:600;font-size:.85rem;margin-bottom:6px;color:var(--text-secondary,#555)}
.vl-form input,.vl-form select,.vl-form textarea{width:100%;padding:10px 12px;border:1px solid var(--border-default,#ddd);border-radius:8px;font-size:.95rem;box-sizing:border-box}
.vl-form textarea{resize:vertical;min-height:70px}
.vl-form .row2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.vl-form .modal-ft{display:flex;gap:12px;padding:16px 20px;border-top:1px solid var(--border-default,#eee);justify-content:flex-end;position:sticky;bottom:0;background:var(--bg-card,#fff)}
/* Detail modal */
.vl-detail .d-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border-default,#f0f0f0)}
.vl-detail .d-row .d-lbl{color:var(--text-secondary,#777);font-size:.85rem}
.vl-detail .d-row .d-val{font-weight:600}
.vl-page{display:flex;justify-content:center;gap:8px;margin-top:16px;flex-wrap:wrap;align-items:center}
.vl-page button{padding:6px 14px;border:1px solid var(--border-default,#ddd);border-radius:6px;background:#fff;cursor:pointer;transition:all .3s}
.vl-page button:hover:not(:disabled){background:var(--primary-main,#1a5276);color:#fff}
.vl-page button.active{background:var(--primary-main,#1a5276);color:#fff;border-color:var(--primary-main)}
.vl-page button:disabled{opacity:.4;cursor:not-allowed}
.vl-page-info{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-top:8px;font-size:.85rem;color:var(--text-secondary,#666)}
.vl-page-info .pg-goto{display:flex;align-items:center;gap:6px}
.vl-page-info .pg-goto input{width:60px;height:30px;text-align:center;border:1px solid var(--border-default,#ddd);border-radius:6px;font-size:.85rem}
.vl-page-info .pg-goto button{height:30px;padding:0 10px;border:1px solid var(--primary-main,#1a5276);background:var(--primary-main,#1a5276);color:#fff;border-radius:6px;cursor:pointer;font-size:.8rem}
.vl-holder{display:flex;align-items:center;gap:6px;font-size:.85rem;color:var(--primary-main,#1a5276)}
.vl-holder .holder-icon{font-size:1rem}
/* Skeleton */
.skel-row td{padding:10px 14px}
.skel-cell{height:14px;border-radius:6px;background:linear-gradient(90deg,#e8e8e8 25%,#f5f5f5 50%,#e8e8e8 75%);background-size:200% 100%;animation:vl-shimmer 1.5s infinite}
@keyframes vl-shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
</style>

<div class="page-header"><h2 id="vlPageTitle" data-label-en="Violations" data-label-ar="المخالفات">Violations</h2></div>

<!-- Stats -->
<div class="vl-stats">
    <div class="vl-stat"><div class="num" id="vlStatTotal">0</div><div class="lbl" id="vlLblTotal" data-label-en="Total Violations" data-label-ar="إجمالي المخالفات">Total Violations</div></div>
    <div class="vl-stat"><div class="num" id="vlStatAmount">0</div><div class="lbl" id="vlLblAmount" data-label-en="Total Amount" data-label-ar="إجمالي المبالغ">Total Amount</div><div class="amt">AED</div></div>
    <div class="vl-stat paid"><div class="num" id="vlStatPaid">0</div><div class="lbl" id="vlLblPaid" data-label-en="Paid" data-label-ar="المدفوعة">Paid Amount</div><div class="amt" id="vlStatPaidAmt">0 AED</div></div>
    <div class="vl-stat unpaid"><div class="num" id="vlStatUnpaid">0</div><div class="lbl" id="vlLblUnpaid" data-label-en="Unpaid" data-label-ar="غير المدفوعة">Unpaid Amount</div><div class="amt" id="vlStatUnpaidAmt">0 AED</div></div>
</div>

<!-- Toolbar -->
<div class="vl-toolbar">
    <div class="search-box">
        <span class="ico">🔍</span>
        <input type="text" id="vlSearch" placeholder="Search violation..." data-placeholder-en="Search by vehicle code or holder..." data-placeholder-ar="بحث برقم المركبة أو المستلم...">
    </div>
    <select id="vlFilterStatus">
        <option value="" id="vlOptAll" data-label-en="All Statuses" data-label-ar="كل الحالات">All Statuses</option>
        <option value="paid" id="vlOptPaid" data-label-en="Paid" data-label-ar="مدفوعة">Paid</option>
        <option value="unpaid" id="vlOptUnpaid" data-label-en="Unpaid" data-label-ar="غير مدفوعة">Unpaid</option>
    </select>
    <button class="btn btn-primary btn-sm btn-add" id="vlBtnAdd" data-label-en="➕ Add Violation" data-label-ar="➕ إضافة مخالفة">➕ Add Violation</button>
</div>

<!-- Table -->
<div class="table-responsive">
<table class="vl-table data-table">
    <thead><tr>
        <th>#</th>
        <th id="vlThVehicle" data-label-en="Vehicle Code" data-label-ar="رقم المركبة">Vehicle Code</th>
        <th id="vlThDate" data-label-en="Violation Date" data-label-ar="تاريخ المخالفة">Violation Date</th>
        <th id="vlThAmount" data-label-en="Amount" data-label-ar="المبلغ">Amount</th>
        <th id="vlThStatus" data-label-en="Payment Status" data-label-ar="حالة الدفع">Payment Status</th>
        <th id="vlThHolder" data-label-en="Violation Holder" data-label-ar="مستلم المركبة">Violation Holder</th>
        <th id="vlThReceived" data-label-en="Received At" data-label-ar="وقت الاستلام">Received At</th>
        <th id="vlThAddedBy" data-label-en="Added By" data-label-ar="أضيف بواسطة">Added By</th>
        <th id="vlThNotes" data-label-en="Notes" data-label-ar="ملاحظات">Notes</th>
        <th id="vlThActions" data-label-en="Actions" data-label-ar="الإجراءات">Actions</th>
    </tr></thead>
    <tbody id="vlTableBody"></tbody>
</table>
</div>
<div class="vl-page" id="vlPagination"></div>
<div class="vl-page-info" id="vlPaginationInfo"></div>

<!-- Add/Edit Modal -->
<div class="vl-modal-bg" id="vlModal">
    <div class="vl-modal">
        <div class="modal-hd">
            <h3 id="vlModalTitle" data-label-en="➕ Add Violation" data-label-ar="➕ إضافة مخالفة">➕ Add Violation</h3>
            <button class="close" id="vlModalClose">&times;</button>
        </div>
        <div class="modal-bd">
            <form class="vl-form" id="vlForm">
                <input type="hidden" id="vlId">
                <div class="row2">
                    <div class="fg">
                        <label id="vlLblVehicleCode" data-label-en="Vehicle Code *" data-label-ar="رقم المركبة *">Vehicle Code *</label>
                        <input type="text" id="vlVehicleCode" required placeholder="e.g.: SHJ-1234">
                    </div>
                    <div class="fg">
                        <label id="vlLblDatetime" data-label-en="Violation Date *" data-label-ar="تاريخ المخالفة *">Violation Date *</label>
                        <input type="datetime-local" id="vlDatetime" required>
                    </div>
                </div>
                <div class="row2">
                    <div class="fg">
                        <label id="vlLblAmountField" data-label-en="Amount (AED) *" data-label-ar="المبلغ (درهم) *">Amount (AED) *</label>
                        <input type="number" id="vlAmount" min="0" step="0.01" required placeholder="0.00">
                    </div>
                    <div class="fg">
                        <label id="vlLblPayStatus" data-label-en="Payment Status" data-label-ar="حالة الدفع">Payment Status</label>
                        <select id="vlStatus">
                            <option value="unpaid" id="vlFormOptUnpaid" data-label-en="Unpaid" data-label-ar="غير مدفوعة">Unpaid</option>
                            <option value="paid" id="vlFormOptPaid" data-label-en="Paid" data-label-ar="مدفوعة">Paid</option>
                        </select>
                    </div>
                </div>
                <div class="fg">
                    <label id="vlLblNotes" data-label-en="Notes" data-label-ar="ملاحظات">Notes</label>
                    <textarea id="vlNotes" rows="3" placeholder="Add additional notes here..."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-ft">
            <button class="btn btn-ghost" id="vlCancelBtn" data-label-en="Cancel" data-label-ar="إلغاء">Cancel</button>
            <button class="btn btn-primary" id="vlSaveBtn" data-label-en="💾 Save" data-label-ar="💾 حفظ">💾 Save</button>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div class="vl-modal-bg" id="vlDetailModal">
    <div class="vl-modal">
        <div class="modal-hd">
            <h3 id="vlDetailTitle" data-label-en="Violation Details" data-label-ar="تفاصيل المخالفة">Violation Details</h3>
            <button class="close" id="vlDetailClose">&times;</button>
        </div>
        <div class="modal-bd vl-detail" id="vlDetailBody"></div>
    </div>
</div>

<?php ob_start(); ?>
<script>
(function(){
    const $=id=>document.getElementById(id);
    const esc=s=>{const d=document.createElement('div');d.textContent=s||'';return d.innerHTML;};
    let allViolations=[], filteredViolations=[], currentPage=1, perPage=100;
    var vlCanCreate=false, vlCanEdit=false, vlCanDelete=false;

    /* ---- Skeleton loader ---- */
    function showSkeleton(){
        const cols=10, rows=5;
        const cell='<td><div class="skel-cell"></div></td>';
        const row='<tr class="skel-row">'+Array(cols).fill(cell).join('')+'</tr>';
        $('vlTableBody').innerHTML=Array(rows).fill(row).join('');
    }

    /* ---- Load ---- */
    async function loadViolations(){
        showSkeleton();
        try{
            const res=await API.get('/violations');
            allViolations=(res.data||res)||[];
        }catch(e){allViolations=[];}
        applyFilters();
        loadStats();
    }

    async function loadStats(){
        try{
            const res=await API.get('/violations/stats');
            const s=res.data||res;
            $('vlStatTotal').textContent=s.total||0;
            $('vlStatAmount').textContent=Number(s.total_amount||0).toLocaleString();
            $('vlStatPaid').textContent=s.paid||0;
            $('vlStatPaidAmt').textContent=Number(s.paid_amount||0).toLocaleString()+' AED';
            $('vlStatUnpaid').textContent=s.unpaid||0;
            $('vlStatUnpaidAmt').textContent=Number(s.unpaid_amount||0).toLocaleString()+' AED';
        }catch(e){}
    }

    function applyFilters(){
        const q=($('vlSearch').value||'').toLowerCase();
        const st=$('vlFilterStatus').value;
        filteredViolations=allViolations.filter(v=>{
            if(st && v.violation_status!==st) return false;
            if(q && !((v.vehicle_code||'').toLowerCase().includes(q)||(v.holder_name||'').toLowerCase().includes(q)||(v.holder_emp_id||'').toLowerCase().includes(q))) return false;
            return true;
        });
        currentPage=1;
        render();
    }

    function render(){
        const start=(currentPage-1)*perPage;
        const page=filteredViolations.slice(start,start+perPage);
        const tbody=$('vlTableBody');
        if(!page.length){
            tbody.innerHTML='<tr><td colspan="10"><div class="vl-empty"><div class="ico">⚠️</div><p>'+i18n.t('no_violations')+'</p></div></td></tr>';
            $('vlPagination').innerHTML='';
            return;
        }
        let h='';
        page.forEach((v,i)=>{
            const statusLabel=v.violation_status==='paid'?i18n.t('paid'):i18n.t('unpaid');
            const holderInfo=v.holder_name?'<span class="vl-holder"><span class="holder-icon">👤</span>'+esc(v.holder_name)+'</span>':'—';
            const pickupDt=v.pickup_datetime?esc((v.pickup_datetime||'').replace('T',' ').substring(0,16)):'—';
            h+='<tr>';
            h+='<td>'+(start+i+1)+'</td>';
            h+='<td><strong>'+esc(v.vehicle_code)+'</strong></td>';
            h+='<td>'+esc((v.violation_datetime||'').replace('T',' ').substring(0,16))+'</td>';
            h+='<td><strong>'+Number(v.violation_amount||0).toLocaleString()+' AED</strong></td>';
            h+='<td><span class="vl-badge '+v.violation_status+'">'+statusLabel+'</span></td>';
            h+='<td>'+holderInfo+'</td>';
            h+='<td>'+pickupDt+'</td>';
            h+='<td>'+esc(v.issued_by_emp_id||'—')+'</td>';
            h+='<td>'+esc(v.notes||'—')+'</td>';
            h+='<td class="vl-actions">';
            h+='<button onclick="VlPage.view('+v.id+')" title="'+i18n.t('view')+'">👁</button>';
            if(vlCanEdit) h+='<button onclick="VlPage.edit('+v.id+')" title="'+i18n.t('edit_violation')+'">✏️</button>';
            if(vlCanDelete) h+='<button onclick="VlPage.del('+v.id+')" title="'+i18n.t('delete')+'">🗑️</button>';
            h+='</td></tr>';
        });
        tbody.innerHTML=h;
        renderPagination();
    }

    function renderPagination(){
        const totalItems=filteredViolations.length;
        const totalPg=Math.ceil(totalItems/perPage);
        const pg=$('vlPagination'),info=$('vlPaginationInfo');
        if(totalPg<=1){pg.innerHTML='';info.innerHTML=totalItems?'<span>'+i18n.t('total_records')+': '+totalItems+'</span>':'';return;}
        let h='<button '+(currentPage<=1?'disabled':'')+' onclick="VlPage.goPage('+(currentPage-1)+')">'+i18n.t('previous')+'</button>';
        let start=Math.max(1,currentPage-3),end=Math.min(totalPg,currentPage+3);
        if(start>1){h+='<button onclick="VlPage.goPage(1)">1</button>';if(start>2)h+='<span style="padding:0 4px">…</span>';}
        for(let i=start;i<=end;i++){
            h+='<button class="'+(i===currentPage?'active':'')+'" onclick="VlPage.goPage('+i+')">'+i+'</button>';
        }
        if(end<totalPg){if(end<totalPg-1)h+='<span style="padding:0 4px">…</span>';h+='<button onclick="VlPage.goPage('+totalPg+')">'+totalPg+'</button>';}
        h+='<button '+(currentPage>=totalPg?'disabled':'')+' onclick="VlPage.goPage('+(currentPage+1)+')">'+i18n.t('next')+'</button>';
        pg.innerHTML=h;
        info.innerHTML='<span>'+i18n.t('total_records')+': '+totalItems+' | '+i18n.t('page')+' '+currentPage+' '+i18n.t('of')+' '+totalPg+'</span>'+
            '<div class="pg-goto"><label>'+i18n.t('go_to_page')+':</label><input type="number" min="1" max="'+totalPg+'" id="vlGotoInput" value="'+currentPage+'"><button onclick="VlPage.gotoPage()">↵</button></div>';
    }

    /* ---- Modal ---- */
    function openModal(title){
        $('vlModalTitle').textContent=title||i18n.t('add_violation');
        $('vlModal').classList.add('show');
    }
    function closeModal(){$('vlModal').classList.remove('show');}

    $('vlBtnAdd').addEventListener('click',()=>{
        $('vlForm').reset();$('vlId').value='';
        openModal('➕ '+i18n.t('add_violation'));
    });
    $('vlModalClose').addEventListener('click',closeModal);
    $('vlCancelBtn').addEventListener('click',closeModal);

    /* ---- Filters ---- */
    $('vlSearch').addEventListener('input',applyFilters);
    $('vlFilterStatus').addEventListener('change',applyFilters);

    /* ---- Save ---- */
    $('vlSaveBtn').addEventListener('click',async()=>{
        const id=$('vlId').value;
        const data={
            vehicle_code:$('vlVehicleCode').value.trim(),
            violation_datetime:$('vlDatetime').value,
            violation_amount:$('vlAmount').value,
            violation_status:$('vlStatus').value,
            notes:$('vlNotes').value.trim(),
        };
        if(!data.vehicle_code||!data.violation_datetime||!data.violation_amount){
            UI.showToast(i18n.t('required_fields'),'error');return;
        }
        try{
            if(id){
                await API.put('/violations/'+id,data);
                UI.showToast(i18n.t('violation_updated'),'success');
            }else{
                await API.post('/violations',data);
                UI.showToast(i18n.t('violation_added'),'success');
            }
            closeModal();
            loadViolations();
        }catch(e){UI.showToast(e.message||i18n.t('error'),'error');}
    });

    /* ---- Global methods ---- */
    window.VlPage={
        goPage(p){const totalPg=Math.ceil(filteredViolations.length/perPage);if(p<1)p=1;if(p>totalPg)p=totalPg;currentPage=p;render();window.scrollTo({top:0,behavior:'smooth'});},
        gotoPage(){const inp=$('vlGotoInput');if(inp){const p=parseInt(inp.value);if(p&&p>=1)this.goPage(p);}},
        async view(id){
            try{
                const res=await API.get('/violations/'+id);
                const v=res.data||res;
                const statusLabel=v.violation_status==='paid'?i18n.t('paid'):i18n.t('unpaid');
                let h='';
                h+='<div class="d-row"><span class="d-lbl">'+i18n.t('vehicle_code')+'</span><span class="d-val">'+esc(v.vehicle_code)+'</span></div>';
                h+='<div class="d-row"><span class="d-lbl">'+i18n.t('violation_date')+'</span><span class="d-val">'+esc(v.violation_datetime)+'</span></div>';
                h+='<div class="d-row"><span class="d-lbl">'+i18n.t('amount')+'</span><span class="d-val">'+Number(v.violation_amount||0).toLocaleString()+' AED</span></div>';
                h+='<div class="d-row"><span class="d-lbl">'+i18n.t('payment_status')+'</span><span class="d-val"><span class="vl-badge '+v.violation_status+'">'+statusLabel+'</span></span></div>';
                if(v.holder_name){
                    h+='<div class="d-row"><span class="d-lbl">'+i18n.t('violation_holder')+'</span><span class="d-val"><span class="vl-holder"><span class="holder-icon">👤</span>'+esc(v.holder_name)+' ('+esc(v.holder_emp_id)+')</span></span></div>';
                    h+='<div class="d-row"><span class="d-lbl">'+i18n.t('received_at')+'</span><span class="d-val">'+esc(v.pickup_datetime||'—')+'</span></div>';
                }
                h+='<div class="d-row"><span class="d-lbl">'+i18n.t('added_by')+'</span><span class="d-val">'+esc(v.issued_by_emp_id||'—')+'</span></div>';
                h+='<div class="d-row"><span class="d-lbl">'+i18n.t('notes')+'</span><span class="d-val">'+esc(v.notes||'—')+'</span></div>';
                if(v.paid_by_emp_id){
                    h+='<div class="d-row"><span class="d-lbl">'+i18n.t('paid_by')+'</span><span class="d-val">'+esc(v.paid_by_emp_id)+'</span></div>';
                }
                if(v.payment_datetime){
                    h+='<div class="d-row"><span class="d-lbl">'+i18n.t('payment_date')+'</span><span class="d-val">'+esc(v.payment_datetime)+'</span></div>';
                }
                $('vlDetailBody').innerHTML=h;
                $('vlDetailTitle').textContent=i18n.t('violations')+' #'+id;
                $('vlDetailModal').classList.add('show');
            }catch(e){UI.showToast(i18n.t('load_failed'),'error');}
        },
        async edit(id){
            try{
                const res=await API.get('/violations/'+id);
                const v=res.data||res;
                $('vlId').value=v.id;
                $('vlVehicleCode').value=v.vehicle_code||'';
                $('vlDatetime').value=(v.violation_datetime||'').replace(' ','T').substring(0,16);
                $('vlAmount').value=v.violation_amount||'';
                $('vlStatus').value=v.violation_status||'unpaid';
                $('vlNotes').value=v.notes||'';
                openModal('✏️ '+i18n.t('edit_violation')+' #'+id);
            }catch(e){UI.showToast(i18n.t('load_failed'),'error');}
        },
        async del(id){
            if(!confirm(i18n.t('confirm_delete')))return;
            try{
                await API.del('/violations/'+id);
                UI.showToast(i18n.t('violation_deleted'),'success');
                loadViolations();
            }catch(e){UI.showToast(e.message||i18n.t('error'),'error');}
        }
    };

    $('vlDetailClose').addEventListener('click',()=>$('vlDetailModal').classList.remove('show'));

    // Translate static HTML elements
    function translateStatic(){
        // Retry if i18n translations are not loaded yet
        if(!i18n.strings || !Object.keys(i18n.strings).length){
            setTimeout(translateStatic,100);
            return;
        }
        $('vlPageTitle').textContent=i18n.t('violations');
        $('vlLblTotal').textContent=i18n.t('total_violations');
        $('vlLblAmount').textContent=i18n.t('total_amount');
        $('vlLblPaid').textContent=i18n.t('paid_amount');
        $('vlLblUnpaid').textContent=i18n.t('unpaid_amount');
        $('vlSearch').placeholder=i18n.t('search_violation');
        $('vlOptAll').textContent=i18n.t('all_statuses');
        $('vlOptPaid').textContent=i18n.t('paid');
        $('vlOptUnpaid').textContent=i18n.t('unpaid');
        $('vlBtnAdd').textContent='➕ '+i18n.t('add_violation');
        $('vlThVehicle').textContent=i18n.t('vehicle_code');
        $('vlThDate').textContent=i18n.t('violation_date');
        $('vlThAmount').textContent=i18n.t('amount');
        $('vlThStatus').textContent=i18n.t('payment_status');
        $('vlThHolder').textContent=i18n.t('violation_holder');
        $('vlThReceived').textContent=i18n.t('received_at');
        $('vlThAddedBy').textContent=i18n.t('added_by');
        $('vlThNotes').textContent=i18n.t('notes');
        $('vlThActions').textContent=i18n.t('actions');
        $('vlLblVehicleCode').textContent=i18n.t('vehicle_code')+' *';
        $('vlLblDatetime').textContent=i18n.t('violation_date')+' *';
        $('vlLblAmountField').textContent=i18n.t('amount')+' (AED) *';
        $('vlLblPayStatus').textContent=i18n.t('payment_status');
        $('vlFormOptUnpaid').textContent=i18n.t('unpaid');
        $('vlFormOptPaid').textContent=i18n.t('paid');
        $('vlLblNotes').textContent=i18n.t('notes');
        $('vlCancelBtn').textContent=i18n.t('cancel');
        $('vlSaveBtn').textContent='💾 '+i18n.t('save');
    }

    // Init
    (function initPerms(){
        var user=Auth.getUser();
        if(!user){setTimeout(initPerms,100);return;}
        var perms=(user.permissions)||[];
        vlCanCreate=perms.includes('manage_violations')||perms.includes('*');
        vlCanEdit=perms.includes('manage_violations')||perms.includes('*');
        vlCanDelete=perms.includes('manage_violations')||perms.includes('*');
        if(!vlCanCreate){var ab=$('vlBtnAdd');if(ab)ab.style.display='none';}
        translateStatic();
        loadViolations();
    })();
})();
</script>
<?php $pageScripts = ob_get_clean(); ?>
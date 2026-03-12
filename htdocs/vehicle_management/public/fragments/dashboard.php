<?php
/**
 * Dashboard Fragment — Statistics, Quick Actions, Recent Vehicles
 * Loaded inside dashboard.php shell (header/sidebar/footer already rendered).
 */
?>
<style>
.dashboard-stats{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:20px;margin-bottom:32px}
.dash-stat{display:flex;gap:16px;align-items:center;background:var(--bg-card);padding:20px;border-radius:12px;box-shadow:var(--card-shadow);border:1px solid var(--border-default);border-inline-start:4px solid var(--border-default);transition:transform .3s,box-shadow .3s;position:relative;overflow:hidden}
.dash-stat:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.12)}
.dash-stat.accent-blue{border-inline-start-color:var(--status-info)}
.dash-stat.accent-green{border-inline-start-color:var(--status-success)}
.dash-stat.accent-warning{border-inline-start-color:var(--status-warning)}
.dash-stat.accent-danger{border-inline-start-color:var(--status-danger)}
.dash-stat.accent-gold{border-inline-start-color:var(--accent-gold)}
.dash-stat .s-icon{width:52px;height:52px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0}
.dash-stat .s-icon.blue{background:rgba(23,162,184,.12);color:var(--status-info)}
.dash-stat .s-icon.green{background:rgba(40,167,69,.12);color:var(--status-success)}
.dash-stat .s-icon.gold{background:rgba(212,175,55,.12);color:var(--accent-gold)}
.dash-stat .s-icon.red{background:rgba(220,53,69,.12);color:var(--status-danger)}
.dash-stat .s-icon.warn{background:rgba(255,193,7,.15);color:#c49000}
.dash-stat .s-info h4{font-size:.85rem;color:var(--text-secondary);font-weight:500;margin-bottom:4px}
.dash-stat .s-info .s-value{font-size:1.6rem;font-weight:700;color:var(--text-primary)}
.section-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:8px}
.section-header h3{font-size:1.1rem;font-weight:700;color:var(--text-primary)}
.section-card{background:var(--bg-card);border-radius:12px;box-shadow:var(--card-shadow);border:1px solid var(--border-default);padding:20px;margin-bottom:28px}
.vehicles-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px}
.vehicles-grid .vehicle-card{cursor:pointer;transition:transform .3s,box-shadow .3s}
.vehicles-grid .vehicle-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.12)}
.quick-actions{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;margin-bottom:28px}
.action-card{display:flex;flex-direction:column;align-items:center;gap:10px;padding:24px 16px;background:var(--bg-card);border-radius:12px;box-shadow:var(--card-shadow);border:1px solid var(--border-default);cursor:pointer;transition:all .3s;text-decoration:none;color:var(--text-primary)}
.action-card:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.12);border-color:var(--primary-light)}
.action-card .action-icon{font-size:2rem;width:56px;height:56px;display:flex;align-items:center;justify-content:center;border-radius:14px;background:rgba(58,81,58,.06)}
.action-card .action-label{font-size:.9rem;font-weight:600;text-align:center}
.handovers-table{width:100%;border-collapse:collapse}
.handovers-table th,.handovers-table td{padding:12px 16px;text-align:start;vertical-align:middle;font-size:.875rem}
.handovers-table thead{background:var(--bg-main);font-weight:600;border-bottom:1px solid var(--border-default)}
.handovers-table tbody tr{border-bottom:1px solid var(--border-default);transition:background .3s}
.handovers-table tbody tr:hover{background:rgba(58,81,58,.04)}
.loading-placeholder{display:flex;align-items:center;justify-content:center;padding:48px;color:var(--text-secondary)}
.empty-state{text-align:center;padding:40px 24px;color:var(--text-secondary)}
.empty-state .empty-icon{font-size:2.5rem;margin-bottom:10px;opacity:.5}
@media(max-width:768px){.dashboard-stats{grid-template-columns:repeat(2,1fr)}.vehicles-grid{grid-template-columns:1fr}.quick-actions{grid-template-columns:repeat(2,1fr)}}
@media(max-width:480px){.dashboard-stats{grid-template-columns:repeat(2,1fr);gap:12px}.dash-stat{padding:14px;gap:10px}.quick-actions{grid-template-columns:1fr 1fr}}
</style>

<div class="page-header">
    <h2>لوحة التحكم</h2>
</div>

<!-- Stats -->
<div class="dashboard-stats" id="statsGrid">
    <div class="dash-stat accent-blue">
        <div class="s-icon blue">🚗</div>
        <div class="s-info"><h4>إجمالي المركبات</h4><div class="s-value" id="statTotal">&mdash;</div></div>
    </div>
    <div class="dash-stat accent-green">
        <div class="s-icon green">✅</div>
        <div class="s-info"><h4>تعمل</h4><div class="s-value" id="statOperational">&mdash;</div></div>
    </div>
    <div class="dash-stat accent-warning">
        <div class="s-icon warn">🔧</div>
        <div class="s-info"><h4>صيانة</h4><div class="s-value" id="statMaintenance">&mdash;</div></div>
    </div>
    <div class="dash-stat accent-danger">
        <div class="s-icon red">⛔</div>
        <div class="s-info"><h4>خارج الخدمة</h4><div class="s-value" id="statOutOfService">&mdash;</div></div>
    </div>
    <div class="dash-stat accent-blue">
        <div class="s-icon blue">📋</div>
        <div class="s-info"><h4>مخالفات غير مدفوعة</h4><div class="s-value" id="statViolations">&mdash;</div></div>
    </div>
    <div class="dash-stat accent-gold">
        <div class="s-icon gold">👥</div>
        <div class="s-info"><h4>المستخدمون النشطون</h4><div class="s-value" id="statUsers">&mdash;</div></div>
    </div>
</div>

<!-- Quick Actions -->
<div class="section-header"><h3>إجراءات سريعة</h3></div>
<div class="quick-actions">
    <a class="action-card" href="<?= $publicUrl ?>/dashboard.php?page=vehicles&action=add">
        <div class="action-icon">🚗</div><div class="action-label">إضافة مركبة</div>
    </a>
    <a class="action-card" href="<?= $publicUrl ?>/dashboard.php?page=vehicles">
        <div class="action-icon">🔄</div><div class="action-label">تسليم / استلام</div>
    </a>
    <a class="action-card" href="<?= $publicUrl ?>/dashboard.php?page=vehicles">
        <div class="action-icon">📊</div><div class="action-label">عرض التقارير</div>
    </a>
    <a class="action-card" href="<?= $publicUrl ?>/dashboard.php?page=users">
        <div class="action-icon">👥</div><div class="action-label">إدارة المستخدمين</div>
    </a>
</div>

<!-- Recent Vehicles -->
<div class="section-card">
    <div class="section-header">
        <h3>أحدث المركبات</h3>
        <a href="<?= $publicUrl ?>/dashboard.php?page=vehicles" class="btn btn-outline btn-sm">عرض الكل</a>
    </div>
    <div id="recentVehicles">
        <div class="loading-placeholder"><div class="spinner spinner-sm"></div><span>جارٍ التحميل...</span></div>
    </div>
</div>

<!-- Recent Handovers -->
<div class="section-card" id="handoversSection" style="display:none">
    <div class="section-header"><h3>آخر عمليات التسليم</h3></div>
    <div class="table-wrapper">
        <table class="handovers-table">
            <thead><tr>
                <th>رمز المركبة</th><th>من موظف</th><th>إلى موظف</th><th>التاريخ</th><th>النوع</th>
            </tr></thead>
            <tbody id="handoversBody"></tbody>
        </table>
    </div>
</div>

<?php
// Page-specific script (will be output in footer.php)
$pageScripts = <<<'SCRIPT'
<script>
(function(){
    'use strict';
    const $ = id => document.getElementById(id);
    const STATUS_MAP = {
        operational:{ar:'تعمل',badge:'badge-success'},
        maintenance:{ar:'صيانة',badge:'badge-warning'},
        out_of_service:{ar:'خارج الخدمة',badge:'badge-danger'},
        default:{ar:'غير محدد',badge:'badge-info'}
    };
    function statusBadge(s){const m=STATUS_MAP[s]||STATUS_MAP.default;return '<span class="badge '+m.badge+'">'+m.ar+'</span>';}

    async function loadStats(){
        try{
            const res=await API.get('/dashboard/stats');
            const d=res.data||res;
            if(d.total!==undefined) $('statTotal').textContent=d.total;
            if(d.operational!==undefined) $('statOperational').textContent=d.operational;
            if(d.maintenance!==undefined) $('statMaintenance').textContent=d.maintenance;
            if(d.out_of_service!==undefined) $('statOutOfService').textContent=d.out_of_service;
            if(d.unpaid_violations!==undefined) $('statViolations').textContent=d.unpaid_violations;
            if(d.active_users!==undefined) $('statUsers').textContent=d.active_users;
        }catch(e){}
    }

    async function loadRecentVehicles(){
        const c=$('recentVehicles');
        try{
            const res=await API.get('/vehicles');
            const vehicles=Array.isArray(res.data)?res.data:(Array.isArray(res)?res:[]);
            const list=vehicles.slice(0,8);
            if(!list.length){c.innerHTML='<div class="empty-state"><div class="empty-icon">🚗</div><p>لا توجد مركبات بعد</p></div>';return;}
            let h='<div class="vehicles-grid">';
            list.forEach(v=>{
                const code=UI._escapeHtml(v.vehicle_code||'—');
                const type=UI._escapeHtml(v.type||'—');
                const driver=UI._escapeHtml(v.driver_name||'—');
                const dept=UI._escapeHtml(v.department_name_ar||'—');
                const year=v.manufacture_year||'—';
                h+='<div class="card vehicle-card" onclick="location.href=\'dashboard.php?page=vehicles\'">';
                h+='<div class="vehicle-card-header"><strong>'+code+'</strong>'+statusBadge(v.status)+'</div>';
                h+='<div class="vehicle-card-body">';
                h+='<div class="detail-row"><span class="label">النوع</span><span class="value">'+type+'</span></div>';
                h+='<div class="detail-row"><span class="label">السائق</span><span class="value">'+driver+'</span></div>';
                h+='<div class="detail-row"><span class="label">الإدارة</span><span class="value">'+dept+'</span></div>';
                h+='<div class="detail-row"><span class="label">السنة</span><span class="value">'+year+'</span></div>';
                h+='</div></div>';
            });
            h+='</div>';
            c.innerHTML=h;
        }catch(e){c.innerHTML='<div class="empty-state"><div class="empty-icon">⚠️</div><p>تعذر تحميل المركبات</p></div>';}
    }

    document.addEventListener('DOMContentLoaded',async()=>{
        await new Promise(r=>setTimeout(r,150));
        loadStats();
        loadRecentVehicles();
        setInterval(loadStats,60000);
    });
})();
</script>
SCRIPT;
?>

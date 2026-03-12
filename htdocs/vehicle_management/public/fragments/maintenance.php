<?php
/**
 * Maintenance Fragment — Vehicle Maintenance Records
 */
?>
<div class="page-header"><h2>الصيانة</h2></div>
<div class="section-card">
    <div class="section-header">
        <h3>سجلات الصيانة</h3>
        <button class="btn btn-primary btn-sm" id="btnAddMaint">➕ إضافة سجل</button>
    </div>
    <div id="maintContent">
        <div class="empty-state"><div class="empty-icon">🔧</div><p>لا توجد سجلات صيانة بعد</p></div>
    </div>
</div>
<?php $pageScripts = '<script>/* Maintenance fragment - extends Vehicle_Maintenance.html functionality */</script>'; ?>

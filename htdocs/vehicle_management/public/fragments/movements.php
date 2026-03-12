<?php
/**
 * Movements Fragment — Vehicle Movement Tracking
 */
?>
<div class="page-header"><h2>حركات المركبات</h2></div>
<div class="section-card">
    <div class="section-header">
        <h3>سجل الحركات</h3>
        <button class="btn btn-primary btn-sm" id="btnAddMovement">➕ إضافة حركة</button>
    </div>
    <div id="movementsContent">
        <div class="empty-state"><div class="empty-icon">🔄</div><p>لا توجد حركات مسجلة بعد</p></div>
    </div>
</div>
<?php $pageScripts = '<script>/* Movements fragment - extends vehicle_movements.html */</script>'; ?>

<?php
/**
 * Violations Fragment — Vehicle Violations List
 */
?>
<div class="page-header"><h2>المخالفات</h2></div>
<div class="section-card">
    <div class="section-header">
        <h3>قائمة المخالفات</h3>
        <button class="btn btn-primary btn-sm" id="btnAddViolation">➕ إضافة مخالفة</button>
    </div>
    <div id="violationsContent">
        <div class="empty-state"><div class="empty-icon">⚠️</div><p>لا توجد مخالفات مسجلة بعد</p></div>
    </div>
</div>
<?php $pageScripts = '<script>/* Violations fragment - extends violations_list.html */</script>'; ?>

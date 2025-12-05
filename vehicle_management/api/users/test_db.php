<?php
// test_db.php - اختبار الاتصال بقاعدة البيانات
// مسار الاتصال الرئيسي
require_once __DIR__ . '/../../api/config/db.php';

// التحقق من الاتصال
if ($conn->connect_error) {
    die("❌ فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
} else {
    echo "✅ تم الاتصال بقاعدة البيانات بنجاح. اسم قاعدة البيانات الحالية: " . $conn->query("SELECT DATABASE()")->fetch_row()[0];
}
?>

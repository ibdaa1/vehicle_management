<?php
// تضمين ملف الاتصال بقاعدة البيانات
require_once 'db.php';

// اختبار الاتصال عن طريق استعلام بسيط
$sql = "SELECT DATABASE() AS db_name";
$result = $conn->query($sql);

if ($result) {
    $row = $result->fetch_assoc();
    echo "✅ تم الاتصال بقاعدة البيانات بنجاح. اسم قاعدة البيانات الحالية: " . $row['db_name'];
} else {
    echo "❌ فشل تنفيذ الاستعلام: " . $conn->error;
}

// غلق الاتصال بعد الاختبار (اختياري)
$conn->close();
?>

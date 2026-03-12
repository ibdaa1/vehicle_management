<?php
declare(strict_types=1);

// استدعاء ملف الاتصال
require_once __DIR__ . '/config/db.php';

// التحقق من الاتصال
if ($conn->connect_errno) {
    echo "❌ فشل الاتصال بقاعدة البيانات<br>";
    echo "Error: " . $conn->connect_error;
    exit;
}

echo "✅ الاتصال بقاعدة البيانات ناجح<br><br>";

// معلومات السيرفر
echo "MySQL Version: " . $conn->server_info . "<br>";
echo "Host Info: " . $conn->host_info . "<br>";
echo "Charset: " . $conn->character_set_name() . "<br><br>";

// اختبار استعلام
$query = "SELECT NOW() AS server_time";
$result = $conn->query($query);

if ($result) {
    $row = $result->fetch_assoc();
    echo "🕒 وقت السيرفر: " . $row['server_time'];
} else {
    echo "❌ فشل تنفيذ الاستعلام";
}

// إغلاق الاتصال
$conn->close();
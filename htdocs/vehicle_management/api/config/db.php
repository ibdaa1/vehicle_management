<?php
// db.php - ملف موحد للاتصال بقاعدة البيانات

// ⚠️ يتم حذف دالة connectDB() لتجنب التعارض وتكرار تعريف الاتصال.

// ⚠️ قم بتحديث هذه المتغيرات بمعلومات قاعدة البيانات الخاصة بك
$servername = "sql311.infinityfree.com"; 
$username   = "if0_39652926";
$password   = "Mohd28332"; // 🚨 يفضل وضع بيانات الاعتماد في ملف .env أو خارج مجلد الويب
$dbname     = "if0_39652926_vehicle_management";

// --- إنشاء اتصال (يتم تخزينه مباشرة في المتغير $conn) ---
$conn = new mysqli($servername, $username, $password, $dbname);

// --- التحقق من الاتصال ---
if ($conn->connect_error) {
    // تسجيل الخطأ في السجل الخاص بالخادم (مهم)
    error_log("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
    
    // إعداد رسالة الخطأ للمستخدم
    $errorMessage = ['status' => 'error', 'message' => 'فشل الاتصال بقاعدة البيانات.'];

    // إذا كان الطلب AJAX أو API (لتجنب إخراج HTML عشوائي)
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        // تأكد من أننا نرسل JSON (فقط إذا لم يتم إرسال أي output قبله)
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        die(json_encode($errorMessage, JSON_UNESCAPED_UNICODE));
    } else {
        // إخراج خطأ بسيط لمتصفح الويب العادي
        die("فشل الاتصال بقاعدة البيانات. يرجى المحاولة لاحقاً.");
    }
}

// --- تعيين ترميز الأحرف ---
// تعيين utf8mb4 يدعم Emoji وأحرف خاصة أكثر من utf8
$conn->set_charset("utf8mb4");

// 💡 المتغير $conn جاهز الآن للاستخدام في أي ملف يقوم بتضمين 'db.php'
?>
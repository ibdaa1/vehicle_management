<?php
// vehicle_management/api/config/timezone.php
// يقوم بتعيين المنطقة الزمنية للتطبيق ويجهز كائن DateTime بتوقيت الإمارات (Asia/Dubai)

// 1. تعيين المنطقة الزمنية الافتراضية إلى آسيا/دبي
// هذا يضمن أن جميع وظائف التاريخ والوقت في PHP تستخدم هذا التوقيت
date_default_timezone_set('Asia/Dubai');

// 2. إنشاء كائن DateTime لكافة الملفات التي تتضمن هذا الملف
// يتم تعريف $nowDt كمتغير عام لسهولة الوصول إليه في نطاق الملفات التي تتضمنه
global $nowDt;

try {
    // إنشاء كائن DateTime الآن (Now) باستخدام المنطقة الزمنية المحددة
    $nowDt = new DateTime('now', new DateTimeZone('Asia/Dubai'));

} catch (Exception $e) {
    // في حالة حدوث خطأ (نادراً ما يحدث إذا كان إعداد PHP صحيحاً)
    error_log("Timezone Error: Could not create DateTime object for Asia/Dubai: " . $e->getMessage());
    // يمكنك هنا تعيين قيمة احتياطية أو إيقاف التنفيذ حسب الضرورة
}

/*
 * ملاحظة الاستخدام:
 *
 * عند تضمين هذا الملف في أي ملف PHP آخر (مثل add_vehicle_movements.php):
 * require_once __DIR__ . '/../config/timezone.php';
 *
 * يمكنك الوصول إلى الوقت والتاريخ الحالي لتوقيت الإمارات عبر:
 * global $nowDt;
 * $current_datetime_uae = $nowDt->format('Y-m-d H:i:s');
 */
?>
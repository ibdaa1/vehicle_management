<?php
/**
 * bootstrap.php
 * موقع: vehicle_management/api/config/bootstrap.php
 *
 * يقوم بضبط المنطقة الزمنية الى Asia/Dubai، وإعدادات عرض/تسجيل الأخطاء
 * وإعدادات ترميز النصوص العامة. استدعي هذا الملف في بداية سكربتات الـ API.
 */

// timezone
date_default_timezone_set('Asia/Dubai');

// error reporting: سجّل كل الأخطاء لكن تجاهل تحذيرات deprecated في بيئة الإنتاج
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// لا تعرض الأخطاء للمستخدم في بيئة الإنتاج
ini_set('display_errors', '0');

// فعّل تسجيل الأخطاء في سجل الخادم
ini_set('log_errors', '1');

// تأكد من أن الترميز الافتراضي UTF-8
ini_set('default_charset', 'UTF-8');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

// اختياري: تعريف ثابت يشير إن البيئة إنتاجية (يمكن تغييره حسب حاجتك)
if (!defined('APP_ENV')) {
    define('APP_ENV', 'production'); // أو 'development'
}

// إذا أردت أثناء التطوير رؤية تحذيرات deprecated مؤقتًا ضع APP_ENV = 'development'
// يمكنك إضافة في بداية bootstrap.php:
// if (APP_ENV === 'development') {
//     error_reporting(E_ALL);
//     ini_set('display_errors', '1');
// }

?>
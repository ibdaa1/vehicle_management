<?php
// vehicle_management/api/config/mail.php
// ضع هنا بيانات حساب البريد على الإستضافة (لا ترسل كلمة المرور لأي شخص)
return [
    'host' => 'mail.hcsfcs.top',
    'username' => 'hcsfcsto@hcsfcs.top',
    'password' => 'Mohd28332@', // <-- ضع كلمة المرور هنا
    'port' => 465,             // جرب 465 (ssl) أو 587 (tls) إن لم ينجح
    'secure' => 'ssl',         // 'ssl' أو 'tls'
    'from_email' => 'hcsfcsto@hcsfcs.top',
    'from_name' => 'Vehicle Management'
];
?>
<?php
// vehicle_management/public/index.php
// Simple landing page that respects PHP session.
// If user is not authenticated -> redirect to login.html
session_start();

// If your login sets a different session key adjust this check
if (empty($_SESSION['user_id'])) {
    header('Location: /vehicle_management/public/login.html');
    exit;
}

$username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') : 'المستخدم';
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>لوحة المستخدم</title>
  <style>
    body{font-family: Arial, "Tajawal",sans-serif;background:#f6f7f9;color:#222;padding:24px}
    .card{max-width:920px;margin:24px auto;background:#fff;padding:20px;border-radius:10px;box-shadow:0 6px 18px rgba(0,0,0,0.06)}
    .row{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
    .welcome{font-size:1.1rem;font-weight:700;color:#2b6a00}
    .actions{display:flex;gap:12px}
    .btn{padding:10px 14px;border-radius:8px;border:0;background:#2b6a00;color:#fff;cursor:pointer;text-decoration:none}
    .btn.secondary{background:#eee;color:#333;border:1px solid #ddd}
    .icon{width:56px;height:56px;border-radius:10px;background:linear-gradient(135deg,#556b2f,#4a5d23);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:18px}
    .note{color:#666;margin-top:12px}
  </style>
</head>
<body>
  <div class="card" role="main">
    <div class="row">
      <div style="display:flex;gap:12px;align-items:center">
        <div class="icon">ملف</div>
        <div>
          <div class="welcome">مرحباً، <?php echo $username; ?></div>
          <div class="note">اضغط على أيقونة "ملفي" لتعديل بياناتك أو تغيير كلمة المرور.</div>
        </div>
      </div>
      <div class="actions">
        <a class="btn" href="/vehicle_management/public/profile.php" title="افتح ملفي">ملفي للتعديل</a>
        <a class="btn secondary" href="/vehicle_management/public/login.html" title="رجوع لتسجيل الدخول">صفحة تسجيل الدخول</a>
        <a class="btn secondary" href="/vehicle_management/public/logout.php" title="خروج">تسجيل خروج</a>
      </div>
    </div>
  </div>
</body>
</html>
<?php
// vehicle_management/api/users/activate_user.php
// This endpoint is intended to be visited via browser link from activation email.
// It will activate the user if the token is valid and not expired.

require_once __DIR__ . '/../../api/config/db.php';

header('Content-Type: text/html; charset=utf-8');

$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (!$token) {
    http_response_code(400);
    echo "<h3>Invalid activation token.</h3>";
    exit;
}

try {
    // Verify activation record
    $stmt = $conn->prepare("SELECT id, user_id, expires_at, used FROM user_activations WHERE token = ? LIMIT 1");
    if ($stmt === false) throw new Exception('Prepare failed: ' . $conn->error);
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $res = $stmt->get_result();
    $act = $res->fetch_assoc();
    $stmt->close();

    if (!$act) {
        echo "<h3>Invalid or unknown activation token.</h3>";
        exit;
    }
    if ((int)$act['used'] === 1) {
        echo "<h3>This activation link has already been used.</h3>";
        exit;
    }

    // check expiry (Asia/Dubai)
    $tz = new DateTimeZone('Asia/Dubai');
    $now = new DateTime('now', $tz);
    $expires = new DateTime($act['expires_at'], $tz);
    if ($now > $expires) {
        echo "<h3>Activation link has expired.</h3>";
        exit;
    }

    // Activate user (set is_active = 1)
    $uid = (int)$act['user_id'];
    $up = $conn->prepare("UPDATE users SET is_active = 1, updated_at = NOW() WHERE id = ? LIMIT 1");
    if ($up === false) throw new Exception('Prepare failed (activate user): ' . $conn->error);
    $up->bind_param('i', $uid);
    if (!$up->execute()) throw new Exception('Execute failed (activate user): ' . $up->error);
    $up->close();

    // mark activation used
    $usedAt = $now->format('Y-m-d H:i:s');
    $mark = $conn->prepare("UPDATE user_activations SET used = 1, used_at = ? WHERE id = ? LIMIT 1");
    if ($mark === false) throw new Exception('Prepare failed (mark used): ' . $conn->error);
    $mark->bind_param('si', $usedAt, $act['id']);
    $mark->execute();
    $mark->close();

    // show success page or redirect to login
    // يمكنك تغيير الرابط للقيام بإعادة توجيه إلى صفحة واجهة المستخدم
    echo "<h2>Account activated</h2><p>Your account has been activated. You can now <a href='/vehicle_management/public/login.html'>login</a>.</p>";
    exit;

} catch (Exception $e) {
    error_log("activate_user error: " . $e->getMessage());
    http_response_code(500);
    echo "<h3>Server error.</h3>";
    exit;
}
?>
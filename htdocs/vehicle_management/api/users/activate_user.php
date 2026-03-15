<?php
// vehicle_management/api/users/activate_user.php
// Activates a user account via token from email link.

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (empty($token)) {
    echo '<h2>Invalid activation link.</h2>';
    exit;
}

try {
    // Check if table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'user_activations'");
    if ($tableCheck->num_rows === 0) {
        echo '<h2>Activation system not configured.</h2>';
        exit;
    }

    $stmt = $conn->prepare("SELECT id, user_id, expires_at, used FROM user_activations WHERE token = ? LIMIT 1");
    if ($stmt === false) throw new Exception('Prepare failed: ' . $conn->error);
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $res = $stmt->get_result();
    $activation = $res->fetch_assoc();
    $stmt->close();

    if (!$activation) {
        echo '<h2>Invalid or expired activation link.</h2>';
        exit;
    }

    if ($activation['used']) {
        echo '<h2>Account already activated. You can login.</h2>';
        exit;
    }

    $tz = new DateTimeZone('Asia/Dubai');
    $now = new DateTime('now', $tz);
    $expires = new DateTime($activation['expires_at'], $tz);
    if ($now > $expires) {
        echo '<h2>Activation link has expired.</h2>';
        exit;
    }

    // Activate the user
    $userId = (int)$activation['user_id'];
    $upd = $conn->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
    if ($upd === false) throw new Exception('Prepare failed: ' . $conn->error);
    $upd->bind_param('i', $userId);
    $upd->execute();
    $upd->close();

    // Mark token as used
    $mark = $conn->prepare("UPDATE user_activations SET used = 1 WHERE id = ?");
    if ($mark === false) throw new Exception('Prepare failed: ' . $conn->error);
    $activationId = (int)$activation['id'];
    $mark->bind_param('i', $activationId);
    $mark->execute();
    $mark->close();

    echo '<h2>Account activated successfully! You can now login.</h2>';
    echo '<p><a href="/vehicle_management/public/login.html">Go to Login</a></p>';

} catch (Exception $e) {
    error_log("activate_user error: " . $e->getMessage());
    echo '<h2>An error occurred during activation.</h2>';
}
?>

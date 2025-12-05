<?php
// vehicle_management/api/users/update_user_session.php
// Updates the logged-in user's profile and optional password.
// Accepts form-data or application/x-www-form-urlencoded.
// Fields supported:
// - username, email, phone, emp_id, preferred_language
// - department_id, section_id, division_id (تمت إضافتها وتحديثها)
// - current_password, new_password (to change password)
// Returns JSON.

header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$uid = (int)$_SESSION['user_id'];

// Accept both JSON and form-data; but we expect form-data from profile.html
$data = $_POST;
if (empty($data)) {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) $data = $json;
}

// collect fields (Department, Section, Division fields are correctly collected and cast to int/null)
// تم إزالة full_name لأنه غير موجود في عمود جدول users بناءً على DESCRIBE users.
$username = isset($data['username']) ? trim($data['username']) : null;
$email = isset($data['email']) ? trim($data['email']) : null;
$phone = isset($data['phone']) ? trim($data['phone']) : null;
$emp_id = isset($data['emp_id']) ? trim($data['emp_id']) : null;
$preferred_language = isset($data['preferred_language']) && in_array($data['preferred_language'], ['ar','en']) ? $data['preferred_language'] : null;

// الحقول الجديدة التي تم إضافتها للتحديث
$department_id = isset($data['department_id']) && $data['department_id'] !== '' ? (int)$data['department_id'] : null;
$section_id = isset($data['section_id']) && $data['section_id'] !== '' ? (int)$data['section_id'] : null;
$division_id = isset($data['division_id']) && $data['division_id'] !== '' ? (int)$data['division_id'] : null;

$current_password = isset($data['current_password']) ? $data['current_password'] : '';
$new_password = isset($data['new_password']) ? $data['new_password'] : '';

$errors = [];

// basic validations
if ($username !== null && $username === '') $errors[] = 'Username is required.';
if ($email !== null && $email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email.';

// uniqueness check for username/email/emp_id
if ($username || $email || $emp_id) {
    $check = $conn->prepare("SELECT id, username, email, emp_id FROM users WHERE (username = ? OR email = ? OR emp_id = ?) AND id <> ? LIMIT 1");
    if ($check) {
        $u_val = $username ?? '';
        $e_val = $email ?? '';
        $emp_val = $emp_id ?? '';
        $check->bind_param('sssi', $u_val, $e_val, $emp_val, $uid);
        $check->execute();
        $cres = $check->get_result();
        if ($conf = $cres->fetch_assoc()) {
            if (!empty($u_val) && $conf['username'] === $u_val) $errors[] = 'Username already in use.';
            if (!empty($e_val) && $conf['email'] === $e_val) $errors[] = 'Email already in use.';
            if (!empty($emp_val) && $conf['emp_id'] === $emp_val) $errors[] = 'Emp ID already in use.';
        }
        $check->close();
    }
}

// handle password change: require current_password verification
$changePassword = false;
if ($new_password !== '') {
    if (strlen($new_password) < 6) $errors[] = 'New password must be at least 6 characters.';
    // verify current
    $st = $conn->prepare("SELECT password_hash FROM users WHERE id = ? LIMIT 1");
    $st->bind_param('i', $uid);
    $st->execute();
    $rr = $st->get_result();
    $row = $rr->fetch_assoc();
    $st->close();
    if (!$row || !password_verify($current_password, $row['password_hash'])) {
        $errors[] = 'Current password is incorrect.';
    } else {
        $changePassword = true;
        $newHash = password_hash($new_password, PASSWORD_DEFAULT);
    }
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// build update statement dynamically
$fields = [];
$params = [];
$types = '';

// خريطة لربط الحقول بالقيم المدخلة (تم إزالة full_name من هنا)
$map = [
    'username' => $username,
    'email' => $email,
    'phone' => $phone,
    'emp_id' => $emp_id,
    'preferred_language' => $preferred_language,
    'department_id' => $department_id,
    'section_id' => $section_id,
    'division_id' => $division_id
];

foreach ($map as $col => $val) {
    if ($val !== null) {
        $fields[] = "`$col` = ?";
        // determine type
        // تحديد النوع "i" (integer) للحقول الثلاثة
        if (in_array($col, ['department_id','section_id','division_id'])) {
            $types .= 'i';
            $params[] = $val === '' ? null : (int)$val;
        } else {
            $types .= 's';
            $params[] = $val;
        }
    }
}

if (!empty($fields)) {
    $sql = "UPDATE users SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log('update_user_session prepare: ' . $conn->error);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error (prepare).']);
        exit;
    }
    // bind types + params + id
    $typesWithId = $types . 'i';
    $params[] = $uid;
    $bind_names[] = $typesWithId;
    for ($i=0;$i<count($params);$i++){
        $bind_names[] = & $params[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
    if (!$stmt->execute()) {
        error_log('update_user_session execute: ' . $stmt->error);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error (execute).']);
        exit;
    }
    $stmt->close();
}

// update password if needed
if ($changePassword) {
    $p = $conn->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ? LIMIT 1");
    if ($p) {
        $p->bind_param('si', $newHash, $uid);
        $p->execute();
        $p->close();
    }
}

// refresh session username/language if changed
if ($username) $_SESSION['username'] = $username;
if ($preferred_language) $_SESSION['preferred_language'] = $preferred_language;

// return updated user (تم تعديل استعلام SELECT لإزالة full_name الذي تسبب في الخطأ)
$s = $conn->prepare("SELECT id, emp_id, username, email, phone, preferred_language, department_id, section_id, division_id, is_active FROM users WHERE id = ? LIMIT 1");
$s->bind_param('i', $uid);
$s->execute();
$res = $s->get_result();
$user = $res->fetch_assoc();
$s->close();

echo json_encode(['success' => true, 'message' => 'تم التحديث', 'user' => $user]);
exit;
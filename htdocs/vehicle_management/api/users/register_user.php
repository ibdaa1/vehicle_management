<?php
// vehicle_management/api/users/register_user.php
// يسجّل مستخدماً، ينشئ رمز تفعيل، ويطلب من send_report_mail.php إرسال رسالة التفعيل عبر POST.
// لا يعتمد على config.php خارجي؛ يستخدم اتصال قاعدة البيانات في api/config/db.php

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

// اتصال قاعدة البيانات (تأكد أن الملف موجود في هذا المسار)
require_once __DIR__ . '/../config/db.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) $data = $_POST ?? [];

$required = ['emp_id', 'username', 'email', 'password', 'role_id'];
$missing = [];
foreach ($required as $r) {
    if (!isset($data[$r]) || trim((string)$data[$r]) === '') $missing[] = $r;
}
if (!empty($missing)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing fields: ' . implode(',', $missing)]);
    exit;
}

// تنظيف القيم
$emp_id = trim($data['emp_id']);
$username = trim($data['username']);
$email = trim($data['email']);
$password = $data['password'];
$role_id = (int)$data['role_id'];
$department_id = isset($data['department_id']) && $data['department_id'] !== '' ? (int)$data['department_id'] : null;
$section_id    = isset($data['section_id']) && $data['section_id'] !== '' ? (int)$data['section_id'] : null;
$division_id   = isset($data['division_id']) && $data['division_id'] !== '' ? (int)$data['division_id'] : null;
$sector_id     = isset($data['sector_id']) && $data['sector_id'] !== '' ? (int)$data['sector_id'] : null;
$preferred_language = (isset($data['preferred_language']) && in_array($data['preferred_language'], ['en','ar'])) ? $data['preferred_language'] : 'en';
$phone = isset($data['phone']) && $data['phone'] !== '' ? trim($data['phone']) : null;

// validations
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email.']);
    exit;
}
if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
    exit;
}

try {
    // ensure sectors infrastructure exists (auto-migration like user_activations)
    $conn->query("CREATE TABLE IF NOT EXISTS `sectors` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `sector_code` VARCHAR(50) NOT NULL,
        `name` VARCHAR(255) NOT NULL,
        `name_en` VARCHAR(255) DEFAULT NULL,
        `description` MEDIUMTEXT DEFAULT NULL,
        `manager_user_id` INT(11) DEFAULT NULL,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_sector_code` (`sector_code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ensure sector_id column exists on users table
    $colCheck = $conn->query("SHOW COLUMNS FROM `users` LIKE 'sector_id'");
    if ($colCheck && $colCheck->num_rows === 0) {
        $conn->query("ALTER TABLE `users` ADD COLUMN `sector_id` INT(11) DEFAULT NULL AFTER `role_id`");
    }

    // helper to check existence of optional references
    function existsRef($conn, $table, $pkName, $id) {
        if ($id === null) return false;
        $sql = "SELECT 1 FROM `{$table}` WHERE `{$pkName}` = ? LIMIT 1";
        $st = $conn->prepare($sql);
        if ($st === false) return false; // table may not exist — treat as invalid
        $st->bind_param('i', $id);
        $st->execute();
        $res = $st->get_result();
        $exists = (bool)$res->fetch_assoc();
        $st->close();
        return $exists;
    }

    $invalid = [];
    if ($sector_id !== null    && !existsRef($conn, 'sectors',     'id',            $sector_id))    $invalid[] = "sector_id:{$sector_id}";
    if ($department_id !== null && !existsRef($conn, 'Departments', 'department_id', $department_id)) $invalid[] = "department_id:{$department_id}";
    if ($section_id !== null    && !existsRef($conn, 'Sections',    'section_id',    $section_id))    $invalid[] = "section_id:{$section_id}";
    if ($division_id !== null   && !existsRef($conn, 'Divisions',   'division_id',   $division_id))   $invalid[] = "division_id:{$division_id}";

    if (!empty($invalid)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid reference ids.', 'invalid' => $invalid]);
        exit;
    }

    // uniqueness check
    $check = $conn->prepare("SELECT emp_id, username, email FROM users WHERE emp_id = ? OR username = ? OR email = ? LIMIT 1");
    if ($check === false) throw new Exception('Prepare failed (check): ' . $conn->error);
    $check->bind_param('sss', $emp_id, $username, $email);
    $check->execute();
    $res = $check->get_result();
    if ($row = $res->fetch_assoc()) {
        $conflict = [];
        if ($row['emp_id'] === $emp_id) $conflict[] = 'emp_id';
        if ($row['username'] === $username) $conflict[] = 'username';
        if ($row['email'] === $email) $conflict[] = 'email';
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Conflict: ' . implode(',', $conflict)]);
        exit;
    }
    $check->close();

    // insert user (dynamic placeholders for optional fields)
    $fields = ['emp_id','username','email','password_hash','preferred_language','phone','role_id','profile_image','is_active','created_at','updated_at'];
    $placeholders = ['?','?','?','?','?','?','?','NULL','0','NOW()','NOW()'];
    $params = [];

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $params[] = $emp_id;
    $params[] = $username;
    $params[] = $email;
    $params[] = $passwordHash;
    $params[] = $preferred_language;
    $params[] = $phone;
    $params[] = $role_id;

    if ($sector_id !== null)    { $fields[] = 'sector_id';    $placeholders[] = '?'; $params[] = $sector_id; }    else { $fields[] = 'sector_id';    $placeholders[] = 'NULL'; }
    if ($department_id !== null) { $fields[] = 'department_id'; $placeholders[] = '?'; $params[] = $department_id; } else { $fields[] = 'department_id'; $placeholders[] = 'NULL'; }
    if ($section_id !== null)    { $fields[] = 'section_id';    $placeholders[] = '?'; $params[] = $section_id; }    else { $fields[] = 'section_id';    $placeholders[] = 'NULL'; }
    if ($division_id !== null)   { $fields[] = 'division_id';   $placeholders[] = '?'; $params[] = $division_id; }   else { $fields[] = 'division_id';   $placeholders[] = 'NULL'; }

    $sql = "INSERT INTO users (" . implode(',', $fields) . ") VALUES (" . implode(',', $placeholders) . ")";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) throw new Exception('Prepare failed (insert): ' . $conn->error);

    $types = str_repeat('s', 6) . 'i';
    $optionalCount = 0;
    if ($sector_id !== null) $optionalCount++;
    if ($department_id !== null) $optionalCount++;
    if ($section_id !== null) $optionalCount++;
    if ($division_id !== null) $optionalCount++;
    if ($optionalCount > 0) $types .= str_repeat('i', $optionalCount);

    if (strlen($types) !== count($params)) {
        throw new Exception('Internal error: types length does not match params count ('.strlen($types).' != '.count($params).')');
    }

    $bind_params = [];
    $bind_params[] = & $types;
    for ($i = 0; $i < count($params); $i++) $bind_params[] = & $params[$i];
    call_user_func_array([$stmt, 'bind_param'], $bind_params);

    if (!$stmt->execute()) {
        throw new Exception('Execute failed (user insert): ' . $stmt->error);
    }
    $newUserId = (int)$conn->insert_id;
    $stmt->close();

    // create activation token (gracefully handle missing table)
    $activationLink = '';
    $mailSent = false;
    $activationError = '';
    try {
        // ensure user_activations table exists
        $conn->query("CREATE TABLE IF NOT EXISTS `user_activations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `token` VARCHAR(255) NOT NULL,
            `created_at` DATETIME NOT NULL,
            `expires_at` DATETIME NOT NULL,
            `used` TINYINT(1) NOT NULL DEFAULT 0,
            UNIQUE KEY `uk_token` (`token`),
            KEY `idx_user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $token = bin2hex(random_bytes(32));
        $tz = new DateTimeZone('Asia/Dubai');
        $now = new DateTime('now', $tz);
        $created_at = $now->format('Y-m-d H:i:s');
        $expires_at = (clone $now)->modify('+2 days')->format('Y-m-d H:i:s');

        $ins = $conn->prepare("INSERT INTO user_activations (user_id, token, created_at, expires_at, used) VALUES (?, ?, ?, ?, 0)");
        if ($ins === false) throw new Exception('Prepare failed (activation insert): ' . $conn->error);
        $ins->bind_param('isss', $newUserId, $token, $created_at, $expires_at);
        if (!$ins->execute()) {
            throw new Exception('Execute failed (activation insert): ' . $ins->error);
        }
        $ins->close();

        // build base URL dynamically
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = $scheme . '://' . $host;

        $activationLink = rtrim($baseUrl, '/') . "/vehicle_management/api/users/activate_user.php?token={$token}";

        // --- Call send_report_mail.php endpoint via internal HTTP POST (cURL) ---
        $sendEndpoint = $baseUrl . '/vehicle_management/api/users/send_report_mail.php';
        $mailSubject = ($preferred_language === 'ar') ? "تفعيل حسابك - نظام متابعة السيارات" : "Activate your account - Vehicle Management";
        if ($preferred_language === 'ar') {
            $mailBody = "مرحباً {$username},\n\nشكراً لتسجيلك في نظام متابعة السيارات.\nالرجاء الضغط على الرابط لتفعيل حسابك:\n{$activationLink}\n\nهذا الرابط صالح حتى {$expires_at} (Asia/Dubai).\n\nإذا لم تقم بطلب هذا الحساب، تجاهل هذا البريد.";
        } else {
            $mailBody = "Hello {$username},\n\nThanks for registering at Vehicle Management.\nPlease click the link below to activate your account:\n{$activationLink}\n\nThis link is valid until {$expires_at} (Asia/Dubai).\n\nIf you did not request this, please ignore this email.";
        }

        // prepare POST fields
        $postFields = http_build_query([
            'to' => $email,
            'subject' => $mailSubject,
            'body' => $mailBody
        ]);

        // cURL request to local endpoint
        $ch = curl_init($sendEndpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $curlResp = curl_exec($ch);
        $curlErr = curl_error($ch);
        $curlCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlResp !== false && $curlCode >= 200 && $curlCode < 300) {
            $decoded = json_decode($curlResp, true);
            if (is_array($decoded) && isset($decoded['success']) && $decoded['success'] === true) {
                $mailSent = true;
            } else {
                error_log("send_report_mail response: " . $curlResp);
                if ($decoded && isset($decoded['message'])) $activationError = $decoded['message'];
            }
        } else {
            error_log("cURL to send_report_mail failed: code={$curlCode}, err={$curlErr}, resp={$curlResp}");
            $activationError = $curlErr;
        }
    } catch (Exception $actEx) {
        error_log("Activation step failed for user {$newUserId}: " . $actEx->getMessage());
        $activationError = $actEx->getMessage();
    }

    if ($mailSent) {
        echo json_encode(['success' => true, 'message' => 'User registered. Activation email sent. Please check your inbox.', 'user_id' => $newUserId]);
        exit;
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'User registered successfully.',
            'user_id' => $newUserId,
            'activation_link' => $activationLink ?: null
        ]);
        exit;
    }

} catch (Exception $e) {
    error_log("register_user error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.', 'debug' => $e->getMessage()]);
    exit;
}
?>
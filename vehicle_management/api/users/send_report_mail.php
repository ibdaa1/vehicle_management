<?php
// vehicle_management/api/users/send_report_mail.php
// Improved: supports form or JSON POST, sends plain-text and HTML parts, logs attempts, returns JSON.

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit;
}

// read input (support JSON and form)
$ctype = $_SERVER['CONTENT_TYPE'] ?? '';
$raw = file_get_contents('php://input');
if (stripos($ctype, 'application/json') !== false) {
    $data = json_decode($raw, true) ?: [];
} else {
    parse_str($raw, $parsed);
    $data = !empty($parsed) ? $parsed : $_POST;
}

$to = trim($data['to'] ?? '');
$subject = trim($data['subject'] ?? 'Report from Vehicle Management');
$bodyPlain = trim($data['body'] ?? '');
$bodyHtml = $data['html'] ?? ''; // optional HTML body

if (!$to || !$bodyPlain) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing "to" or "body" parameter.']);
    exit;
}
if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid recipient email.']);
    exit;
}

// From address must be an existing mailbox on the hosting
$from = 'hcsfcsto@hcsfcs.top';

// If no HTML provided, create simple HTML from plain
if (!$bodyHtml) {
    $bodyHtml = nl2br(htmlspecialchars($bodyPlain, ENT_QUOTES, 'UTF-8'));
    $bodyHtml = "<html><body>{$bodyHtml}</body></html>";
}

// Create multipart MIME message
$boundary = md5(uniqid(time(), true));
$headers = [];
$headers[] = "From: {$from}";
$headers[] = "Reply-To: {$from}";
$headers[] = "MIME-Version: 1.0";
$headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";
$headers[] = "X-Mailer: PHP/" . phpversion();
$headers[] = "Message-ID: <" . time() . "." . bin2hex(random_bytes(6)) . "@" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ">";

$multipart = [];
$multipart[] = "--{$boundary}";
$multipart[] = "Content-Type: text/plain; charset=UTF-8";
$multipart[] = "Content-Transfer-Encoding: 8bit";
$multipart[] = "";
$multipart[] = $bodyPlain;
$multipart[] = "";
$multipart[] = "--{$boundary}";
$multipart[] = "Content-Type: text/html; charset=UTF-8";
$multipart[] = "Content-Transfer-Encoding: 8bit";
$multipart[] = "";
$multipart[] = $bodyHtml;
$multipart[] = "";
$multipart[] = "--{$boundary}--";
$multipart[] = "";

$headers_str = implode("\r\n", $headers) . "\r\n";
$message = implode("\r\n", $multipart);

// try send
$mailOk = false;
$mailError = '';
$additionalParams = "-f{$from}"; // helps set envelope sender on some hosts

set_error_handler(function($errno, $errstr) use (&$mailError) {
    $mailError .= "PHP mail warning: {$errstr}; ";
    return true;
});
try {
    $mailOk = @mail($to, $subject, $message, $headers_str, $additionalParams);
} catch (Exception $e) {
    $mailOk = false;
    $mailError .= 'Exception: ' . $e->getMessage();
}
restore_error_handler();

// log the attempt with timestamp
$logLine = date('Y-m-d H:i:s') . " send_report_mail -> to={$to} from={$from} mailOk=" . ($mailOk ? '1' : '0') . " php_warn={$mailError}\n";
error_log($logLine);

if ($mailOk) {
    echo json_encode(['success' => true, 'message' => 'Mail sent']);
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'mail_failed', 'debug' => $mailError]);
    exit;
}
?>
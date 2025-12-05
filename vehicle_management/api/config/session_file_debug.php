<?php
// vehicle_management/api/config/session_file_debug.php
// Temporary debug: inspect session storage for provided session id.
// Usage (from browser console):
// fetch('/vehicle_management/api/config/session_file_debug.php?debug=1', { credentials:'include' }).then(r=>r.json()).then(console.log)
// Or: fetch('/vehicle_management/api/config/session_file_debug.php?sid=THE_SID', { credentials:'include' }).then(r=>r.json()).then(console.log)
// REMOVE this file after debugging.

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// get sid from cookie/header/param
$sid = null;
$sname = session_name();

// cookie
if (!empty($_COOKIE[$sname])) $sid = $_COOKIE[$sname];

// headers
if (!$sid && function_exists('getallheaders')) {
    $h = getallheaders();
    if (!empty($h['X-Session-Id'])) $sid = $h['X-Session-Id'];
    elseif (!empty($h['x-session-id'])) $sid = $h['x-session-id'];
    elseif (!empty($h['Authorization']) && preg_match('#Bearer\s+([A-Za-z0-9,-]+)#', $h['Authorization'], $m)) $sid = $m[1];
}

// query param
if (!$sid && !empty($_GET['sid'])) $sid = trim($_GET['sid']);

$response = [
    'time' => date('c'),
    'session_name' => session_name(),
    'session_save_path' => ini_get('session.save_path'),
    'provided_sid' => $sid ?: null,
];

// if we have a sid, try to inspect file and also start session with that id
if ($sid) {
    // sanitize basic
    if (!preg_match('/^[a-zA-Z0-9,-]{5,128}$/', $sid)) {
        $response['error'] = 'Invalid SID format';
        echo json_encode($response, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
        exit;
    }

    // compute session file path (common case "files" handler)
    $savePath = ini_get('session.save_path') ?: sys_get_temp_dir();
    $sessFile = rtrim($savePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'sess_' . $sid;
    $response['computed_session_file'] = $sessFile;
    $response['session_file_exists'] = file_exists($sessFile);
    if (file_exists($sessFile)) {
        $response['session_file_mtime'] = date('c', filemtime($sessFile));
        $raw = @file_get_contents($sessFile);
        $response['session_file_raw_preview'] = $raw ? substr($raw, 0, 400) : null;
    }

    // Try to load session using this id
    session_write_close(); // close any active session
    session_id($sid);
    // ensure cookie params (do not send headers) - rely on server config
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();

    $response['active_session_id_after_start'] = session_id();
    $response['session_status_after_start'] = session_status();
    $response['session_contents'] = $_SESSION ?? null;

    // optionally, include list of keys
    $response['session_keys'] = is_array($_SESSION) ? array_keys($_SESSION) : null;

    // clean up: do not destroy session; close for safety
    session_write_close();
} else {
    $response['notice'] = 'No session id provided via cookie/header/param';
}

echo json_encode($response, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
exit;
?>
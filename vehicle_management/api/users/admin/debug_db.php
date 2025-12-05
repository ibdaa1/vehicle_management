<?php
// debug_db.php
// تشغيل هذا الملف من: /vehicle_management/api/users/admin/debug_db.php
// سيحاول تضمين ../../config/db.php ويعطي معلومات تشخيصية.
// احذر: لا تترك هذا الملف على البيئة الاصلية بعد الانتهاء من التشخيص.
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

// show errors temporarily (for debugging only)
ini_set('display_errors', 1);
error_reporting(E_ALL);

$wanted = __DIR__ . '/../../config/db.php';
$paths_tested = [];
$exists = file_exists($wanted);
$real = $exists ? realpath($wanted) : null;
$paths_tested[] = $wanted;

$out = [
    'requested_path' => $wanted,
    'file_exists' => $exists,
    'realpath' => $real,
    'cwd' => getcwd(),
    'php_sapi' => PHP_SAPI,
    'include_path' => explode(PATH_SEPARATOR, get_include_path()),
    'open_basedir' => ini_get('open_basedir'),
    'php_version' => PHP_VERSION,
];

if ($exists) {
    // attempt to include and check $conn
    try {
        require_once $wanted;
        $out['include_result'] = 'included';
        // check $conn variable
        if (isset($conn)) {
            $out['conn_defined'] = true;
            // basic mysqli check
            if ($conn instanceof mysqli) {
                $out['conn_type'] = 'mysqli';
                $out['connect_errno'] = $conn->connect_errno;
                $out['connect_error'] = $conn->connect_error;
                // optionally check ping
                $ping = @$conn->ping();
                $out['connect_ping'] = $ping ? 'ok' : 'failed';
            } else {
                $out['conn_type'] = gettype($conn);
            }
        } else {
            $out['conn_defined'] = false;
        }
    } catch (Throwable $e) {
        $out['include_result'] = 'exception';
        $out['include_exception'] = $e->getMessage();
    }
} else {
    $out['include_result'] = 'not_found';
}

// Print result
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit;
?>
<?php
/**
 * Legacy DB connection bridge for /api/* scripts.
 *
 * Reads credentials from the MVC config/database.php and exposes a plain
 * $conn (mysqli) variable that every legacy API file expects.
 *
 * This avoids duplicating credentials and keeps one source of truth.
 */

$dbConfig = require __DIR__ . '/database.php';

mysqli_report(MYSQLI_REPORT_OFF);

$conn = @new mysqli(
    $dbConfig['host']     ?? 'localhost',
    $dbConfig['username'] ?? '',
    $dbConfig['password'] ?? '',
    $dbConfig['database'] ?? ''
);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    error_log('db.php connection error: ' . $conn->connect_error);
    exit;
}

if (!empty($dbConfig['charset'])) {
    $conn->set_charset($dbConfig['charset']);
}

if (!empty($dbConfig['timezone'])) {
    $conn->query("SET time_zone = '" . $conn->real_escape_string($dbConfig['timezone']) . "'");
}

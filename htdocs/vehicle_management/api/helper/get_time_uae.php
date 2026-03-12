<?php
// vehicle_management/api/helper/get_time_uae.php
require_once __DIR__ . '/../config/timezone.php'; 
header('Content-Type: application/json; charset=utf-8');

global $nowDt;
$time_format = 'Y-m-d H:i:s';

echo json_encode([
    "success" => true,
    "datetime" => $nowDt->format($time_format) 
]);
?>
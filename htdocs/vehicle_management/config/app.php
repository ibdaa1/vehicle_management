<?php
/**
 * Application Configuration
 * 
 * base_url: Set via APP_BASE_URL env var. Defaults to '/vehicle_management' for production.
 *           Set to '' (empty) for local development with PHP built-in server.
 */

return [
    'name'     => 'Vehicle Management System',
    'env'      => getenv('APP_ENV')      ?: 'production',
    'timezone' => 'Asia/Dubai',
    'charset'  => 'UTF-8',
    'locale'   => 'ar',
    'base_url' => getenv('APP_BASE_URL') !== false ? getenv('APP_BASE_URL') : '/vehicle_management',
    'debug'    => (bool)(getenv('APP_DEBUG') ?: false),
];

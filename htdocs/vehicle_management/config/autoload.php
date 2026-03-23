<?php
/**
 * Autoloader
 * 
 * PSR-4 compatible autoloader for the App namespace.
 * Maps App\* classes to app/* directory.
 * Also provides polyfills for PHP 8.0 functions on older PHP versions.
 */

// PHP 8.0 compatibility: polyfill str_starts_with
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

// PHP 8.0 compatibility: polyfill str_contains
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';

    // Check if class uses the App namespace
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Get relative class name and convert to file path
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});
<?php
/**
 * Dynamic Web App Manifest
 * 
 * Serves manifest.json with theme colors from the database.
 * Falls back to defaults if DB is unavailable.
 */

header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/includes/config.php';

// Get theme colors from DB
$theme = vm_get_theme();
$settings = vm_get_settings();

$themeColor = '#1a1a2e'; // default
$bgColor    = '#1a1a2e'; // default
$systemName   = $settings['system_title_ar'] ?? 'نظام إدارة المركبات';
$shortName    = $settings['site_name_ar']    ?? 'إدارة المركبات';

// Use DB theme colors if available
if ($theme && !empty($theme['colors'])) {
    if (!empty($theme['colors']['primary_dark'])) {
        $themeColor = $theme['colors']['primary_dark'];
        $bgColor    = $theme['colors']['primary_dark'];
    }
    if (!empty($theme['colors']['bg_main'])) {
        $bgColor = $theme['colors']['bg_main'];
    }
}

$logoUrl = $settings['logo_url'] ?? './logo/shjmunlogo.png';

$manifest = [
    'name'             => $systemName,
    'short_name'       => $shortName,
    'description'      => 'Vehicle Management System - نظام متابعة وإدارة السيارات',
    'start_url'        => './dashboard.php',
    'display'          => 'standalone',
    'orientation'      => 'any',
    'background_color' => $bgColor,
    'theme_color'      => $themeColor,
    'icons'            => [
        [
            'src'   => $logoUrl,
            'sizes' => '192x192',
            'type'  => 'image/png',
        ],
    ],
];

echo json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
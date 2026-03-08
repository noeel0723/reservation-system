<?php
/**
 * Bootstrap / Initialization File
 * Include this at the top of every page
 */

// Timezone (WITA - Waktu Indonesia Tengah, UTC+8)
date_default_timezone_set('Asia/Makassar');

// Error reporting (production: set to 0)
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Load dependencies
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../functions/log_helper.php';

// Start secure session
startSecureSession();

// Base URL constant
define('BASE_URL', '/reservasi-sistem');
define('SITE_NAME', 'SITARU');
define('SITE_FULL_NAME', 'SITARU - Sistem Tata Ruang & Alat TVRI');

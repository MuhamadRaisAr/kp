<?php
/**
 * Configuration File
 * Place all global settings here for easy environment switching.
 */

// 1. Database Configuration
// Use environment variables for production, or hardcoded values for local.
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: 'root');
define('DB_NAME', getenv('DB_NAME') ?: 'db_nilai');

// 2. Base URL Management
// Dynamically determine the base URL to prevent broken links on different hosting paths.
// 2. Base URL Management
// Untuk mengatasi masalah path/link error 404
// Kita set manual saja sesuai nama folder di Laragon
$base_url = '/sistem-penilaian/';

// Pastikan tidak ada double slash
$base_url = preg_replace('#/+#', '/', $base_url);

// 3. Error Reporting (Set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

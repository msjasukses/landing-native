<?php
/**
 * Konfigurasi dasar aplikasi landing page.
 */

define('APP_ROOT', dirname(__DIR__));
define('DATA_DIR', APP_ROOT . DIRECTORY_SEPARATOR . 'data');
define('UPLOAD_DIR', APP_ROOT . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads');
define('DB_FILE', DATA_DIR . DIRECTORY_SEPARATOR . 'landing.sqlite');

// Base URL dideteksi otomatis dari lokasi folder (mis. /landing atau /)
$scriptDir = str_replace(DIRECTORY_SEPARATOR, '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
if (substr($scriptDir, -6) === '/admin') {
    $scriptDir = substr($scriptDir, 0, -6);
}
define('BASE_URL', rtrim($scriptDir, '/'));

date_default_timezone_set('Asia/Jakarta');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

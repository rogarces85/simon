<?php
// Enable Error Reporting for Debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Credenciales reales: se leen de config/config.local.php (no versionado,
// ver config/config.example.php) o de variables de entorno. Nunca se
// hardcodean aquí para evitar exponerlas si el repositorio es público.
$localConfig = __DIR__ . '/config.local.php';
if (file_exists($localConfig)) {
    require_once $localConfig;
}

if (!defined('DB_HOST')) {
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', getenv('DB_NAME') ?: '');
}
if (!defined('DB_USER')) {
    define('DB_USER', getenv('DB_USER') ?: '');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', getenv('DB_PASS') ?: '');
}
define('DB_CHARSET', 'utf8mb4');

// Other configurations
define('SITE_NAME', 'RUNCOACH');
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('BASE_URL', 'http://' . $host . '/SIMON/');

// Email Configuration (uses PHP mail() by default)
// Set SMTP_ENABLED to true and fill in SMTP credentials if using external SMTP
define('SMTP_ENABLED', false);
define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('MAIL_FROM_EMAIL', 'noreply@runcoach.com');
define('MAIL_FROM_NAME', SITE_NAME);

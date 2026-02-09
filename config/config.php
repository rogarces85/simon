<?php
// Enable Error Reporting for Debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('DB_HOST', 'srv1663.hstgr.io');
define('DB_NAME', 'u901416689_runcoach');
define('DB_USER', 'u901416689_runcoach');
define('DB_PASS', '9D8Q2>uR');
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

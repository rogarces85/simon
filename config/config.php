<?php
// Configuration for Hostinger MySQL Database
define('DB_HOST', 'srv1663.hstgr.io');
define('DB_NAME', 'u901416689_runcoach');
define('DB_USER', 'u901416689_runcoach');
define('DB_PASS', '9D8Q2>uR');
define('DB_CHARSET', 'utf8mb4');

// Other configurations
define('SITE_NAME', 'RUNCOACH');
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('BASE_URL', 'http://' . $host . '/runcoach/'); // Updated to match hosting path

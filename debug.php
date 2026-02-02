<?php
// PHP Error Diagnostic Script
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>TrainPro Diagnostic</h1>";
echo "PHP Version: " . phpversion() . "<br>";

echo "<h2>Checking Files:</h2>";
$files = [
    'config/config.php',
    'includes/db.php',
    'includes/auth.php',
    'models/User.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file NOT found<br>";
    }
}

echo "<h2>Checking Database Connection:</h2>";
try {
    require_once 'includes/db.php';
    $db = Database::getInstance();
    echo "✅ Database connection successful!<br>";

    // Check if tables exist
    $tables = ['users', 'workouts', 'templates'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists<br>";
        } else {
            echo "❌ Table '$table' NOT found (Did you run scripts/migrate_db.php?)<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "<br>";
}

echo "<h2>Server Info:</h2>";
echo "HTTP_HOST: " . $_SERVER['HTTP_HOST'] . "<br>";
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
?>
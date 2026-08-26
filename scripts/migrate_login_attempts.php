<?php
require_once __DIR__ . '/../includes/db.php';

$db = Database::getInstance();

echo "Starting login_attempts migration...\n";

try {
    $db->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL,
        ip_address VARCHAR(45) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (username, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Table login_attempts ready.\n";
} catch (PDOException $e) {
    echo "Error creating login_attempts: " . $e->getMessage() . "\n";
}

echo "Migration completed!\n";

<?php
require_once __DIR__ . '/../includes/db.php';

$db = Database::getInstance();

echo "Starting migration...\n";

// Check if columns exist and add them if not
$columns = [
    'coach_feedback' => 'TEXT NULL',
    'coach_feedback_at' => 'DATETIME NULL',
    'delivery_status' => "VARCHAR(20) DEFAULT 'pending'",
    'viewed_at' => 'DATETIME NULL'
];

foreach ($columns as $column => $definition) {
    try {
        $stmt = $db->query("SHOW COLUMNS FROM workouts LIKE '$column'");
        if ($stmt->rowCount() == 0) {
            $db->exec("ALTER TABLE workouts ADD COLUMN $column $definition");
            echo "Added column: $column\n";
        } else {
            echo "Column already exists: $column\n";
        }
    } catch (PDOException $e) {
        echo "Error with column $column: " . $e->getMessage() . "\n";
    }
}

echo "Migration completed!\n";

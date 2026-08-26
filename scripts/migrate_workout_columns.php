<?php
require_once __DIR__ . '/../includes/db.php';

$db = Database::getInstance();

echo "Starting migration...\n";

// Check if columns exist and add them if not
$columns = [
    'coach_feedback' => 'TEXT NULL',
    'coach_feedback_at' => 'DATETIME NULL',
    'delivery_status' => "VARCHAR(20) DEFAULT 'pending'",
    'viewed_at' => 'DATETIME NULL',
    'evidence_path' => 'VARCHAR(255) NULL'
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

// Corregir el tipo de columna de distancia/tiempo real: eran INT y truncaban
// los decimales que el formulario de mi_plan.php ya envía (step 0.01 / 0.1).
// MODIFY COLUMN es idempotente: no falla si el tipo ya es el correcto.
try {
    $db->exec("ALTER TABLE workouts MODIFY COLUMN actual_distance DECIMAL(6,2) NULL");
    $db->exec("ALTER TABLE workouts MODIFY COLUMN actual_time DECIMAL(6,2) NULL");
    echo "actual_distance / actual_time ahora son DECIMAL(6,2)\n";
} catch (PDOException $e) {
    echo "Error ajustando tipo de actual_distance/actual_time: " . $e->getMessage() . "\n";
}

// OPCIONAL: evidence_url nunca la lee ni la escribe el código (se usa
// evidence_path). Descomentar la siguiente línea solo si se confirma que
// no tiene datos que se quieran conservar — DROP COLUMN es irreversible.
// $db->exec("ALTER TABLE workouts DROP COLUMN evidence_url");

echo "Migration completed!\n";

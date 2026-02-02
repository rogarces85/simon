<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../models/User.php';

// Seed a default coach for testing
$coachData = [
    'username' => 'coach@trainpro.com',
    'password' => password_hash('coach123', PASSWORD_DEFAULT),
    'role' => 'coach',
    'name' => 'Entrenador Demo',
    'observations' => 'Usuario administrativo inicial'
];

try {
    $db = Database::getInstance();
    // Check if exists
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$coachData['username']]);
    if ($stmt->fetch()) {
        echo "Demo coach already exists.\n";
    } else {
        User::create($coachData);
        echo "Demo coach created: coach@trainpro.com / coach123\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

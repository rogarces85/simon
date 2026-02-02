<?php
// Habilitar errores al máximo para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Depuración de Configuración</h1>";

try {
    echo "Cargando dependencias...<br>";

    if (!file_exists(__DIR__ . '/../config/config.php')) {
        die("❌ ERROR: No se encuentra config/config.php");
    }
    require_once __DIR__ . '/../config/config.php';
    echo "✅ config.php cargado.<br>";

    if (!file_exists(__DIR__ . '/../includes/db.php')) {
        die("❌ ERROR: No se encuentra includes/db.php");
    }
    require_once __DIR__ . '/../includes/db.php';
    echo "✅ db.php cargado.<br>";

    if (!file_exists(__DIR__ . '/../models/User.php')) {
        die("❌ ERROR: No se encuentra models/User.php");
    }
    require_once __DIR__ . '/../models/User.php';
    echo "✅ models/User.php cargado.<br>";

    $db = Database::getInstance();
    echo "✅ Conexión a Base de Datos establecida.<br>";

    // 1. Crear Tablas (Usando TEXT en lugar de JSON por si es una versión antigua de MariaDB/MySQL)
    $queries = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(50) NOT NULL DEFAULT 'athlete',
            name VARCHAR(255) NOT NULL,
            coach_id INT NULL,
            goal_date DATETIME NULL,
            goal_pace VARCHAR(50) NULL,
            level VARCHAR(50) NULL,
            available_days TEXT NULL,
            preferred_long_run_day VARCHAR(50) NULL,
            max_time_per_session INT NULL,
            observations TEXT NULL,
            INDEX (coach_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS templates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            coach_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            type VARCHAR(100) NOT NULL,
            block_type VARCHAR(100) NULL,
            structure TEXT NOT NULL,
            INDEX (coach_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS workouts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            athlete_id INT NOT NULL,
            date DATETIME NOT NULL,
            type VARCHAR(100) NOT NULL,
            description TEXT NULL,
            status VARCHAR(50) DEFAULT 'pending',
            planned_distance INT NULL,
            planned_time INT NULL,
            actual_distance INT NULL,
            actual_time INT NULL,
            rpe INT NULL,
            feedback TEXT NULL,
            coach_feedback TEXT NULL,
            completed_at DATETIME NULL,
            structure TEXT NULL,
            INDEX (athlete_id),
            INDEX (date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    ];

    foreach ($queries as $sql) {
        $db->exec($sql);
        echo "✅ Una tabla fue procesada/verificada.<br>";
    }

    // 2. Crear Usuario Coach por defecto
    $coachData = [
        'username' => 'coach@trainpro.com',
        'password' => password_hash('coach123', PASSWORD_DEFAULT),
        'role' => 'coach',
        'name' => 'Entrenador Principal',
        'observations' => 'Usuario administrativo inicial'
    ];

    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$coachData['username']]);
    if ($stmt->fetch()) {
        echo "ℹ️ El usuario Coach ya existe.<br>";
    } else {
        User::create($coachData);
        echo "✅ Usuario Coach creado con éxito!<br>";
        echo "<b>Email:</b> coach@trainpro.com<br>";
        echo "<b>Password:</b> coach123<br>";
    }

    echo "<h2>¡Todo listo!</h2>";
    echo "<a href='../login.php'>Ir al Login</a>";

} catch (Throwable $e) {
    echo "<h1>❌ ERROR FATAL</h1>";
    echo "Mensaje: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . "<br>";
    echo "Línea: " . $e->getLine() . "<br>";
}
?>
<?php
// Habilitar errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🚀 Setup RUNCOACH</h1>";

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
    echo "✅ Conexión a Base de Datos establecida.<br><br>";

    // 1. Crear Tablas
    echo "<h2>📦 Creando Tablas...</h2>";

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
        echo "✅ Tabla procesada.<br>";
    }

    // 2. Crear Usuario Coach por defecto
    echo "<h2>👤 Creando Usuario Coach...</h2>";

    $coachData = [
        'username' => 'coach@runcoach.com',
        'password' => password_hash('coach123', PASSWORD_DEFAULT),
        'role' => 'coach',
        'name' => 'Entrenador Principal',
        'observations' => 'Usuario administrativo inicial'
    ];

    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$coachData['username']]);
    $existingCoach = $stmt->fetch();

    if ($existingCoach) {
        echo "ℹ️ El usuario Coach ya existe.<br>";
        $coachId = $existingCoach['id'];
    } else {
        $coachId = User::create($coachData);
        echo "✅ Usuario Coach creado con éxito!<br>";
    }

    echo "<b>Email:</b> coach@runcoach.com<br>";
    echo "<b>Password:</b> coach123<br><br>";

    // 3. Crear Plantillas de Demostración
    echo "<h2>📋 Creando Plantillas Demo...</h2>";

    $demoTemplates = [
        ['name' => '15x400m ritmo 1:38-1:34', 'type' => 'Series', 'block_type' => 'Construcción', 'structure' => 'Series de 400m con recuperación activa'],
        ['name' => '12x400m ritmo 1:40', 'type' => 'Series', 'block_type' => 'Construcción', 'structure' => 'Series de 400m a ritmo constante'],
        ['name' => '10x400m ritmo 1:35', 'type' => 'Series', 'block_type' => 'Pico', 'structure' => 'Series cortas a alta intensidad'],
        ['name' => '8x400m ritmo 1:30', 'type' => 'Intervalos', 'block_type' => 'Pico', 'structure' => 'Intervalos cortos con recuperación'],
        ['name' => '6x400m ritmo 1:28', 'type' => 'Intervalos', 'block_type' => 'Pico', 'structure' => 'Intervalos de velocidad'],
        ['name' => '20x400m ritmo 1:45', 'type' => 'Intervalos', 'block_type' => 'Base', 'structure' => 'Intervalos de volumen a ritmo moderado'],
        ['name' => '16x400m ritmo 1:42', 'type' => 'Intervalos', 'block_type' => 'Construcción', 'structure' => 'Intervalos de resistencia'],
        ['name' => '10x400m progresivo 1:50-1:35', 'type' => 'Intervalos', 'block_type' => 'Construcción', 'structure' => 'Progresión controlada'],
        ['name' => '20x200m ritmo 48s', 'type' => 'Intervalos', 'block_type' => 'Pico', 'structure' => 'Repeticiones cortas velocidad'],
        ['name' => '15x200m ritmo 45s', 'type' => 'Intervalos', 'block_type' => 'Pico', 'structure' => 'Series velocidad pura'],
        ['name' => '12x200m ritmo 42s', 'type' => 'Intervalos', 'block_type' => 'Pico', 'structure' => 'Series velocidad alta'],
        ['name' => '10x200m ritmo 40s', 'type' => 'Intervalos', 'block_type' => 'Pico', 'structure' => 'Series velocidad máxima'],
        ['name' => 'Fondo 20km ritmo suave', 'type' => 'Fondo', 'block_type' => 'Base', 'structure' => 'Carrera larga a ritmo conversacional'],
        ['name' => 'Fondo 25km con progresivo', 'type' => 'Fondo', 'block_type' => 'Construcción', 'structure' => 'Fondo con últimos 5km más rápido'],
        ['name' => 'Tempo 10km ritmo objetivo', 'type' => 'Tempo', 'block_type' => 'Pico', 'structure' => 'Carrera a ritmo de competencia'],
        ['name' => 'Recuperación suave 30min', 'type' => 'Recuperación', 'block_type' => 'Base', 'structure' => 'Trote suave regenerativo'],
        ['name' => 'Descanso activo - caminata', 'type' => 'Descanso', 'block_type' => 'Base', 'structure' => 'Caminata de 30-45min'],
        ['name' => 'Descanso total', 'type' => 'Descanso', 'block_type' => 'Base', 'structure' => 'Día de recuperación completa'],
    ];

    $insertedCount = 0;
    foreach ($demoTemplates as $template) {
        // Check if exists
        $stmt = $db->prepare("SELECT id FROM templates WHERE coach_id = ? AND name = ?");
        $stmt->execute([$coachId, $template['name']]);

        if (!$stmt->fetch()) {
            $sql = "INSERT INTO templates (coach_id, name, type, block_type, structure) VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $coachId,
                $template['name'],
                $template['type'],
                $template['block_type'],
                $template['structure']
            ]);
            $insertedCount++;
        }
    }

    echo "✅ $insertedCount plantillas demo creadas.<br><br>";

    echo "<h2 style='color: green;'>🎉 ¡Configuración Completada!</h2>";
    echo "<a href='../login.php' style='display: inline-block; background: #3b82f6; color: white; padding: 12px 24px; border-radius: 12px; text-decoration: none; font-weight: bold;'>Ir al Login →</a>";

} catch (Throwable $e) {
    echo "<h1 style='color: red;'>❌ ERROR FATAL</h1>";
    echo "Mensaje: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . "<br>";
    echo "Línea: " . $e->getLine() . "<br>";
}
?>
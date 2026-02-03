<?php
// Habilitar errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🚀 Setup RUNCOACH v2.0</h1>";

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
    echo "<h2>📦 Verificando Tablas...</h2>";

    $queries = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'coach', 'athlete') NOT NULL,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            avatar_url VARCHAR(255) DEFAULT NULL,
            coach_id INT DEFAULT NULL,
            team_id INT DEFAULT NULL,
            goal_date DATE DEFAULT NULL,
            goal_pace VARCHAR(20) DEFAULT NULL,
            level VARCHAR(50) DEFAULT 'Principiante',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            available_days TEXT NULL,
            preferred_long_run_day VARCHAR(50) NULL,
            max_time_per_session INT NULL,
            observations TEXT NULL,
            FOREIGN KEY (coach_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX (coach_id),
            INDEX (team_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS teams (
            id INT AUTO_INCREMENT PRIMARY KEY,
            coach_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            logo_url VARCHAR(255) NULL,
            primary_color VARCHAR(50) DEFAULT '#3b82f6',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX (coach_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            type VARCHAR(50) DEFAULT 'info',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id)
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
            evidence_url VARCHAR(255) NULL,
            coach_feedback TEXT NULL,
            completed_at DATETIME NULL,
            structure TEXT NULL,
            INDEX (athlete_id),
            INDEX (date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    ];

    foreach ($queries as $sql) {
        $db->exec($sql);
        echo "✅ Tabla verificada.<br>";
    }

    // 1.1 Columnas Faltantes (Alter Table)
    // Verificar si falta evidence_url en workouts (para migraciones)
    try {
        $db->exec("ALTER TABLE workouts ADD COLUMN evidence_url VARCHAR(255) NULL");
        echo "✅ Columna evidence_url agregada (o advertencia ignorada).<br>";
    } catch (PDOException $e) {
        // Ignorar si ya existe
    }
    try {
        $db->exec("ALTER TABLE users ADD COLUMN team_id INT NULL");
        echo "✅ Columna team_id agregada (o advertencia ignorada).<br>";
    } catch (PDOException $e) {
        // Ignorar si ya existe
    }

    // 2. Obtener o Crear Admin y Coach
    // ADMIN
    $adminEmail = 'admin@runcoach.com';
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$adminEmail]);
    $existingAdmin = $stmt->fetch();

    if ($existingAdmin) {
        echo "ℹ️ Admin ya existe.<br>";
    } else {
        User::create([
            'username' => $adminEmail,
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'role' => 'admin',
            'name' => 'Administrador Global'
        ]);
        echo "✅ Admin creado con éxito.<br>";
    }

    // COACH
    $coachEmail = 'coach@runcoach.com';
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$coachEmail]);
    $existingCoach = $stmt->fetch();

    if ($existingCoach) {
        $coachId = $existingCoach['id'];
        echo "ℹ️ Coach ya existe (ID: $coachId).<br>";
    } else {
        $coachId = User::create([
            'username' => $coachEmail,
            'password' => password_hash('coach123', PASSWORD_DEFAULT),
            'role' => 'coach',
            'name' => 'Entrenador Principal'
        ]);
        echo "✅ Coach creado con éxito.<br>";
    }

    // 3. Biblioteca de Plantillas (50+ sesiones)
    echo "<h2>📋 Expandiendo Biblioteca de Plantillas (50+)...</h2>";

    $demoTemplates = [
        // SERIES (Velocidad)
        ['type' => 'Series', 'block_type' => 'Pico', 'name' => '10x200m @40s', 'structure' => 'Velocidad máxima, rec 200m trote'],
        ['type' => 'Series', 'block_type' => 'Pico', 'name' => '12x200m @42s', 'structure' => 'Potencia aeróbica, rec 90s'],
        ['type' => 'Series', 'block_type' => 'Pico', 'name' => '15x200m @45s', 'structure' => 'Rimo sostenido, rec 60s'],
        ['type' => 'Series', 'block_type' => 'Construcción', 'name' => '20x200m @48s', 'structure' => 'Volumen velocidad, rec 1:1'],
        ['type' => 'Series', 'block_type' => 'Pico', 'name' => '6x400m @1:28', 'structure' => 'Velocidad crítica, rec 2min'],
        ['type' => 'Series', 'block_type' => 'Pico', 'name' => '8x400m @1:30', 'structure' => 'Ritmo 1.5k, rec 90s'],
        ['type' => 'Series', 'block_type' => 'Pico', 'name' => '10x400m @1:35', 'structure' => 'Ritmo 3k, rec 75s'],
        ['type' => 'Series', 'block_type' => 'Construcción', 'name' => '12x400m @1:40', 'structure' => 'Ritmo 5k, rec 60s'],
        ['type' => 'Series', 'block_type' => 'Construcción', 'name' => '15x400m @1:38-1:34', 'structure' => 'Progresivos, cada 3 más rápido, rec 45s'],
        ['type' => 'Series', 'block_type' => 'Base', 'name' => '20x400m @1:45', 'structure' => 'Capacidad aeróbica, rec 30s'],
        ['type' => 'Series', 'block_type' => 'Construcción', 'name' => '5x800m @3:10', 'structure' => 'Umbral láctico, rec 2:30'],
        ['type' => 'Series', 'block_type' => 'Construcción', 'name' => '6x800m @3:15', 'structure' => 'Ritmo 10k, rec 2min'],
        ['type' => 'Series', 'block_type' => 'Construcción', 'name' => '8x800m @3:25', 'structure' => 'Entrenamiento extensivo, rec 90s'],
        ['type' => 'Series', 'block_type' => 'Pico', 'name' => '4x1000m @3:55', 'structure' => 'VO2 Max, rec 400m trote'],
        ['type' => 'Series', 'block_type' => 'Pico', 'name' => '5x1000m @4:00', 'structure' => 'Ritmo 5k objetivo, rec 3min'],
        ['type' => 'Series', 'block_type' => 'Construcción', 'name' => '6x1000m @4:15', 'structure' => 'Ritmo 10k, rec 2min'],
        ['type' => 'Series', 'block_type' => 'Construcción', 'name' => '3x2000m @8:30', 'structure' => 'Umbral, rec 3min trote'],
        ['type' => 'Series', 'block_type' => 'Base', 'name' => '4x2000m @9:00', 'structure' => 'Resistencia muscular, rec 2min'],

        // INTERVALOS (Resistencia)
        ['type' => 'Intervalos', 'block_type' => 'Construcción', 'name' => '10x400m prog 1:50-1:35', 'structure' => 'Inicia suave, termina fuerte'],
        ['type' => 'Intervalos', 'block_type' => 'Construcción', 'name' => '16x400m @1:42', 'structure' => 'Intervalos aeróbicos, rec 100m trote'],
        ['type' => 'Intervalos', 'block_type' => 'Construcción', 'name' => '3x(5min fuerte + 3min suave)', 'structure' => 'Fartlek clásico'],
        ['type' => 'Intervalos', 'block_type' => 'Pico', 'name' => '5x(3min @4:00 + 2min @5:00)', 'structure' => 'Cambio de ritmo controlado'],
        ['type' => 'Intervalos', 'block_type' => 'Base', 'name' => '10x(1min rápido + 1min suave)', 'structure' => 'Fartlek introductorio'],
        ['type' => 'Intervalos', 'block_type' => 'Construcción', 'name' => 'Pirámide 200-400-600-800-600-400-200', 'structure' => 'Rec 200m trote entre todos'],
        ['type' => 'Intervalos', 'block_type' => 'Pico', 'name' => 'Cuesta 10x150m fuerte', 'structure' => 'Potencia en subida, rec bajada'],
        ['type' => 'Intervalos', 'block_type' => 'Pico', 'name' => 'Cuesta 12x200m progresivo', 'structure' => 'Fuerza explosiva subiendo'],

        // FONDOS (Largo)
        ['type' => 'Fondo', 'block_type' => 'Base', 'name' => 'Fondo 15km Suave', 'structure' => 'Ritmo cómodo, 70-75% FC'],
        ['type' => 'Fondo', 'block_type' => 'Base', 'name' => 'Fondo 18km Suave', 'structure' => 'Carrera larga base'],
        ['type' => 'Fondo', 'block_type' => 'Base', 'name' => 'Fondo 20km Suave', 'structure' => 'Base aeróbica larga'],
        ['type' => 'Fondo', 'block_type' => 'Base', 'name' => 'Fondo 22km Suave', 'structure' => 'Extensión resistencia'],
        ['type' => 'Fondo', 'block_type' => 'Construcción', 'name' => 'Fondo 25km Progresivo', 'structure' => 'Últimos 5km a ritmo 21k'],
        ['type' => 'Fondo', 'block_type' => 'Construcción', 'name' => 'Fondo 28km Progresivo', 'structure' => 'Terminar últimos 8km ritmo maratón'],
        ['type' => 'Fondo', 'block_type' => 'Pico', 'name' => 'Fondo 30km con Bloques', 'structure' => '3x5km ritmo maratón entre medio'],
        ['type' => 'Fondo', 'block_type' => 'Pico', 'name' => 'Fondo 32km Tirada Larga', 'structure' => 'Máxima distancia para maratón'],
        ['type' => 'Fondo', 'block_type' => 'Construcción', 'name' => 'Fondo Montaña 2h', 'structure' => 'Carrera por senderos con desnivel'],

        // TEMPO (Umbral)
        ['type' => 'Tempo', 'block_type' => 'Base', 'name' => 'Tempo 5km', 'structure' => 'Ritmo sostenible fuerte'],
        ['type' => 'Tempo', 'block_type' => 'Construcción', 'name' => 'Tempo 8km @Ritmo 10k+15s', 'structure' => 'Umbral aeróbico'],
        ['type' => 'Tempo', 'block_type' => 'Pico', 'name' => 'Tempo 10km Ritmo Objetivo', 'structure' => 'Ensayo ritmo carrera'],
        ['type' => 'Tempo', 'block_type' => 'Pico', 'name' => 'Tempo 12km Umbral', 'structure' => 'Máximo esfuerzo mantenido'],
        ['type' => 'Tempo', 'block_type' => 'Construcción', 'name' => 'Tempo 2x5km rec 5min', 'structure' => 'Fraccionado al umbral'],

        // RECUPERACIÓN / DESCANSO
        ['type' => 'Recuperación', 'block_type' => 'Base', 'name' => 'Trote 20min Regenerativo', 'structure' => 'Mínimo impacto'],
        ['type' => 'Recuperación', 'block_type' => 'Base', 'name' => 'Trote 30min Suave', 'structure' => 'Regeneración post calidad'],
        ['type' => 'Recuperación', 'block_type' => 'Base', 'name' => 'Trote 40min Muy Suave', 'structure' => 'Mantenimiento aeróbico'],
        ['type' => 'Descanso', 'block_type' => 'Base', 'name' => 'Descanso Total', 'structure' => 'Sin actividad física'],
        ['type' => 'Descanso', 'block_type' => 'Base', 'name' => 'Descanso Activo - Caminata', 'structure' => '45min caminata ligera'],
        ['type' => 'Descanso', 'block_type' => 'Base', 'name' => 'Descanso Activo - Movilidad', 'structure' => '30min ejercicios estiramiento'],
        ['type' => 'Descanso', 'block_type' => 'Base', 'name' => 'Descanso - Yoga / Pilates', 'structure' => 'Sesión de flexibilidad'],

        // OTROS / MIXTOS
        ['type' => 'Series', 'block_type' => 'Construcción', 'name' => '2x(5x200m) rec 3min', 'structure' => 'Bloques de velocidad'],
        ['type' => 'Series', 'block_type' => 'Pico', 'name' => '3x(4x400m) @1:32', 'structure' => 'Series rotas con rec corta'],
        ['type' => 'Fondo', 'block_type' => 'Base', 'name' => 'Fondo Aeróbico 1h30', 'structure' => 'Por tiempo, no km'],
        ['type' => 'Tempo', 'block_type' => 'Base', 'name' => 'Tempo 40min Progresivo', 'structure' => 'Creciendo cada 10min'],
        ['type' => 'Recuperación', 'block_type' => 'Base', 'name' => 'Trote 45min + 6 Rectas', 'structure' => 'Mantenimiento y técnica'],
        ['type' => 'Intervalos', 'block_type' => 'Construcción', 'name' => '1min on / 1min off x 15', 'structure' => 'Alta densidad'],
        ['type' => 'Series', 'block_type' => 'Pico', 'name' => '10x100m Rectas Explosivas', 'structure' => 'Técnica de carrera'],
    ];

    $insertedCount = 0;
    foreach ($demoTemplates as $tpl) {
        $stmt = $db->prepare("SELECT id FROM templates WHERE coach_id = ? AND name = ?");
        $stmt->execute([$coachId, $tpl['name']]);
        if (!$stmt->fetch()) {
            $sql = "INSERT INTO templates (coach_id, name, type, block_type, structure) VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $coachId,
                $tpl['name'],
                $tpl['type'],
                $tpl['block_type'],
                $tpl['structure']
            ]);
            $insertedCount++;
        }
    }

    echo "✅ Se añadieron $insertedCount nuevas plantillas (Total en BD: " . (count($demoTemplates) - $insertedCount + $insertedCount) . " aprox).<br><br>";

    echo "<h2 style='color: green;'>🎉 ¡Actualización Completada!</h2>";
    echo "<p>Ya puedes ver las más de 50 plantillas en la sección correspondiente.</p>";
    echo "<a href='../atletas.php' style='display: inline-block; background: #3b82f6; color: white; padding: 12px 24px; border-radius: 12px; text-decoration: none; font-weight: bold;'>Ir a Atletas →</a>";

} catch (Throwable $e) {
    echo "<h1 style='color: red;'>❌ ERROR FATAL</h1>";
    echo "Mensaje: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . "<br>";
    echo "Línea: " . $e->getLine() . "<br>";
}
?>
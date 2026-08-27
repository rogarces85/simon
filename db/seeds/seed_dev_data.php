<?php
/**
 * Datos de prueba para desarrollo local. NO correr contra produccion.
 *
 * Crea dos coaches con sus teams y un atleta cada uno. Hacen falta dos de cada
 * uno para poder verificar los controles de pertenencia de la Fase 0: que un
 * coach no pueda tocar al atleta del otro, ni usar sus plantillas, ni que un
 * atleta complete el entrenamiento de otro.
 *
 * Es idempotente: se apoya en el username, asi que correrlo dos veces no
 * duplica nada. Las contraseñas son las del arreglo $USERS, en claro, porque
 * esto solo debe existir en la maquina de desarrollo.
 *
 * Uso:  php db/seeds/seed_dev_data.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/Team.php';
require_once __DIR__ . '/../../models/Workout.php';

$db = Database::getInstance();

// Salvaguarda: este seed inventa usuarios, no debe tocar la base real.
if (DB_HOST !== 'localhost' && DB_HOST !== '127.0.0.1') {
    fwrite(STDERR, "Abortado: DB_HOST es '" . DB_HOST . "'. Este seed es solo para la base local.\n");
    exit(1);
}

function findUserByUsername(PDO $db, string $username)
{
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch();
}

echo "Base: " . DB_NAME . " en " . DB_HOST . "\n\n";

// --- Coaches y sus teams -------------------------------------------------

$coaches = [
    [
        'username' => 'coach1@test.local',
        'name' => 'Ana Coach Uno',
        'team' => ['name' => 'Runners del Sur', 'primary_color' => '#2563eb'],
    ],
    [
        'username' => 'coach2@test.local',
        'name' => 'Beto Coach Dos',
        'team' => ['name' => 'Club Maraton', 'primary_color' => '#db2777'],
    ],
];

$coachIds = [];
foreach ($coaches as $c) {
    $existing = findUserByUsername($db, $c['username']);
    if ($existing) {
        $coachIds[$c['username']] = $existing['id'];
        echo "coach {$c['username']} ya existia (id {$existing['id']})\n";
    } else {
        $id = User::create([
            'username' => $c['username'],
            'password' => password_hash('test1234', PASSWORD_DEFAULT),
            'role' => 'coach',
            'name' => $c['name'],
        ]);
        $coachIds[$c['username']] = $id;
        echo "coach {$c['username']} creado (id $id)\n";
    }

    $coachId = $coachIds[$c['username']];
    if (!Team::findByCoach($coachId)) {
        Team::create([
            'coach_id' => $coachId,
            'name' => $c['team']['name'],
            'primary_color' => $c['team']['primary_color'],
        ]);
        echo "  team '{$c['team']['name']}' creado\n";
    } else {
        echo "  team ya existia\n";
    }
}

// --- Atletas -------------------------------------------------------------

$athletes = [
    [
        'username' => 'atleta1@test.local',
        'name' => 'Carla Atleta Uno',
        'coach' => 'coach1@test.local',
        // Perfil pensado para la Fase 2: tope bajo y fondo el domingo, para que
        // el validador de carga tenga contra que avisar.
        'level' => 'Principiante',
        'goal_pace' => '5:30',
        'max_time_per_session' => 60,
        'preferred_long_run_day' => 'Domingo',
    ],
    [
        'username' => 'atleta2@test.local',
        'name' => 'Diego Atleta Dos',
        'coach' => 'coach2@test.local',
        'level' => 'Avanzado',
        'goal_pace' => '4:15',
        'max_time_per_session' => 120,
        'preferred_long_run_day' => 'Sabado',
    ],
];

$athleteIds = [];
foreach ($athletes as $a) {
    $existing = findUserByUsername($db, $a['username']);
    if ($existing) {
        $athleteIds[$a['username']] = $existing['id'];
        echo "atleta {$a['username']} ya existia (id {$existing['id']})\n";
        continue;
    }

    $id = User::create([
        'username' => $a['username'],
        'password' => password_hash('test1234', PASSWORD_DEFAULT),
        'role' => 'athlete',
        'name' => $a['name'],
        'coach_id' => $coachIds[$a['coach']],
        'goal_date' => (new DateTime('+10 weeks'))->format('Y-m-d'),
        'goal_pace' => $a['goal_pace'],
        'level' => $a['level'],
        'available_days' => ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'],
        'preferred_long_run_day' => $a['preferred_long_run_day'],
        'max_time_per_session' => $a['max_time_per_session'],
        'observations' => 'Usuario de prueba.',
    ]);
    $athleteIds[$a['username']] = $id;
    echo "atleta {$a['username']} creado (id $id), coach {$a['coach']}\n";
}

// --- Plantillas: una por coach, para probar el filtro por coach_id --------

$templates = [
    ['coach' => 'coach1@test.local', 'name' => 'Series 5x1000', 'type' => 'Series', 'structure' => "20' entrada en calor a 6:30/6:00 min/km\n5x1000m (5' los 1000m), rec. 3' trote suave\n15' vuelta a la calma"],
    ['coach' => 'coach2@test.local', 'name' => 'Fondo 16km', 'type' => 'Fondo', 'structure' => "16km a 5:30/5:15 min/km\nElongacion 20/25'"],
    // Texto plano legacy: sirve para comprobar que TrainingStructure::parse lo
    // sigue mostrando cuando llegue la Fase 1.
    ['coach' => 'coach1@test.local', 'name' => 'Regenerativo (legacy)', 'type' => 'Recuperación', 'structure' => 'Trote suave 40 minutos'],
];

foreach ($templates as $t) {
    $coachId = $coachIds[$t['coach']];
    $stmt = $db->prepare("SELECT id FROM templates WHERE coach_id = ? AND name = ?");
    $stmt->execute([$coachId, $t['name']]);
    if ($stmt->fetch()) {
        echo "plantilla '{$t['name']}' ya existia\n";
        continue;
    }
    $stmt = $db->prepare("INSERT INTO templates (coach_id, name, type, structure) VALUES (?, ?, ?, ?)");
    $stmt->execute([$coachId, $t['name'], $t['type'], $t['structure']]);
    echo "plantilla '{$t['name']}' creada para {$t['coach']}\n";
}

// --- Entrenamientos ------------------------------------------------------
// Dos el mismo dia para el atleta 1: asi se comprueba que el calendario de
// mi_plan.php muestra ambos y ya no los colapsa en uno solo.

$monday = (new DateTime('monday this week'))->format('Y-m-d');

$workouts = [
    ['athlete' => 'atleta1@test.local', 'date' => $monday, 'type' => 'Series', 'description' => 'Series 5x1000', 'structure' => "5x1000m (5' los 1000m), rec. 3' trote suave"],
    ['athlete' => 'atleta1@test.local', 'date' => $monday, 'type' => 'Fortalecimiento', 'description' => 'Circuito de fuerza', 'structure' => '2 vueltas: 20 abdominal puro, 10 media sentadilla, 10 punta de pie'],
    ['athlete' => 'atleta1@test.local', 'date' => (new DateTime($monday))->modify('+2 days')->format('Y-m-d'), 'type' => 'Descanso', 'description' => 'Descanso', 'structure' => null],
    ['athlete' => 'atleta2@test.local', 'date' => $monday, 'type' => 'Fondo', 'description' => 'Fondo 16km', 'structure' => '16km a 5:30/5:15 min/km'],
    // Plantilla con HTML: en mi_plan.php debe verse como texto, no ejecutarse.
    ['athlete' => 'atleta1@test.local', 'date' => (new DateTime($monday))->modify('+3 days')->format('Y-m-d'), 'type' => 'Tempo', 'description' => 'Prueba XSS <b>negrita</b>', 'structure' => '<img src=x onerror="document.title=\'XSS\'"> 8km en tempo'],
];

foreach ($workouts as $w) {
    $athleteId = $athleteIds[$w['athlete']];
    $stmt = $db->prepare("SELECT id FROM workouts WHERE athlete_id = ? AND date = ? AND description = ?");
    $stmt->execute([$athleteId, $w['date'], $w['description']]);
    if ($stmt->fetch()) {
        echo "entrenamiento '{$w['description']}' del {$w['date']} ya existia\n";
        continue;
    }
    Workout::create([
        'athlete_id' => $athleteId,
        'date' => $w['date'],
        'type' => $w['type'],
        'description' => $w['description'],
        'status' => 'pending',
        'structure' => $w['structure'],
        'delivery_status' => 'sent',
    ]);
    echo "entrenamiento '{$w['description']}' creado para {$w['athlete']} el {$w['date']}\n";
}

echo "\nListo. Usuarios de prueba (contraseña: test1234):\n";
foreach (array_merge(array_keys($coachIds), array_keys($athleteIds)) as $u) {
    echo "  $u\n";
}

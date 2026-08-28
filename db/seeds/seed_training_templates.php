<?php
/**
 * Seed de biblioteca de plantillas de entrenamiento basadas en los
 * planes impresos de la carpeta plans/ (5K, 10K, 21K, 42K).
 *
 * Cada sesion se guarda en formato JSON v2 (TrainingStructure::VERSION).
 * Las plantillas se asignan a todos los coaches existentes, con el
 * nombre del plan original para identificar su procedencia.
 *
 * Paces extraidos de los PDFs originales:
 *   5K  -> 6:50-7:30 min/km facil, 5:00/km series, 5:30-6:00 km fondo
 *   10K -> 6:40-7:30 min/km facil, 5:25-5:30/km series, 6:40-7:20 km fondo
 *   21K -> 6:00-6:30 min/km facil, 5:00/km series, 6:00-7:10 km fondo
 *   42K -> 6:00-6:50 min/km facil, 5:00/km series, 6:20-7:30 km fondo
 *
 * Es idempotente: por plan + coach + tipo, solo inserta si no existe.
 *
 * Uso:
 *   php db/seeds/seed_training_templates.php
 *   php db/seeds/seed_training_templates.php --coach=coach1
 *   php db/seeds/seed_training_templates.php --dry-run
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../models/TrainingStructure.php';
require_once __DIR__ . '/../../models/User.php';

$db = Database::getInstance();

$dryRun = in_array('--dry-run', $argv, true);
$allowRemote = in_array('--allow-remote', $argv, true);

// Salvaguarda: por defecto este seed solo toca la base local. Con
// --allow-remote se permite cargar la biblioteca en produccion (Hostinger).
if (DB_HOST !== 'localhost' && DB_HOST !== '127.0.0.1') {
    if (!$allowRemote) {
        fwrite(STDERR, "Abortado: DB_HOST es '" . DB_HOST . "'. Este seed es solo para la base local (usa --allow-remote para cargas intencionales a produccion).\n");
        exit(1);
    }
    fwrite(STDERR, "AVISO: cargando en base REMOTA '" . DB_NAME . "' en '" . DB_HOST . "'\n");
}
$targetCoach = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--coach=')) {
        $targetCoach = substr($arg, 8);
    }
}

$stmt = $db->prepare("SELECT id, username FROM users WHERE role = ? ORDER BY username");
$stmt->execute(['coach']);
$coaches = $stmt->fetchAll();

if (empty($coaches)) {
    echo "No se encontro ningun coach en la base.\n";
    exit(0);
}

if ($targetCoach !== null) {
    $found = false;
    foreach ($coaches as $c) {
        if ($c['username'] === $targetCoach) {
            $coaches = [$c];
            $found = true;
            break;
        }
    }
    if (!$found) {
        echo "Coach '$targetCoach' no encontrado. Coaches disponibles: " . implode(', ', array_column($coaches, 'username')) . "\n";
        exit(0);
    }
}

echo "Base: " . DB_NAME . " en " . DB_HOST . "\n";
echo "Coaches a cargar: " . count($coaches) . "\n";
if ($dryRun) {
    echo "MODO DRY RUN: no se insertara nada.\n";
}
echo "\n";

// Template data modeled on the printed plans.
// Each block uses the exact keys from TrainingStructure::BLOCKS.
// Paces are taken directly from the PDF plans.
$plans = [
    '5K' => [
        [
            'name' => '5K - Serie 6x1000',
            'type' => 'Series',
            'blocks' => [
                'warm_up' => "20' entrada en calor a 6:50/7:30 min/km + movilidad articular + tecnicas: 2x30m (skipping alto, talon a la cola, skipping corto, carioca)",
                'mobility' => 'Cadera, isquiotibiales y tobillos',
                'drills' => 'Skipping alto, talon a la cola, carioca, skipping ruso',
                'strides' => '6x100m al 70/75% capacidad, rec. 40" caminando',
                'main_set' => "6x1000m a 5' los 1000m, rec. 2' trote suave",
                'strength' => "Circuito 2 vueltas: 20 abdominales, 12 espalda natacion, 10 fuerza brazos, 10 media sentadillas, 10 punta de pie, 20 oblicuos, 10 subir al banco, 10 espalda alternado, 20 paso al frente, 20 rodilla al pecho",
                'cool_down' => "15' vuelta a la calma a 7:00/7:30 min/km",
                'elongation' => "20/25' elongacion estatica: isquiotibiales, cuadraces, gemelos",
                'notes' => 'Ritmo facil 6:50-7:25 min/km en zonas continuas. Recuperar completo entre series.',
                'estimated_minutes' => 55,
                'tip_ids' => [1, 5],
            ]
        ],
        [
            'name' => '5K - Fondo Largo 8km',
            'type' => 'Fondo',
            'blocks' => [
                'warm_up' => "8' trote suave a 6:50/7:25 min/km + 4x100m movilidad",
                'mobility' => 'Tobillos y cadera',
                'drills' => 'Footwork y pasos altos suaves',
                'strides' => '4x80m progresivos',
                'main_set' => "8km a ritmo 6:50/7:25 min/km (conversacional)",
                'strength' => "Circuito 2 vueltas: abdominales, espalda, paso al frente, balanceo de brazos, oblicuos, espalda comun, punta de pie, circunducciones, rodilla al pecho, fuerza brazos, media sentadillas",
                'cool_down' => "8' trote suave a 6:50/7:25 min/km",
                'elongation' => "12' elongacion general",
                'notes' => 'Ritmo constante sin llegar al limite. No frenar en subidas.',
                'estimated_minutes' => 60,
                'tip_ids' => [2],
            ]
        ],
        [
            'name' => '5K - Interval 5x1000',
            'type' => 'Series',
            'blocks' => [
                'warm_up' => "10' trote a 6:50/7:25 min/km + 4x100m movilidad + 3x100m strides",
                'mobility' => 'Cadera, isquiotibiales',
                'drills' => 'Alta rodilla y talones al gluteo',
                'strides' => '3x100m aceleraciones progresivas',
                'main_set' => "5x1000m a 5' los 1000m, rec. 2' trote suave",
                'strength' => "10' core: plancha 2x45",
                'cool_down' => "10' trote de recuperacion a 7:00/7:30 min/km",
                'elongation' => "12' elongacion estatica",
                'notes' => 'Cada 1000m igual o mas rapida. Recupero activo entre rep.',
                'estimated_minutes' => 50,
                'tip_ids' => [3],
            ]
        ],
        [
            'name' => '5K - Regenerativo',
            'type' => 'Recuperación',
            'blocks' => [
                'warm_up' => "5' trote muy suave a 7:25 min/km",
                'mobility' => 'Respiracion y movilidad articular ligera',
                'drills' => '',
                'strides' => '',
                'main_set' => "20' trote suave a 7:00/7:30 min/km",
                'strength' => "10' core ligero: plancha 2x30",
                'cool_down' => "5' trote suave",
                'elongation' => "10' elongacion suave",
                'notes' => 'Sesion de activacion sin fatigar.',
                'estimated_minutes' => 35,
                'tip_ids' => [4],
            ]
        ],
        [
            'name' => '5K - Bajas y Cambios de Ritmo',
            'type' => 'Series',
            'blocks' => [
                'warm_up' => "20' entrada en calor a 6:50/7:30 min/km + movilidad",
                'mobility' => 'Tobillos y cadera',
                'drills' => 'Tecnica de carrera',
                'strides' => "5 cambios de ritmo: 3' trote al 70% x 2' trote al 40%",
                'main_set' => "6x100m al 70/75% capacidad, rec. 40' caminando + pendientes 10x200m subida buscando frecuencia",
                'strength' => "Circuito 2 vueltas: 20 abdominales, 12 espalda natacion, 10 fuerza brazos, 10 media sentadillas, 10 punta de pie, 20 oblicuos, 10 subir al banco, 10 espalda alternado, 20 paso al frente, 20 rodilla al pecho",
                'cool_down' => "10' trote a 6:50/7:30 min/km + elongacion 20/25'",
                'elongation' => "20/25' elongacion estatica",
                'notes' => 'Cambios de ritmo progresivos. Sin forzar el trote lento.',
                'estimated_minutes' => 50,
                'tip_ids' => [5],
            ]
        ],
    ],
    '10K' => [
        [
            'name' => '10K - Interval 8x1000',
            'type' => 'Series',
            'blocks' => [
                'warm_up' => "10' trote a 6:40/7:20 min/km + 6x100m movilidad + 4x100m strides",
                'mobility' => 'Cadera, isquiotibiales, gluteos',
                'drills' => 'Alta rodilla, talones al gluteo, bounds',
                'strides' => '4x100m progresivos',
                'main_set' => "8x1000m entre 5'25\"\" y 5'30\"\"/km, rec. 1'45\"\" trote suave",
                'strength' => "15' core: plancha 3x50",
                'cool_down' => "10' trote de recuperacion a 7:00/7:30 min/km",
                'elongation' => "15' elongacion estatica: isquiotibiales, cuadraces",
                'notes' => 'Ritmo objetivo 10K. Recupero corto entre intervalos.',
                'estimated_minutes' => 60,
                'tip_ids' => [3],
            ]
        ],
        [
            'name' => '10K - Fondo Largo 14km',
            'type' => 'Fondo',
            'blocks' => [
                'warm_up' => "8' trote suave a 6:40/7:20 min/km + 4x100m movilidad",
                'mobility' => 'Tobillos y cadera',
                'drills' => 'Footwork y pasos altos',
                'strides' => '4x80m progresivos',
                'main_set' => "14km a 6:40/7:20 min/km",
                'strength' => "Tablilla isometrica, espalda alternado, buda, equilibrio una pierna, abdominales, espalda manos costado, flexion cintura, paso al costado",
                'cool_down' => "8' trote suave a 6:40/7:20 min/km",
                'elongation' => "15' elongacion general",
                'notes' => 'Constantes en ritmo. No frenar en subidas.',
                'estimated_minutes' => 75,
                'tip_ids' => [2],
            ]
        ],
        [
            'name' => '10K - Series 6x2000',
            'type' => 'Series',
            'blocks' => [
                'warm_up' => "10' trote a 6:40/7:20 min/km + 6x100m movilidad + 4x100m strides",
                'mobility' => 'Cadera, isquiotibiales',
                'drills' => 'Tecnica de carrera y bounds',
                'strides' => '4x100m aceleraciones',
                'main_set' => "6x2000m a 5'25-5'30/km, rec. 2'45\" trote suave",
                'strength' => "15' core: plancha 3x50 + hip thrusts",
                'cool_down' => "10' trote de recuperacion a 7:00/7:30 min/km",
                'elongation' => "15' elongacion estatica",
                'notes' => 'Cada 2000m igual o mas rapida.',
                'estimated_minutes' => 70,
                'tip_ids' => [3],
            ]
        ],
        [
            'name' => '10K - Fondo + Fortalecimiento',
            'type' => 'Fondo',
            'blocks' => [
                'warm_up' => "8' trote suave a 6:40/7:20 min/km + movilidad",
                'mobility' => 'Cadera y tobillos',
                'drills' => 'Footwork suave',
                'strides' => '4x80m progresivos',
                'main_set' => "6km trote a 6:40/7:20 min/km + circuito fortalecimiento 2 vueltas x 40\"",
                'strength' => "Circuito 2 vueltas: abdominales, espalda alternado, fuerza brazos, media sentadillas, separar y juntar, abdominal rodilla pecho, espalda natacion, paso al frente, tijera sajital, oblicuos, punta de pie, circunducciones brazos",
                'cool_down' => "40' trote a 6:40/7:20 min/km + elongacion 20/25'",
                'elongation' => "20/25' elongacion estatica",
                'notes' => 'Fondo + refuerzo muscular integrado.',
                'estimated_minutes' => 80,
                'tip_ids' => [2],
            ]
        ],
        [
            'name' => '10K - Regenerativo',
            'type' => 'Recuperación',
            'blocks' => [
                'warm_up' => "5' trote muy suave",
                'mobility' => 'Movilidad articular ligera',
                'drills' => '',
                'strides' => '',
                'main_set' => "25' trote suave a 5:10/km (recuperacion)",
                'strength' => "10' core ligero: plancha 2x30",
                'cool_down' => "5' trote suave",
                'elongation' => "10' elongacion suave",
                'notes' => 'Activacion sin carga.',
                'estimated_minutes' => 40,
                'tip_ids' => [4],
            ]
        ],
    ],
    '21K' => [
        [
            'name' => '21K - Media Maraton 18km',
            'type' => 'Fondo',
            'blocks' => [
                'warm_up' => "10' trote suave a 6:00/6:30 min/km + 6x100m movilidad + 4x100m strides",
                'mobility' => 'Cadera, isquiotibiales, gluteos',
                'drills' => 'Alta rodilla, talones al gluteo, bounds',
                'strides' => '4x100m progresivos',
                'main_set' => "18km a ritmo 5'30-5'45/km (media maraton)",
                'strength' => "15' core: plancha 3x50 + sentadillas",
                'cool_down' => "10' trote suave a 6:50/7:30 min/km",
                'elongation' => "15' elongacion estatica completa",
                'notes' => 'Control de ritmo desde el inicio. No saldr rapido.',
                'estimated_minutes' => 85,
                'tip_ids' => [2],
            ]
        ],
        [
            'name' => '21K - Interval 5x2000',
            'type' => 'Series',
            'blocks' => [
                'warm_up' => "10' trote a 6:30/6:00 min/km + 6x100m movilidad + 4x100m strides",
                'mobility' => 'Cadera, isquiotibiales',
                'drills' => 'Tecnica de carrera y bounds',
                'strides' => '4x100m aceleraciones',
                'main_set' => "5x2000m a 5' los 1000m (ritmo 10K), rec. 2' trote",
                'strength' => "15' core: plancha 3x50",
                'cool_down' => "10' trote de recuperacion a 6:50/7:30 min/km",
                'elongation' => "15' elongacion estatica",
                'notes' => 'Intervalos de calidad. Recupero entre ellos corto.',
                'estimated_minutes' => 70,
                'tip_ids' => [3],
            ]
        ],
        [
            'name' => '21K - Series 10x1000',
            'type' => 'Series',
            'blocks' => [
                'warm_up' => "12' trote a 6:00/6:30 min/km + 8x100m movilidad + 5x100m strides",
                'mobility' => 'Cadera, isquiotibiales, gluteos',
                'drills' => 'Alta rodilla, talones al gluteo, skips',
                'strides' => '5x100m progresivos',
                'main_set' => "10x1000m a 5' los 1000m, rec. 1'30\" trote suave",
                'strength' => "15' core: plancha 3x50 + hip thrusts",
                'cool_down' => "10' trote suave a 6:50/7:30 min/km",
                'elongation' => "15' elongacion estatica",
                'notes' => 'Umbral aerobic. Recupero activo entre cada 1000.',
                'estimated_minutes' => 75,
                'tip_ids' => [3],
            ]
        ],
        [
            'name' => '21K - Fondo Largo 10km + Fuerte',
            'type' => 'Fondo',
            'blocks' => [
                'warm_up' => "20' trote a 6:00/6:30 min/km + movilidad articular",
                'mobility' => 'Cadera, isquiotibiales',
                'drills' => 'Tecnica de carrera',
                'strides' => '6 rectas x 100m al 70/75%, rec. 40" caminando + elongacion',
                'main_set' => "10km trote a 6:30/7:10 min/km",
                'strength' => "Fortalecimiento 2 vueltas x 40\" (sin descanso entre ejercicio): abdominales, espalda natacion, paso al frente, balanceo brazos, oblicuos, espalda comun, punta de pie, circunducciones, rodilla al pecho, fuerza brazos, media sentadillas, gluteos",
                'cool_down' => "3km trote suave a 6:50/7:30 min/km + elongacion 20/25'",
                'elongation' => "20/25' elongacion estatica",
                'notes' => 'Fondo largo con fuerte integrado. Ritmo constante.',
                'estimated_minutes' => 80,
                'tip_ids' => [2],
            ]
        ],
        [
            'name' => '21K - Regenerativo',
            'type' => 'Recuperación',
            'blocks' => [
                'warm_up' => "5' trote muy suave",
                'mobility' => 'Movilidad articular ligera',
                'drills' => '',
                'strides' => '',
                'main_set' => "30' trote suave a 5:00/km (recuperacion)",
                'strength' => "10' core ligero: plancha 2x30",
                'cool_down' => "5' trote suave",
                'elongation' => "10' elongacion suave",
                'notes' => 'Recuperacion activa.',
                'estimated_minutes' => 45,
                'tip_ids' => [4],
            ]
        ],
    ],
    '42K' => [
        [
            'name' => '42K - Maraton 30km',
            'type' => 'Fondo',
            'blocks' => [
                'warm_up' => "10' trote a 6:00/6:10 min/km + 6x100m movilidad + 4x100m strides",
                'mobility' => 'Cadera, isquiotibiales, gluteos',
                'drills' => 'Alta rodilla, talones al gluteo, bounds',
                'strides' => '4x100m progresivos',
                'main_set' => "30km a ritmo maraton ~5'00-5:10/km",
                'strength' => "20' core: plancha 3x50 + sentadillas + hip thrusts",
                'cool_down' => "10' trote suave a 6:50/7:30 min/km",
                'elongation' => "20' elongacion estatica completa",
                'notes' => 'Ritmo constante. Abastecimiento practicado si aplica.',
                'estimated_minutes' => 120,
                'tip_ids' => [2],
            ]
        ],
        [
            'name' => '42K - Interval 6x2000',
            'type' => 'Series',
            'blocks' => [
                'warm_up' => "10' trote a 6:00/6:30 min/km + 6x100m movilidad + 4x100m strides",
                'mobility' => 'Cadera, isquiotibiales',
                'drills' => 'Tecnica de carrera y bounds',
                'strides' => '4x100m aceleraciones',
                'main_set' => "6x2000m a 5' los 1000m (ritmo 10K), rec. 2' trote",
                'strength' => "15' core: plancha 3x50 + hip thrusts",
                'cool_down' => "10' trote de recuperacion a 6:50/7:30 min/km",
                'elongation' => "15' elongacion estatica",
                'notes' => 'Calidad para llegar al 42K con fuerza.',
                'estimated_minutes' => 75,
                'tip_ids' => [3],
            ]
        ],
        [
            'name' => '42K - Series 10x1000',
            'type' => 'Series',
            'blocks' => [
                'warm_up' => "12' trote a 6:00/6:30 min/km + 8x100m movilidad + 5x100m strides",
                'mobility' => 'Cadera, isquiotibiales, gluteos',
                'drills' => 'Alta rodilla, talones al gluteo, skips',
                'strides' => '5x100m progresivos',
                'main_set' => "10x1000m a 5' los 1000m, rec. 1'30\" trote suave",
                'strength' => "15' core: plancha 3x50 + hip thrusts",
                'cool_down' => "10' trote suave a 6:50/7:30 min/km",
                'elongation' => "15' elongacion estatica",
                'notes' => 'Umbral aerobic para fondo de maraton.',
                'estimated_minutes' => 75,
                'tip_ids' => [3],
            ]
        ],
        [
            'name' => '42K - Fondo Largo 28km',
            'type' => 'Fondo',
            'blocks' => [
                'warm_up' => "10' trote a 6:00/6:30 min/km + 6x100m movilidad + 4x100m strides",
                'mobility' => 'Cadera, isquiotibiales, gluteos',
                'drills' => 'Footwork y pasos altos',
                'strides' => '4x80m progresivos',
                'main_set' => "28km a ritmo lento-medio 6:20/6:50 min/km",
                'strength' => "15' core: plancha 3x45 + sentadillas",
                'cool_down' => "10' trote suave a 6:50/7:30 min/km",
                'elongation' => "20' elongacion estatica completa",
                'notes' => 'Fondo largo sin prisas. Ritmo de fondo.',
                'estimated_minutes' => 120,
                'tip_ids' => [2],
            ]
        ],
        [
            'name' => '42K - Series 10x400m',
            'type' => 'Series',
            'blocks' => [
                'warm_up' => "12' trote a 6:00/6:30 min/km + 8x100m movilidad + 5x100m strides",
                'mobility' => 'Cadera, isquiotibiales, gluteos',
                'drills' => 'Tecnica de carrera',
                'strides' => '5x100m progresivos',
                'main_set' => "10x400m en 2' los 400m, rec. 2' caminando agil",
                'strength' => "Fortalecimiento 2 series x 40\"",
                'cool_down' => "15' trote suave a 6:20/6:50 min/km + elongacion",
                'elongation' => "20/25' elongacion estatica",
                'notes' => 'Rapidez y tecnica para maraton.',
                'estimated_minutes' => 60,
                'tip_ids' => [3],
            ]
        ],
        [
            'name' => '42K - Regenerativo',
            'type' => 'Recuperación',
            'blocks' => [
                'warm_up' => "5' trote muy suave",
                'mobility' => 'Movilidad articular ligera',
                'drills' => '',
                'strides' => '',
                'main_set' => "35' trote suave a 5:00/km (recuperacion)",
                'strength' => "10' core ligero: plancha 2x30",
                'cool_down' => "5' trote suave",
                'elongation' => "10' elongacion suave",
                'notes' => 'Recuperacion activa post-fondo o post-series.',
                'estimated_minutes' => 50,
                'tip_ids' => [4],
            ]
        ],
    ],
];

$inserted = 0;
$skipped = 0;

foreach ($coaches as $coach) {
    foreach ($plans as $distance => $templates) {
        foreach ($templates as $tpl) {
            $structure = TrainingStructure::toJson($tpl['blocks']);
            $stmt = $db->prepare("SELECT id FROM templates WHERE coach_id = ? AND name = ? AND type = ?");
            $stmt->execute([$coach['id'], $tpl['name'], $tpl['type']]);
            if ($stmt->fetch()) {
                $skipped++;
                continue;
            }
            if (!$dryRun) {
                $stmt2 = $db->prepare("INSERT INTO templates (coach_id, name, type, block_type, structure) VALUES (?, ?, ?, ?, ?)");
                $stmt2->execute([
                    $coach['id'],
                    $tpl['name'],
                    $tpl['type'],
                    'split',
                    $structure
                ]);
            }
            $inserted++;
        }
    }
}

$coachNames = implode(', ', array_column($coaches, 'username'));
echo "Coaches: $coachNames\n";
echo "Plantillas procesadas: $inserted insertadas, $skipped ya existentes\n";
echo "Total por distancia: " . implode(', ', array_map(fn($k, $v) => "$k = " . count($v), array_keys($plans), $plans)) . "\n";

$stmt = $db->prepare("SELECT COUNT(*) as c FROM templates WHERE coach_id = ?");
foreach ($coaches as $c) {
    $stmt->execute([$c['id']]);
    $row = $stmt->fetch();
    echo "  {$c['username']}: {$row['c']} plantillas\n";
}
echo "\nHecho.\n";
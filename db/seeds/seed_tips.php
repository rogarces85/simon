<?php
/**
 * Seed de tips de entrenamiento basados en "Tips para el plan de 42km.pdf".
 * Crea 19 tips categorizados y los vincula a plantillas existentes vía tip_ids.
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';

$db = Database::getInstance();

$dryRun = in_array('--dry-run', $argv, true);
$allowRemote = in_array('--allow-remote', $argv, true);

// Salvaguarda: por defecto este seed solo toca la base local. Con
// --allow-remote se permite cargar los tips en produccion (Hostinger).
if (DB_HOST !== 'localhost' && DB_HOST !== '127.0.0.1') {
    if (!$allowRemote) {
        fwrite(STDERR, "Abortado: DB_HOST es '" . DB_HOST . "'. Solo para base local (usa --allow-remote para cargas intencionales a produccion).\n");
        exit(1);
    }
    fwrite(STDERR, "AVISO: cargando en base REMOTA '" . DB_NAME . "' en '" . DB_HOST . "'\n");
}

$tips = [
    [
        'title' => 'Escucha a tu cuerpo: enfermedad y fatiga',
        'content' => "Si estás enfermo o te sientes cansado evita ese día seguir el plan y pásalo sin agregar kms al otro día, es algo normal no poder seguir un plan 100% y no por eso no lograrás el resultado planificado si sigues en por lo menos un 88% lo indicado. Al revés, si entrenas esos días que te sientes mal puede ser que llegues a sobreentrenarte y generar el efecto inverso con las cargas.",
        'category' => 'salud',
        'distances' => '5K,10K,21K,42K',
    ],
    [
        'title' => 'El descanso es fundamental',
        'content' => "El descanso es fundamental que no te lo saltes, al revés verás cómo asimilas después de esos días tus trabajos, descansa tus 8hs diarias para mayor recuperación.",
        'category' => 'recuperacion',
        'distances' => '5K,10K,21K,42K',
    ],
    [
        'title' => 'Hidratación mínima diaria',
        'content' => "Es importante la hidratación, recuerda que el mínimo es de 2 litros diarios, aún sin sentir sed.",
        'category' => 'nutricion',
        'distances' => '5K,10K,21K,42K',
    ],
    [
        'title' => 'Suplementación con sal (sodio) desde el inicio',
        'content' => "Empieza a consumir cápsulas de sal al comenzar el entrenamiento de maratón. El sodio y el cloruro resultan vitales para mantener el volumen sanguíneo y prestan soporte a los músculos y la función nerviosa. La sal promueve adicionalmente la absorción de calcio.",
        'category' => 'nutricion',
        'distances' => '21K,42K',
    ],
    [
        'title' => 'Geles: entrena su consumo en tiradas largas',
        'content' => "El consumo de geles es esencial, puedes empezar a entrenar a tu cuerpo al consumo. Importante practicar en las tiradas largas su consumo. Ver dónde los vas a llevar, lo que te cuesta abrirlos y tomarlos (si son más densos costará más que aquellos hydro que son más líquidos). También será muy diferente si no es tu primera maratón o si estás acostumbrado a consumirlos. En ese caso, todo será más fácil, pero igualmente tendremos que volver a probar cómo los toleramos.",
        'category' => 'nutricion',
        'distances' => '21K,42K',
    ],
    [
        'title' => 'Alimentación variada y equilibrada para maratón',
        'content' => "Recuerda llevar una alimentación, la Maratón es la prueba atlética por excelencia. Realizar una dieta variada y equilibrada, es decir, hacer una correcta ingesta diaria de energía y líquidos, tanto en calidad como en cantidad, siempre adaptada y personalizada a las características de cada atleta. Las necesidades de hidratos de carbono (cereales, arroz, legumbres, pan, pasta, patata…) están incrementadas, la dieta será alta en este nutriente y la recomendación se basa en ingerir a lo largo del día entre 5-7 g/kg de peso corporal, cuando los entrenamientos duren una hora; y por encima de 7g por kilo si superan la hora de duración. El objetivo es poder rendir al máximo, retrasar la aparición del cansancio y la fatiga, recuperar los depósitos de glucógeno después del entrenamiento y, por lo tanto, disminuir el estrés físico del organismo.",
        'category' => 'nutricion',
        'distances' => '21K,42K',
    ],
    [
        'title' => 'Evita problemas gastrointestinales en carrera',
        'content' => "Cómo evitar problemas estomacales en la maratón. Tanto en el entrenamiento como en el transcurso de la prueba, ciertos atletas sufren alteraciones gastrointestinales o problemas más graves como diarrea y vómitos que conllevan una disminución del rendimiento. Algunas recomendaciones para evitar estas molestias gastrointestinales son: realizar la última comida sólida mínimo 3 horas antes de la maratón. Probar la tolerancia a los distintos alimentos y evitar antes de la prueba alimentos ricos en fibras, grasas o proteínas.",
        'category' => 'nutricion',
        'distances' => '21K,42K',
    ],
    [
        'title' => 'Carga de carbohidratos 2-3 días previos (9-12 g/kg)',
        'content' => "Los 2-3 días previos a la maratón es conveniente una sobrecarga de hidratos de carbono de 9-12 g/kg de peso corporal para cubrir las necesidades de energía y llenar al máximo los depósitos de glucógeno. Así pues, el día antes se tomarán abundantes hidratos de carbono, cierta carga proteica digerible y con poca grasa (pollo o pescado blanco) y se evitarán alimentos grasos ya que enlentecen la digestión, picantes, fibrosos y que fermentan en el intestino como los alimentos integrales, las verduras y las legumbres. Beber agua a lo largo del día.",
        'category' => 'nutricion',
        'distances' => '42K',
    ],
    [
        'title' => 'Hidratación pre-competición (5-7 ml/kg)',
        'content' => "Asimismo, conviene beber lentamente unos 5-7 ml/kg de peso corporal, es decir, entre 2-3 vasos de líquido.",
        'category' => 'nutricion',
        'distances' => '21K,42K',
    ],
    [
        'title' => 'Recuperación nutricional post-maratón (4:1 carbs/proteína)',
        'content' => "Qué comer después de la maratón. Una vez terminada la maratón hay que prestar atención a la recuperación para asegurar que esta sea lo más completa posible. A nivel nutricional se recomienda tomar un batido con una proporción de hidratos de carbono/proteína de 4:1 y con contenido en sodio, siempre dentro de la hora posterior a la finalización de la competición. Después, la comida deberá garantizar el restablecimiento del balance hidromineral, ser rica en verduras y fruta para disminuir la acidosis, contener abundancia de hidratos de carbono para recuperar los niveles de glucógeno y algún alimento proteico para favorecer la síntesis de proteínas.",
        'category' => 'recuperacion',
        'distances' => '42K',
    ],
    [
        'title' => 'Carb-loading y hidratación 2-3 días antes (repaso)',
        'content' => "2-3 días antes de la maratón realizar una dieta de sobrecarga de hidratos de carbono (ver más abajo un ejemplo). Incrementa en 1 o 2 vasos la ingesta de líquido diaria. Asegura una ingesta de agua de 1,5L mínimo.",
        'category' => 'nutricion',
        'distances' => '42K',
    ],
    [
        'title' => 'No cambies nada el día de la carrera',
        'content' => "No pruebes nada nuevo y no cambies tu rutina habitual. Es el momento de hacer aquello que sabes que te funciona y te sienta bien.",
        'category' => 'general',
        'distances' => '5K,10K,21K,42K',
    ],
    [
        'title' => 'Evita alimentos flatulentos y picantes',
        'content' => "Evita tomar alimentos picantes y verduras que te puedan resultar flatulentas como coliflor, brócoli, coles de Bruselas, alcachofas o puerro.",
        'category' => 'nutricion',
        'distances' => '21K,42K',
    ],
    [
        'title' => 'Reduce alimentos crudos, prioriza cocidos',
        'content' => "Reduce el consumo de alimentos crudos como la ensalada. Combínalo con verdura cocida en crema, vapor o plancha.",
        'category' => 'nutricion',
        'distances' => '21K,42K',
    ],
    [
        'title' => 'Evita legumbres y fibra alta 1-2 días antes',
        'content' => "En estos días evita tomar legumbres (lentejas, garbanzos…) incluyendo los guisantes. Así como los alimentos muy ricos en fibra o integrales, 1 o 2 días antes de la maratón.",
        'category' => 'nutricion',
        'distances' => '42K',
    ],
    [
        'title' => 'Reduce grasas: embutidos, fritos, salsas, bollería',
        'content' => "Reduce la ingesta de grasas en forma de embutidos tipo chorizo o salchichón, quesos curados, fritos y rebozados, salsas como mayonesa o bollería.",
        'category' => 'nutricion',
        'distances' => '21K,42K',
    ],
    [
        'title' => 'Come despacio y mastica bien',
        'content' => "Come despacio.",
        'category' => 'nutricion',
        'distances' => '5K,10K,21K,42K',
    ],
    [
        'title' => 'Cortarse las uñas una semana antes',
        'content' => "Cortarse las uñas una semana antes de la competencia, evitamos un lastimado superficial.",
        'category' => 'general',
        'distances' => '5K,10K,21K,42K',
    ],
    [
        'title' => 'Evita el alcohol',
        'content' => "Evitar el consumo de alcohol.",
        'category' => 'general',
        'distances' => '5K,10K,21K,42K',
    ],
    [
        'title' => 'No estrenes ropa ni calzado el día de la carrera',
        'content' => "No estrenes nada de ropa ese día, todo tiene que haber sido usado en entrenamiento (calzas, calcetines, shorts, zapatillas, etc).",
        'category' => 'general',
        'distances' => '5K,10K,21K,42K',
    ],
    [
        'title' => 'Vaselina en pliegues para evitar roces',
        'content' => "Utilizar vaselina en todos los pliegues del cuerpo, evitamos roces.",
        'category' => 'general',
        'distances' => '21K,42K',
    ],
];

echo "Base: " . DB_NAME . " en " . DB_HOST . "\n";
if ($dryRun) {
    echo "MODO DRY RUN\n";
}
echo "\n";

$inserted = 0;
$skipped = 0;

foreach ($tips as $tip) {
    $stmt = $db->prepare("SELECT id FROM tips WHERE title = ?");
    $stmt->execute([$tip['title']]);
    if ($stmt->fetch()) {
        $skipped++;
        continue;
    }
    if (!$dryRun) {
        $stmt2 = $db->prepare("INSERT INTO tips (title, content, category, applicable_distances) VALUES (?, ?, ?, ?)");
        $stmt2->execute([
            $tip['title'],
            $tip['content'],
            $tip['category'],
            $tip['distances'],
        ]);
    }
    $inserted++;
}

echo "Tips procesados: $inserted insertados, $skipped ya existentes\n";

if (!$dryRun) {
    // Vincular tips a plantillas según tip_ids del seed de plantillas
    echo "\nVinculando tips a plantillas...\n";
    $stmt = $db->query("SELECT id, name, structure FROM templates");
    $templates = $stmt->fetchAll();
    
    $tipMap = [];
    $stmt = $db->query("SELECT id, title FROM tips");
    foreach ($stmt->fetchAll() as $t) {
        $tipMap[$t['title']] = $t['id'];
    }
    
    // Mapear categorías a tip_ids según lo definido en seed_training_templates.php
    // tip_ids usados: [1,2,3,4,5] -> índices 0-4 en array tips
    $categoryTipIds = [
        'salud' => [1],           // tip 1
        'recuperacion' => [2, 10], // tips 2, 10
        'nutricion' => [3, 4, 5, 6, 7, 8, 12, 13, 14, 15, 16], // tips 3-7, 12-16
        'general' => [11, 17, 18, 19, 20], // tips 11, 17-20
    ];
    
    foreach ($templates as $tpl) {
        $structure = json_decode($tpl['structure'], true);
        $currentTipIds = $structure['tip_ids'] ?? [];
        
        // Resolver tip_ids por posición en array $tips (1-indexed en seed)
        $resolved = [];
        foreach ($currentTipIds as $idx) {
            if ($idx >= 1 && $idx <= count($tips)) {
                $title = $tips[$idx - 1]['title'];
                if (isset($tipMap[$title])) {
                    $resolved[] = $tipMap[$title];
                }
            }
        }
        
        if ($resolved) {
            $stmt2 = $db->prepare("UPDATE templates SET structure = JSON_SET(structure, '$.tip_ids', ?) WHERE id = ?");
            $stmt2->execute([json_encode(array_values(array_unique($resolved))), $tpl['id']]);
        }
    }
    echo "Vinculación completada.\n";
}

echo "\nHecho.\n";
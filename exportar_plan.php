<?php
/**
 * Exporta el plan de entrenamiento a PDF.
 *
 * GET exportar_plan.php
 *   mode=week  (default)  -> detalle de una semana, una pagina por sesion
 *   mode=full             -> vista tipo plan impreso, una fila por semana
 *   week_start=YYYY-MM-DD -> lunes de la semana inicial
 *   weeks=N               -> semanas para mode=full (default 4, max 16)
 *   athlete_id=N          -> requerido para coach/admin
 */
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'models/TrainingStructure.php';
require_once 'models/Workout.php';
require_once 'models/User.php';
require_once 'includes/PDFPlanExporter.php';

Auth::init();
$user = Auth::user();

// --- Resolver atleta ------------------------------------------------------
$role = $user['role'] ?? 'athlete';

if ($role === 'coach' || $role === 'admin') {
    $athleteId = (int) ($_GET['athlete_id'] ?? 0);
    if ($athleteId <= 0) {
        http_response_code(400);
        exit('Falta athlete_id');
    }
    // Un coach solo exporta planes de sus atletas.
    if ($role === 'coach' && !User::belongsToCoach($athleteId, $user['id'])) {
        http_response_code(403);
        exit('Ese atleta no pertenece a tu equipo');
    }
} else {
    $athleteId = (int) $user['id'];
}

$athlete = User::getById($athleteId);
if (!$athlete) {
    http_response_code(404);
    exit('Atleta no encontrado');
}

// --- Parametros -----------------------------------------------------------
$mode = ($_GET['mode'] ?? 'week') === 'full' ? 'full' : 'week';
$weekStartInput = $_GET['week_start'] ?? '';

// Normalizar a lunes de esa semana
try {
    $start = $weekStartInput !== '' ? new DateTime($weekStartInput) : new DateTime('monday this week');
} catch (Exception $e) {
    $start = new DateTime('monday this week');
}
$dayOfWeek = (int) $start->format('N');
if ($dayOfWeek !== 1) {
    $start->modify('-' . ($dayOfWeek - 1) . ' days');
}

$weeksCount = $mode === 'full'
    ? min(16, max(1, (int) ($_GET['weeks'] ?? 4)))
    : 1;

// --- Cargar workouts del rango -------------------------------------------
$end = (clone $start)->modify('+' . $weeksCount . ' weeks -1 day');
$workouts = Workout::getByAthlete($athleteId, $start->format('Y-m-d'), $end->format('Y-m-d'));

// Indexar por fecha normalizada (la columna date puede ser DATETIME). Cada
// fecha guarda una LISTA de sesiones: un dia puede tener varios entrenamientos.
$byDate = [];
foreach ($workouts as $w) {
    $dateKey = (new DateTime($w['date']))->format('Y-m-d');
    $byDate[$dateKey][] = $w;
}

// --- Armar estructura de semanas ------------------------------------------
$weeks = [];
$tipIds = [];
for ($i = 0; $i < $weeksCount; $i++) {
    $weekStart = (clone $start)->modify("+$i weeks");
    $days = [];
    $dates = [];
    for ($j = 0; $j < 7; $j++) {
        $date = (clone $weekStart)->modify("+$j days");
        $dates[$j] = $date->format('Y-m-d');
        $days[$j] = $byDate[$dates[$j]] ?? [];

        foreach ($days[$j] as $w) {
            if (!empty($w['structure'])) {
                $struct = is_array($w['structure'])
                    ? $w['structure']
                    : (json_decode($w['structure'], true) ?: []);
                foreach ($struct['tip_ids'] ?? [] as $tid) {
                    $tipIds[(int) $tid] = true;
                }
            }
        }
    }
    $weeks[] = [
        'label' => (string) ($i + 1),
        'days' => $days,
        'dates' => $dates,
    ];
}

// --- Tips asociados --------------------------------------------------------
$tipsById = [];
if ($tipIds) {
    $db = Database::getInstance();
    $ids = array_keys($tipIds);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare("SELECT title, content FROM tips WHERE id IN ($placeholders) AND is_active = 1");
    $stmt->execute($ids);
    $tipsById = $stmt->fetchAll();
}

// --- Generar PDF -----------------------------------------------------------
$pdf = new PDFPlanExporter($mode === 'full' ? 'L' : 'L');
$title = $mode === 'full'
    ? "Plan de Entrenamiento ($weeksCount semanas)"
    : 'Plan de Entrenamiento - Semana del ' . $start->format('d/m/Y');
$pdf->setMeta($athlete['name'] ?? '', $athlete['coach_name'] ?? '', $title);

if ($mode === 'full') {
    $pdf->exportMultiWeek($weeks);
} else {
    $pdf->exportWeekDetail($weeks[0], $tipsById);
}

$filename = 'plan_' . preg_replace('/[^a-z0-9]+/i', '_', $athlete['name'] ?? 'atleta')
    . '_' . $start->format('Ymd')
    . ($mode === 'full' ? '_' . $weeksCount . 'sem' : '')
    . '.pdf';

$pdf->outputPDF($filename);
exit;
<?php
require_once 'includes/auth.php';
require_once 'includes/Csrf.php';
require_once 'includes/db.php';
require_once 'includes/Mailer.php';
require_once 'models/User.php';
require_once 'models/Workout.php';
require_once 'models/Notification.php';
Auth::init();
Auth::requireRole('coach');

$coach = Auth::user();
$db = Database::getInstance();

// Get athletes for dropdown
$athletes = User::getByCoachId($coach['id']);

// Handle form submissions
$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        header('Location: generar_plan.php?csrf_error=1');
        exit;
    }

    // Generate plan
    if ($_POST['action'] === 'generate_plan') {
        $athleteId = $_POST['athlete_id'];
        $weekStart = $_POST['week_start'];
        $days = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
        $createdWorkouts = [];

        // Sin esta comprobacion un coach podia generarle un plan al atleta de otro.
        if (!User::belongsToCoach($athleteId, $coach['id'])) {
            header('Location: generar_plan.php?denied=1');
            exit;
        }

        foreach ($days as $i => $day) {
            $templateId = $_POST['template_' . $day] ?? null;
            if ($templateId) {
                // El coach_id evita usar las plantillas de otro coach.
                $stmt = $db->prepare("SELECT * FROM templates WHERE id = ? AND coach_id = ?");
                $stmt->execute([$templateId, $coach['id']]);
                $template = $stmt->fetch();

                if ($template) {
                    $workoutDate = date('Y-m-d', strtotime($weekStart . " + $i days"));
                    $workoutData = [
                        'athlete_id' => $athleteId,
                        'date' => $workoutDate,
                        'type' => $template['type'],
                        'description' => $template['name'],
                        'status' => 'pending',
                        'structure' => $template['structure'],
                        'delivery_status' => 'sent'
                    ];
                    Workout::create($workoutData);
                    $createdWorkouts[] = [
                        'date' => $workoutDate,
                        'type' => $template['type'],
                        'description' => $template['name']
                    ];
                }
            }
        }

        if (!empty($createdWorkouts)) {
            $athlete = User::getById($athleteId);
            if ($athlete && $athlete['username']) {
                Mailer::sendNewPlanNotification(
                    $athlete['username'],
                    $athlete['name'],
                    $coach['name'],
                    $weekStart,
                    $createdWorkouts
                );
                $msg = "Nuevo plan de entrenamiento generado para la semana del " . (new DateTime($weekStart))->format('d/m/Y');
                Notification::create($athleteId, $msg, 'info');
            }
        }
        // Sin entrenamientos creados no hubo plan: puede ser que no se eligiera
        // ninguna plantilla, o que las elegidas no sean de este coach.
        if (empty($createdWorkouts)) {
            header('Location: generar_plan.php?vacio=1');
            exit;
        }

        header('Location: generar_plan.php?success=plan');
        exit;
    }

    // Generate Auto Plan
    if ($_POST['action'] === 'generate_auto_plan') {
        $athleteId = $_POST['athlete_id'];
        $distance = $_POST['distance'];
        $weekStart = $_POST['week_start'];

        if (!User::belongsToCoach($athleteId, $coach['id'])) {
            header('Location: generar_plan.php?tab=auto&denied=1');
            exit;
        }

        // Definir semanas por distancia
        $weeksMap = ['5K' => 8, '10K' => 10, '21K' => 12, '42K' => 16];
        $totalWeeks = $weeksMap[$distance] ?? 8;

        // Obtener plantillas del coach para esta distancia
        $stmt = $db->prepare("SELECT * FROM templates WHERE coach_id = ? AND name LIKE ? ORDER BY type, name");
        $stmt->execute([$coach['id'], $distance . '%']);
        $distanceTemplates = $stmt->fetchAll();

        if (empty($distanceTemplates)) {
            header('Location: generar_plan.php?tab=auto&error=no_templates');
            exit;
        }

        // Agrupar por tipo
        $byType = [];
        foreach ($distanceTemplates as $tpl) {
            $byType[$tpl['type']][] = $tpl;
        }

        // Patrones semanales estándar por distancia
        $patterns = [
            '5K' => [
                ['Lunes' => 'Series', 'Martes' => 'Fondo', 'Miércoles' => 'Recuperación', 'Jueves' => 'Series', 'Viernes' => 'Fondo', 'Sábado' => 'Fondo', 'Domingo' => 'Descanso'],
                ['Lunes' => 'Fondo', 'Martes' => 'Series', 'Miércoles' => 'Recuperación', 'Jueves' => 'Fondo', 'Viernes' => 'Series', 'Sábado' => 'Fondo', 'Domingo' => 'Descanso'],
                ['Lunes' => 'Recuperación', 'Martes' => 'Fondo', 'Miércoles' => 'Series', 'Jueves' => 'Recuperación', 'Viernes' => 'Fondo', 'Sábado' => 'Series', 'Domingo' => 'Descanso'],
                ['Lunes' => 'Fondo', 'Martes' => 'Recuperación', 'Miércoles' => 'Fondo', 'Jueves' => 'Series', 'Viernes' => 'Recuperación', 'Sábado' => 'Fondo', 'Domingo' => 'Descanso'],
            ],
            '10K' => [
                ['Lunes' => 'Series', 'Martes' => 'Fondo', 'Miércoles' => 'Recuperación', 'Jueves' => 'Series', 'Viernes' => 'Fondo', 'Sábado' => 'Fondo', 'Domingo' => 'Descanso'],
                ['Lunes' => 'Fondo', 'Martes' => 'Intervalos', 'Miércoles' => 'Recuperación', 'Jueves' => 'Fondo', 'Viernes' => 'Series', 'Sábado' => 'Fondo', 'Domingo' => 'Descanso'],
                ['Lunes' => 'Recuperación', 'Martes' => 'Fondo', 'Miércoles' => 'Series', 'Jueves' => 'Recuperación', 'Viernes' => 'Fondo', 'Sábado' => 'Intervalos', 'Domingo' => 'Descanso'],
                ['Lunes' => 'Fondo', 'Martes' => 'Recuperación', 'Miércoles' => 'Fondo', 'Jueves' => 'Intervalos', 'Viernes' => 'Recuperación', 'Sábado' => 'Fondo', 'Domingo' => 'Descanso'],
            ],
            '21K' => [
                ['Lunes' => 'Series', 'Martes' => 'Fondo', 'Miércoles' => 'Recuperación', 'Jueves' => 'Series', 'Viernes' => 'Fondo', 'Sábado' => 'Fondo', 'Domingo' => 'Descanso'],
                ['Lunes' => 'Fondo', 'Martes' => 'Intervalos', 'Miércoles' => 'Recuperación', 'Jueves' => 'Fondo', 'Viernes' => 'Series', 'Sábado' => 'Fondo', 'Domingo' => 'Descanso'],
                ['Lunes' => 'Recuperación', 'Martes' => 'Fondo', 'Miércoles' => 'Series', 'Jueves' => 'Recuperación', 'Viernes' => 'Fondo', 'Sábado' => 'Fondo', 'Domingo' => 'Descanso'],
                ['Lunes' => 'Fondo', 'Martes' => 'Recuperación', 'Miércoles' => 'Fondo', 'Jueves' => 'Intervalos', 'Viernes' => 'Recuperación', 'Sábado' => 'Fondo', 'Domingo' => 'Descanso'],
            ],
            '42K' => [
                ['Lunes' => 'Recuperación', 'Martes' => 'Fondo', 'Miércoles' => 'Series', 'Jueves' => 'Recuperación', 'Viernes' => 'Fondo', 'Sábado' => 'Fondo', 'Domingo' => 'Descanso'],
                ['Lunes' => 'Fondo', 'Martes' => 'Recuperación', 'Miércoles' => 'Intervalos', 'Jueves' => 'Fondo', 'Viernes' => 'Recuperación', 'Sábado' => 'Fondo', 'Domingo' => 'Descanso'],
                ['Lunes' => 'Recuperación', 'Martes' => 'Series', 'Miércoles' => 'Fondo', 'Jueves' => 'Recuperación', 'Viernes' => 'Fondo', 'Sábado' => 'Fondo', 'Domingo' => 'Descanso'],
                ['Lunes' => 'Fondo', 'Martes' => 'Recuperación', 'Miércoles' => 'Fondo', 'Jueves' => 'Series', 'Viernes' => 'Recuperación', 'Sábado' => 'Fondo', 'Domingo' => 'Descanso'],
            ],
        ];

        $pattern = $patterns[$distance] ?? $patterns['5K'];

        $allCreated = [];
        $templateUsage = [];

        for ($week = 0; $week < $totalWeeks; $week++) {
            $patternWeek = $pattern[$week % count($pattern)];
            $weekStartDate = date('Y-m-d', strtotime($weekStart . " +$week weeks"));
            $days = ['Lunes' => 0, 'Martes' => 1, 'Miércoles' => 2, 'Jueves' => 3, 'Viernes' => 4, 'Sábado' => 5, 'Domingo' => 6];
            $weekCreated = [];

            foreach ($patternWeek as $dayName => $type) {
                if ($type === 'Descanso') continue;

                if (empty($byType[$type])) continue;

                // Rotar plantillas del mismo tipo para variar
                $typeTemplates = $byType[$type];
                $usageKey = $type;
                $usageIdx = ($templateUsage[$usageKey] ?? 0) % count($typeTemplates);
                $template = $typeTemplates[$usageIdx];
                $templateUsage[$usageKey] = $usageIdx + 1;

                $workoutDate = date('Y-m-d', strtotime($weekStartDate . " +{$days[$dayName]} days"));
                $workoutData = [
                    'athlete_id' => $athleteId,
                    'date' => $workoutDate,
                    'type' => $template['type'],
                    'description' => $template['name'],
                    'status' => 'pending',
                    'structure' => $template['structure'],
                    'delivery_status' => 'sent'
                ];
                Workout::create($workoutData);
                $weekCreated[] = [
                    'date' => $workoutDate,
                    'day' => $dayName,
                    'type' => $template['type'],
                    'description' => $template['name']
                ];
            }
            $allCreated = array_merge($allCreated, $weekCreated);
        }

        if (!empty($allCreated)) {
            $athlete = User::getById($athleteId);
            if ($athlete && $athlete['username']) {
                Mailer::sendNewPlanNotification(
                    $athlete['username'],
                    $athlete['name'],
                    $coach['name'],
                    $weekStart,
                    $allCreated
                );
                $msg = "Plan automático de $totalWeeks semanas ($distance) generado para la semana del " . (new DateTime($weekStart))->format('d/m/Y');
                Notification::create($athleteId, $msg, 'info');
            }
        }

        header('Location: generar_plan.php?tab=auto&success=auto_plan&weeks=' . $totalWeeks);
        exit;
    }

    // Template CRUD
    if ($_POST['action'] === 'create_template') {
        $sql = "INSERT INTO templates (coach_id, name, type, block_type, structure) VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $coach['id'],
            $_POST['name'],
            $_POST['type'],
            $_POST['block_type'] ?? null,
            $_POST['structure'] ?? null
        ]);
        header('Location: generar_plan.php?tab=plantillas&success=template_created');
        exit;
    }

    if ($_POST['action'] === 'update_template') {
        $sql = "UPDATE templates SET name = ?, type = ?, block_type = ?, structure = ? WHERE id = ? AND coach_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $_POST['name'],
            $_POST['type'],
            $_POST['block_type'] ?? null,
            $_POST['structure'] ?? null,
            $_POST['template_id'],
            $coach['id']
        ]);
        header('Location: generar_plan.php?tab=plantillas&success=template_updated');
        exit;
    }

    if ($_POST['action'] === 'delete_template') {
        $sql = "DELETE FROM templates WHERE id = ? AND coach_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$_POST['template_id'], $coach['id']]);
        header('Location: generar_plan.php?tab=plantillas&success=template_deleted');
        exit;
    }
}

// Get templates
$stmt = $db->prepare("SELECT * FROM templates WHERE coach_id = ? ORDER BY type, name");
$stmt->execute([$coach['id']]);
$templates = $stmt->fetchAll();

$activeTab = $_GET['tab'] ?? 'plan';

include 'views/layout/header.php';
?>

<!-- Page Header -->
<div class="mb-8">
    <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">GENERAR PLAN DE ENTRENAMIENTO</h1>
    <p class="text-slate-500 mt-1">Crea planes semanales y gestiona tus plantillas de entrenamiento</p>
</div>

<?php if (isset($_GET['success'])): ?>
    <?php
    $msgs = [
        'plan' => 'Plan semanal generado exitosamente',
        'template_created' => 'Plantilla creada exitosamente',
        'template_updated' => 'Plantilla actualizada exitosamente',
        'template_deleted' => 'Plantilla eliminada exitosamente'
    ];
    $msg = $msgs[$_GET['success']] ?? 'Operación exitosa';
    ?>
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6 flex items-center gap-3" role="alert">
        <i data-lucide="check-circle" class="w-5 h-5"></i>
        <?php echo $msg; ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['denied'])): ?>
    <div class="bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 rounded-xl mb-6 flex items-center gap-3" role="alert">
        <i data-lucide="alert-triangle" class="w-5 h-5"></i>
        No se generó el plan: ese atleta no pertenece a tu equipo.
    </div>
<?php endif; ?>

<?php if (isset($_GET['vacio'])): ?>
    <div class="bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 rounded-xl mb-6 flex items-center gap-3" role="alert">
        <i data-lucide="alert-triangle" class="w-5 h-5"></i>
        No se creó ningún entrenamiento. Revisa que hayas elegido al menos una plantilla propia.
    </div>
<?php endif; ?>

<!-- Tabs -->
<div class="flex gap-1 mb-8 bg-slate-100 rounded-xl p-1 w-fit">
    <a href="generar_plan.php?tab=plan"
        class="px-6 py-3 rounded-lg font-semibold text-sm transition-all <?php echo $activeTab === 'plan' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'; ?>">
        <i data-lucide="calendar-plus" class="w-4 h-4 inline mr-2"></i>Generar Plan
    </a>
    <a href="generar_plan.php?tab=auto"
        class="px-6 py-3 rounded-lg font-semibold text-sm transition-all <?php echo $activeTab === 'auto' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'; ?>">
        <i data-lucide="sparkles" class="w-4 h-4 inline mr-2"></i>Plan Semanal Auto
    </a>
    <a href="generar_plan.php?tab=plantillas"
        class="px-6 py-3 rounded-lg font-semibold text-sm transition-all <?php echo $activeTab === 'plantillas' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'; ?>">
        <i data-lucide="file-text" class="w-4 h-4 inline mr-2"></i>Mis Plantillas
        <span
            class="ml-1 px-2 py-0.5 bg-slate-200 text-slate-600 rounded-full text-xs"><?php echo count($templates); ?></span>
    </a>
</div>

<?php if ($activeTab === 'plan'): ?>
    <!-- ========== TAB: GENERAR PLAN ========== -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Form -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                        <i data-lucide="calendar" class="w-5 h-5 text-blue-600"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Configuración del Plan</h2>
                        <p class="text-sm text-slate-500">Selecciona el atleta y la semana para el plan</p>
                    </div>
                </div>

                <form method="POST" id="planForm">
                    <?php echo Csrf::field(); ?>
                    <input type="hidden" name="action" value="generate_plan">
                    <div class="grid grid-cols-2 gap-6 mb-8">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Atleta</label>
                            <select name="athlete_id" required
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                                <option value="">Seleccionar atleta...</option>
                                <?php foreach ($athletes as $athlete): ?>
                                    <option value="<?php echo $athlete['id']; ?>">
                                        <?php echo htmlspecialchars($athlete['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Semana desde (Lunes)</label>
                            <input type="date" name="week_start" required
                                value="<?php echo date('Y-m-d', strtotime('next monday')); ?>"
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        </div>
                    </div>
            </div>

            <!-- Weekly Plan Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <div class="mb-6">
                    <h2 class="text-lg font-bold text-slate-900">Plan Semanal</h2>
                    <p class="text-sm text-slate-500">Asigna entrenamientos a cada día de la semana</p>
                </div>

                <div class="space-y-4">
                    <?php
                    $days = [
                        'lunes' => 'Lunes',
                        'martes' => 'Martes',
                        'miercoles' => 'Miércoles',
                        'jueves' => 'Jueves',
                        'viernes' => 'Viernes',
                        'sabado' => 'Sábado',
                        'domingo' => 'Domingo'
                    ];
                    foreach ($days as $key => $label):
                        ?>
                        <div class="flex items-center gap-4 py-3 border-b border-slate-100 last:border-0">
                            <div class="w-24">
                                <span class="font-semibold text-slate-900"><?php echo $label; ?></span>
                            </div>
                            <select name="template_<?php echo $key; ?>"
                                class="flex-1 px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                                <option value="">Seleccionar entrenamiento...</option>
                                <option value="" disabled>──────────────</option>
                                <?php
                                $currentType = '';
                                foreach ($templates as $template):
                                    if ($template['type'] !== $currentType):
                                        if ($currentType !== '')
                                            echo '</optgroup>';
                                        $currentType = $template['type'];
                                        echo '<optgroup label="' . htmlspecialchars($currentType) . '">';
                                    endif;
                                    ?>
                                    <option value="<?php echo $template['id']; ?>">
                                        <?php echo htmlspecialchars($template['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                                <?php if ($currentType !== '')
                                    echo '</optgroup>'; ?>
                            </select>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-8 pt-6 border-t border-slate-100">
                    <button type="submit"
                        class="w-full bg-blue-600 text-white py-4 rounded-xl font-bold text-lg hover:bg-blue-700 transition-all shadow-lg shadow-blue-100">
                        Generar Plan Semanal
                    </button>
                </div>
                </form>
            </div>
        </div>

        <!-- Sidebar: Templates Preview -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Exportar PDF -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-900 mb-1 flex items-center gap-2">
                    <i data-lucide="download" class="w-5 h-5 text-blue-600"></i>Exportar PDF
                </h3>
                <p class="text-sm text-slate-500 mb-4">Descarga el plan imprimible de un atleta</p>
                <form onsubmit="return exportAthletePlan(event)" class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1" for="exportAthlete">Atleta</label>
                        <select id="exportAthlete" required
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                            <option value="">Seleccionar...</option>
                            <?php foreach ($athletes as $athlete): ?>
                                <option value="<?php echo $athlete['id']; ?>"><?php echo htmlspecialchars($athlete['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1" for="exportWeek">Semana</label>
                            <input type="date" id="exportWeek" required
                                value="<?php echo date('Y-m-d', strtotime('monday this week')); ?>"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1" for="exportWeeks">Semanas</label>
                            <select id="exportWeeks"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                                <option value="1">1 (detalle)</option>
                                <option value="4" selected>4 (resumen)</option>
                                <option value="8">8</option>
                                <option value="12">12</option>
                                <option value="16">16</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit"
                        class="w-full bg-blue-600 text-white py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all text-sm">
                        Descargar PDF
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sticky top-8">
                <h3 class="text-lg font-bold text-slate-900 mb-2">Plantillas Disponibles</h3>
                <p class="text-sm text-slate-500 mb-4">Filtra y selecciona para el plan</p>

                <!-- Filters -->
                <div class="space-y-3 mb-4 p-3 bg-slate-50 rounded-xl">
                    <div class="relative">
                        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                        <input type="text" id="sidebarSearch"
                            placeholder="Buscar..."
                            class="w-full pl-9 pr-3 py-2 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <select id="sidebarDistance"
                            class="px-3 py-2 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                            <option value="">Todas</option>
                            <option value="5K">5K</option>
                            <option value="10K">10K</option>
                            <option value="21K">21K</option>
                            <option value="42K">42K</option>
                        </select>
                        <select id="sidebarType"
                            class="px-3 py-2 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                            <option value="">Todos</option>
                            <option value="Series">Series</option>
                            <option value="Fondo">Fondo</option>
                            <option value="Intervalos">Intervalos</option>
                            <option value="Tempo">Tempo</option>
                            <option value="Recuperación">Recuperación</option>
                            <option value="Descanso">Descanso</option>
                        </select>
                    </div>
                    <button type="button" onclick="clearSidebarFilters()"
                        class="w-full text-xs text-slate-500 hover:text-slate-700">Limpiar filtros</button>
                </div>

                <?php if (empty($templates)): ?>
                    <div class="text-center py-8">
                        <p class="text-slate-500 mb-4">No hay plantillas creadas</p>
                        <a href="generar_plan.php?tab=plantillas" class="text-blue-600 font-semibold">Crear plantillas</a>
                    </div>
                <?php else: ?>
                    <div class="space-y-3 max-h-[500px] overflow-y-auto pr-2" id="sidebarTemplates">
                        <?php foreach ($templates as $template): ?>
                            <?php
                            $typeColors = [
                                'Intervalos' => 'bg-blue-500',
                                'Series' => 'bg-red-500',
                                'Fondo' => 'bg-green-500',
                                'Tempo' => 'bg-purple-500',
                                'Descanso' => 'bg-slate-400',
                                'Recuperación' => 'bg-orange-500'
                            ];
                            $typeColor = $typeColors[$template['type']] ?? 'bg-slate-500';
                            $structure = json_decode($template['structure'] ?? '{}', true);
                            $tipIds = $structure['tip_ids'] ?? [];
                            ?>
                            <div class="p-3 border border-slate-200 rounded-xl hover:border-blue-300 hover:bg-blue-50/50 transition-all cursor-pointer"
                                data-name="<?php echo mb_strtolower(htmlspecialchars($template['name'])); ?>"
                                data-distance="<?php echo mb_strpos($template['name'], '5K') === 0 ? '5K' : (mb_strpos($template['name'], '10K') === 0 ? '10K' : (mb_strpos($template['name'], '21K') === 0 ? '21K' : (mb_strpos($template['name'], '42K') === 0 ? '42K' : ''))); ?>"
                                data-type="<?php echo htmlspecialchars($template['type']); ?>"
                                onclick="openSidebarPreview(this, <?php echo json_encode($template); ?>)">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="px-2 py-0.5 <?php echo $typeColor; ?> text-white text-xs font-semibold rounded">
                                        <?php echo htmlspecialchars($template['type']); ?>
                                    </span>
                                    <?php if ($tipIds): ?>
                                        <span class="px-1.5 py-0.5 bg-amber-50 border border-amber-200 text-amber-700 text-[10px] rounded">💡 <?php echo count($tipIds); ?></span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-sm font-medium text-slate-900 truncate">
                                    <?php echo htmlspecialchars($template['name']); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="text-center text-sm text-slate-400 mt-4">+
                        <?php echo count($templates); ?> plantillas disponibles
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function exportAthletePlan(e) {
            e.preventDefault();
            const athlete = document.getElementById('exportAthlete').value;
            const week = document.getElementById('exportWeek').value;
            const weeks = document.getElementById('exportWeeks').value;
            if (!athlete) return false;
            const mode = parseInt(weeks, 10) === 1 ? 'week' : 'full';
            window.location = `exportar_plan.php?mode=${mode}&weeks=${weeks}&week_start=${week}&athlete_id=${athlete}`;
            return false;
        }
    </script>

<?php elseif ($activeTab === 'auto'): ?>
    <!-- ========== TAB: PLAN SEMANAL AUTO ========== -->
    <div class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="sparkles" class="w-5 h-5 text-purple-600"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Generador Automático de Planes</h2>
                    <p class="text-sm text-slate-500">Crea planes completos de 8/10/12/16 semanas basado en tus plantillas</p>
                </div>
            </div>

            <?php if (isset($_GET['success']) && $_GET['success'] === 'auto_plan'): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6 flex items-center gap-3" role="alert">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                    Plan semanal generado exitosamente para <?php echo htmlspecialchars($_GET['weeks'] ?? ''); ?> semanas.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error']) && $_GET['error'] === 'no_templates'): ?>
                <div class="bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 rounded-xl mb-6 flex items-center gap-3" role="alert">
                    <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                    No hay plantillas suficientes para la distancia seleccionada. Crea plantillas para esa distancia.
                </div>
            <?php endif; ?>

            <form method="POST" id="autoPlanForm" class="space-y-6">
                <?php echo Csrf::field(); ?>
                <input type="hidden" name="action" value="generate_auto_plan">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Atleta</label>
                        <select name="athlete_id" required
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                            <option value="">Seleccionar atleta...</option>
                            <?php foreach ($athletes as $athlete): ?>
                                <option value="<?php echo $athlete['id']; ?>">
                                    <?php echo htmlspecialchars($athlete['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Distancia objetivo</label>
                        <select name="distance" id="autoDistance" required
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                            <option value="">Seleccionar...</option>
                            <option value="5K">5K (8 semanas)</option>
                            <option value="10K">10K (10 semanas)</option>
                            <option value="21K">21K (12 semanas)</option>
                            <option value="42K">42K (16 semanas)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Semana de inicio (Lunes)</label>
                        <input type="date" name="week_start" required
                            value="<?php echo date('Y-m-d', strtotime('next monday')); ?>"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>
                </div>

                <div class="bg-slate-50 rounded-xl p-6">
                    <h3 class="font-bold text-slate-900 mb-4">Estructura del Plan</h3>
                    <p class="text-sm text-slate-500 mb-4">El sistema distribuirá automáticamente tus plantillas según el patrón semanal estándar:</p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm" id="weekPattern">
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-200">
                    <button type="submit"
                        class="w-full bg-purple-600 text-white py-4 rounded-xl font-bold text-lg hover:bg-purple-700 transition-all shadow-lg shadow-purple-100">
                        <i data-lucide="sparkles" class="w-5 h-5 inline mr-2"></i>
                        Generar Plan Completo
                    </button>
                </div>
            </form>
        </div>

        <!-- Preview de plantillas disponibles por distancia -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="text-lg font-bold text-slate-900 mb-4">Plantillas disponibles por distancia</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php
                $distances = ['5K' => 8, '10K' => 10, '21K' => 12, '42K' => 16];
                foreach ($distances as $dist => $weeks):
                    $count = count(array_filter($templates, fn($t) => mb_strpos($t['name'], $dist) === 0));
                    $minRequired = $dist === '5K' ? 3 : ($dist === '10K' ? 4 : ($dist === '21K' ? 4 : 5));
                    $ready = $count >= $minRequired;
                    ?>
                    <div class="p-4 rounded-xl border-2 <?php echo $ready ? 'border-green-300 bg-green-50' : 'border-red-200 bg-red-50'; ?>">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-lg text-slate-900"><?php echo $dist; ?></span>
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?php echo $ready ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                <?php echo $ready ? 'Listo' : 'Faltan ' . ($minRequired - $count); ?>
                            </span>
                        </div>
                        <p class="text-sm text-slate-500"><?php echo $count; ?> plantillas</p>
                        <p class="text-xs text-slate-400 mt-1"><?php echo $weeks; ?> semanas • min. <?php echo $minRequired; ?> plantillas</p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- ========== TAB: MIS PLANTILLAS ========== -->
    <div class="space-y-6">
        <!-- Toolbar: Search + Filters -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <form method="GET" class="flex flex-col md:flex-row gap-4 items-end">
                <input type="hidden" name="tab" value="plantillas">
                <div class="flex-1">
                    <label class="sr-only" for="search">Buscar plantillas</label>
                    <div class="relative">
                        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400"></i>
                        <input type="text" name="search" id="search"
                            value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                            placeholder="Buscar por nombre..."
                            class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                </div>
                <div class="md:w-48">
                    <label class="sr-only" for="filter_distance">Filtrar por distancia</label>
                    <select name="distance" id="filter_distance"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                        <option value="">Todas las distancias</option>
                        <option value="5K" <?php echo ($_GET['distance'] ?? '') === '5K' ? 'selected' : ''; ?>>5K</option>
                        <option value="10K" <?php echo ($_GET['distance'] ?? '') === '10K' ? 'selected' : ''; ?>>10K</option>
                        <option value="21K" <?php echo ($_GET['distance'] ?? '') === '21K' ? 'selected' : ''; ?>>21K</option>
                        <option value="42K" <?php echo ($_GET['distance'] ?? '') === '42K' ? 'selected' : ''; ?>>42K</option>
                    </select>
                </div>
                <div class="md:w-48">
                    <label class="sr-only" for="filter_type">Filtrar por tipo</label>
                    <select name="type" id="filter_type"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                        <option value="">Todos los tipos</option>
                        <option value="Series" <?php echo ($_GET['type'] ?? '') === 'Series' ? 'selected' : ''; ?>>Series</option>
                        <option value="Fondo" <?php echo ($_GET['type'] ?? '') === 'Fondo' ? 'selected' : ''; ?>>Fondo</option>
                        <option value="Intervalos" <?php echo ($_GET['type'] ?? '') === 'Intervalos' ? 'selected' : ''; ?>>Intervalos</option>
                        <option value="Tempo" <?php echo ($_GET['type'] ?? '') === 'Tempo' ? 'selected' : ''; ?>>Tempo</option>
                        <option value="Recuperación" <?php echo ($_GET['type'] ?? '') === 'Recuperación' ? 'selected' : ''; ?>>Recuperación</option>
                        <option value="Descanso" <?php echo ($_GET['type'] ?? '') === 'Descanso' ? 'selected' : ''; ?>>Descanso</option>
                    </select>
                </div>
                <a href="generar_plan.php?tab=plantillas"
                    class="px-4 py-3 text-slate-500 hover:text-slate-700 font-semibold text-sm whitespace-nowrap">
                    <i data-lucide="x" class="w-4 h-4 inline mr-1"></i>Limpiar
                </a>
            </form>
        </div>

        <!-- Create Template Button -->
        <div class="flex justify-end">
            <button onclick="openTemplateModal()"
                class="bg-blue-600 text-white px-6 py-3 rounded-xl font-semibold hover:bg-blue-700 transition-all shadow-lg shadow-blue-100 flex items-center gap-2">
                <i data-lucide="plus" class="w-5 h-5"></i>
                Nueva Plantilla
            </button>
        </div>

        <!-- Templates Grid -->
        <?php
        // Filtrar plantillas según GET params
        $filtered = $templates;
        if (!empty($_GET['search'])) {
            $q = mb_strtolower($_GET['search']);
            $filtered = array_filter($filtered, fn($t) => mb_strpos(mb_strtolower($t['name']), $q) !== false);
        }
        if (!empty($_GET['distance'])) {
            $filtered = array_filter($filtered, fn($t) => mb_strpos($t['name'], $_GET['distance']) === 0);
        }
        if (!empty($_GET['type'])) {
            $filtered = array_filter($filtered, fn($t) => $t['type'] === $_GET['type']);
        }
        $filtered = array_values($filtered);
        ?>

        <?php if (empty($filtered)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-12 text-center">
                <i data-lucide="filter" class="w-16 h-16 mx-auto mb-4 text-slate-300"></i>
                <h3 class="text-xl font-bold text-slate-700 mb-2">No hay plantillas que coincidan</h3>
                <p class="text-slate-500 mb-6">Prueba a cambiar los filtros o la búsqueda</p>
                <a href="generar_plan.php?tab=plantillas"
                    class="text-blue-600 font-semibold">Ver todas</a>
            </div>
        <?php else: ?>
            <p class="text-sm text-slate-500">
                <strong><?php echo count($filtered); ?></strong> de <?php echo count($templates); ?> plantillas
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($filtered as $template): ?>
                    <?php
                    $typeColors = [
                        'Intervalos' => ['bg-blue-500', 'bg-blue-50', 'border-blue-200'],
                        'Series' => ['bg-red-500', 'bg-red-50', 'border-red-200'],
                        'Fondo' => ['bg-green-500', 'bg-green-50', 'border-green-200'],
                        'Tempo' => ['bg-purple-500', 'bg-purple-50', 'border-purple-200'],
                        'Descanso' => ['bg-slate-400', 'bg-slate-50', 'border-slate-200'],
                        'Recuperación' => ['bg-orange-500', 'bg-orange-50', 'border-orange-200']
                    ];
                    $colors = $typeColors[$template['type']] ?? ['bg-slate-500', 'bg-slate-50', 'border-slate-200'];
                    $structure = json_decode($template['structure'] ?? '{}', true);
                    $tipIds = $structure['tip_ids'] ?? [];
                    $estimatedMin = $structure['estimated_minutes'] ?? null;
                    $estimatedKm = $structure['estimated_km'] ?? null;
                    ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-md transition-all">
                        <div class="flex items-start justify-between mb-3">
                            <span class="px-3 py-1 <?php echo $colors[0]; ?> text-white text-xs font-bold rounded-lg">
                                <?php echo htmlspecialchars($template['type']); ?>
                            </span>
                            <div class="flex gap-1">
                                <button onclick='openPreviewModal(<?php echo json_encode($template); ?>)'
                                    class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all"
                                    title="Ver detalles" aria-label="Ver detalles de <?php echo htmlspecialchars($template['name']); ?>">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                                <button onclick='openEditModal(<?php echo json_encode($template); ?>)'
                                    class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all"
                                    title="Editar" aria-label="Editar plantilla <?php echo htmlspecialchars($template['name']); ?>">
                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                </button>
                                <button onclick="deleteTemplate(<?php echo $template['id']; ?>)"
                                    class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all"
                                    title="Eliminar" aria-label="Eliminar plantilla <?php echo htmlspecialchars($template['name']); ?>">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                        <h3 class="font-bold text-slate-900 text-lg mb-1"><?php echo htmlspecialchars($template['name']); ?></h3>
                        <?php if ($template['block_type']): ?>
                            <span class="text-xs font-medium px-2 py-1 <?php echo $colors[1]; ?> <?php echo $colors[2]; ?> border rounded-full mb-2 inline-block">
                                Bloque: <?php echo htmlspecialchars($template['block_type']); ?>
                            </span>
                        <?php endif; ?>
                        <div class="flex flex-wrap gap-1.5 mb-3 text-xs text-slate-500">
                            <?php if ($estimatedMin): ?>
                                <span class="px-2 py-0.5 bg-slate-100 rounded">⏱ <?php echo $estimatedMin; ?> min</span>
                            <?php endif; ?>
                            <?php if ($estimatedKm): ?>
                                <span class="px-2 py-0.5 bg-slate-100 rounded">📏 <?php echo $estimatedKm; ?> km</span>
                            <?php endif; ?>
                            <?php if ($tipIds): ?>
                                <span class="px-2 py-0.5 bg-amber-50 border border-amber-200 rounded">💡 <?php echo count($tipIds); ?> tips</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($template['structure']): ?>
                            <p class="text-slate-500 text-sm line-clamp-2">
                                <?php echo htmlspecialchars(substr($template['structure'], 0, 140)); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Template Create/Edit Modal -->
    <div id="templateModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50" role="dialog" aria-modal="true" aria-labelledby="templateModalTitle">
        <div class="bg-white rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto m-4">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                <h3 class="text-xl font-bold text-slate-900" id="templateModalTitle">Nueva Plantilla</h3>
                <button onclick="closeTemplateModal()" class="text-slate-400 hover:text-slate-600" aria-label="Cerrar modal">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            <form method="POST" class="p-6 space-y-5">
                <?php echo Csrf::field(); ?>
                <input type="hidden" name="action" id="templateAction" value="create_template">
                <input type="hidden" name="template_id" id="templateId" value="">

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Nombre de la Plantilla</label>
                    <input type="text" name="name" id="templateName" required placeholder="Ej: Intervalos 5x1000m"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Tipo</label>
                        <select name="type" id="templateType" required
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="Intervalos">Intervalos</option>
                            <option value="Series">Series</option>
                            <option value="Fondo">Fondo</option>
                            <option value="Tempo">Tempo</option>
                            <option value="Recuperación">Recuperación</option>
                            <option value="Descanso">Descanso</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Bloque</label>
                        <select name="block_type" id="templateBlock"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="">Sin bloque</option>
                            <option value="Base">Base</option>
                            <option value="Construcción">Construcción</option>
                            <option value="Pico">Pico</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Estructura / Instrucciones</label>
                    <textarea name="structure" id="templateStructure" rows="4"
                        placeholder="Describe la estructura del entrenamiento..."
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none"></textarea>
                </div>

                <button type="submit"
                    class="w-full bg-blue-600 text-white py-3 rounded-xl font-bold hover:bg-blue-700 transition-all">
                    <span id="templateSubmitText">Crear Plantilla</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Preview Modal -->
    <div id="previewModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50" role="dialog" aria-modal="true" aria-labelledby="previewModalTitle">
        <div class="bg-white rounded-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto m-4">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                <h3 class="text-xl font-bold text-slate-900" id="previewModalTitle">Detalle de Plantilla</h3>
                <button onclick="closePreviewModal()" class="text-slate-400 hover:text-slate-600" aria-label="Cerrar vista previa">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <div class="p-6 space-y-6" id="previewContent">
            </div>
        </div>
    </div>

    <script>
        const CSRF_TOKEN = "<?php echo htmlspecialchars(Csrf::token()); ?>";

        function openTemplateModal() {
            document.getElementById('templateModalTitle').textContent = 'Nueva Plantilla';
            document.getElementById('templateAction').value = 'create_template';
            document.getElementById('templateId').value = '';
            document.getElementById('templateName').value = '';
            document.getElementById('templateType').value = 'Intervalos';
            document.getElementById('templateBlock').value = '';
            document.getElementById('templateStructure').value = '';
            document.getElementById('templateSubmitText').textContent = 'Crear Plantilla';
            document.getElementById('templateModal').classList.remove('hidden');
            document.getElementById('templateModal').classList.add('flex');
        }

        function openEditModal(template) {
            document.getElementById('templateModalTitle').textContent = 'Editar Plantilla';
            document.getElementById('templateAction').value = 'update_template';
            document.getElementById('templateId').value = template.id;
            document.getElementById('templateName').value = template.name;
            document.getElementById('templateType').value = template.type;
            document.getElementById('templateBlock').value = template.block_type || '';
            document.getElementById('templateStructure').value = template.structure || '';
            document.getElementById('templateSubmitText').textContent = 'Guardar Cambios';
            document.getElementById('templateModal').classList.remove('hidden');
            document.getElementById('templateModal').classList.add('flex');
        }

        function closeTemplateModal() {
            document.getElementById('templateModal').classList.add('hidden');
            document.getElementById('templateModal').classList.remove('flex');
        }

        function deleteTemplate(id) {
            if (confirm('¿Estás seguro de eliminar esta plantilla?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="csrf_token" value="${CSRF_TOKEN}"><input type="hidden" name="action" value="delete_template"><input type="hidden" name="template_id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function openPreviewModal(template) {
            const structure = template.structure ? JSON.parse(template.structure) : {};
            const tipIds = structure.tip_ids || [];
            const estimatedMin = structure.estimated_minutes;
            const estimatedKm = structure.estimated_km;

            let html = `
                <div class="flex items-center gap-3 mb-4">
                    <span class="px-3 py-1 bg-blue-500 text-white text-xs font-bold rounded-lg">
                        ${escapeHtml(template.type)}
                    </span>
                    ${template.block_type ? `<span class="px-2 py-1 bg-slate-100 text-slate-700 text-xs rounded">Bloque: ${escapeHtml(template.block_type)}</span>` : ''}
                </div>
                <h4 class="text-lg font-bold text-slate-900 mb-4">${escapeHtml(template.name)}</h4>
            `;

            if (estimatedMin || estimatedKm) {
                html += '<div class="flex flex-wrap gap-3 mb-4 text-sm text-slate-600">';
                if (estimatedMin) html += `<span class="flex items-center gap-1"><i data-lucide="clock" class="w-4 h-4"></i> ${estimatedMin} min</span>`;
                if (estimatedKm) html += `<span class="flex items-center gap-1"><i data-lucide="map-pin" class="w-4 h-4"></i> ${estimatedKm} km</span>`;
                html += '</div>';
            }

            const blocks = [
                {key: 'warm_up', label: 'Entrada en calor', icon: 'sunrise'},
                {key: 'mobility', label: 'Movilidad', icon: 'rotate-ccw'},
                {key: 'drills', label: 'Técnicas', icon: 'zap'},
                {key: 'strides', label: 'Rectas/Progresivos', icon: 'arrow-right'},
                {key: 'main_set', label: 'Trabajo principal', icon: 'activity'},
                {key: 'strength', label: 'Fortalecimiento', icon: 'dumbbell'},
                {key: 'cool_down', label: 'Vuelta a la calma', icon: 'moon'},
                {key: 'elongation', label: 'Elongación', icon: 'stretch-horizontal'},
                {key: 'notes', label: 'Notas', icon: 'file-text'},
            ];

            let hasBlocks = false;
            for (const b of blocks) {
                if (structure[b.key] && structure[b.key].trim()) {
                    hasBlocks = true;
                    break;
                }
            }

            if (hasBlocks) {
                html += '<div class="space-y-3 border-t border-slate-100 pt-4">';
                html += '<h5 class="font-semibold text-slate-900 mb-3">Estructura de la sesión</h5>';
                for (const b of blocks) {
                    if (structure[b.key] && structure[b.key].trim()) {
                        html += `
                            <div class="bg-slate-50 rounded-xl p-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <i data-lucide="${b.icon}" class="w-5 h-5 text-blue-600"></i>
                                    <span class="font-medium text-slate-900">${b.label}</span>
                                </div>
                                <p class="text-slate-600 text-sm whitespace-pre-wrap">${escapeHtml(structure[b.key])}</p>
                            </div>
                        `;
                    }
                }
                html += '</div>';
            }

            if (tipIds.length > 0) {
                html += `
                    <div class="border-t border-slate-100 pt-4">
                        <h5 class="font-semibold text-slate-900 mb-3 flex items-center gap-2">
                            <i data-lucide="lightbulb" class="w-5 h-5 text-amber-500"></i>
                            Tips asociados (${tipIds.length})
                        </h5>
                        <div id="previewTips" class="space-y-2">
                            <p class="text-slate-500 text-sm">Cargando tips...</p>
                        </div>
                    </div>
                `;
            }

            document.getElementById('previewContent').innerHTML = html;
            document.getElementById('previewModal').classList.remove('hidden');
            document.getElementById('previewModal').classList.add('flex');
            lucide.createIcons();

            // Cargar tips asociados vía AJAX
            if (tipIds.length > 0) {
                fetch('api/get_tips.php?ids=' + tipIds.join(','))
                    .then(r => r.json())
                    .then(data => {
                        const container = document.getElementById('previewTips');
                        if (data.tips && data.tips.length > 0) {
                            container.innerHTML = data.tips.map(t => `
                                <div class="bg-amber-50 border border-amber-200 rounded-xl p-3">
                                    <h6 class="font-semibold text-amber-900 mb-1">${escapeHtml(t.title)}</h6>
                                    <p class="text-amber-800 text-sm">${escapeHtml(t.content.substring(0, 200))}${t.content.length > 200 ? '...' : ''}</p>
                                </div>
                            `).join('');
                            lucide.createIcons();
                        } else {
                            container.innerHTML = '<p class="text-slate-500 text-sm">No se encontraron tips</p>';
                        }
                    })
                    .catch(() => {
                        document.getElementById('previewTips').innerHTML = '<p class="text-slate-500 text-sm">Error al cargar tips</p>';
                    });
            }
        }

        function closePreviewModal() {
            document.getElementById('previewModal').classList.add('hidden');
            document.getElementById('previewModal').classList.remove('flex');
        }

        // Sidebar filters
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('sidebarSearch');
            const distanceSelect = document.getElementById('sidebarDistance');
            const typeSelect = document.getElementById('sidebarType');
            const templateContainer = document.getElementById('sidebarTemplates');
            const clearBtn = document.querySelector('button[onclick="clearSidebarFilters()"]');

            if (searchInput && templateContainer) {
                const templates = templateContainer.querySelectorAll('[data-name]');

                function filterTemplates() {
                    const search = searchInput.value.toLowerCase();
                    const distance = distanceSelect.value;
                    const type = typeSelect.value;
                    let visible = 0;

                    templates.forEach(el => {
                        const name = el.dataset.name;
                        const tDistance = el.dataset.distance;
                        const tType = el.dataset.type;

                        const matchSearch = !search || name.includes(search);
                        const matchDistance = !distance || tDistance === distance;
                        const matchType = !type || tType === type;

                        if (matchSearch && matchDistance && matchType) {
                            el.style.display = '';
                            visible++;
                        } else {
                            el.style.display = 'none';
                        }
                    });
                }

                searchInput.addEventListener('input', filterTemplates);
                distanceSelect.addEventListener('change', filterTemplates);
                typeSelect.addEventListener('change', filterTemplates);
            }

            if (clearBtn) {
                clearBtn.onclick = function() {
                    if (searchInput) searchInput.value = '';
                    if (distanceSelect) distanceSelect.value = '';
                    if (typeSelect) typeSelect.value = '';
                    const templates = templateContainer.querySelectorAll('[data-name]');
                    templates.forEach(el => el.style.display = '');
                };
            }

            // Auto Plan Pattern Preview
            const autoDistanceSelect = document.getElementById('autoDistance');
            const weekPatternContainer = document.getElementById('weekPattern');

            if (autoDistanceSelect && weekPatternContainer) {
                const patterns = {
                    '5K': [
                        ['Lunes' => 'Series', 'Martes' => 'Fondo', 'Miércoles' => 'Recuperación', 'Jueves' => 'Series', 'Viernes' => 'Fondo', 'Sábado' => 'Fondo', 'Domingo' => 'Descanso'],
                        ['Lunes' => 'Fondo', 'Martes' => 'Series', 'Miércoles' => 'Recuperación', 'Jueves' => 'Fondo', 'Viernes' => 'Series', 'Sábado' => 'Fondo', 'Domingo' => 'Descanso'],
                        ['Lunes' => 'Recuperación', 'Martes' => 'Fondo', 'Miércoles' => 'Series', 'Jueves' => 'Recuperación', 'Viernes' => 'Fondo', 'Sábado' => 'Series', 'Domingo' => 'Descanso'],
                        ['Lunes' => 'Fondo', 'Martes' => 'Recuperación', 'Miércoles' => 'Fondo', 'Jueves' => 'Series', 'Viernes' => 'Recuperación', 'Sábado' => 'Fondo', 'Domingo' => 'Descanso'],
                    ],
                    '10K': [
                        ['Lunes' => 'Series', 'Martes' => 'Fondo', 'Miércoles' => 'Recuperación', 'Jueves' => 'Series', 'Viernes' => 'Fondo', 'Sábado' => 'Fondo', 'Domingo' => 'Descanso'],
                        ['Lunes' => 'Fondo', 'Martes' => 'Intervalos', 'Miércoles' => 'Recuperación', 'Jueves' => 'Fondo', 'Viernes' => 'Series', 'Sábado' => 'Fondo', 'Domingo' => 'Descanso'],
                        ['Lunes' => 'Recuperación', 'Martes' => 'Fondo', 'Miércoles' => 'Series', 'Jueves' => 'Recuperación', 'Viernes' => 'Fondo', 'Sábado' => 'Intervalos', 'Domingo' => 'Descanso'],
                        ['Lunes' => 'Fondo', 'Martes' => 'Recuperación', 'Miércoles' => 'Fondo', 'Jueves' => 'Intervalos', 'Viernes' => 'Recuperación', 'Sábado' => 'Fondo', 'Domingo' => 'Descanso'],
                    ],
                    '21K': [
                        ['Lunes' => 'Series', 'Martes' => 'Fondo', 'Miércoles' => 'Recuperación', 'Jueves' => 'Series', 'Viernes' => 'Fondo', 'Sábado' => 'Fondo', 'Domingo' => 'Descanso'],
                        ['Lunes' => 'Fondo', 'Martes' => 'Intervalos', 'Miércoles' => 'Recuperación', 'Jueves' => 'Fondo', 'Viernes' => 'Series', 'Sábado' => 'Fondo', 'Domingo' => 'Descanso'],
                        ['Lunes' => 'Recuperación', 'Martes' => 'Fondo', 'Miércoles' => 'Series', 'Jueves' => 'Recuperación', 'Viernes' => 'Fondo', 'Sábado' => 'Fondo', 'Domingo' => 'Descanso'],
                        ['Lunes' => 'Fondo', 'Martes' => 'Recuperación', 'Miércoles' => 'Fondo', 'Jueves' => 'Intervalos', 'Viernes' => 'Recuperación', 'Sábado' => 'Fondo', 'Domingo' => 'Descanso'],
                    ],
                    '42K': [
                        ['Lunes' => 'Recuperación', 'Martes' => 'Fondo', 'Miércoles' => 'Series', 'Jueves' => 'Recuperación', 'Viernes' => 'Fondo', 'Sábado' => 'Fondo', 'Domingo' => 'Descanso'],
                        ['Lunes' => 'Fondo', 'Martes' => 'Recuperación', 'Miércoles' => 'Intervalos', 'Jueves' => 'Fondo', 'Viernes' => 'Recuperación', 'Sábado' => 'Fondo', 'Domingo' => 'Descanso'],
                        ['Lunes' => 'Recuperación', 'Martes' => 'Series', 'Miércoles' => 'Fondo', 'Jueves' => 'Recuperación', 'Viernes' => 'Fondo', 'Sábado' => 'Fondo', 'Domingo' => 'Descanso'],
                        ['Lunes' => 'Fondo', 'Martes' => 'Recuperación', 'Miércoles' => 'Fondo', 'Jueves' => 'Series', 'Viernes' => 'Recuperación', 'Sábado' => 'Fondo', 'Domingo' => 'Descanso'],
                    ],
                };

                const typeColors = {
                    'Series': 'bg-red-500',
                    'Fondo': 'bg-green-500',
                    'Intervalos': 'bg-blue-500',
                    'Recuperación': 'bg-orange-500',
                    'Descanso': 'bg-slate-400',
                    'Tempo': 'bg-purple-500',
                };

                function renderPattern() {
                    const dist = autoDistanceSelect.value;
                    if (!dist || !patterns[dist]) {
                        weekPatternContainer.innerHTML = '<p class="text-slate-400 text-sm col-span-full text-center">Selecciona una distancia para ver el patrón semanal</p>';
                        return;
                    }

                    const pattern = patterns[dist];
                    const weeksMap = { '5K': 8, '10K': 10, '21K': 12, '42K': 16 };
                    const totalWeeks = weeksMap[dist];

                    let html = '';
                    for (let week = 0; week < Math.min(4, pattern.length); week++) {
                        const weekPattern = pattern[week];
                        html += '<div class="bg-white border border-slate-200 rounded-lg p-3">';
                        html += `<h5 class="font-semibold text-slate-900 mb-2 text-sm">Semana ${week + 1}</h5>`;
                        html += '<div class="grid grid-cols-7 gap-1 text-xs">';
                        const days = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
                        days.forEach(day => {
                            const type = weekPattern[day];
                            const color = typeColors[type] || 'bg-slate-500';
                            const label = type === 'Descanso' ? '😴' : type;
                            html += `<div class="px-1 py-1.5 ${color} text-white rounded text-center truncate" title="${type}">${label}</div>`;
                        });
                        html += '</div></div>';
                    }

                    if (totalWeeks > 4) {
                        html += `<p class="text-slate-500 text-xs mt-2 text-center">+ ${totalWeeks - 4} semanas más siguiendo el mismo ciclo de 4 semanas</p>`;
                    }

                    weekPatternContainer.innerHTML = html;
                }

                autoDistanceSelect.addEventListener('change', renderPattern);
                // Initial render if value already set
                if (autoDistanceSelect.value) {
                    renderPattern();
                }
            }
        });

        function openSidebarPreview(el, template) {
            openPreviewModal(template);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
<?php endif; ?>

<?php include 'views/layout/footer.php'; ?>

<?php
require_once 'includes/auth.php';
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

// Get templates for selection
$stmt = $db->prepare("SELECT * FROM templates WHERE coach_id = ? ORDER BY type, name");
$stmt->execute([$coach['id']]);
$templates = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'generate_plan') {
        $athleteId = $_POST['athlete_id'];
        $weekStart = $_POST['week_start'];
        $days = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];

        // Store created workouts for email
        $createdWorkouts = [];

        foreach ($days as $i => $day) {
            $templateId = $_POST['template_' . $day] ?? null;
            if ($templateId) {
                // Get template details
                $stmt = $db->prepare("SELECT * FROM templates WHERE id = ?");
                $stmt->execute([$templateId]);
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

                    // Add to email list
                    $createdWorkouts[] = [
                        'date' => $workoutDate,
                        'type' => $template['type'],
                        'description' => $template['name']
                    ];
                }
            }
        }

        // Send email notification to athlete
        if (!empty($createdWorkouts)) {
            $athlete = User::getById($athleteId);
            if ($athlete && $athlete['username']) {
                Mailer::sendNewPlanNotification(
                    $athlete['username'], // email
                    $athlete['name'],
                    $coach['name'],
                    $weekStart,
                    $createdWorkouts
                );

                // Also create in-app notification
                $msg = "📋 Tu entrenador ha generado un nuevo plan de entrenamiento para la semana del " . (new DateTime($weekStart))->format('d/m/Y');
                Notification::create($athleteId, $msg, 'info');
            }
        }

        header('Location: generar_plan.php?success=1');
        exit;
    }
}

include 'views/layout/header.php';
?>

<!-- Page Header -->
<div class="mb-8">
    <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">GENERAR PLAN DE ENTRENAMIENTO</h1>
    <p class="text-slate-500 mt-1">Crea un plan semanal personalizado para tus atletas</p>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
        ✅ Plan semanal generado exitosamente
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Main Form -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Configuration Card -->
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
                            <span class="font-semibold text-slate-900">
                                <?php echo $label; ?>
                            </span>
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
    <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sticky top-8">
            <h3 class="text-lg font-bold text-slate-900 mb-2">Plantillas Disponibles</h3>
            <p class="text-sm text-slate-500 mb-6">Selecciona para ver detalles</p>

            <?php if (empty($templates)): ?>
                <div class="text-center py-8">
                    <p class="text-slate-500 mb-4">No hay plantillas creadas</p>
                    <a href="plantillas.php" class="text-blue-600 font-semibold">Crear plantillas</a>
                </div>
            <?php else: ?>
                <div class="space-y-3 max-h-[500px] overflow-y-auto pr-2">
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
                        ?>
                        <div
                            class="p-3 border border-slate-200 rounded-xl hover:border-blue-300 hover:bg-blue-50/50 transition-all cursor-pointer">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2 py-0.5 <?php echo $typeColor; ?> text-white text-xs font-semibold rounded">
                                    <?php echo htmlspecialchars($template['type']); ?>
                                </span>
                            </div>
                            <p class="text-sm font-medium text-slate-900">
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

<?php include 'views/layout/footer.php'; ?>
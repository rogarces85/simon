<?php
require_once 'includes/auth.php';
// Enable Error Reporting Debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'includes/db.php';
require_once 'models/Team.php';
require_once 'models/Workout.php'; // Fix: Import required model
require_once 'models/User.php'; // Fix: Import required model for line 15

Auth::init();
Auth::requireRole('athlete');

$user = Auth::user();
$athlete = User::getById($user['id']);
$team = null;
if ($athlete['coach_id']) {
    $team = Team::findByCoach($athlete['coach_id']);
}

// Branding Defaults
// Branding Defaults
$primaryColor = ($team && isset($team['primary_color'])) ? $team['primary_color'] : '#3b82f6'; // Blue-500
// Convert hex to rgb for tailwind opacity usage if needed, or just use style attribute.

require_once 'models/Notification.php';

// Manejar registro de resultados
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete_workout') {
    $workoutId = $_POST['workout_id'];

    // Handle Evidence Upload
    $evidenceUrl = null;
    if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads/evidence/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileExt = strtolower(pathinfo($_FILES['evidence']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($fileExt, $allowed)) {
            $fileName = 'evidence_' . $user['id'] . '_' . time() . '.' . $fileExt;
            if (move_uploaded_file($_FILES['evidence']['tmp_name'], $uploadDir . $fileName)) {
                $evidenceUrl = 'uploads/evidence/' . $fileName;
            }
        }
    }

    $data = [
        'actual_distance' => $_POST['actual_distance'],
        'actual_time' => $_POST['actual_time'],
        'rpe' => $_POST['rpe'],
        'feedback' => $_POST['feedback'],
        'evidence_url' => $evidenceUrl,
        'status' => 'completed',
        'completed_at' => date('Y-m-d H:i:s')
    ];

    Workout::update($workoutId, $data);

    // Notify Coach
    if ($athlete['coach_id']) {
        $msg = "🏃 " . $athlete['name'] . " completó un entrenamiento: " . ($_POST['feedback'] ? 'Con feedback' : 'Sin feedback');
        Notification::create($athlete['coach_id'], $msg, 'success');
    }

    header('Location: mi_plan.php?success=1');
    exit;
}

// Obtener semana actual (Lunes a Domingo)
$currentDate = isset($_GET['date']) ? new DateTime($_GET['date']) : new DateTime();
$monday = clone $currentDate;
$monday->modify('last monday');
if ($currentDate->format('N') == 1)
    $monday = clone $currentDate;

$sunday = clone $monday;
$sunday->modify('+6 days');

$workouts = Workout::getByAthlete($user['id'], $monday->format('Y-m-d 00:00:00'), $sunday->format('Y-m-d 23:59:59'));

// Mark workouts as received when athlete views them
Workout::markAsReceived($user['id']);

// Indexar entrenamientos por fecha para fácil acceso
$indexedWorkouts = [];
foreach ($workouts as $w) {
    $dateKey = (new DateTime($w['date']))->format('Y-m-d');
    $indexedWorkouts[$dateKey] = $w;
}

include 'views/layout/header.php';
?>

<!-- Perfil del Atleta (Banner) -->
<div class="rounded-2xl p-8 mb-8 text-white shadow-xl relative overflow-hidden"
    style="background-color: <?php echo htmlspecialchars($primaryColor); ?>;">

    <!-- Decorative Circle/Gradient -->
    <div
        class="absolute top-0 right-0 w-64 h-64 bg-white opacity-10 rounded-full -mr-16 -mt-16 blur-2xl pointer-events-none">
    </div>

    <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
        <div class="flex items-center gap-6">
            <?php if (isset($team['logo_url']) && $team['logo_url']): ?>
                <img src="<?php echo htmlspecialchars($team['logo_url']); ?>" alt="Team Logo"
                    class="w-20 h-20 rounded-full border-4 border-white/20 bg-white object-cover">
            <?php endif; ?>
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight">¡Hola,
                    <?php echo htmlspecialchars($athlete['name']); ?>! 👋
                </h1>
                <p class="text-white/90 mt-2 font-medium">
                    <?php if ($team): ?>
                        Team <span
                            class="font-bold underlineDecoration"><?php echo htmlspecialchars($team['name']); ?></span>
                    <?php else: ?>
                        Plan de Entrenamiento
                    <?php endif; ?>
                    • Objetivo: <span
                        class="font-bold"><?php echo htmlspecialchars($athlete['goal_pace'] ?? '-'); ?>/km</span>
                </p>
            </div>
        </div>

        <div class="flex gap-4">
            <div class="bg-white/10 backdrop-blur-md p-4 rounded-xl border border-white/20">
                <span class="block text-xs uppercase text-white/80 font-bold mb-1">Nivel</span>
                <span
                    class="text-lg font-bold"><?php echo htmlspecialchars($athlete['level'] ?? 'Principiante'); ?></span>
            </div>
            <div class="bg-white/10 backdrop-blur-md p-4 rounded-xl border border-white/20">
                <span class="block text-xs uppercase text-white/80 font-bold mb-1">Prox. Meta</span>
                <span class="text-lg font-bold">
                    <?php echo $athlete['goal_date'] ? (new DateTime($athlete['goal_date']))->format('d M') : '-'; ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Navegación de Semana -->
<div class="flex justify-between items-center mb-6">
    <div class="flex items-center gap-2">
        <h2 class="text-xl font-bold text-slate-800">Semana del
            <?php echo $monday->format('d'); ?> al
            <?php echo $sunday->format('d \d\e M'); ?>
        </h2>
    </div>
    <div class="flex gap-2">
        <?php
        $prevWeek = clone $monday;
        $prevWeek->modify('-7 days');
        $nextWeek = clone $monday;
        $nextWeek->modify('+7 days');
        ?>
        <a href="?date=<?php echo $prevWeek->format('Y-m-d'); ?>"
            class="p-2 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 text-slate-600 transition-all">
            <i data-lucide="chevron-left" class="w-5 h-5"></i>
        </a>
        <a href="mi_plan.php"
            class="px-4 py-2 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 text-slate-600 font-semibold text-sm transition-all">Hoy</a>
        <a href="?date=<?php echo $nextWeek->format('Y-m-d'); ?>"
            class="p-2 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 text-slate-600 transition-all">
            <i data-lucide="chevron-right" class="w-5 h-5"></i>
        </a>
    </div>
</div>

<!-- Calendario Semanal (Horizontal) -->
<div class="grid grid-cols-1 md:grid-cols-7 gap-4 mb-8">
    <?php
    $days = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
    for ($i = 0; $i < 7; $i++):
        $currentDayDate = clone $monday;
        $currentDayDate->modify("+$i days");
        $dateKey = $currentDayDate->format('Y-m-d');
        $workout = $indexedWorkouts[$dateKey] ?? null;
        $isToday = $dateKey === (new DateTime())->format('Y-m-d');
        ?>
        <div
            class="flex flex-col h-full <?php echo $isToday ? 'ring-2 ring-blue-500 bg-blue-50/30' : 'bg-white'; ?> rounded-2xl border <?php echo $isToday ? 'border-blue-200' : 'border-slate-200'; ?> overflow-hidden shadow-sm transition-all hover:shadow-md">
            <div
                class="p-4 border-b <?php echo $isToday ? 'bg-blue-600 text-white' : 'bg-slate-50 text-slate-800'; ?> flex flex-col items-center">
                <span class="text-xs uppercase font-bold opacity-80">
                    <?php echo $days[$i]; ?>
                </span>
                <span class="text-xl font-black mt-1">
                    <?php echo $currentDayDate->format('d'); ?>
                </span>
            </div>

            <div class="p-4 flex-1 flex flex-col gap-3">
                <?php if ($workout): ?>
                    <div class="flex-1">
                        <?php
                        $typeColors = [
                            'Series' => 'text-purple-600 bg-purple-50',
                            'Intervalos' => 'text-orange-600 bg-orange-50',
                            'Fondo' => 'text-blue-600 bg-blue-50',
                            'Tempo' => 'text-red-600 bg-red-50',
                            'Recuperación' => 'text-green-600 bg-green-50',
                            'Descanso' => 'text-slate-500 bg-slate-100'
                        ];
                        $color = $typeColors[$workout['type']] ?? 'bg-slate-50 text-slate-600';
                        ?>
                        <span
                            class="inline-block px-2 py-1 rounded-md text-[10px] font-bold uppercase mb-2 <?php echo $color; ?>">
                            <?php echo $workout['type']; ?>
                        </span>
                        <h3 class="text-sm font-bold text-slate-900 leading-tight mb-2">
                            <?php echo htmlspecialchars($workout['description']); ?>
                        </h3>

                        <?php if ($workout['status'] === 'completed'): ?>
                            <button onclick='openCompletedModal(<?php echo json_encode($workout); ?>)'
                                class="w-full mt-4 py-2 bg-emerald-50 border border-emerald-500 text-emerald-600 text-xs font-bold rounded-lg hover:bg-emerald-600 hover:text-white transition-all flex items-center justify-center gap-1">
                                <i data-lucide="check-circle" class="w-4 h-4"></i>
                                Ver Detalles
                            </button>
                        <?php else: ?>
                            <button onclick='openWorkoutModal(<?php echo json_encode($workout); ?>)'
                                class="w-full mt-4 py-2 bg-white border border-blue-500 text-blue-600 text-xs font-bold rounded-lg hover:bg-blue-600 hover:text-white transition-all">
                                Ver Detalles
                            </button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div
                        class="flex-1 flex flex-col items-center justify-center opacity-30 italic text-slate-400 text-xs text-center py-8">
                        <i data-lucide="calendar" class="w-6 h-6 mb-2"></i>
                        Sin asignar
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endfor; ?>
</div>

<!-- Modal Detalle/Completar Entrenamiento -->
<div id="workoutModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto transform transition-all">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <div class="flex items-center gap-3">
                <div id="modalIconContainer" class="p-2 rounded-xl">
                    <i data-lucide="activity" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900 leading-none" id="modalTitle">Detalle de Sesión</h3>
                    <p class="text-sm text-slate-500 mt-1" id="modalDate"></p>
                </div>
            </div>
            <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 p-2">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>

        <div class="p-6">
            <!-- Estructura del Coach -->
            <div class="bg-blue-50 rounded-xl p-4 mb-6 border border-blue-100">
                <h4 class="text-xs uppercase font-bold text-blue-500 mb-2 flex items-center gap-2">
                    <i data-lucide="target" class="w-4 h-4"></i> Instrucciones del Entrenador
                </h4>
                <p id="modalDescription" class="text-slate-800 font-medium"></p>
                <div id="modalStructure" class="mt-3 text-sm text-slate-600 border-t border-blue-100 pt-3 italic"></div>
            </div>

            <!-- Formulario de Resultados -->
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="complete_workout">
                <input type="hidden" name="workout_id" id="modalWorkoutId">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Distancia Real (km)</label>
                        <input type="number" step="0.01" name="actual_distance" required placeholder="0.00"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Tiempo Real (min)</label>
                        <input type="number" name="actual_time" required placeholder="0"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Esfuerzo Percibido (RPE 1-10)</label>
                    <div class="flex justify-between gap-1">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <label class="flex-1">
                                <input type="radio" name="rpe" value="<?php echo $i; ?>" class="sr-only peer" required>
                                <div
                                    class="cursor-pointer text-center py-2 rounded-lg bg-slate-100 peer-checked:bg-blue-600 peer-checked:text-white hover:bg-slate-200 transition-all text-xs font-bold">
                                    <?php echo $i; ?>
                                </div>
                            </label>
                        <?php endfor; ?>
                    </div>
                    <div class="flex justify-between text-[10px] text-slate-400 mt-1 uppercase font-bold">
                        <span>Muy Fácil</span>
                        <span>Máximo</span>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Evidencia (Opcional)</label>
                    <input type="file" name="evidence" accept="image/*" class="w-full text-sm text-slate-500
                        file:mr-4 file:py-2 file:px-4
                        file:rounded-full file:border-0
                        file:text-sm file:font-semibold
                        file:bg-blue-50 file:text-blue-700
                        hover:file:bg-blue-100
                    " />
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Feedback / Sensaciones</label>
                    <textarea name="feedback" rows="3" placeholder="¿Cómo te sentiste? ¿Hubo algún problema?"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none resize-none text-sm"></textarea>
                </div>

                <div class="pt-4">
                    <button type="submit"
                        class="w-full py-4 bg-emerald-600 text-white rounded-xl font-bold hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-100 flex items-center justify-center gap-2">
                        <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                        Registrar Sesión Completada
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ver Entrenamiento Completado -->
<div id="completedModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto transform transition-all">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-emerald-50">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-xl bg-emerald-100">
                    <i data-lucide="check-circle" class="w-6 h-6 text-emerald-600"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900 leading-none" id="completedModalTitle">Entrenamiento
                        Completado</h3>
                    <p class="text-sm text-slate-500 mt-1" id="completedModalDate"></p>
                </div>
            </div>
            <button onclick="closeCompletedModal()" class="text-slate-400 hover:text-slate-600 p-2">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>

        <div class="p-6 space-y-6">
            <!-- Descripción del entrenamiento -->
            <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                <h4 class="text-xs uppercase font-bold text-blue-500 mb-2 flex items-center gap-2">
                    <i data-lucide="target" class="w-4 h-4"></i> Entrenamiento Asignado
                </h4>
                <p id="completedModalDescription" class="text-slate-800 font-medium"></p>
                <div id="completedModalStructure"
                    class="mt-3 text-sm text-slate-600 border-t border-blue-100 pt-3 italic"></div>
            </div>

            <!-- Resultados del atleta -->
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                <h4 class="text-xs uppercase font-bold text-slate-500 mb-4 flex items-center gap-2">
                    <i data-lucide="bar-chart-3" class="w-4 h-4"></i> Mis Resultados
                </h4>
                <div class="grid grid-cols-3 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-slate-900" id="completedDistance">-</div>
                        <div class="text-xs text-slate-500">km recorridos</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-slate-900" id="completedTime">-</div>
                        <div class="text-xs text-slate-500">minutos</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-slate-900" id="completedRPE">-</div>
                        <div class="text-xs text-slate-500">RPE</div>
                    </div>
                </div>
            </div>

            <!-- Evidencia subida -->
            <div id="evidenceContainer" class="hidden">
                <h4 class="text-xs uppercase font-bold text-slate-500 mb-2 flex items-center gap-2">
                    <i data-lucide="image" class="w-4 h-4"></i> Evidencia
                </h4>
                <img id="evidenceImage" src="" alt="Evidencia"
                    class="rounded-xl w-full max-h-48 object-cover border border-slate-200">
            </div>

            <!-- Mi feedback -->
            <div id="myFeedbackContainer" class="hidden">
                <h4 class="text-xs uppercase font-bold text-slate-500 mb-2 flex items-center gap-2">
                    <i data-lucide="message-circle" class="w-4 h-4"></i> Mi Feedback
                </h4>
                <div class="bg-white rounded-xl p-4 border border-slate-200">
                    <p id="myFeedbackText" class="text-slate-700 text-sm"></p>
                </div>
            </div>

            <!-- Respuesta del Coach -->
            <div id="coachFeedbackContainer" class="hidden">
                <h4 class="text-xs uppercase font-bold text-emerald-500 mb-2 flex items-center gap-2">
                    <i data-lucide="user-check" class="w-4 h-4"></i> Respuesta del Entrenador
                </h4>
                <div class="bg-emerald-50 rounded-xl p-4 border border-emerald-200">
                    <p id="coachFeedbackText" class="text-slate-700 text-sm"></p>
                    <p id="coachFeedbackDate" class="text-xs text-emerald-600 mt-2"></p>
                </div>
            </div>

            <!-- Sin respuesta del coach aún -->
            <div id="noCoachFeedbackContainer" class="hidden">
                <div class="bg-amber-50 rounded-xl p-4 border border-amber-200 flex items-center gap-3">
                    <i data-lucide="clock" class="w-5 h-5 text-amber-500"></i>
                    <p class="text-sm text-amber-700">Esperando respuesta del entrenador...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment-with-locales.min.js"></script>
<script>
    moment.locale('es');

    function openWorkoutModal(workout) {
        const modal = document.getElementById('workoutModal');
        const modalIconContainer = document.getElementById('modalIconContainer');

        document.getElementById('modalWorkoutId').value = workout.id;
        document.getElementById('modalTitle').innerText = workout.type;
        document.getElementById('modalDate').innerText = moment(workout.date).format('dddd D [de] MMMM');
        document.getElementById('modalDescription').innerText = workout.description;
        document.getElementById('modalStructure').innerText = workout.structure ? (typeof workout.structure === 'string' ? workout.structure : JSON.stringify(workout.structure)) : 'No hay detalles adicionales.';

        const types = {
            'Series': 'bg-purple-100 text-purple-600',
            'Intervalos': 'bg-orange-100 text-orange-600',
            'Fondo': 'bg-blue-100 text-blue-600',
            'Tempo': 'bg-red-100 text-red-600',
            'Recuperación': 'bg-green-100 text-green-600',
            'Descanso': 'bg-slate-100 text-slate-600'
        };
        modalIconContainer.className = 'p-2 rounded-xl ' + (types[workout.type] || 'bg-slate-100 text-slate-600');

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        lucide.createIcons();
    }

    function openCompletedModal(workout) {
        const modal = document.getElementById('completedModal');

        document.getElementById('completedModalTitle').innerText = workout.type + ' - Completado';
        document.getElementById('completedModalDate').innerText = moment(workout.completed_at || workout.date).format('dddd D [de] MMMM, YYYY');
        document.getElementById('completedModalDescription').innerText = workout.description;
        document.getElementById('completedModalStructure').innerText = workout.structure ? (typeof workout.structure === 'string' ? workout.structure : JSON.stringify(workout.structure)) : 'No hay detalles adicionales.';

        // Results
        document.getElementById('completedDistance').innerText = workout.actual_distance || '-';
        document.getElementById('completedTime').innerText = workout.actual_time || '-';
        document.getElementById('completedRPE').innerText = workout.rpe || '-';

        // Evidence
        const evidenceContainer = document.getElementById('evidenceContainer');
        if (workout.evidence_url) {
            document.getElementById('evidenceImage').src = workout.evidence_url;
            evidenceContainer.classList.remove('hidden');
        } else {
            evidenceContainer.classList.add('hidden');
        }

        // My feedback
        const myFeedbackContainer = document.getElementById('myFeedbackContainer');
        if (workout.feedback) {
            document.getElementById('myFeedbackText').innerText = workout.feedback;
            myFeedbackContainer.classList.remove('hidden');
        } else {
            myFeedbackContainer.classList.add('hidden');
        }

        // Coach feedback
        const coachFeedbackContainer = document.getElementById('coachFeedbackContainer');
        const noCoachFeedbackContainer = document.getElementById('noCoachFeedbackContainer');
        if (workout.coach_feedback) {
            document.getElementById('coachFeedbackText').innerText = workout.coach_feedback;
            document.getElementById('coachFeedbackDate').innerText = workout.coach_feedback_at ? moment(workout.coach_feedback_at).format('D MMM YYYY, HH:mm') : '';
            coachFeedbackContainer.classList.remove('hidden');
            noCoachFeedbackContainer.classList.add('hidden');
        } else {
            coachFeedbackContainer.classList.add('hidden');
            noCoachFeedbackContainer.classList.remove('hidden');
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        lucide.createIcons();
    }

    function closeModal() {
        document.getElementById('workoutModal').classList.add('hidden');
        document.getElementById('workoutModal').classList.remove('flex');
    }

    function closeCompletedModal() {
        document.getElementById('completedModal').classList.add('hidden');
        document.getElementById('completedModal').classList.remove('flex');
    }

    // Cerrar modal si se hace clic fuera del contenido
    window.onclick = function (event) {
        const modal = document.getElementById('workoutModal');
        const completedModal = document.getElementById('completedModal');
        if (event.target == modal) {
            closeModal();
        }
        if (event.target == completedModal) {
            closeCompletedModal();
        }
    }
</script>

<?php include 'views/layout/footer.php'; ?>
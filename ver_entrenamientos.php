<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'models/User.php';
require_once 'models/Workout.php';
require_once 'models/Notification.php';

Auth::init();
Auth::requireRole('coach');

$coach = Auth::user();

// Get athletes for filter
$athletes = User::getByCoachId($coach['id']);
$athleteId = $_GET['athlete_id'] ?? 'all';

// Handle coach feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_feedback') {
        $workoutId = $_POST['workout_id'];
        $feedback = $_POST['coach_feedback'];

        Workout::addCoachFeedback($workoutId, $feedback);

        // Get workout to find athlete
        $workout = Workout::getById($workoutId);
        if ($workout) {
            $msg = "💬 Tu entrenador ha respondido a tu feedback del entrenamiento: " . $workout['description'];
            Notification::create($workout['athlete_id'], $msg, 'info');
        }

        $redirect = 'ver_entrenamientos.php?success=1';
        if ($athleteId !== 'all')
            $redirect .= '&athlete_id=' . $athleteId;
        header('Location: ' . $redirect);
        exit;
    }
}

// Get completed workouts (filtered by athlete if selected)
$completedWorkouts = Workout::getCompletedByCoach($coach['id'], $athleteId !== 'all' ? $athleteId : null);

// Get plan delivery stats
$planStats = Workout::getPlanStatsByCoach($coach['id']);
$statsIndexed = [];
foreach ($planStats as $stat) {
    $statsIndexed[$stat['delivery_status']] = $stat['count'];
}

include 'views/layout/header.php';
?>

<!-- Page Header -->
<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
    <div>
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">ENTRENAMIENTOS COMPLETADOS</h1>
        <p class="text-slate-500 mt-1">Revisa y responde al feedback de tus atletas</p>
    </div>
    
    <!-- Athlete Filter -->
    <div class="w-full md:w-64">
        <form method="GET" id="filterForm">
            <select name="athlete_id" onchange="this.form.submit()"
                class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white focus:ring-2 focus:ring-blue-500 outline-none text-slate-700 font-medium">
                <option value="all">Todos los Atletas</option>
                <?php foreach ($athletes as $athlete): ?>
                    <option value="<?php echo $athlete['id']; ?>" <?php echo $athleteId == $athlete['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($athlete['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
        ✅ Feedback enviado exitosamente
    </div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                <i data-lucide="clock" class="w-6 h-6 text-amber-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900">
                    <?php echo $statsIndexed['pending'] ?? 0; ?>
                </p>
                <p class="text-sm text-slate-500">Pendientes</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                <i data-lucide="send" class="w-6 h-6 text-blue-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900">
                    <?php echo $statsIndexed['sent'] ?? 0; ?>
                </p>
                <p class="text-sm text-slate-500">Enviados</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
                <i data-lucide="check-circle" class="w-6 h-6 text-emerald-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900">
                    <?php echo $statsIndexed['received'] ?? 0; ?>
                </p>
                <p class="text-sm text-slate-500">Recibidos</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                <i data-lucide="activity" class="w-6 h-6 text-purple-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900">
                    <?php echo count($completedWorkouts); ?>
                </p>
                <p class="text-sm text-slate-500">Completados</p>
            </div>
        </div>
    </div>
</div>

<!-- Completed Workouts List -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-6 border-b border-slate-100">
        <h2 class="text-xl font-bold text-slate-900">Historial de Entrenamientos</h2>
    </div>

    <?php if (empty($completedWorkouts)): ?>
        <div class="p-12 text-center text-slate-500">
            <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-4 opacity-30"></i>
            <p>No hay entrenamientos completados aún</p>
        </div>
    <?php else: ?>
        <div class="divide-y divide-slate-100">
            <?php foreach ($completedWorkouts as $workout): ?>
                <div class="p-6 hover:bg-slate-50 transition-colors">
                    <div class="flex flex-col lg:flex-row gap-6">
                        <!-- Left: Workout Info -->
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-3">
                                <?php
                                $typeColors = [
                                    'Series' => 'bg-purple-100 text-purple-600',
                                    'Intervalos' => 'bg-orange-100 text-orange-600',
                                    'Fondo' => 'bg-blue-100 text-blue-600',
                                    'Tempo' => 'bg-red-100 text-red-600',
                                    'Recuperación' => 'bg-green-100 text-green-600',
                                    'Descanso' => 'bg-slate-100 text-slate-600'
                                ];
                                $color = $typeColors[$workout['type']] ?? 'bg-slate-100 text-slate-600';
                                ?>
                                <span class="px-3 py-1 rounded-full text-xs font-bold <?php echo $color; ?>">
                                    <?php echo htmlspecialchars($workout['type']); ?>
                                </span>
                                <span class="text-sm text-slate-500">
                                    <?php echo (new DateTime($workout['completed_at']))->format('d M Y, H:i'); ?>
                                </span>
                            </div>

                            <h3 class="font-bold text-slate-900 mb-1">
                                <?php echo htmlspecialchars($workout['athlete_name']); ?>
                            </h3>
                            <p class="text-slate-600 text-sm mb-3">
                                <?php echo htmlspecialchars($workout['description']); ?>
                            </p>

                            <!-- Results Grid -->
                            <div class="flex gap-6 text-sm">
                                <div>
                                    <span class="text-slate-500">Distancia:</span>
                                    <span class="font-semibold text-slate-900">
                                        <?php echo $workout['actual_distance'] ?? '-'; ?> km
                                    </span>
                                </div>
                                <div>
                                    <span class="text-slate-500">Tiempo:</span>
                                    <span class="font-semibold text-slate-900">
                                        <?php echo $workout['actual_time'] ?? '-'; ?> min
                                    </span>
                                </div>
                                <div>
                                    <span class="text-slate-500">RPE:</span>
                                    <span class="font-semibold text-slate-900">
                                        <?php echo $workout['rpe'] ?? '-'; ?>/10
                                    </span>
                                </div>
                            </div>

                            <!-- Athlete Feedback -->
                            <?php if ($workout['feedback']): ?>
                                <div class="mt-4 bg-slate-50 rounded-xl p-4 border border-slate-200">
                                    <h4 class="text-xs uppercase font-bold text-slate-500 mb-2 flex items-center gap-2">
                                        <i data-lucide="message-circle" class="w-4 h-4"></i> Feedback del Atleta
                                    </h4>
                                    <p class="text-slate-700 text-sm">
                                        <?php echo htmlspecialchars($workout['feedback']); ?>
                                    </p>
                                </div>
                            <?php endif; ?>

                            <!-- Evidence -->
                            <?php if ($workout['evidence_url']): ?>
                                <div class="mt-4">
                                    <img src="<?php echo htmlspecialchars($workout['evidence_url']); ?>" alt="Evidencia"
                                        class="rounded-xl max-h-32 object-cover border border-slate-200 cursor-pointer hover:opacity-80 transition-opacity"
                                        onclick="window.open('<?php echo htmlspecialchars($workout['evidence_url']); ?>', '_blank')">
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Right: Coach Feedback Section -->
                        <div class="lg:w-80 shrink-0">
                            <?php if ($workout['coach_feedback']): ?>
                                <div class="bg-emerald-50 rounded-xl p-4 border border-emerald-200">
                                    <h4 class="text-xs uppercase font-bold text-emerald-600 mb-2 flex items-center gap-2">
                                        <i data-lucide="check-circle" class="w-4 h-4"></i> Tu Respuesta
                                    </h4>
                                    <p class="text-slate-700 text-sm">
                                        <?php echo htmlspecialchars($workout['coach_feedback']); ?>
                                    </p>
                                    <p class="text-xs text-emerald-600 mt-2">
                                        <?php echo $workout['coach_feedback_at'] ? (new DateTime($workout['coach_feedback_at']))->format('d M Y, H:i') : ''; ?>
                                    </p>
                                </div>
                            <?php else: ?>
                                <form method="POST" class="space-y-3">
                                    <input type="hidden" name="action" value="add_feedback">
                                    <input type="hidden" name="workout_id" value="<?php echo $workout['id']; ?>">
                                    <textarea name="coach_feedback" rows="3" required
                                        placeholder="Escribe tu feedback para el atleta..."
                                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none resize-none text-sm"></textarea>
                                    <button type="submit"
                                        class="w-full py-2 bg-blue-600 text-white rounded-xl font-semibold text-sm hover:bg-blue-700 transition-all flex items-center justify-center gap-2">
                                        <i data-lucide="send" class="w-4 h-4"></i>
                                        Enviar Respuesta
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'views/layout/footer.php'; ?>
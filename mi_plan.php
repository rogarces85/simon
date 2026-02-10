<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'models/User.php';
require_once 'models/Workout.php';
require_once 'models/Team.php';
require_once 'models/Notification.php';

Auth::init();
Auth::requireRole('athlete');

$user = Auth::user();
$db = Database::getInstance();

// Get team info for branding
$team = Team::findByCoach($user['coach_id'] ?? 0);

// Handle form submissions (record results, upload evidence)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'complete_workout') {
        $workoutId = $_POST['workout_id'];
        $updateData = [
            'status' => 'completed',
            'actual_distance' => $_POST['actual_distance'] ?: null,
            'actual_time' => $_POST['actual_time'] ?: null,
            'rpe' => $_POST['rpe'] ?: null,
            'feedback' => $_POST['feedback'] ?: null,
            'completed_at' => date('Y-m-d H:i:s'),
            'delivery_status' => 'received'
        ];

        // Handle file upload
        if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/evidence/';
            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0777, true);
            $ext = pathinfo($_FILES['evidence']['name'], PATHINFO_EXTENSION);
            $filename = 'evidence_' . $workoutId . '_' . time() . '.' . $ext;
            $dest = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['evidence']['tmp_name'], $dest)) {
                $updateData['evidence_path'] = $dest;
            }
        }

        Workout::update($workoutId, $updateData);

        // Notify coach about completed workout
        if ($user['coach_id']) {
            $workout = Workout::getById($workoutId);
            $dateStr = (new DateTime($workout['date']))->format('d/m/Y');
            $hasFeedback = !empty($_POST['feedback']);

            $message = "✅ {$user['name']} completó su entrenamiento del {$dateStr}";
            if ($hasFeedback) {
                $message .= " y dejó feedback para revisar";
            }

            Notification::create($user['coach_id'], $message, 'info');
        }

        header('Location: mi_plan.php?success=1&month=' . ($_GET['month'] ?? date('Y-m')));
        exit;
    }
}

// Calculate current month
$monthParam = $_GET['month'] ?? date('Y-m');
$currentMonth = new DateTime($monthParam . '-01');
$monthStart = clone $currentMonth;
$monthEnd = (clone $currentMonth)->modify('last day of this month');
$prevMonth = (clone $currentMonth)->modify('-1 month')->format('Y-m');
$nextMonth = (clone $currentMonth)->modify('+1 month')->format('Y-m');
$today = new DateTime();

// Get workouts for the entire month
$workouts = Workout::getByAthlete(
    $user['id'],
    $monthStart->format('Y-m-d 00:00:00'),
    $monthEnd->format('Y-m-d 23:59:59')
);

// Index workouts by date
$workoutsByDate = [];
foreach ($workouts as $w) {
    $dateKey = (new DateTime($w['date']))->format('Y-m-d');
    $workoutsByDate[$dateKey] = $w;
}

// Calendar calculations
$firstDayOfMonth = (int) $monthStart->format('N'); // 1=Monday
$daysInMonth = (int) $monthEnd->format('d');

include 'views/layout/header.php';
?>

<!-- Team Branding -->
<?php if ($team && $team['logo_url']): ?>
    <div class="flex items-center gap-4 mb-6">
        <img src="<?php echo htmlspecialchars($team['logo_url']); ?>"
            class="w-12 h-12 rounded-xl object-cover border border-slate-200">
        <div>
            <h2 class="font-bold text-slate-900"><?php echo htmlspecialchars($team['name'] ?? 'Mi Equipo'); ?></h2>
            <p class="text-sm text-slate-500">Programación de entrenamiento</p>
        </div>
    </div>
<?php endif; ?>

<!-- Page Header with Month Navigation -->
<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
    <div>
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">MI PROGRAMACIÓN</h1>
        <p class="text-slate-500 mt-1">Tu plan de entrenamiento mensual</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="mi_plan.php?month=<?php echo $prevMonth; ?>"
            class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold transition-all">
            <i data-lucide="chevron-left" class="w-5 h-5 inline"></i>
        </a>
        <div
            class="px-6 py-2 bg-white rounded-xl border border-slate-200 font-bold text-slate-900 text-center min-w-[200px]">
            <?php
            $months_es = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
            $monthIdx = (int) $currentMonth->format('n') - 1;
            echo $months_es[$monthIdx] . ' ' . $currentMonth->format('Y');
            ?>
        </div>
        <a href="mi_plan.php?month=<?php echo $nextMonth; ?>"
            class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold transition-all">
            <i data-lucide="chevron-right" class="w-5 h-5 inline"></i>
        </a>
        <a href="mi_plan.php"
            class="px-4 py-2 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 transition-all text-sm">
            Hoy
        </a>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
        ✅ Entrenamiento registrado exitosamente
    </div>
<?php endif; ?>

<!-- Monthly Summary -->
<?php
$totalMonth = count($workouts);
$completedMonth = 0;
$pendingMonth = 0;
$restDays = 0;
foreach ($workouts as $w) {
    if ($w['type'] === 'Descanso')
        $restDays++;
    elseif ($w['status'] === 'completed')
        $completedMonth++;
    elseif ($w['status'] === 'pending')
        $pendingMonth++;
}
$activeWorkouts = $totalMonth - $restDays;
$complianceRate = $activeWorkouts > 0 ? round(($completedMonth / $activeWorkouts) * 100) : 0;
?>
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 bg-blue-100 rounded-xl flex items-center justify-center">
                <i data-lucide="calendar" class="w-5 h-5 text-blue-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900"><?php echo $totalMonth; ?></p>
                <p class="text-xs text-slate-500">Sesiones del Mes</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 bg-green-100 rounded-xl flex items-center justify-center">
                <i data-lucide="check-circle" class="w-5 h-5 text-green-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900"><?php echo $completedMonth; ?></p>
                <p class="text-xs text-slate-500">Completados</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 bg-amber-100 rounded-xl flex items-center justify-center">
                <i data-lucide="clock" class="w-5 h-5 text-amber-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900"><?php echo $pendingMonth; ?></p>
                <p class="text-xs text-slate-500">Pendientes</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 bg-purple-100 rounded-xl flex items-center justify-center">
                <i data-lucide="target" class="w-5 h-5 text-purple-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900"><?php echo $complianceRate; ?>%</p>
                <p class="text-xs text-slate-500">Cumplimiento</p>
            </div>
        </div>
    </div>
</div>

<!-- Monthly Calendar -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <!-- Day headers -->
    <div class="grid grid-cols-7 bg-slate-50 border-b border-slate-200">
        <?php
        $dayHeaders = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
        foreach ($dayHeaders as $dh):
            ?>
            <div class="px-3 py-3 text-center text-sm font-bold text-slate-600"><?php echo $dh; ?></div>
        <?php endforeach; ?>
    </div>

    <!-- Calendar grid -->
    <div class="grid grid-cols-7">
        <?php
        // Empty cells before first day
        for ($i = 1; $i < $firstDayOfMonth; $i++):
            ?>
            <div class="min-h-[120px] border-b border-r border-slate-100 bg-slate-50/50"></div>
        <?php endfor; ?>

        <?php for ($day = 1; $day <= $daysInMonth; $day++):
            $dateKey = $currentMonth->format('Y-m') . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
            $workout = $workoutsByDate[$dateKey] ?? null;
            $isToday = $dateKey === $today->format('Y-m-d');
            $isRest = $workout && $workout['type'] === 'Descanso';
            $isCompleted = $workout && $workout['status'] === 'completed';
            $isPending = $workout && $workout['status'] === 'pending';

            $typeColors = [
                'Series' => 'bg-purple-100 text-purple-700 border-purple-200',
                'Intervalos' => 'bg-orange-100 text-orange-700 border-orange-200',
                'Fondo' => 'bg-blue-100 text-blue-700 border-blue-200',
                'Tempo' => 'bg-red-100 text-red-700 border-red-200',
                'Recuperación' => 'bg-green-100 text-green-700 border-green-200',
                'Descanso' => 'bg-slate-100 text-slate-500 border-slate-200'
            ];
            $typeColor = $workout ? ($typeColors[$workout['type']] ?? 'bg-slate-100 text-slate-600 border-slate-200') : '';
            ?>
            <div
                class="min-h-[120px] border-b border-r border-slate-100 p-2 <?php echo $isToday ? 'bg-blue-50/50 ring-2 ring-inset ring-blue-300' : ''; ?> hover:bg-slate-50 transition-colors">
                <!-- Day number -->
                <div class="flex justify-between items-center mb-1">
                    <span
                        class="text-sm font-bold <?php echo $isToday ? 'text-blue-600 bg-blue-600 text-white w-7 h-7 rounded-full flex items-center justify-center' : 'text-slate-600'; ?>">
                        <?php echo $day; ?>
                    </span>
                    <?php if ($isCompleted): ?>
                        <span class="text-green-500 text-xs">✅</span>
                    <?php elseif ($isRest): ?>
                        <span class="text-xs">😴</span>
                    <?php endif; ?>
                </div>

                <?php if ($workout): ?>
                    <?php if ($isRest): ?>
                        <!-- Rest Day -->
                        <div class="px-2 py-1.5 rounded-lg bg-slate-100 border border-slate-200 text-center">
                            <p class="text-xs font-semibold text-slate-500">Descanso</p>
                        </div>
                    <?php else: ?>
                        <!-- Workout Cell -->
                        <div onclick='openWorkoutModal(<?php echo json_encode($workout); ?>, "<?php echo $dateKey; ?>")'
                            class="px-2 py-1.5 rounded-lg border cursor-pointer transition-all hover:shadow-sm <?php echo $typeColor; ?>">
                            <p class="text-xs font-bold truncate"><?php echo htmlspecialchars($workout['type']); ?></p>
                            <p class="text-[10px] truncate opacity-80">
                                <?php echo htmlspecialchars($workout['description'] ?? ''); ?>
                            </p>
                            <?php if ($isCompleted && $workout['actual_distance']): ?>
                                <p class="text-[10px] font-semibold mt-0.5"><?php echo $workout['actual_distance']; ?> km</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endfor; ?>

        <?php
        // Fill remaining cells
        $totalCells = ($firstDayOfMonth - 1) + $daysInMonth;
        $remaining = 7 - ($totalCells % 7);
        if ($remaining < 7):
            for ($i = 0; $i < $remaining; $i++):
                ?>
                <div class="min-h-[120px] border-b border-r border-slate-100 bg-slate-50/50"></div>
                <?php
            endfor;
        endif;
        ?>
    </div>
</div>

<!-- Legend -->
<div class="flex flex-wrap gap-4 mt-4 text-xs text-slate-500">
    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-purple-200 inline-block"></span> Series</span>
    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-orange-200 inline-block"></span>
        Intervalos</span>
    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-blue-200 inline-block"></span> Fondo</span>
    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-red-200 inline-block"></span> Tempo</span>
    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-green-200 inline-block"></span>
        Recuperación</span>
    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-slate-200 inline-block"></span>
        Descanso</span>
</div>

<!-- Workout Detail Modal (for non-rest days only) -->
<div id="workoutModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto m-4">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center">
            <div>
                <h3 class="text-xl font-bold text-slate-900" id="modalTitle">Detalle del Entrenamiento</h3>
                <p class="text-slate-500 text-sm mt-1" id="modalDate"></p>
            </div>
            <button onclick="closeWorkoutModal()" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        <div class="p-6 space-y-5" id="modalContent">
            <!-- Content filled by JS -->
        </div>
    </div>
</div>

<script>
    function openWorkoutModal(workout, dateKey) {
        const modal = document.getElementById('workoutModal');
        const content = document.getElementById('modalContent');
        const dateObj = new Date(dateKey + 'T12:00:00');
        document.getElementById('modalDate').textContent = dateObj.toLocaleDateString('es-CL', { weekday: 'long', day: 'numeric', month: 'long' });

        let html = '';

        // Workout info
        html += `
            <div class="flex items-center gap-3">
                <span class="px-3 py-1 rounded-lg text-sm font-bold bg-blue-100 text-blue-700">${workout.type}</span>
                <span class="text-slate-600">${workout.description || ''}</span>
            </div>
        `;

        // Structure/instructions from coach
        if (workout.structure) {
            const structure = typeof workout.structure === 'string' ? workout.structure : JSON.stringify(workout.structure, null, 2);
            html += `
                <div class="bg-blue-50 rounded-xl p-4 border border-blue-200">
                    <h4 class="text-xs uppercase font-bold text-blue-600 mb-2 flex items-center gap-2">
                        <i data-lucide="clipboard" class="w-4 h-4"></i> Instrucciones del Entrenador
                    </h4>
                    <p class="text-slate-700 whitespace-pre-wrap text-sm">${structure}</p>
                </div>
            `;
        }

        if (workout.status === 'completed') {
            // Show results
            html += `
                <div class="bg-green-50 rounded-xl p-4 border border-green-200">
                    <h4 class="text-xs uppercase font-bold text-green-600 mb-3">Tus Resultados</h4>
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div>
                            <p class="text-xs text-slate-500">Distancia</p>
                            <p class="text-lg font-bold text-slate-900">${workout.actual_distance ? workout.actual_distance + ' km' : '-'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Tiempo</p>
                            <p class="text-lg font-bold text-slate-900">${workout.actual_time ? workout.actual_time + ' min' : '-'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">RPE</p>
                            <p class="text-lg font-bold text-slate-900">${workout.rpe ? workout.rpe + '/10' : '-'}</p>
                        </div>
                    </div>
                </div>
            `;

            if (workout.feedback) {
                html += `
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                        <h4 class="text-xs uppercase font-bold text-slate-500 mb-2">Tu Feedback</h4>
                        <p class="text-slate-700">${workout.feedback}</p>
                    </div>
                `;
            }

            if (workout.coach_feedback) {
                html += `
                    <div class="bg-purple-50 rounded-xl p-4 border border-purple-200">
                        <h4 class="text-xs uppercase font-bold text-purple-600 mb-2 flex items-center gap-2">
                            <i data-lucide="check-check" class="w-4 h-4"></i> Respuesta del Entrenador
                        </h4>
                        <p class="text-slate-700">${workout.coach_feedback}</p>
                    </div>
                `;
            }
        } else {
            // Show complete form
            html += `
                <form method="POST" enctype="multipart/form-data" class="space-y-4 border-t border-slate-100 pt-4">
                    <input type="hidden" name="action" value="complete_workout">
                    <input type="hidden" name="workout_id" value="${workout.id}">
                    <h4 class="text-sm font-bold text-slate-700">Registrar Resultados</h4>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Distancia (km)</label>
                            <input type="number" name="actual_distance" step="0.01" placeholder="0.00"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Tiempo (min)</label>
                            <input type="number" name="actual_time" step="0.1" placeholder="0"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">RPE (1-10)</label>
                            <select name="rpe" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                                <option value="">-</option>
                                ${[1, 2, 3, 4, 5, 6, 7, 8, 9, 10].map(n => `<option value="${n}">${n}</option>`).join('')}
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Feedback (opcional)</label>
                        <textarea name="feedback" rows="2" placeholder="¿Cómo te sentiste?"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Evidencia (foto/captura)</label>
                        <input type="file" name="evidence" accept="image/*"
                            class="w-full text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-100 file:text-blue-700 file:font-semibold hover:file:bg-blue-200">
                    </div>
                    <button type="submit"
                        class="w-full bg-green-600 text-white py-3 rounded-xl font-bold hover:bg-green-700 transition-all">
                        ✅ Completar Entrenamiento
                    </button>
                </form>
            `;
        }

        content.innerHTML = html;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        lucide.createIcons();
    }

    function closeWorkoutModal() {
        document.getElementById('workoutModal').classList.add('hidden');
        document.getElementById('workoutModal').classList.remove('flex');
    }
</script>

<?php include 'views/layout/footer.php'; ?>
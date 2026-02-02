<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'models/Workout.php';
require_once 'models/User.php';

Auth::init();
Auth::requireRole('athlete');

$user = Auth::user();
$athlete = User::getById($user['id']);

// Manejar registro de resultados
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete_workout') {
    $workoutId = $_POST['workout_id'];
    $data = [
        'actual_distance' => $_POST['actual_distance'],
        'actual_time' => $_POST['actual_time'],
        'rpe' => $_POST['rpe'],
        'feedback' => $_POST['feedback'],
        'status' => 'completed',
        'completed_at' => date('Y-m-d H:i:s')
    ];

    Workout::update($workoutId, $data);
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

// Indexar entrenamientos por fecha para fácil acceso
$indexedWorkouts = [];
foreach ($workouts as $w) {
    $dateKey = (new DateTime($w['date']))->format('Y-m-d');
    $indexedWorkouts[$dateKey] = $w;
}

include 'views/layout/header.php';
?>

<!-- Perfil del Atleta (Banner) -->
<div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-2xl p-8 mb-8 text-white shadow-xl">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight">¡Hola,
                <?php echo htmlspecialchars($athlete['name']); ?>! 👋
            </h1>
            <p class="text-blue-100 mt-2 opacity-90">Aquí tienes tu plan para esta semana. Sigue enfocado en tu
                objetivo: <span class="font-bold underline">
                    <?php echo htmlspecialchars($athlete['goal_pace'] ?? '-'); ?>/km
                </span></p>
        </div>
        <div class="flex gap-4">
            <div class="bg-white/10 backdrop-blur-md p-4 rounded-xl border border-white/20">
                <span class="block text-xs uppercase text-blue-200 font-bold mb-1">Nivel</span>
                <span class="text-lg font-bold">
                    <?php echo htmlspecialchars($athlete['level'] ?? 'Principiante'); ?>
                </span>
            </div>
            <div class="bg-white/10 backdrop-blur-md p-4 rounded-xl border border-white/20">
                <span class="block text-xs uppercase text-blue-200 font-bold mb-1">Prox. Meta</span>
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
                            <div class="flex items-center gap-1 text-green-600 text-xs font-bold mt-2">
                                <i data-lucide="check-circle" class="w-4 h-4"></i>
                                Completado
                            </div>
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
            <form method="POST" class="space-y-4">
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

<script>
    function openWorkoutModal(workout) {
        const modal = document.getElementById('workoutModal');
        const modalIconContainer = document.getElementById('modalIconContainer');

        document.getElementById('modalWorkoutId').value = workout.id;
        document.getElementById('modalTitle').innerText = workout.type;
        document.getElementById('modalDate').innerText = moment(workout.date).format('dddd D [de] MMMM');
        document.getElementById('modalDescription').innerText = workout.description;
        document.getElementById('modalStructure').innerText = workout.structure ? workout.structure : 'No hay detalles adicionales.';

        // Ajustar colores según tipo
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

    function closeModal() {
        document.getElementById('workoutModal').classList.add('hidden');
        document.getElementById('workoutModal').classList.remove('flex');
    }

    // Cerrar modal si se hace clic fuera del contenido
    window.onclick = function (event) {
        const modal = document.getElementById('workoutModal');
        if (event.target == modal) {
            closeModal();
        }
    }
</script>

<?php include 'views/layout/footer.php'; ?>
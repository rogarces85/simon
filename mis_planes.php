<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'models/User.php';
require_once 'models/Workout.php';

Auth::init();
Auth::requireRole('coach');

$coach = Auth::user();

// Get athletes for filter
$athletes = User::getByCoachId($coach['id']);
$athleteId = $_GET['athlete_id'] ?? 'all';
$statusFilter = $_GET['status'] ?? 'all';
$periodFilter = $_GET['period'] ?? 'all';

// Calculate date range based on period filter
$dateFrom = null;
$dateTo = null;
$now = new DateTime();

switch ($periodFilter) {
    case 'this_week':
        $dateFrom = (clone $now)->modify('monday this week')->format('Y-m-d');
        $dateTo = (clone $now)->modify('sunday this week')->format('Y-m-d');
        break;
    case 'last_week':
        $dateFrom = (clone $now)->modify('monday last week')->format('Y-m-d');
        $dateTo = (clone $now)->modify('sunday last week')->format('Y-m-d');
        break;
    case 'this_month':
        $dateFrom = $now->format('Y-m-01');
        $dateTo = $now->format('Y-m-t');
        break;
    case 'last_month':
        $lastMonth = (clone $now)->modify('first day of last month');
        $dateFrom = $lastMonth->format('Y-m-01');
        $dateTo = $lastMonth->format('Y-m-t');
        break;
    default:
        // 'all' - no date filter
        break;
}

// Get all workouts (filtered)
$allWorkouts = Workout::getAllByCoach(
    $coach['id'],
    $athleteId !== 'all' ? $athleteId : null,
    $statusFilter !== 'all' ? $statusFilter : null,
    $dateFrom,
    $dateTo
);

// Get summary stats (with same date filter)
$plansSummary = Workout::getPlansSummaryByCoach($coach['id'], $dateFrom, $dateTo);

include 'views/layout/header.php';
?>

<!-- Page Header -->
<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
    <div>
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">MIS PLANES GENERADOS</h1>
        <p class="text-slate-500 mt-1">Visualiza todos los planes de entrenamiento asignados a tus atletas</p>
    </div>
    
    <!-- Filters -->
    <div class="flex flex-wrap gap-3 w-full md:w-auto">
        <form method="GET" id="filterForm" class="flex flex-wrap gap-3">
            <select name="period" onchange="this.form.submit()"
                class="px-4 py-3 rounded-xl border border-slate-200 bg-white focus:ring-2 focus:ring-blue-500 outline-none text-slate-700 font-medium">
                <option value="all" <?php echo $periodFilter === 'all' ? 'selected' : ''; ?>>Todos los Períodos</option>
                <option value="this_week" <?php echo $periodFilter === 'this_week' ? 'selected' : ''; ?>>Esta Semana</option>
                <option value="last_week" <?php echo $periodFilter === 'last_week' ? 'selected' : ''; ?>>Semana Pasada</option>
                <option value="this_month" <?php echo $periodFilter === 'this_month' ? 'selected' : ''; ?>>Este Mes</option>
                <option value="last_month" <?php echo $periodFilter === 'last_month' ? 'selected' : ''; ?>>Mes Anterior</option>
            </select>
            <select name="athlete_id" onchange="this.form.submit()"
                class="px-4 py-3 rounded-xl border border-slate-200 bg-white focus:ring-2 focus:ring-blue-500 outline-none text-slate-700 font-medium">
                <option value="all">Todos los Atletas</option>
                <?php foreach ($athletes as $athlete): ?>
                    <option value="<?php echo $athlete['id']; ?>" <?php echo $athleteId == $athlete['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($athlete['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="status" onchange="this.form.submit()"
                class="px-4 py-3 rounded-xl border border-slate-200 bg-white focus:ring-2 focus:ring-blue-500 outline-none text-slate-700 font-medium">
                <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>Todos los Estados</option>
                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pendientes</option>
                <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completados</option>
            </select>
        </form>
    </div>
</div>

<!-- Period indicator -->
<?php if ($periodFilter !== 'all' && $dateFrom && $dateTo): ?>
<div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-xl mb-6 flex items-center gap-2 text-sm">
    <i data-lucide="calendar" class="w-4 h-4"></i>
    Mostrando planes del <strong><?php echo (new DateTime($dateFrom))->format('d M Y'); ?></strong> al <strong><?php echo (new DateTime($dateTo))->format('d M Y'); ?></strong>
</div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center">
                <i data-lucide="layers" class="w-6 h-6 text-slate-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900">
                    <?php echo $plansSummary['total_plans'] ?? 0; ?>
                </p>
                <p class="text-sm text-slate-500">Total Planes</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                <i data-lucide="clock" class="w-6 h-6 text-amber-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900">
                    <?php echo $plansSummary['pending_count'] ?? 0; ?>
                </p>
                <p class="text-sm text-slate-500">Pendientes</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                <i data-lucide="check-circle" class="w-6 h-6 text-blue-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900">
                    <?php echo $plansSummary['completed_no_feedback'] ?? 0; ?>
                </p>
                <p class="text-sm text-slate-500">Completados</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                <i data-lucide="message-circle" class="w-6 h-6 text-green-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900">
                    <?php echo $plansSummary['with_feedback_count'] ?? 0; ?>
                </p>
                <p class="text-sm text-slate-500">Con Feedback</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                <i data-lucide="check-check" class="w-6 h-6 text-purple-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900">
                    <?php echo $plansSummary['responded_count'] ?? 0; ?>
                </p>
                <p class="text-sm text-slate-500">Respondidos</p>
            </div>
        </div>
    </div>
</div>

<!-- Plans List -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-6 border-b border-slate-100 flex justify-between items-center">
        <h2 class="text-xl font-bold text-slate-900">Historial de Planes</h2>
        <span class="text-sm text-slate-500"><?php echo count($allWorkouts); ?> planes encontrados</span>
    </div>

    <?php if (empty($allWorkouts)): ?>
        <div class="p-12 text-center text-slate-500">
            <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-4 opacity-30"></i>
            <p>No hay planes generados aún</p>
            <a href="generar_plan.php" class="text-blue-600 font-semibold hover:underline">Genera tu primer plan</a>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="text-left px-6 py-4 text-sm font-semibold text-slate-600">Atleta</th>
                        <th class="text-left px-6 py-4 text-sm font-semibold text-slate-600">Fecha</th>
                        <th class="text-left px-6 py-4 text-sm font-semibold text-slate-600">Tipo</th>
                        <th class="text-left px-6 py-4 text-sm font-semibold text-slate-600">Descripción</th>
                        <th class="text-left px-6 py-4 text-sm font-semibold text-slate-600">Estado</th>
                        <th class="text-left px-6 py-4 text-sm font-semibold text-slate-600">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($allWorkouts as $workout): ?>
                        <?php
                        // Determine status badge
                        $statusBadge = '';
                        $statusClass = '';
                        if ($workout['status'] === 'pending') {
                            $statusBadge = '🟡 Pendiente';
                            $statusClass = 'bg-amber-100 text-amber-700';
                        } elseif ($workout['status'] === 'completed' && $workout['coach_feedback']) {
                            $statusBadge = '✅ Respondido';
                            $statusClass = 'bg-purple-100 text-purple-700';
                        } elseif ($workout['status'] === 'completed' && $workout['feedback']) {
                            $statusBadge = '🟢 Con Feedback';
                            $statusClass = 'bg-green-100 text-green-700';
                        } elseif ($workout['status'] === 'completed') {
                            $statusBadge = '🔵 Completado';
                            $statusClass = 'bg-blue-100 text-blue-700';
                        }

                        // Type colors
                        $typeColors = [
                            'Series' => 'bg-purple-100 text-purple-600',
                            'Intervalos' => 'bg-orange-100 text-orange-600',
                            'Fondo' => 'bg-blue-100 text-blue-600',
                            'Tempo' => 'bg-red-100 text-red-600',
                            'Recuperación' => 'bg-green-100 text-green-600',
                            'Descanso' => 'bg-slate-100 text-slate-600'
                        ];
                        $typeColor = $typeColors[$workout['type']] ?? 'bg-slate-100 text-slate-600';
                        ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-900"><?php echo htmlspecialchars($workout['athlete_name']); ?></div>
                                <div class="text-xs text-slate-500"><?php echo htmlspecialchars($workout['athlete_email']); ?></div>
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                <?php echo (new DateTime($workout['date']))->format('d M Y'); ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-bold <?php echo $typeColor; ?>">
                                    <?php echo htmlspecialchars($workout['type']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-600 max-w-xs truncate">
                                <?php echo htmlspecialchars($workout['description'] ?? '-'); ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $statusClass; ?>">
                                    <?php echo $statusBadge; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <button onclick='openDetailModal(<?php echo json_encode($workout); ?>)'
                                    class="text-blue-500 hover:text-blue-700 p-2 rounded-lg hover:bg-blue-50 transition-all"
                                    title="Ver Detalles">
                                    <i data-lucide="eye" class="w-5 h-5"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Detail Modal -->
<div id="detailModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto m-4">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center">
            <div>
                <h3 class="text-xl font-bold text-slate-900" id="modalTitle">DETALLE DEL PLAN</h3>
                <p class="text-slate-500 text-sm mt-1" id="modalSubtitle">Información completa del entrenamiento</p>
            </div>
            <button onclick="closeDetailModal()" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>

        <div class="p-6 space-y-6" id="modalContent">
            <!-- Content filled by JavaScript -->
        </div>
    </div>
</div>

<script>
    function openDetailModal(workout) {
        const modal = document.getElementById('detailModal');
        const content = document.getElementById('modalContent');

        // Status badge
        let statusBadge = '';
        let statusClass = '';
        if (workout.status === 'pending') {
            statusBadge = '🟡 Pendiente';
            statusClass = 'bg-amber-100 text-amber-700';
        } else if (workout.status === 'completed' && workout.coach_feedback) {
            statusBadge = '✅ Respondido';
            statusClass = 'bg-purple-100 text-purple-700';
        } else if (workout.status === 'completed' && workout.feedback) {
            statusBadge = '🟢 Con Feedback';
            statusClass = 'bg-green-100 text-green-700';
        } else if (workout.status === 'completed') {
            statusBadge = '🔵 Completado';
            statusClass = 'bg-blue-100 text-blue-700';
        }

        let html = `
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs uppercase font-bold text-slate-500">Atleta</label>
                    <p class="font-semibold text-slate-900">${workout.athlete_name}</p>
                </div>
                <div>
                    <label class="text-xs uppercase font-bold text-slate-500">Fecha</label>
                    <p class="font-semibold text-slate-900">${new Date(workout.date).toLocaleDateString('es-CL', { day: 'numeric', month: 'long', year: 'numeric' })}</p>
                </div>
                <div>
                    <label class="text-xs uppercase font-bold text-slate-500">Tipo</label>
                    <p class="font-semibold text-slate-900">${workout.type}</p>
                </div>
                <div>
                    <label class="text-xs uppercase font-bold text-slate-500">Estado</label>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold ${statusClass}">${statusBadge}</span>
                </div>
            </div>
            <div>
                <label class="text-xs uppercase font-bold text-slate-500">Descripción</label>
                <p class="text-slate-700">${workout.description || '-'}</p>
            </div>
        `;

        if (workout.status === 'completed') {
            html += `
                <div class="bg-slate-50 rounded-xl p-4 space-y-3">
                    <h4 class="text-sm font-bold text-slate-700">Resultados del Atleta</h4>
                    <div class="grid grid-cols-3 gap-4 text-sm">
                        <div>
                            <span class="text-slate-500">Distancia:</span>
                            <span class="font-semibold text-slate-900">${workout.actual_distance ? workout.actual_distance + ' km' : '-'}</span>
                        </div>
                        <div>
                            <span class="text-slate-500">Tiempo:</span>
                            <span class="font-semibold text-slate-900">${workout.actual_time ? workout.actual_time + ' min' : '-'}</span>
                        </div>
                        <div>
                            <span class="text-slate-500">RPE:</span>
                            <span class="font-semibold text-slate-900">${workout.rpe ? workout.rpe + '/10' : '-'}</span>
                        </div>
                    </div>
                </div>
            `;
        }

        if (workout.feedback) {
            html += `
                <div class="bg-green-50 rounded-xl p-4 border border-green-200">
                    <h4 class="text-xs uppercase font-bold text-green-600 mb-2 flex items-center gap-2">
                        <i data-lucide="message-circle" class="w-4 h-4"></i> Feedback del Atleta
                    </h4>
                    <p class="text-slate-700">${workout.feedback}</p>
                </div>
            `;
        }

        if (workout.coach_feedback) {
            html += `
                <div class="bg-purple-50 rounded-xl p-4 border border-purple-200">
                    <h4 class="text-xs uppercase font-bold text-purple-600 mb-2 flex items-center gap-2">
                        <i data-lucide="check-check" class="w-4 h-4"></i> Tu Respuesta
                    </h4>
                    <p class="text-slate-700">${workout.coach_feedback}</p>
                    <p class="text-xs text-purple-600 mt-2">${workout.coach_feedback_at ? new Date(workout.coach_feedback_at).toLocaleString('es-CL') : ''}</p>
                </div>
            `;
        }

        // If has feedback but no coach response, show link to respond
        if (workout.feedback && !workout.coach_feedback) {
            html += `
                <div class="pt-4 border-t border-slate-100">
                    <a href="entrenamientos.php" class="inline-flex items-center gap-2 text-blue-600 font-semibold hover:underline">
                        <i data-lucide="send" class="w-4 h-4"></i>
                        Ir a responder feedback
                    </a>
                </div>
            `;
        }

        content.innerHTML = html;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        lucide.createIcons();
    }

    function closeDetailModal() {
        document.getElementById('detailModal').classList.add('hidden');
        document.getElementById('detailModal').classList.remove('flex');
    }
</script>

<?php include 'views/layout/footer.php'; ?>

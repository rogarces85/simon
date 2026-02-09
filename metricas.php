<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'models/User.php';
require_once 'models/Workout.php';
Auth::init();
Auth::requireRole('coach');

$coach = Auth::user();
$db = Database::getInstance();
$athletes = User::getByCoachId($coach['id']);

$athleteId = $_GET['athlete_id'] ?? 'all';

// Build Query Logic
$whereClause = "JOIN users u ON w.athlete_id = u.id WHERE u.coach_id = ?";
$params = [$coach['id']];

if ($athleteId !== 'all') {
    $whereClause .= " AND w.athlete_id = ?";
    $params[] = $athleteId;
}

// Stats: Completed Workouts
$stmt = $db->prepare("SELECT COUNT(*) FROM workouts w $whereClause AND w.status = 'completed'");
$stmt->execute($params);
$completedCount = $stmt->fetchColumn();

// Stats: Pending This Week
$stmt = $db->prepare("SELECT COUNT(*) FROM workouts w $whereClause AND w.status = 'pending' AND WEEK(w.date, 1) = WEEK(CURDATE(), 1)");
$stmt->execute($params);
$pendingThisWeek = $stmt->fetchColumn();

// Stats: Compliance Rate
$stmt = $db->prepare("SELECT COUNT(*) FROM workouts w $whereClause");
$stmt->execute($params);
$totalWorkouts = $stmt->fetchColumn();
$complianceRate = $totalWorkouts > 0 ? round(($completedCount / $totalWorkouts) * 100) : 0;

// Get individual athlete metrics or all athletes metrics
if ($athleteId !== 'all') {
    $athleteMetrics = Workout::getAthleteMetrics($athleteId);
    $progressionData = Workout::getProgressionData($athleteId, 8);
    $selectedAthlete = null;
    foreach ($athletes as $a) {
        if ($a['id'] == $athleteId) {
            $selectedAthlete = $a;
            break;
        }
    }
} else {
    $athletesMetrics = Workout::getCoachAthletesMetrics($coach['id']);
}

include 'views/layout/header.php';
?>

<!-- Page Header -->
<div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4">
    <div>
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">MÉTRICAS</h1>
        <p class="text-slate-500 mt-1">Analiza el progreso de tu equipo</p>
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
            <noscript><button type="submit" class="mt-2 text-sm text-blue-600">Filtrar</button></noscript>
        </form>
    </div>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                <i data-lucide="users" class="w-6 h-6 text-blue-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900">
                    <?php echo $athleteId === 'all' ? count($athletes) : 1; ?>
                </p>
                <p class="text-sm text-slate-500">Atletas Visualizados</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                <i data-lucide="check-circle" class="w-6 h-6 text-green-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900"><?php echo $completedCount; ?></p>
                <p class="text-sm text-slate-500">Entrenamientos Completados</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
                <i data-lucide="clock" class="w-6 h-6 text-orange-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900"><?php echo $pendingThisWeek; ?></p>
                <p class="text-sm text-slate-500">Pendientes Esta Semana</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                <i data-lucide="trending-up" class="w-6 h-6 text-purple-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900"><?php echo $complianceRate; ?>%</p>
                <p class="text-sm text-slate-500">Tasa de Cumplimiento</p>
            </div>
        </div>
    </div>
</div>

<?php if ($athleteId !== 'all' && isset($athleteMetrics)): ?>
    <!-- Individual Athlete Metrics -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Athlete Summary -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                <i data-lucide="user" class="w-5 h-5 text-blue-600"></i>
                <?php echo htmlspecialchars($selectedAthlete['name'] ?? 'Atleta'); ?>
            </h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                    <span class="text-slate-600">Distancia Total</span>
                    <span class="font-bold text-slate-900"><?php echo number_format($athleteMetrics['total_distance'] ?? 0, 1); ?> km</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                    <span class="text-slate-600">Tiempo Total</span>
                    <span class="font-bold text-slate-900"><?php echo number_format($athleteMetrics['total_time'] ?? 0, 0); ?> min</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                    <span class="text-slate-600">Ritmo Promedio</span>
                    <span class="font-bold text-slate-900"><?php echo $athleteMetrics['avg_pace'] ? number_format($athleteMetrics['avg_pace'], 2) . ' min/km' : '-'; ?></span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                    <span class="text-slate-600">RPE Promedio</span>
                    <span class="font-bold text-slate-900"><?php echo $athleteMetrics['avg_rpe'] ? number_format($athleteMetrics['avg_rpe'], 1) . '/10' : '-'; ?></span>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="text-slate-600">Entrenamientos</span>
                    <span class="font-bold text-slate-900"><?php echo $athleteMetrics['completed_workouts'] ?? 0; ?> completados</span>
                </div>
            </div>
        </div>

        <!-- Distance Chart -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                <i data-lucide="bar-chart-3" class="w-5 h-5 text-green-600"></i>
                Volumen Semanal (km)
            </h3>
            <div class="h-48">
                <canvas id="distanceChart"></canvas>
            </div>
        </div>

        <!-- Pace Chart -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                <i data-lucide="activity" class="w-5 h-5 text-purple-600"></i>
                Evolución de Ritmo (min/km)
            </h3>
            <div class="h-48">
                <canvas id="paceChart"></canvas>
            </div>
        </div>
    </div>

    <!-- RPE Trend -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-8">
        <h3 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
            <i data-lucide="heart" class="w-5 h-5 text-red-500"></i>
            Tendencia de Esfuerzo Percibido (RPE)
        </h3>
        <div class="h-48">
            <canvas id="rpeChart"></canvas>
        </div>
    </div>

<?php else: ?>
    <!-- All Athletes Comparison Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-8">
        <div class="p-6 border-b border-slate-100">
            <h3 class="text-lg font-bold text-slate-900">Comparativa de Atletas</h3>
        </div>
        <?php if (empty($athletesMetrics)): ?>
            <div class="p-12 text-center text-slate-500">
                <i data-lucide="users" class="w-12 h-12 mx-auto mb-4 opacity-30"></i>
                <p>No hay atletas registrados</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left px-6 py-4 text-sm font-semibold text-slate-600">Atleta</th>
                            <th class="text-center px-6 py-4 text-sm font-semibold text-slate-600">Distancia Total</th>
                            <th class="text-center px-6 py-4 text-sm font-semibold text-slate-600">Tiempo Total</th>
                            <th class="text-center px-6 py-4 text-sm font-semibold text-slate-600">Ritmo Prom.</th>
                            <th class="text-center px-6 py-4 text-sm font-semibold text-slate-600">RPE Prom.</th>
                            <th class="text-center px-6 py-4 text-sm font-semibold text-slate-600">Cumplimiento</th>
                            <th class="text-center px-6 py-4 text-sm font-semibold text-slate-600">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($athletesMetrics as $athlete): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="font-medium text-slate-900"><?php echo htmlspecialchars($athlete['athlete_name']); ?></span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="font-semibold text-slate-900"><?php echo number_format($athlete['total_distance'] ?? 0, 1); ?> km</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-slate-600"><?php echo number_format($athlete['total_time'] ?? 0, 0); ?> min</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-slate-600"><?php echo $athlete['avg_pace'] ? number_format($athlete['avg_pace'], 2) : '-'; ?></span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if ($athlete['avg_rpe']): ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-bold <?php 
                                            echo $athlete['avg_rpe'] <= 5 ? 'bg-green-100 text-green-600' : 
                                                ($athlete['avg_rpe'] <= 7 ? 'bg-yellow-100 text-yellow-600' : 'bg-red-100 text-red-600'); 
                                        ?>">
                                            <?php echo number_format($athlete['avg_rpe'], 1); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <div class="w-16 bg-slate-200 rounded-full h-2">
                                            <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $athlete['compliance_rate']; ?>%"></div>
                                        </div>
                                        <span class="text-xs font-medium text-slate-600"><?php echo $athlete['compliance_rate']; ?>%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <a href="metricas.php?athlete_id=<?php echo $athlete['athlete_id']; ?>" 
                                       class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                                        Ver Detalles
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php if ($athleteId !== 'all' && isset($progressionData)): ?>
<script>
const progressionData = <?php echo json_encode($progressionData); ?>;

// Prepare data for charts
const weeks = progressionData.map(d => {
    const date = new Date(d.week_start);
    return date.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' });
});
const distances = progressionData.map(d => parseFloat(d.total_distance) || 0);
const paces = progressionData.map(d => d.avg_pace || null);
const rpes = progressionData.map(d => parseFloat(d.avg_rpe) || null);

// Distance Chart
new Chart(document.getElementById('distanceChart'), {
    type: 'bar',
    data: {
        labels: weeks,
        datasets: [{
            label: 'Kilómetros',
            data: distances,
            backgroundColor: 'rgba(34, 197, 94, 0.7)',
            borderColor: 'rgb(34, 197, 94)',
            borderWidth: 1,
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
            x: { grid: { display: false } }
        }
    }
});

// Pace Chart
new Chart(document.getElementById('paceChart'), {
    type: 'line',
    data: {
        labels: weeks,
        datasets: [{
            label: 'min/km',
            data: paces,
            borderColor: 'rgb(147, 51, 234)',
            backgroundColor: 'rgba(147, 51, 234, 0.1)',
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: 'rgb(147, 51, 234)'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { reverse: true, grid: { color: 'rgba(0,0,0,0.05)' } },
            x: { grid: { display: false } }
        }
    }
});

// RPE Chart
new Chart(document.getElementById('rpeChart'), {
    type: 'line',
    data: {
        labels: weeks,
        datasets: [{
            label: 'RPE',
            data: rpes,
            borderColor: 'rgb(239, 68, 68)',
            backgroundColor: 'rgba(239, 68, 68, 0.1)',
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: 'rgb(239, 68, 68)'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { min: 0, max: 10, grid: { color: 'rgba(0,0,0,0.05)' } },
            x: { grid: { display: false } }
        }
    }
});
</script>
<?php endif; ?>

<?php include 'views/layout/footer.php'; ?>
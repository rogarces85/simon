<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'models/User.php';
require_once 'models/Workout.php';
require_once 'models/Team.php';

Auth::init();
Auth::requireRole('athlete');

$user = Auth::user();
$db = Database::getInstance();

// Get team info
$team = Team::findByCoach($user['coach_id'] ?? 0);

// Get athlete metrics
$metrics = Workout::getAthleteMetrics($user['id']);
$progressionData = Workout::getProgressionData($user['id'], 8);
$streak = Workout::getAthleteStreak($user['id']);
$recentWorkouts = Workout::getRecentCompletedByAthlete($user['id'], 20);

// Calculate additional stats
$totalDistance = $metrics['total_distance'] ?? 0;
$totalTime = $metrics['total_time'] ?? 0;
$totalWorkouts = $metrics['total_workouts'] ?? 0;
$avgPace = ($totalDistance > 0 && $totalTime > 0) ? round($totalTime / $totalDistance, 2) : 0;
$avgRpe = $metrics['avg_rpe'] ?? 0;
$complianceRate = $metrics['compliance_rate'] ?? 0;

// Prepare chart data
$weekLabels = [];
$distanceData = [];
$paceData = [];
$rpeData = [];

if ($progressionData) {
    $weekCounter = 1;
    foreach ($progressionData as $week) {
        $weekLabels[] = 'Sem ' . $weekCounter++;
        $distanceData[] = round($week['total_distance'] ?? 0, 1);
        $paceData[] = round($week['avg_pace'] ?? 0, 2);
        $rpeData[] = round($week['avg_rpe'] ?? 0, 1);
    }
}

include 'views/layout/header.php';
?>

<!-- Page Header -->
<div class="mb-8">
    <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">MI PROGRESO</h1>
    <p class="text-slate-500 mt-1">Visualiza tu evolución y estadísticas de entrenamiento</p>
</div>

<!-- Summary Stats -->
<div class="grid grid-cols-2 lg:grid-cols-6 gap-4 mb-8">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <div class="text-center">
            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mx-auto mb-2">
                <i data-lucide="map-pin" class="w-6 h-6 text-blue-600"></i>
            </div>
            <p class="text-2xl font-bold text-slate-900">
                <?php echo round($totalDistance, 1); ?>
            </p>
            <p class="text-xs text-slate-500">km Totales</p>
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <div class="text-center">
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mx-auto mb-2">
                <i data-lucide="clock" class="w-6 h-6 text-green-600"></i>
            </div>
            <p class="text-2xl font-bold text-slate-900">
                <?php echo round($totalTime); ?>
            </p>
            <p class="text-xs text-slate-500">min Totales</p>
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <div class="text-center">
            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mx-auto mb-2">
                <i data-lucide="activity" class="w-6 h-6 text-purple-600"></i>
            </div>
            <p class="text-2xl font-bold text-slate-900">
                <?php echo $totalWorkouts; ?>
            </p>
            <p class="text-xs text-slate-500">Entrenamientos</p>
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <div class="text-center">
            <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center mx-auto mb-2">
                <i data-lucide="gauge" class="w-6 h-6 text-amber-600"></i>
            </div>
            <p class="text-2xl font-bold text-slate-900">
                <?php echo $avgPace; ?>
            </p>
            <p class="text-xs text-slate-500">min/km Prom</p>
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <div class="text-center">
            <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center mx-auto mb-2">
                <i data-lucide="heart-pulse" class="w-6 h-6 text-red-600"></i>
            </div>
            <p class="text-2xl font-bold text-slate-900">
                <?php echo round($avgRpe, 1); ?>
            </p>
            <p class="text-xs text-slate-500">RPE Promedio</p>
        </div>
    </div>
    <div class="bg-gradient-to-br from-orange-500 to-red-500 rounded-2xl shadow-sm p-5 text-white">
        <div class="text-center">
            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mx-auto mb-2">
                <i data-lucide="flame" class="w-6 h-6 text-white"></i>
            </div>
            <p class="text-2xl font-bold">
                <?php echo $streak; ?>
            </p>
            <p class="text-xs opacity-90">Racha (días)</p>
        </div>
    </div>
</div>

<!-- Compliance Bar -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-8">
    <div class="flex justify-between items-center mb-3">
        <h3 class="font-bold text-slate-900 flex items-center gap-2">
            <i data-lucide="target" class="w-5 h-5 text-blue-600"></i>
            Tasa de Cumplimiento
        </h3>
        <span class="text-2xl font-bold text-blue-600">
            <?php echo round($complianceRate); ?>%
        </span>
    </div>
    <div class="w-full bg-slate-200 rounded-full h-4 overflow-hidden">
        <div class="h-full rounded-full transition-all duration-1000 <?php echo $complianceRate >= 80 ? 'bg-green-500' : ($complianceRate >= 50 ? 'bg-amber-500' : 'bg-red-500'); ?>"
            style="width: <?php echo min($complianceRate, 100); ?>%"></div>
    </div>
    <p class="text-xs text-slate-500 mt-2">
        <?php if ($complianceRate >= 80): ?>
            🏆 ¡Excelente! Mantén este ritmo.
        <?php elseif ($complianceRate >= 50): ?>
            💪 ¡Buen trabajo! Intenta completar más sesiones.
        <?php else: ?>
            🎯 Cada entrenamiento cuenta. ¡Ánimo!
        <?php endif; ?>
    </p>
</div>

<!-- Charts -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Volume Chart -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <h3 class="font-bold text-slate-900 mb-4 flex items-center gap-2">
            <i data-lucide="bar-chart-3" class="w-5 h-5 text-blue-600"></i>
            Volumen Semanal (km)
        </h3>
        <canvas id="volumeChart" height="200"></canvas>
    </div>

    <!-- RPE Chart -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <h3 class="font-bold text-slate-900 mb-4 flex items-center gap-2">
            <i data-lucide="heart-pulse" class="w-5 h-5 text-red-600"></i>
            RPE Semanal (Esfuerzo Percibido)
        </h3>
        <canvas id="rpeChart" height="200"></canvas>
    </div>
</div>

<!-- Pace Chart Full Width -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-8">
    <h3 class="font-bold text-slate-900 mb-4 flex items-center gap-2">
        <i data-lucide="trending-down" class="w-5 h-5 text-green-600"></i>
        Ritmo Promedio Semanal (min/km)
    </h3>
    <canvas id="paceChart" height="150"></canvas>
</div>

<!-- Recent Workouts History -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-6 border-b border-slate-100">
        <h3 class="text-xl font-bold text-slate-900 flex items-center gap-2">
            <i data-lucide="history" class="w-5 h-5 text-slate-600"></i>
            Historial Reciente
        </h3>
    </div>

    <?php if (empty($recentWorkouts)): ?>
        <div class="p-12 text-center text-slate-500">
            <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-4 opacity-30"></i>
            <p>Aún no tienes entrenamientos completados</p>
            <a href="mi_plan.php" class="text-blue-600 font-semibold hover:underline">Ver mi programación</a>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="text-left px-6 py-3 text-sm font-semibold text-slate-600">Fecha</th>
                        <th class="text-left px-6 py-3 text-sm font-semibold text-slate-600">Tipo</th>
                        <th class="text-left px-6 py-3 text-sm font-semibold text-slate-600">Descripción</th>
                        <th class="text-left px-6 py-3 text-sm font-semibold text-slate-600">Distancia</th>
                        <th class="text-left px-6 py-3 text-sm font-semibold text-slate-600">Tiempo</th>
                        <th class="text-left px-6 py-3 text-sm font-semibold text-slate-600">RPE</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($recentWorkouts as $w): ?>
                        <?php
                        $typeColors = [
                            'Series' => 'bg-purple-100 text-purple-600',
                            'Intervalos' => 'bg-orange-100 text-orange-600',
                            'Fondo' => 'bg-blue-100 text-blue-600',
                            'Tempo' => 'bg-red-100 text-red-600',
                            'Recuperación' => 'bg-green-100 text-green-600'
                        ];
                        $typeColor = $typeColors[$w['type']] ?? 'bg-slate-100 text-slate-600';
                        ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-3 text-sm text-slate-700">
                                <?php echo (new DateTime($w['date']))->format('d M Y'); ?>
                            </td>
                            <td class="px-6 py-3">
                                <span class="px-3 py-1 rounded-full text-xs font-bold <?php echo $typeColor; ?>">
                                    <?php echo htmlspecialchars($w['type']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-3 text-sm text-slate-600 max-w-[200px] truncate">
                                <?php echo htmlspecialchars($w['description'] ?? '-'); ?>
                            </td>
                            <td class="px-6 py-3 text-sm font-semibold text-slate-900">
                                <?php echo $w['actual_distance'] ? $w['actual_distance'] . ' km' : '-'; ?>
                            </td>
                            <td class="px-6 py-3 text-sm font-semibold text-slate-900">
                                <?php echo $w['actual_time'] ? $w['actual_time'] . ' min' : '-'; ?>
                            </td>
                            <td class="px-6 py-3">
                                <?php if ($w['rpe']): ?>
                                    <?php $rpeColor = $w['rpe'] <= 3 ? 'text-green-600 bg-green-100' : ($w['rpe'] <= 6 ? 'text-amber-600 bg-amber-100' : 'text-red-600 bg-red-100'); ?>
                                    <span class="px-2 py-1 rounded-lg text-sm font-bold <?php echo $rpeColor; ?>">
                                        <?php echo $w['rpe']; ?>/10
                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-400">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    const weekLabels = <?php echo json_encode(array_reverse($weekLabels)); ?>;
    const distanceData = <?php echo json_encode(array_reverse($distanceData)); ?>;
    const paceData = <?php echo json_encode(array_reverse($paceData)); ?>;
    const rpeData = <?php echo json_encode(array_reverse($rpeData)); ?>;

    const commonOptions = {
        responsive: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { font: { size: 11, weight: '600' }, color: '#64748b' }
            },
            y: {
                beginAtZero: true,
                grid: { color: '#f1f5f9' },
                ticks: { font: { size: 11 }, color: '#94a3b8' }
            }
        }
    };

    // Volume Chart (Bar)
    new Chart(document.getElementById('volumeChart'), {
        type: 'bar',
        data: {
            labels: weekLabels,
            datasets: [{
                data: distanceData,
                backgroundColor: 'rgba(59, 130, 246, 0.6)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            ...commonOptions,
            scales: {
                ...commonOptions.scales,
                y: { ...commonOptions.scales.y, title: { display: true, text: 'km', font: { size: 11 } } }
            }
        }
    });

    // RPE Chart (Line)
    new Chart(document.getElementById('rpeChart'), {
        type: 'line',
        data: {
            labels: weekLabels,
            datasets: [{
                data: rpeData,
                borderColor: 'rgba(239, 68, 68, 1)',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: 'rgba(239, 68, 68, 1)',
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            ...commonOptions,
            scales: {
                ...commonOptions.scales,
                y: { ...commonOptions.scales.y, max: 10, title: { display: true, text: 'RPE', font: { size: 11 } } }
            }
        }
    });

    // Pace Chart (Line)
    new Chart(document.getElementById('paceChart'), {
        type: 'line',
        data: {
            labels: weekLabels,
            datasets: [{
                data: paceData,
                borderColor: 'rgba(16, 185, 129, 1)',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: 'rgba(16, 185, 129, 1)',
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            ...commonOptions,
            scales: {
                ...commonOptions.scales,
                y: {
                    ...commonOptions.scales.y,
                    reverse: true,
                    title: { display: true, text: 'min/km (menor es mejor)', font: { size: 11 } }
                }
            }
        }
    });
</script>

<?php include 'views/layout/footer.php'; ?>
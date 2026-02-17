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

$metrics = Workout::getAthleteMetrics($user['id']);
$progressionData = Workout::getProgressionData($user['id'], 8);
$streak = Workout::getAthleteStreak($user['id']);
$recentWorkouts = Workout::getRecentCompletedByAthlete($user['id'], 15);

$totalDistance = $metrics['total_distance'] ?? 0;
$totalTime = $metrics['total_time'] ?? 0;
$avgPace = ($totalDistance > 0 && $totalTime > 0) ? round($totalTime / $totalDistance, 2) : 0;
$complianceRate = $metrics['compliance_rate'] ?? 0;

$weekLabels = [];
$distanceData = [];
$paceData = [];

if ($progressionData) {
    $progressionData = array_reverse($progressionData);
    foreach ($progressionData as $i => $week) {
        $weekLabels[] = 'S' . ($i + 1);
        $distanceData[] = round($week['total_distance'] ?? 0, 1);
        $paceData[] = round($week['avg_pace'] ?? 0, 2);
    }
}

include 'views/layout/header.php';
?>

<!-- Progress Summary Grid -->
<div
    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <div class="stat-card">
        <div
            style="width: 32px; height: 32px; background: rgba(13, 242, 128, 0.1); border-radius: 6px; color: var(--primary); display: flex; align-items: center; justify-content: center; margin-bottom: 0.5rem;">
            <i data-lucide="map" style="width: 16px; height: 16px;"></i>
        </div>
        <div class="stat-value"><?php echo round($totalDistance, 1); ?> <span
                style="font-size: 0.9rem; font-weight: 500; color: var(--text-muted);">km</span></div>
        <div class="stat-label">Distancia total</div>
    </div>

    <div class="stat-card">
        <div
            style="width: 32px; height: 32px; background: rgba(59, 130, 246, 0.1); border-radius: 6px; color: #3b82f6; display: flex; align-items: center; justify-content: center; margin-bottom: 0.5rem;">
            <i data-lucide="timer" style="width: 16px; height: 16px;"></i>
        </div>
        <div class="stat-value"><?php echo round($totalTime); ?> <span
                style="font-size: 0.9rem; font-weight: 500; color: var(--text-muted);">min</span></div>
        <div class="stat-label">Tiempo total</div>
    </div>

    <div class="stat-card" style="background: var(--primary); border: none;">
        <div
            style="width: 32px; height: 32px; background: rgba(0,0,0,0.1); border-radius: 6px; color: #0f172a; display: flex; align-items: center; justify-content: center; margin-bottom: 0.5rem;">
            <i data-lucide="zap" style="width: 16px; height: 16px;"></i>
        </div>
        <div class="stat-value" style="color: #0f172a;"><?php echo $streak; ?> <span
                style="font-size: 0.9rem; font-weight: 500; opacity: 0.6;">días</span></div>
        <div class="stat-label" style="color: #0f172a; font-weight: 600;">Racha actual</div>
    </div>

    <div class="stat-card">
        <div
            style="width: 32px; height: 32px; background: rgba(245, 158, 11, 0.1); border-radius: 6px; color: #f59e0b; display: flex; align-items: center; justify-content: center; margin-bottom: 0.5rem;">
            <i data-lucide="trending-up" style="width: 16px; height: 16px;"></i>
        </div>
        <div class="stat-value"><?php echo round($complianceRate); ?>%</div>
        <div class="stat-label">Cumplimiento</div>
    </div>
</div>

<!-- Analytics Charts -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
    <div class="card">
        <h4
            style="font-weight: 700; margin-bottom: 1.5rem; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
            <i data-lucide="bar-chart-3" style="width: 18px; height: 18px; color: var(--primary);"></i> Volumen por
            Semana
        </h4>
        <canvas id="volChart" height="220"></canvas>
    </div>
    <div class="card">
        <h4
            style="font-weight: 700; margin-bottom: 1.5rem; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
            <i data-lucide="activity" style="width: 18px; height: 18px; color: var(--primary);"></i> Evolución de Ritmo
        </h4>
        <canvas id="ritmoChart" height="220"></canvas>
    </div>
</div>

<!-- Detailed History Card -->
<div class="card" style="padding: 0; overflow: hidden;">
    <div
        style="padding: 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
        <h4 style="font-weight: 700; margin: 0;">Historial de Entrenamientos</h4>
        <span class="badge badge-emerald"><?php echo count($recentWorkouts); ?> Sesiones</span>
    </div>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
            <thead style="background: var(--bg-main); color: var(--text-muted); font-weight: 700;">
                <tr>
                    <th style="padding: 1rem 1.5rem;">FECHA</th>
                    <th style="padding: 1rem 1.5rem;">SESIÓN</th>
                    <th style="padding: 1rem 1.5rem;">DATO</th>
                    <th style="padding: 1rem 1.5rem;">ESFUERZO</th>
                    <th style="padding: 1rem 1.5rem;">ESTADO</th>
                </tr>
            </thead>
            <tbody style="color: var(--text-main);">
                <?php foreach ($recentWorkouts as $w): ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 1rem 1.5rem; font-weight: 600;">
                            <?php echo (new DateTime($w['date']))->format('d M, Y'); ?></td>
                        <td style="padding: 1rem 1.5rem;">
                            <span
                                style="font-weight: 700; opacity: 0.8;"><?php echo htmlspecialchars($w['type']); ?></span><br>
                            <span
                                style="font-size: 0.75rem; color: var(--text-muted);"><?php echo mb_strimwidth($w['description'], 0, 25, '...'); ?></span>
                        </td>
                        <td style="padding: 1rem 1.5rem; font-weight: 800;">
                            <?php echo $w['actual_distance'] ? $w['actual_distance'] . ' km' : '--'; ?>
                        </td>
                        <td style="padding: 1rem 1.5rem;">
                            <?php if ($w['rpe']): ?>
                                <div style="display: flex; align-items: center; gap: 4px;">
                                    <div
                                        style="width: 8px; height: 8px; border-radius: 50%; background: <?php echo $w['rpe'] > 7 ? '#ef4444' : ($w['rpe'] > 4 ? '#f59e0b' : 'var(--primary)'); ?>;">
                                    </div>
                                    <span style="font-weight: 700;"><?php echo $w['rpe']; ?>/10</span>
                                </div>
                            <?php else: ?>
                                <span style="color: var(--text-muted);">--</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 1rem 1.5rem;">
                            <span class="badge badge-emerald">LOGRADO</span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    const labels = <?php echo json_encode($weekLabels); ?>;
    const distance = <?php echo json_encode($distanceData); ?>;
    const pace = <?php echo json_encode($paceData); ?>;

    const chartStyle = {
        font: { family: 'Lexend', size: 10, weight: '600' },
        color: 'rgba(100, 116, 139, 0.4)'
    };

    // Vol Chart
    new Chart(document.getElementById('volChart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'km',
                data: distance,
                backgroundColor: '#0df280',
                borderRadius: 4,
                maxBarThickness: 30
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: chartStyle },
                y: { grid: { color: 'rgba(0,0,0,0.03)' }, ticks: chartStyle }
            }
        }
    });

    // Ritmo Chart
    new Chart(document.getElementById('ritmoChart'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'min/km',
                data: pace,
                borderColor: '#3b82f6',
                borderWidth: 3,
                pointBackgroundColor: '#3b82f6',
                tension: 0.4,
                fill: false
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: chartStyle },
                y: { reverse: true, grid: { color: 'rgba(0,0,0,0.03)' }, ticks: chartStyle }
            }
        }
    });
</script>

<?php include 'views/layout/footer.php'; ?>
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

// NEW: Recent Activity for Sidebar
$recentStmt = $db->prepare("SELECT w.*, u.name as athlete_name FROM workouts w $whereClause ORDER BY w.date DESC LIMIT 5");
$recentStmt->execute($params);
$recentWorkouts = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

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

<!-- Page Title & Actions -->
<div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
    <div>
        <h1 class="text-4xl md:text-5xl font-black tracking-tight text-white mb-2">Performance Dashboard</h1>
        <p class="text-[#9cbaab] text-lg">Track your fitness progression and training load over time.</p>
    </div>
    <div class="flex items-center gap-3">
        <!-- Athlete Filter -->
        <div class="relative">
            <form method="GET" id="filterForm">
                <select name="athlete_id" onchange="this.form.submit()"
                    class="pl-4 pr-10 py-2.5 rounded-lg bg-[#182c23] border border-[#283930] text-white text-sm font-medium hover:bg-[#1e362b] transition-colors appearance-none cursor-pointer outline-none focus:ring-2 focus:ring-primary/50">
                    <option value="all">All Athletes</option>
                    <?php foreach ($athletes as $athlete): ?>
                            <option value="<?php echo $athlete['id']; ?>" <?php echo $athleteId == $athlete['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($athlete['name']); ?>
                            </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <button
            class="flex items-center gap-2 bg-[#182c23] border border-[#283930] text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-[#1e362b] transition-colors">
            <i data-lucide="download" class="w-4 h-4 text-[#9cbaab]"></i>
            <span>Export</span>
        </button>
    </div>
</div>

<!-- KPI Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <!-- Card 1: Total Athletes / Views -->
    <div class="bg-[#182c23] rounded-xl p-6 border border-[#283930] hover:border-primary/30 transition-colors group">
        <div class="flex justify-between items-start mb-4">
            <div class="p-2 bg-[#1e362b] rounded-lg text-primary">
                <i data-lucide="users" class="w-6 h-6"></i>
            </div>
            <span class="flex items-center text-primary text-sm font-medium bg-primary/10 px-2 py-1 rounded">
                <i data-lucide="trending-up" class="w-3 h-3 mr-1"></i> +1
            </span>
        </div>
        <p class="text-[#9cbaab] text-sm font-medium mb-1">Active Athletes</p>
        <p class="text-white text-3xl font-bold tracking-tight group-hover:text-primary transition-colors">
            <?php echo $athleteId === 'all' ? count($athletes) : 1; ?>
        </p>
    </div>

    <!-- Card 2: Completed Workouts -->
    <div class="bg-[#182c23] rounded-xl p-6 border border-[#283930] hover:border-primary/30 transition-colors group">
        <div class="flex justify-between items-start mb-4">
            <div class="p-2 bg-[#1e362b] rounded-lg text-primary">
                <i data-lucide="check-circle" class="w-6 h-6"></i>
            </div>
            <span class="flex items-center text-primary text-sm font-medium bg-primary/10 px-2 py-1 rounded">
                <i data-lucide="trending-up" class="w-3 h-3 mr-1"></i> +12%
            </span>
        </div>
        <p class="text-[#9cbaab] text-sm font-medium mb-1">Completed (All Time)</p>
        <p class="text-white text-3xl font-bold tracking-tight group-hover:text-primary transition-colors">
            <?php echo $completedCount; ?>
        </p>
    </div>

    <!-- Card 3: Pending This Week -->
    <div class="bg-[#182c23] rounded-xl p-6 border border-[#283930] hover:border-primary/30 transition-colors group">
        <div class="flex justify-between items-start mb-4">
            <div class="p-2 bg-[#1e362b] rounded-lg text-orange-400">
                <i data-lucide="clock" class="w-6 h-6"></i>
            </div>
            <span class="flex items-center text-orange-400 text-sm font-medium bg-orange-400/10 px-2 py-1 rounded">
                <i data-lucide="alert-circle" class="w-3 h-3 mr-1"></i> Due
            </span>
        </div>
        <p class="text-[#9cbaab] text-sm font-medium mb-1">Pending This Week</p>
        <p class="text-white text-3xl font-bold tracking-tight group-hover:text-orange-400 transition-colors">
            <?php echo $pendingThisWeek; ?>
        </p>
    </div>

    <!-- Card 4: Compliance -->
    <div class="bg-[#182c23] rounded-xl p-6 border border-[#283930] hover:border-primary/30 transition-colors group">
        <div class="flex justify-between items-start mb-4">
            <div class="p-2 bg-[#1e362b] rounded-lg text-purple-400">
                <i data-lucide="activity" class="w-6 h-6"></i>
            </div>
            <span class="flex items-center text-purple-400 text-sm font-medium bg-purple-400/10 px-2 py-1 rounded">
                <i data-lucide="bar-chart-2" class="w-3 h-3 mr-1"></i> Rate
            </span>
        </div>
        <p class="text-[#9cbaab] text-sm font-medium mb-1">Compliance Rate</p>
        <p class="text-white text-3xl font-bold tracking-tight group-hover:text-purple-400 transition-colors">
            <?php echo $complianceRate; ?>%
        </p>
    </div>
</div>

<!-- Main Section: Charts & Side Panel -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

    <!-- Large Chart Area -->
    <div class="lg:col-span-2 bg-[#182c23] rounded-xl border border-[#283930] p-6 lg:p-8 flex flex-col min-h-[420px]">
        <?php if ($athleteId !== 'all' && isset($progressionData)): ?>
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h3 class="text-white text-lg font-bold">Weekly Volume</h3>
                        <p class="text-[#9cbaab] text-sm">Distance (km) vs Pace (min/km) over last 8 weeks</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-primary"></span>
                            <span class="text-xs text-white">Distance</span>
                        </div>
                    </div>
                </div>
                <div class="relative flex-1 w-full h-full">
                    <canvas id="mainChart"></canvas>
                </div>
        <?php else: ?>
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h3 class="text-white text-lg font-bold">Team Overview</h3>
                        <p class="text-[#9cbaab] text-sm">Compliance per Athlete</p>
                    </div>
                </div>
                <?php if (empty($athletesMetrics)): ?>
                        <div class="flex flex-col items-center justify-center flex-1 text-[#9cbaab]">
                            <i data-lucide="bar-chart" class="w-12 h-12 mb-2 opacity-50"></i>
                            <p>No data available</p>
                        </div>
                <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="text-[#9cbaab] text-xs uppercase border-b border-[#283930]">
                                    <tr>
                                        <th class="pb-3 pl-2">Athlete</th>
                                        <th class="pb-3">Distance</th>
                                        <th class="pb-3">Compliance</th>
                                        <th class="pb-3"></th>
                                    </tr>
                                </thead>
                                <tbody class="text-sm">
                                    <?php foreach ($athletesMetrics as $m): ?>
                                            <tr class="border-b border-[#283930] last:border-0 group hover:bg-[#1e362b] transition-colors">
                                                <td class="py-3 pl-2 font-bold text-white"><?php echo htmlspecialchars($m['athlete_name']); ?>
                                                </td>
                                                <td class="py-3 text-[#9cbaab]"><?php echo number_format($m['total_distance'], 1); ?> km</td>
                                                <td class="py-3">
                                                    <div class="flex items-center gap-2">
                                                        <div class="w-24 h-1.5 bg-[#283930] rounded-full overflow-hidden">
                                                            <div class="h-full bg-primary" style="width: <?php echo $m['compliance_rate']; ?>%">
                                                            </div>
                                                        </div>
                                                        <span class="text-white font-bold"><?php echo $m['compliance_rate']; ?>%</span>
                                                    </div>
                                                </td>
                                                <td class="py-3 text-right">
                                                    <a href="metricas.php?athlete_id=<?php echo $m['athlete_id']; ?>"
                                                        class="text-primary hover:text-white transition-colors text-xs font-bold uppercase">View</a>
                                                </td>
                                            </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Side Stats Panel (Recent Activity) -->
    <div class="flex flex-col gap-4">
        <div class="flex-1 bg-[#182c23] rounded-xl border border-[#283930] p-6">
            <h3 class="text-white text-lg font-bold mb-4">Recent Activities</h3>
            <div class="flex flex-col gap-3">

                <?php foreach ($recentWorkouts as $workout): ?>
                        <?php
                        $iconData = match ($workout['type']) {
                            'Fondo' => ['icon' => 'mountain', 'bg' => 'bg-blue-500/20', 'text' => 'text-blue-400'],
                            'Series' => ['icon' => 'zap', 'bg' => 'bg-purple-500/20', 'text' => 'text-purple-400'],
                            'Intervalos' => ['icon' => 'timer', 'bg' => 'bg-orange-500/20', 'text' => 'text-orange-400'],
                            'Recuperación' => ['icon' => 'heart', 'bg' => 'bg-green-500/20', 'text' => 'text-green-400'],
                            default => ['icon' => 'activity', 'bg' => 'bg-[#283930]', 'text' => 'text-white']
                        };
                        ?>
                        <div
                            class="flex items-center justify-between p-3 rounded-lg hover:bg-[#1e362b] transition-colors cursor-pointer group border border-transparent hover:border-[#283930]">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-full <?php echo $iconData['bg']; ?> flex items-center justify-center <?php echo $iconData['text']; ?>">
                                    <i data-lucide="<?php echo $iconData['icon']; ?>" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <p class="text-white text-sm font-bold"><?php echo htmlspecialchars($workout['type']); ?>
                                    </p>
                                    <p class="text-[#9cbaab] text-xs"><?php echo htmlspecialchars($workout['athlete_name']); ?>
                                        • <?php echo (new DateTime($workout['date']))->format('M j'); ?></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <?php if ($workout['status'] === 'completed'): ?>
                                        <p class="text-white text-sm font-bold group-hover:text-primary transition-colors">
                                            <?php echo $workout['actual_distance']; ?> km</p>
                                        <p class="text-[#9cbaab] text-xs"><?php echo $workout['rpe']; ?> RPE</p>
                                <?php else: ?>
                                        <span
                                            class="text-xs text-amber-400 font-bold bg-amber-400/10 px-2 py-0.5 rounded">Pending</span>
                                <?php endif; ?>
                            </div>
                        </div>
                <?php endforeach; ?>

                <?php if (empty($recentWorkouts)): ?>
                        <p class="text-[#9cbaab] text-sm text-center py-4">No recent activity</p>
                <?php endif; ?>

            </div>
        </div>

        <!-- VO2 Max Mini Card (Static Placeholder for Design Match) -->
        <div
            class="bg-gradient-to-br from-[#182c23] to-[#1e362b] rounded-xl border border-[#283930] p-6 relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-10">
                <i data-lucide="activity" class="w-24 h-24 text-white"></i>
            </div>
            <div class="relative z-10">
                <p class="text-[#9cbaab] text-sm font-medium mb-1">Team Avg RPE</p>
                <div class="flex items-baseline gap-2">
                    <p class="text-white text-4xl font-black">7.2</p>
                    <p class="text-primary text-sm font-bold flex items-center">
                        <i data-lucide="arrow-up" class="w-3 h-3"></i> 0.4
                    </p>
                </div>
                <div class="mt-4 w-full bg-[#283930] h-1.5 rounded-full overflow-hidden">
                    <div class="bg-primary h-full rounded-full" style="width: 72%"></div>
                </div>
                <p class="text-[#9cbaab] text-xs mt-2">Moderate / Hard Intensity</p>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php if ($athleteId !== 'all' && isset($progressionData)): ?>
        <script>
            const progressionData = <?php echo json_encode($progressionData); ?>;

            // Prepare data
            const weeks = progressionData.map(d => {
                const date = new Date(d.week_start);
                return date.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' });
            });
            const distances = progressionData.map(d => parseFloat(d.total_distance) || 0);

            const ctx = document.getElementById('mainChart').getContext('2d');

            // Gradient for bars
            const gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, '#0df280');
            gradient.addColorStop(1, 'rgba(13, 242, 128, 0.2)');

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: weeks,
                    datasets: [{
                        label: 'Distance (km)',
                        data: distances,
                        backgroundColor: gradient,
                        borderRadius: 4,
                        barPercentage: 0.6,
                        hoverBackgroundColor: '#0be075'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            border: { display: false },
                            grid: { color: 'rgba(255,255,255,0.05)' },
                            ticks: { color: '#9cbaab', font: { family: 'Lexend' } }
                        },
                        x: {
                            border: { display: false },
                            grid: { display: false },
                            ticks: { color: '#9cbaab', font: { family: 'Lexend' } }
                        }
                    }
                }
            });
        </script>
<?php endif; ?>

<?php include 'views/layout/footer.php'; ?>

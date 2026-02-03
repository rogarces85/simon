<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'models/User.php';
Auth::init();
Auth::requireRole('coach');

$coach = Auth::user();
$athletes = User::getByCoachId($coach['id']);

$athleteId = $_GET['athlete_id'] ?? 'all';

// Build Query Logic
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

<!-- Charts Placeholder -->
<div
    class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 text-center min-h-[300px] flex flex-col items-center justify-center">
    <div class="w-20 h-20 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
        <i data-lucide="bar-chart-3" class="w-10 h-10 text-slate-400"></i>
    </div>
    <h3 class="text-lg font-semibold text-slate-900 mb-2">
        <?php echo $athleteId === 'all' ? 'Rendimiento Global del Equipo' : 'Rendimiento Individual'; ?>
    </h3>
    <p class="text-slate-500 max-w-md mx-auto">
        Próximamente visualizarás gráficos detallados de volumen, intensidad y progresión
        <?php echo $athleteId === 'all' ? 'de tu equipo.' : 'para este atleta.'; ?>
    </p>
</div>

<?php include 'views/layout/footer.php'; ?>
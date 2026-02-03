<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'models/User.php';

Auth::init();
Auth::requireRole('coach');

$coach = Auth::user();
$db = Database::getInstance();
$athletes = User::getByCoachId($coach['id']);

$athleteId = $_GET['athlete_id'] ?? 'all';

// Build Query Logic
// Build Query Logic
$whereClause = "WHERE u.coach_id = ? AND w.status = 'completed'";
$params = [$coach['id']];

if ($athleteId !== 'all') {
    $whereClause .= " AND w.athlete_id = ?";
    $params[] = $athleteId;
}

// Fetch Completed Workouts
$sql = "SELECT w.*, u.name as athlete_name 
        FROM workouts w 
        JOIN users u ON w.athlete_id = u.id 
        $whereClause 
        ORDER BY w.completed_at DESC, w.date DESC 
        LIMIT 50";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$workouts = $stmt->fetchAll();

include 'views/layout/header.php';
?>

<div class="max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">REPORTES DE ENTRENAMIENTO</h1>
            <p class="text-slate-500 mt-1">Historial de sesiones completadas</p>
        </div>

        <!-- Filter -->
        <div class="w-64">
            <form method="GET">
                <select name="athlete_id" onchange="this.form.submit()"
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white focus:ring-2 focus:ring-blue-500 outline-none text-slate-700 font-medium">
                    <option value="all">Filtro: Todos</option>
                    <?php foreach ($athletes as $athlete): ?>
                        <option value="<?php echo $athlete['id']; ?>" <?php echo $athleteId == $athlete['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($athlete['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <!-- Reports Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <?php if (empty($workouts)): ?>
            <div class="p-12 text-center text-slate-500">
                <i data-lucide="clipboard-x" class="w-12 h-12 mx-auto mb-4 text-slate-300"></i>
                <p class="text-lg font-medium">No hay registros de entrenamientos completados.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead
                        class="bg-slate-50 border-b border-slate-100 uppercase font-semibold text-xs text-slate-500 tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Fecha</th>
                            <th class="px-6 py-4">Atleta</th>
                            <th class="px-6 py-4">Entrenamiento</th>
                            <th class="px-6 py-4 text-center">RPE</th>
                            <th class="px-6 py-4">Distancia / Tiempo</th>
                            <th class="px-6 py-4">Feedback</th>
                            <th class="px-6 py-4 text-right">Evidencia</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($workouts as $workout): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap font-medium text-slate-900">
                                    <?php echo (new DateTime($workout['date']))->format('d M, Y'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-6 h-6 bg-slate-200 rounded-full flex items-center justify-center text-xs font-bold text-slate-600">
                                            <?php echo strtoupper(substr($workout['athlete_name'], 0, 1)); ?>
                                        </div>
                                        <?php echo htmlspecialchars($workout['athlete_name']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 max-w-xs">
                                    <div class="truncate font-medium text-slate-900"
                                        title="<?php echo htmlspecialchars($workout['description']); ?>">
                                        <?php echo htmlspecialchars($workout['type']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2 py-1 rounded font-bold text-xs 
                                    <?php
                                    echo $workout['rpe'] >= 8 ? 'bg-red-100 text-red-700' :
                                        ($workout['rpe'] >= 5 ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700');
                                    ?>">
                                        <?php echo $workout['rpe']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($workout['actual_distance']): ?>
                                        <div class="font-bold">
                                            <?php echo $workout['actual_distance']; ?> km
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($workout['actual_time']): ?>
                                        <div class="text-xs text-slate-500">
                                            <?php echo $workout['actual_time']; ?> min
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 max-w-xs">
                                    <p class="truncate text-slate-500"
                                        title="<?php echo htmlspecialchars($workout['feedback']); ?>">
                                        <?php echo htmlspecialchars($workout['feedback'] ?? '-'); ?>
                                    </p>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <?php if ($workout['evidence_url']): ?>
                                        <a href="<?php echo htmlspecialchars($workout['evidence_url']); ?>" target="_blank"
                                            class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 text-xs font-bold">
                                            <i data-lucide="image" class="w-4 h-4"></i>
                                            Ver
                                        </a>
                                    <?php else: ?>
                                        <span class="text-slate-300">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'views/layout/footer.php'; ?>
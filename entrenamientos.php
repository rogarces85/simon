<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'models/User.php';
require_once 'models/Workout.php';
require_once 'models/Notification.php';

Auth::init();
Auth::requireRole('coach');

$coach = Auth::user();
$db = Database::getInstance();

// Get athletes for filter
$athletes = User::getByCoachId($coach['id']);
$athleteId = $_GET['athlete_id'] ?? 'all';
$periodFilter = $_GET['period'] ?? 'all';

// Handle coach feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_feedback') {
        $workoutId = $_POST['workout_id'];
        $feedback = $_POST['coach_feedback'];
        Workout::addCoachFeedback($workoutId, $feedback);

        // Get the workout to find the athlete
        $workout = Workout::getById($workoutId);
        if ($workout) {
            $msg = "💬 Tu entrenador ha respondido a tu feedback del entrenamiento del " . (new DateTime($workout['date']))->format('d/m/Y');
            Notification::create($workout['athlete_id'], $msg, 'info');
        }

        header('Location: entrenamientos.php?success=feedback&athlete_id=' . $athleteId . '&period=' . $periodFilter);
        exit;
    }
}

// Calculate date range
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
}

// Get completed workouts
$sql = "SELECT w.*, u.name as athlete_name, u.username as athlete_email
        FROM workouts w
        JOIN users u ON w.athlete_id = u.id
        WHERE u.coach_id = ? AND w.status = 'completed'";
$params = [$coach['id']];

if ($athleteId !== 'all') {
    $sql .= " AND w.athlete_id = ?";
    $params[] = $athleteId;
}
if ($dateFrom) {
    $sql .= " AND w.date >= ?";
    $params[] = $dateFrom;
}
if ($dateTo) {
    $sql .= " AND w.date <= ?";
    $params[] = $dateTo;
}

$sql .= " ORDER BY w.completed_at DESC, w.date DESC LIMIT 100";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$completedWorkouts = $stmt->fetchAll();

// Decode structures
foreach ($completedWorkouts as &$w) {
    if ($w['structure'])
        $w['structure'] = json_decode($w['structure'], true);
}
unset($w);

// Get delivery stats
$statsSql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN w.status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN w.status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN w.status = 'completed' AND w.feedback IS NOT NULL AND w.coach_feedback IS NULL THEN 1 ELSE 0 END) as awaiting_response,
    SUM(CASE WHEN w.status = 'completed' AND w.coach_feedback IS NOT NULL THEN 1 ELSE 0 END) as responded
FROM workouts w
JOIN users u ON w.athlete_id = u.id
WHERE u.coach_id = ?";
$stmt = $db->prepare($statsSql);
$stmt->execute([$coach['id']]);
$stats = $stmt->fetch();

include 'views/layout/header.php';
?>

<!-- Style for Emerald Theme -->
<style>
    .text-emerald-accent {
        color: #0df280;
    }

    .bg-emerald-accent {
        background-color: #0df280;
    }

    .border-emerald-accent {
        border-color: #0df280;
    }

    .hover\:bg-emerald-accent:hover {
        background-color: #0df280;
    }

    .group:hover .group-hover\:text-emerald-accent {
        color: #0df280;
    }
</style>

<!-- Page Header -->
<div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-8">
    <div class="flex flex-col gap-1">
        <nav class="flex items-center gap-2 text-sm text-slate-400 mb-1">
            <span>Management</span>
            <i data-lucide="chevron-right" class="w-3 h-3"></i>
            <span class="text-white">Training History</span>
        </nav>
        <h1 class="text-3xl font-black text-white tracking-tight">Completed Workouts</h1>
        <p class="text-slate-400">Review training logs and provide athlete feedback</p>
    </div>

    <div class="flex flex-wrap gap-3 w-full md:w-auto">
        <form method="GET" class="contents">
            <div class="flex items-center bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 gap-2">
                <i data-lucide="calendar" class="text-slate-400 w-4 h-4"></i>
                <select name="period" onchange="this.form.submit()"
                    class="bg-transparent border-none text-white text-sm font-medium focus:ring-0 p-0 cursor-pointer w-32 outline-none">
                    <option value="all" <?php echo $periodFilter === 'all' ? 'selected' : ''; ?>>All Periods</option>
                    <option value="this_week" <?php echo $periodFilter === 'this_week' ? 'selected' : ''; ?>>This Week
                    </option>
                    <option value="last_week" <?php echo $periodFilter === 'last_week' ? 'selected' : ''; ?>>Last Week
                    </option>
                    <option value="this_month" <?php echo $periodFilter === 'this_month' ? 'selected' : ''; ?>>This Month
                    </option>
                </select>
            </div>

            <div class="flex items-center bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 gap-2">
                <i data-lucide="users" class="text-slate-400 w-4 h-4"></i>
                <select name="athlete_id" onchange="this.form.submit()"
                    class="bg-transparent border-none text-white text-sm font-medium focus:ring-0 p-0 cursor-pointer w-40 outline-none">
                    <option value="all">All Athletes</option>
                    <?php foreach ($athletes as $athlete): ?>
                        <option value="<?php echo $athlete['id']; ?>" <?php echo $athleteId == $athlete['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($athlete['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div
        class="bg-emerald-accent/10 border border-emerald-accent text-emerald-accent px-4 py-3 rounded-xl mb-6 flex items-center gap-2">
        <i data-lucide="check-circle" class="w-5 h-5"></i>
        Feedback sent successfully
    </div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div
        class="bg-slate-800 border border-slate-700 p-4 rounded-xl flex items-center gap-4 group transition-colors hover:border-blue-500/50">
        <div class="p-3 bg-blue-500/10 rounded-lg text-blue-400">
            <i data-lucide="layers" class="w-6 h-6"></i>
        </div>
        <div>
            <p class="text-slate-400 text-xs font-medium uppercase">Total Workouts</p>
            <p class="text-xl font-bold text-white group-hover:text-blue-400 transition-colors">
                <?php echo $stats['total'] ?? 0; ?></p>
        </div>
    </div>
    <div
        class="bg-slate-800 border border-slate-700 p-4 rounded-xl flex items-center gap-4 group transition-colors hover:border-emerald-accent/50">
        <div class="p-3 bg-emerald-accent/10 rounded-lg text-emerald-accent">
            <i data-lucide="check-circle" class="w-6 h-6"></i>
        </div>
        <div>
            <p class="text-slate-400 text-xs font-medium uppercase">Completed</p>
            <p class="text-xl font-bold text-white group-hover:text-emerald-accent transition-colors">
                <?php echo $stats['completed'] ?? 0; ?></p>
        </div>
    </div>
    <div
        class="bg-slate-800 border border-slate-700 p-4 rounded-xl flex items-center gap-4 group transition-colors hover:border-amber-500/50">
        <div class="p-3 bg-amber-500/10 rounded-lg text-amber-400">
            <i data-lucide="message-circle" class="w-6 h-6"></i>
        </div>
        <div>
            <p class="text-slate-400 text-xs font-medium uppercase">Pending Reply</p>
            <p class="text-xl font-bold text-white group-hover:text-amber-400 transition-colors">
                <?php echo $stats['awaiting_response'] ?? 0; ?></p>
        </div>
    </div>
    <div
        class="bg-slate-800 border border-slate-700 p-4 rounded-xl flex items-center gap-4 group transition-colors hover:border-purple-500/50">
        <div class="p-3 bg-purple-500/10 rounded-lg text-purple-400">
            <i data-lucide="check-check" class="w-6 h-6"></i>
        </div>
        <div>
            <p class="text-slate-400 text-xs font-medium uppercase">Responded</p>
            <p class="text-xl font-bold text-white group-hover:text-purple-400 transition-colors">
                <?php echo $stats['responded'] ?? 0; ?></p>
        </div>
    </div>
</div>

<!-- Workouts Grid -->
<?php if (empty($completedWorkouts)): ?>
    <div class="bg-slate-800 border border-slate-700 rounded-xl p-12 text-center">
        <div class="w-16 h-16 bg-slate-700/50 rounded-full flex items-center justify-center mx-auto mb-4">
            <i data-lucide="inbox" class="w-8 h-8 text-slate-400"></i>
        </div>
        <p class="text-slate-400">No completed workouts found for this period</p>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php foreach ($completedWorkouts as $workout): ?>
            <?php
            $hasAthlFeedback = !empty($workout['feedback']);
            $hasCoachFeedback = !empty($workout['coach_feedback']);
            ?>
            <div
                class="bg-slate-800 border border-slate-700 rounded-xl p-5 hover:border-slate-600 transition-all flex flex-col gap-4 group relative overflow-hidden">
                <!-- Top Accent -->
                <div
                    class="absolute top-0 left-0 w-1 h-full <?php echo $hasCoachFeedback ? 'bg-purple-500' : ($hasAthlFeedback ? 'bg-amber-500' : 'bg-emerald-accent'); ?>">
                </div>

                <div class="flex justify-between items-start pl-3 relative z-10">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-full bg-slate-700 flex items-center justify-center text-white font-bold border border-slate-600">
                            <?php echo substr($workout['athlete_name'], 0, 1); ?>
                        </div>
                        <div>
                            <h3 class="text-white font-bold text-sm"><?php echo htmlspecialchars($workout['athlete_name']); ?>
                            </h3>
                            <p class="text-slate-400 text-xs"><?php echo (new DateTime($workout['date']))->format('M j, Y'); ?>
                            </p>
                        </div>
                    </div>
                    <span class="px-2 py-1 rounded-md text-xs font-bold bg-[#0f172a] border border-slate-700 text-slate-300">
                        <?php echo htmlspecialchars($workout['type']); ?>
                    </span>
                </div>

                <div class="grid grid-cols-3 gap-2 pl-3">
                    <div class="bg-[#0f172a] p-2 rounded-lg border border-slate-700/50 text-center">
                        <span class="text-[10px] text-slate-500 uppercase block">Dist</span>
                        <span
                            class="text-white font-bold text-sm"><?php echo $workout['actual_distance'] ? $workout['actual_distance'] . ' km' : '-'; ?></span>
                    </div>
                    <div class="bg-[#0f172a] p-2 rounded-lg border border-slate-700/50 text-center">
                        <span class="text-[10px] text-slate-500 uppercase block">Time</span>
                        <span
                            class="text-white font-bold text-sm"><?php echo $workout['actual_time'] ? $workout['actual_time'] . ' min' : '-'; ?></span>
                    </div>
                    <div class="bg-[#0f172a] p-2 rounded-lg border border-slate-700/50 text-center">
                        <span class="text-[10px] text-slate-500 uppercase block">RPE</span>
                        <span
                            class="text-white font-bold text-sm"><?php echo $workout['rpe'] ? $workout['rpe'] . '/10' : '-'; ?></span>
                    </div>
                </div>

                <div class="flex items-center justify-between pl-3 mt-1">
                    <div>
                        <?php if ($hasCoachFeedback): ?>
                            <span
                                class="flex items-center gap-1.5 text-xs text-purple-400 font-bold bg-purple-500/10 px-2 py-1 rounded-full">
                                <i data-lucide="check-check" class="w-3 h-3"></i> Responded
                            </span>
                        <?php elseif ($hasAthlFeedback): ?>
                            <span
                                class="flex items-center gap-1.5 text-xs text-amber-400 font-bold bg-amber-500/10 px-2 py-1 rounded-full">
                                <i data-lucide="message-circle" class="w-3 h-3"></i> Needs Reply
                            </span>
                        <?php else: ?>
                            <span class="flex items-center gap-1.5 text-xs text-slate-500 font-medium">
                                No feedback
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="flex gap-2">
                        <?php if (!empty($workout['evidence_path'])): ?>
                            <a href="<?php echo htmlspecialchars($workout['evidence_path']); ?>" target="_blank"
                                class="p-2 text-slate-400 hover:text-white hover:bg-slate-700 rounded-lg transition-colors border border-transparent hover:border-slate-600">
                                <i data-lucide="image" class="w-4 h-4"></i>
                            </a>
                        <?php endif; ?>
                        <button onclick='openWorkoutModal(<?php echo json_encode($workout); ?>)'
                            class="text-xs bg-slate-700 text-white hover:bg-emerald-accent hover:text-[#0f172a] border border-slate-600 hover:border-emerald-accent px-3 py-2 rounded-lg transition-all font-bold flex items-center gap-2">
                            Details <i data-lucide="arrow-right" class="w-3 h-3"></i>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Workout Detail + Feedback Modal -->
<div id="workoutModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm hidden items-center justify-center z-50">
    <div
        class="bg-slate-800 rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto m-4 border border-slate-700 shadow-2xl">
        <div
            class="p-6 border-b border-slate-700 flex justify-between items-center bg-slate-800/50 sticky top-0 backdrop-blur-md">
            <div>
                <h3 class="text-xl font-bold text-white">Workout Details</h3>
                <p class="text-slate-400 text-sm mt-1" id="modalAthlete"></p>
            </div>
            <button onclick="closeWorkoutModal()" class="text-slate-400 hover:text-white transition-colors">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        <div class="p-6 space-y-6" id="modalContent">
            <!-- Content filled by JavaScript -->
        </div>
    </div>
</div>

<script>
    function openWorkoutModal(workout) {
        const modal = document.getElementById('workoutModal');
        const content = document.getElementById('modalContent');
        document.getElementById('modalAthlete').textContent = workout.athlete_name + ' — ' + new Date(workout.date).toLocaleDateString('es-CL', { day: 'numeric', month: 'long', year: 'numeric' });

        let html = `
            <div class="grid grid-cols-3 gap-4 bg-[#0f172a] rounded-xl p-4 border border-slate-700">
                <div class="text-center">
                    <p class="text-xs text-slate-500 uppercase font-bold tracking-wider">Distance</p>
                    <p class="text-xl font-black text-white mt-1">${workout.actual_distance ? workout.actual_distance + ' km' : '-'}</p>
                </div>
                <div class="text-center border-l border-slate-700">
                    <p class="text-xs text-slate-500 uppercase font-bold tracking-wider">Time</p>
                    <p class="text-xl font-black text-white mt-1">${workout.actual_time ? workout.actual_time + ' min' : '-'}</p>
                </div>
                <div class="text-center border-l border-slate-700">
                    <p class="text-xs text-slate-500 uppercase font-bold tracking-wider">RPE</p>
                    <p class="text-xl font-black text-${getRPESeverityColor(workout.rpe)} mt-1">${workout.rpe ? workout.rpe + '/10' : '-'}</p>
                </div>
            </div>

            <div>
                <label class="text-xs uppercase font-bold text-slate-500 mb-1 block">Workout Type & Description</label>
                <div class="bg-slate-800 p-4 rounded-xl border border-slate-700">
                    <span class="inline-block px-2 py-0.5 rounded text-xs font-bold bg-slate-700 text-white mb-2">${workout.type}</span>
                    <p class="text-slate-300 text-sm leading-relaxed">${workout.description || 'No description provided.'}</p>
                </div>
            </div>
        `;

        if (workout.feedback) {
            html += `
                <div class="bg-amber-500/5 rounded-xl p-4 border border-amber-500/20">
                    <h4 class="text-xs uppercase font-bold text-amber-500 mb-2 flex items-center gap-2">
                        <i data-lucide="message-circle" class="w-4 h-4"></i> Athlete Feedback
                    </h4>
                    <p class="text-slate-200 italic">"${workout.feedback}"</p>
                </div>
            `;
        }

        if (workout.coach_feedback) {
            html += `
                <div class="bg-purple-500/5 rounded-xl p-4 border border-purple-500/20">
                    <h4 class="text-xs uppercase font-bold text-purple-400 mb-2 flex items-center gap-2">
                        <i data-lucide="check-check" class="w-4 h-4"></i> Your Response
                    </h4>
                    <p class="text-slate-200">"${workout.coach_feedback}"</p>
                    <p class="text-xs text-slate-500 mt-2">${workout.coach_feedback_at ? new Date(workout.coach_feedback_at).toLocaleString('es-CL') : ''}</p>
                </div>
            `;
        } else if (workout.feedback) {
            // Show response form
            html += `
                <form method="POST" class="space-y-4 border-t border-slate-700 pt-6">
                    <input type="hidden" name="action" value="add_feedback">
                    <input type="hidden" name="workout_id" value="${workout.id}">
                    <div>
                        <label class="block text-sm font-bold text-white mb-2 flex items-center gap-2">
                            <i data-lucide="send" class="w-4 h-4 text-emerald-accent"></i> Reply to Athlete
                        </label>
                        <textarea name="coach_feedback" rows="3" required
                            placeholder="Write your feedback here..."
                            class="w-full px-4 py-3 bg-[#0f172a] border border-slate-700 rounded-xl focus:ring-1 focus:ring-emerald-accent/50 outline-none text-white placeholder-slate-600 transition-all"></textarea>
                    </div>
                    <button type="submit"
                        class="w-full bg-emerald-accent text-[#0f172a] py-3 rounded-xl font-bold hover:bg-[#0bc568] transition-all shadow-lg shadow-emerald-accent/10">
                        Send Feedback
                    </button>
                </form>
            `;
        }

        if (workout.evidence_path) {
            html += `
                <div>
                    <label class="text-xs uppercase font-bold text-slate-500 mb-2 block">Evidence</label>
                    <a href="${workout.evidence_path}" target="_blank" class="block w-full h-48 bg-[#0f172a] rounded-xl border border-slate-700 flex items-center justify-center text-slate-400 hover:text-white hover:border-emerald-accent transition-all group overflow-hidden relative">
                         <img src="${workout.evidence_path}" class="absolute inset-0 w-full h-full object-cover opacity-50 group-hover:opacity-80 transition-opacity" alt="Evidence">
                         <div class="relative z-10 bg-black/50 px-4 py-2 rounded-lg backdrop-blur-sm flex items-center gap-2">
                            <i data-lucide="maximize-2" class="w-4 h-4"></i> View Full Image
                         </div>
                    </a>
                </div>
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

    function getRPESeverityColor(rpe) {
        if (!rpe) return 'white';
        if (rpe <= 4) return 'emerald-accent'; // Easy
        if (rpe <= 7) return 'amber-400';      // Moderate
        return 'rose-500';                     // Hard
    }
</script>

<?php include 'views/layout/footer.php'; ?>
// Handle coach feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
if ($_POST['action'] === 'add_feedback') {
$workoutId = $_POST['workout_id'];
$feedback = $_POST['coach_feedback'];
Workout::addCoachFeedback($workoutId, $feedback);

// Get the workout to find the athlete
$workout = Workout::getById($workoutId);
if ($workout) {
$msg = "💬 Tu entrenador ha respondido a tu feedback del entrenamiento del " . (new
DateTime($workout['date']))->format('d/m/Y');
Notification::create($workout['athlete_id'], $msg, 'info');
}

header('Location: entrenamientos.php?success=feedback&athlete_id=' . $athleteId . '&period=' . $periodFilter);
exit;
}
}

// Calculate date range
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
}

// Get completed workouts
$sql = "SELECT w.*, u.name as athlete_name, u.username as athlete_email
FROM workouts w
JOIN users u ON w.athlete_id = u.id
WHERE u.coach_id = ? AND w.status = 'completed'";
$params = [$coach['id']];

if ($athleteId !== 'all') {
$sql .= " AND w.athlete_id = ?";
$params[] = $athleteId;
}
if ($dateFrom) {
$sql .= " AND w.date >= ?";
$params[] = $dateFrom;
}
if ($dateTo) {
$sql .= " AND w.date <= ?"; $params[]=$dateTo; } $sql .=" ORDER BY w.completed_at DESC, w.date DESC LIMIT 100" ;
    $stmt=$db->prepare($sql);
    $stmt->execute($params);
    $completedWorkouts = $stmt->fetchAll();

    // Decode structures
    foreach ($completedWorkouts as &$w) {
    if ($w['structure'])
    $w['structure'] = json_decode($w['structure'], true);
    }
    unset($w);

    // Get delivery stats
    $statsSql = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN w.status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN w.status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN w.status = 'completed' AND w.feedback IS NOT NULL AND w.coach_feedback IS NULL THEN 1 ELSE 0 END) as
    awaiting_response,
    SUM(CASE WHEN w.status = 'completed' AND w.coach_feedback IS NOT NULL THEN 1 ELSE 0 END) as responded
    FROM workouts w
    JOIN users u ON w.athlete_id = u.id
    WHERE u.coach_id = ?";
    $stmt = $db->prepare($statsSql);
    $stmt->execute([$coach['id']]);
    $stats = $stmt->fetch();

    include 'views/layout/header.php';
    ?>

    <!-- Page Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">ENTRENAMIENTOS Y REPORTES</h1>
            <p class="text-slate-500 mt-1">Revisa los entrenamientos completados y responde al feedback de tus atletas
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            <form method="GET" class="flex flex-wrap gap-3">
                <select name="period" onchange="this.form.submit()"
                    class="px-4 py-3 rounded-xl border border-slate-200 bg-white focus:ring-2 focus:ring-blue-500 outline-none text-slate-700 font-medium">
                    <option value="all" <?php echo $periodFilter === 'all' ? 'selected' : ''; ?>>Todos los Períodos
                    </option>
                    <option value="this_week" <?php echo $periodFilter === 'this_week' ? 'selected' : ''; ?>>Esta Semana
                    </option>
                    <option value="last_week" <?php echo $periodFilter === 'last_week' ? 'selected' : ''; ?>>Semana Pasada
                    </option>
                    <option value="this_month" <?php echo $periodFilter === 'this_month' ? 'selected' : ''; ?>>Este Mes
                    </option>
                    <option value="last_month" <?php echo $periodFilter === 'last_month' ? 'selected' : ''; ?>>Mes
                        Anterior
                    </option>
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
            </form>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
            ✅ Feedback enviado exitosamente
        </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 bg-slate-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="layers" class="w-5 h-5 text-slate-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900"><?php echo $stats['total'] ?? 0; ?></p>
                    <p class="text-xs text-slate-500">Total Entrenamientos</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 bg-green-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="check-circle" class="w-5 h-5 text-green-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900"><?php echo $stats['completed'] ?? 0; ?></p>
                    <p class="text-xs text-slate-500">Completados</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 bg-amber-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="message-circle" class="w-5 h-5 text-amber-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900"><?php echo $stats['awaiting_response'] ?? 0; ?></p>
                    <p class="text-xs text-slate-500">Esperando Respuesta</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 bg-purple-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="check-check" class="w-5 h-5 text-purple-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900"><?php echo $stats['responded'] ?? 0; ?></p>
                    <p class="text-xs text-slate-500">Respondidos</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Period indicator -->
    <?php if ($periodFilter !== 'all' && $dateFrom && $dateTo): ?>
        <div
            class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-xl mb-6 flex items-center gap-2 text-sm">
            <i data-lucide="calendar" class="w-4 h-4"></i>
            Mostrando del <strong><?php echo (new DateTime($dateFrom))->format('d M Y'); ?></strong> al
            <strong><?php echo (new DateTime($dateTo))->format('d M Y'); ?></strong>
        </div>
    <?php endif; ?>

    <!-- Workouts Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center">
            <h2 class="text-xl font-bold text-slate-900">Entrenamientos Completados</h2>
            <span class="text-sm text-slate-500"><?php echo count($completedWorkouts); ?> entrenamientos</span>
        </div>

        <?php if (empty($completedWorkouts)): ?>
            <div class="p-12 text-center text-slate-500">
                <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-4 opacity-30"></i>
                <p>No hay entrenamientos completados todavía</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left px-6 py-4 text-sm font-semibold text-slate-600">Atleta</th>
                            <th class="text-left px-6 py-4 text-sm font-semibold text-slate-600">Fecha</th>
                            <th class="text-left px-6 py-4 text-sm font-semibold text-slate-600">Tipo</th>
                            <th class="text-left px-6 py-4 text-sm font-semibold text-slate-600">Resultados</th>
                            <th class="text-left px-6 py-4 text-sm font-semibold text-slate-600">RPE</th>
                            <th class="text-left px-6 py-4 text-sm font-semibold text-slate-600">Feedback</th>
                            <th class="text-left px-6 py-4 text-sm font-semibold text-slate-600">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($completedWorkouts as $workout): ?>
                            <?php
                            $typeColors = [
                                'Series' => 'bg-purple-100 text-purple-600',
                                'Intervalos' => 'bg-orange-100 text-orange-600',
                                'Fondo' => 'bg-blue-100 text-blue-600',
                                'Tempo' => 'bg-red-100 text-red-600',
                                'Recuperación' => 'bg-green-100 text-green-600',
                                'Descanso' => 'bg-slate-100 text-slate-600'
                            ];
                            $typeColor = $typeColors[$workout['type']] ?? 'bg-slate-100 text-slate-600';

                            $hasAthlFeedback = !empty($workout['feedback']);
                            $hasCoachFeedback = !empty($workout['coach_feedback']);
                            ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-900">
                                        <?php echo htmlspecialchars($workout['athlete_name']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-600 text-sm">
                                    <?php echo (new DateTime($workout['date']))->format('d M Y'); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold <?php echo $typeColor; ?>">
                                        <?php echo htmlspecialchars($workout['type']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="flex gap-3 text-slate-600">
                                        <?php if ($workout['actual_distance']): ?>
                                            <span title="Distancia">📏 <?php echo $workout['actual_distance']; ?> km</span>
                                        <?php endif; ?>
                                        <?php if ($workout['actual_time']): ?>
                                            <span title="Tiempo">⏱️ <?php echo $workout['actual_time']; ?> min</span>
                                        <?php endif; ?>
                                        <?php if (!$workout['actual_distance'] && !$workout['actual_time']): ?>
                                            <span class="text-slate-400">-</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($workout['rpe']): ?>
                                        <?php
                                        $rpeColor = $workout['rpe'] <= 3 ? 'text-green-600 bg-green-100' : ($workout['rpe'] <= 6 ? 'text-amber-600 bg-amber-100' : 'text-red-600 bg-red-100');
                                        ?>
                                        <span class="px-2 py-1 rounded-lg text-sm font-bold <?php echo $rpeColor; ?>">
                                            <?php echo $workout['rpe']; ?>/10
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($hasCoachFeedback): ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700">✅
                                            Respondido</span>
                                    <?php elseif ($hasAthlFeedback): ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">💬
                                            Pendiente</span>
                                    <?php else: ?>
                                        <span class="text-slate-400 text-xs">Sin feedback</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <button onclick='openWorkoutModal(<?php echo json_encode($workout); ?>)'
                                        class="text-blue-500 hover:text-blue-700 p-2 rounded-lg hover:bg-blue-50 transition-all"
                                        title="Ver Detalles">
                                        <i data-lucide="eye" class="w-5 h-5"></i>
                                    </button>
                                    <?php if (!empty($workout['evidence_path'])): ?>
                                        <a href="<?php echo htmlspecialchars($workout['evidence_path']); ?>" target="_blank"
                                            class="text-green-500 hover:text-green-700 p-2 rounded-lg hover:bg-green-50 transition-all inline-block"
                                            title="Ver Evidencia">
                                            <i data-lucide="image" class="w-5 h-5"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Workout Detail + Feedback Modal -->
    <div id="workoutModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto m-4">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-bold text-slate-900">DETALLE DEL ENTRENAMIENTO</h3>
                    <p class="text-slate-500 text-sm mt-1" id="modalAthlete"></p>
                </div>
                <button onclick="closeWorkoutModal()" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <div class="p-6 space-y-5" id="modalContent">
                <!-- Content filled by JavaScript -->
            </div>
        </div>
    </div>

    <script>
        function openWorkoutModal(workout) {
            const modal = document.getElementById('workoutModal');
            const content = document.getElementById('modalContent');
            document.getElementById('modalAthlete').textContent = workout.athlete_name + ' — ' + new Date(workout.date).toLocaleDateString('es-CL', { day: 'numeric', month: 'long', year: 'numeric' });

            let html = `
            <div class="grid grid-cols-3 gap-4 bg-slate-50 rounded-xl p-4">
                <div class="text-center">
                    <p class="text-xs text-slate-500 uppercase font-semibold">Distancia</p>
                    <p class="text-xl font-bold text-slate-900">${workout.actual_distance ? workout.actual_distance + ' km' : '-'}</p>
                </div>
                <div class="text-center">
                    <p class="text-xs text-slate-500 uppercase font-semibold">Tiempo</p>
                    <p class="text-xl font-bold text-slate-900">${workout.actual_time ? workout.actual_time + ' min' : '-'}</p>
                </div>
                <div class="text-center">
                    <p class="text-xs text-slate-500 uppercase font-semibold">RPE</p>
                    <p class="text-xl font-bold text-slate-900">${workout.rpe ? workout.rpe + '/10' : '-'}</p>
                </div>
            </div>

            <div>
                <label class="text-xs uppercase font-bold text-slate-500">Tipo de Entrenamiento</label>
                <p class="font-semibold text-slate-900">${workout.type} — ${workout.description || ''}</p>
            </div>
        `;

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
            } else if (workout.feedback) {
                // Show response form
                html += `
                <form method="POST" class="space-y-4 border-t border-slate-100 pt-4">
                    <input type="hidden" name="action" value="add_feedback">
                    <input type="hidden" name="workout_id" value="${workout.id}">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2 flex items-center gap-2">
                            <i data-lucide="send" class="w-4 h-4"></i> Responder al Atleta
                        </label>
                        <textarea name="coach_feedback" rows="3" required
                            placeholder="Escribe tu respuesta al feedback del atleta..."
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none"></textarea>
                    </div>
                    <button type="submit"
                        class="w-full bg-blue-600 text-white py-3 rounded-xl font-bold hover:bg-blue-700 transition-all">
                        Enviar Respuesta
                    </button>
                </form>
            `;
            }

            if (workout.evidence_path) {
                html += `
                <div>
                    <label class="text-xs uppercase font-bold text-slate-500 mb-2 block">Evidencia</label>
                    <a href="${workout.evidence_path}" target="_blank" class="text-blue-600 font-semibold hover:underline flex items-center gap-2">
                        <i data-lucide="image" class="w-4 h-4"></i> Ver Evidencia
                    </a>
                </div>
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
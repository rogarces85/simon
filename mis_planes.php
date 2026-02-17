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
<div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-8">
    <div class="flex flex-col gap-1">
        <nav class="flex items-center gap-2 text-sm text-[#9cbaab] mb-1">
            <span>Management</span>
            <i data-lucide="chevron-right" class="w-3 h-3"></i>
            <span class="text-white">Planning</span>
        </nav>
        <h1 class="text-3xl font-black text-white tracking-tight">Active Training Plans</h1>
        <p class="text-[#9cbaab]">Manage and review workouts assigned to your athletes</p>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-3 w-full md:w-auto">
        <form method="GET" id="filterForm" class="contents">
            <div class="flex items-center bg-[#1a241f] border border-[#283930] rounded-lg px-3 py-2 gap-2">
                <i data-lucide="calendar" class="text-[#9cbaab] w-4 h-4"></i>
                <select name="period" onchange="this.form.submit()"
                    class="bg-transparent border-none text-white text-sm font-medium focus:ring-0 p-0 cursor-pointer w-32">
                    <option value="all" <?php echo $periodFilter === 'all' ? 'selected' : ''; ?>>All Periods</option>
                    <option value="this_week" <?php echo $periodFilter === 'this_week' ? 'selected' : ''; ?>>This Week
                    </option>
                    <option value="last_week" <?php echo $periodFilter === 'last_week' ? 'selected' : ''; ?>>Last Week
                    </option>
                    <option value="this_month" <?php echo $periodFilter === 'this_month' ? 'selected' : ''; ?>>This Month
                    </option>
                </select>
            </div>

            <div class="flex items-center bg-[#1a241f] border border-[#283930] rounded-lg px-3 py-2 gap-2">
                <i data-lucide="users" class="text-[#9cbaab] w-4 h-4"></i>
                <select name="athlete_id" onchange="this.form.submit()"
                    class="bg-transparent border-none text-white text-sm font-medium focus:ring-0 p-0 cursor-pointer w-40">
                    <option value="all">All Athletes</option>
                    <?php foreach ($athletes as $athlete): ?>
                        <option value="<?php echo $athlete['id']; ?>" <?php echo $athleteId == $athlete['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($athlete['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex items-center bg-[#1a241f] border border-[#283930] rounded-lg px-3 py-2 gap-2">
                <i data-lucide="filter" class="text-[#9cbaab] w-4 h-4"></i>
                <select name="status" onchange="this.form.submit()"
                    class="bg-transparent border-none text-white text-sm font-medium focus:ring-0 p-0 cursor-pointer w-32">
                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed
                    </option>
                </select>
            </div>
        </form>
    </div>
</div>

<!-- Quick Stats Row -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-[#1a241f] border border-[#283930] p-4 rounded-xl flex items-center gap-4">
        <div class="p-3 bg-blue-500/10 rounded-lg text-blue-400">
            <i data-lucide="layers" class="w-6 h-6"></i>
        </div>
        <div>
            <p class="text-[#9cbaab] text-xs font-medium uppercase">Total Plans</p>
            <p class="text-xl font-bold text-white"><?php echo $plansSummary['total_plans'] ?? 0; ?></p>
        </div>
    </div>
    <div class="bg-[#1a241f] border border-[#283930] p-4 rounded-xl flex items-center gap-4">
        <div class="p-3 bg-amber-500/10 rounded-lg text-amber-400">
            <i data-lucide="clock" class="w-6 h-6"></i>
        </div>
        <div>
            <p class="text-[#9cbaab] text-xs font-medium uppercase">Pending</p>
            <p class="text-xl font-bold text-white"><?php echo $plansSummary['pending_count'] ?? 0; ?></p>
        </div>
    </div>
    <div class="bg-[#1a241f] border border-[#283930] p-4 rounded-xl flex items-center gap-4">
        <div class="p-3 bg-green-500/10 rounded-lg text-green-400">
            <i data-lucide="check-circle" class="w-6 h-6"></i>
        </div>
        <div>
            <p class="text-[#9cbaab] text-xs font-medium uppercase">Completed</p>
            <p class="text-xl font-bold text-white"><?php echo $plansSummary['completed_no_feedback'] ?? 0; ?></p>
        </div>
    </div>
    <div class="bg-[#1a241f] border border-[#283930] p-4 rounded-xl flex items-center gap-4">
        <div class="p-3 bg-purple-500/10 rounded-lg text-purple-400">
            <i data-lucide="message-circle" class="w-6 h-6"></i>
        </div>
        <div>
            <p class="text-[#9cbaab] text-xs font-medium uppercase">Feedback</p>
            <p class="text-xl font-bold text-white"><?php echo $plansSummary['with_feedback_count'] ?? 0; ?></p>
        </div>
    </div>
</div>

<!-- Plans Grid -->
<?php if (empty($allWorkouts)): ?>
    <div class="bg-[#1a241f] border border-[#283930] rounded-xl p-12 text-center">
        <div class="w-16 h-16 bg-[#283930] rounded-full flex items-center justify-center mx-auto mb-4">
            <i data-lucide="inbox" class="w-8 h-8 text-[#9cbaab]"></i>
        </div>
        <h3 class="text-white text-lg font-bold mb-2">No plans found</h3>
        <p class="text-[#9cbaab] text-sm mb-6">Try adjusting your filters or create a new plan.</p>
        <a href="generar_plan.php"
            class="inline-flex items-center gap-2 px-6 py-2 bg-primary text-[#111814] text-sm font-bold rounded-lg hover:bg-primary-dark transition-colors shadow-lg shadow-primary/20">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Create New Plan
        </a>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($allWorkouts as $workout): ?>
            <?php
            // Status Logic & Styling
            $statusColor = 'text-slate-400';
            $borderColor = 'border-slate-700/50';
            $icon = 'circle';
            $statusText = 'Pending';
            $accentClass = 'bg-slate-500';

            if ($workout['status'] === 'pending') {
                $statusColor = 'text-amber-400';
                $borderColor = 'border-amber-500/30';
                $icon = 'clock';
                $statusText = 'Pending';
                $accentClass = 'bg-amber-500';
            } elseif ($workout['status'] === 'completed') {
                if ($workout['coach_feedback']) {
                    $statusColor = 'text-purple-400';
                    $borderColor = 'border-purple-500/30';
                    $icon = 'check-check';
                    $statusText = 'Responded';
                    $accentClass = 'bg-purple-500';
                } elseif ($workout['feedback']) {
                    $statusColor = 'text-green-400';
                    $borderColor = 'border-green-500/30';
                    $icon = 'message-circle';
                    $statusText = 'Feedback';
                    $accentClass = 'bg-green-500';
                } else {
                    $statusColor = 'text-blue-400';
                    $borderColor = 'border-blue-500/30';
                    $icon = 'check-circle';
                    $statusText = 'Completed';
                    $accentClass = 'bg-blue-500';
                }
            }
            ?>

            <!-- Card -->
            <div
                class="bg-[#1a241f] border <?php echo $borderColor; ?> rounded-xl p-5 flex flex-col gap-4 relative overflow-hidden group hover:shadow-lg transition-all duration-300">
                <!-- Hover Glow Effect -->
                <div
                    class="absolute top-0 right-0 w-24 h-24 <?php echo $accentClass; ?>/5 rounded-bl-full -mr-4 -mt-4 transition-all group-hover:<?php echo $accentClass; ?>/10">
                </div>

                <!-- Header -->
                <div class="flex items-start justify-between relative z-10">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-full bg-[#283930] flex items-center justify-center text-white font-bold border border-[#344a3e]">
                            <?php echo substr($workout['athlete_name'], 0, 1); ?>
                        </div>
                        <div>
                            <h3 class="text-white font-bold text-sm"><?php echo htmlspecialchars($workout['athlete_name']); ?>
                            </h3>
                            <p class="text-[#9cbaab] text-xs"><?php echo (new DateTime($workout['date']))->format('M j, Y'); ?>
                            </p>
                        </div>
                    </div>
                    <span
                        class="px-2 py-1 rounded text-xs font-bold bg-[#111814] border border-[#283930] <?php echo $statusColor; ?> flex items-center gap-1">
                        <i data-lucide="<?php echo $icon; ?>" class="w-3 h-3"></i>
                        <?php echo $statusText; ?>
                    </span>
                </div>

                <!-- Body -->
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-white bg-[#283930] px-2 py-0.5 rounded uppercase tracking-wider">
                            <?php echo htmlspecialchars($workout['type']); ?>
                        </span>
                    </div>
                    <p class="text-[#9cbaab] text-sm line-clamp-2 h-10">
                        <?php echo htmlspecialchars($workout['description'] ?? 'No description provided.'); ?>
                    </p>
                </div>

                <!-- Stats / Actions -->
                <div class="mt-auto pt-4 border-t border-[#283930] flex justify-between items-center relative z-10">
                    <div class="flex flex-col">
                        <span class="text-[10px] text-[#52665b] uppercase font-bold">Planned Dist</span>
                        <span class="text-white text-sm font-semibold"><?php echo $workout['distance'] ?? 0; ?> km</span>
                    </div>

                    <button onclick='openDetailModal(<?php echo json_encode($workout); ?>)'
                        class="text-xs bg-[#283930] text-white hover:bg-primary hover:text-[#111814] border border-[#344a3e] hover:border-primary px-3 py-2 rounded-lg transition-all font-bold flex items-center gap-2">
                        View Details
                        <i data-lucide="arrow-right" class="w-3 h-3"></i>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Detail Modal (Styled darker) -->
<div id="detailModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm hidden items-center justify-center z-50">
    <div
        class="bg-[#1a241f] border border-[#283930] rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto m-4 shadow-2xl">
        <div class="p-6 border-b border-[#283930] flex justify-between items-center">
            <div>
                <h3 class="text-xl font-bold text-white" id="modalTitle">Workout Details</h3>
                <p class="text-[#9cbaab] text-sm mt-1" id="modalSubtitle">Complete training information</p>
            </div>
            <button onclick="closeDetailModal()" class="text-[#9cbaab] hover:text-white transition-colors">
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
        const date = new Date(workout.date).toLocaleDateString('en-US', { day: 'numeric', month: 'long', year: 'numeric' });

        let html = `
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="text-xs uppercase font-bold text-[#52665b]">Athlete</label>
                    <p class="font-semibold text-white text-lg">${workout.athlete_name}</p>
                </div>
                <div>
                    <label class="text-xs uppercase font-bold text-[#52665b]">Date</label>
                    <p class="font-semibold text-white text-lg">${date}</p>
                </div>
            </div>
            
            <div class="bg-[#111814] rounded-xl p-4 border border-[#283930] mb-6">
                 <div class="flex items-center justify-between mb-2">
                    <label class="text-xs uppercase font-bold text-[#52665b]">Workout Description</label>
                    <span class="text-xs font-bold text-primary bg-primary/10 px-2 py-0.5 rounded">${workout.type}</span>
                </div>
                <p class="text-[#d1d5db] leading-relaxed">${workout.description || 'No description provided.'}</p>
            </div>
        `;

        if (workout.status === 'completed') {
            html += `
                <div class="bg-[#111814] rounded-xl p-4 border border-[#283930] space-y-3">
                    <h4 class="text-sm font-bold text-white flex items-center gap-2">
                        <i data-lucide="activity" class="w-4 h-4 text-primary"></i> Athlete Results
                    </h4>
                    <div class="grid grid-cols-3 gap-4 text-sm">
                        <div class="bg-[#1a241f] p-2 rounded border border-[#283930]">
                            <span class="text-[#9cbaab] text-xs block">Distance</span>
                            <span class="font-bold text-white text-lg">${workout.actual_distance ? workout.actual_distance + ' km' : '-'}</span>
                        </div>
                        <div class="bg-[#1a241f] p-2 rounded border border-[#283930]">
                            <span class="text-[#9cbaab] text-xs block">Time</span>
                            <span class="font-bold text-white text-lg">${workout.actual_time ? workout.actual_time + ' min' : '-'}</span>
                        </div>
                        <div class="bg-[#1a241f] p-2 rounded border border-[#283930]">
                            <span class="text-[#9cbaab] text-xs block">RPE</span>
                            <span class="font-bold text-white text-lg">${workout.rpe ? workout.rpe + '/10' : '-'}</span>
                        </div>
                    </div>
                </div>
            `;
        }

        if (workout.feedback) {
            html += `
                <div class="bg-[#1a241f] rounded-xl p-4 border border-l-4 border-[#283930] border-l-green-500 mt-4">
                    <h4 class="text-xs uppercase font-bold text-green-400 mb-2 flex items-center gap-2">
                        <i data-lucide="message-circle" class="w-4 h-4"></i> Athlete Feedback
                    </h4>
                    <p class="text-white italic">"${workout.feedback}"</p>
                </div>
            `;
        }

        if (workout.coach_feedback) {
            html += `
                <div class="bg-[#1a241f] rounded-xl p-4 border border-l-4 border-[#283930] border-l-purple-500 mt-4">
                    <h4 class="text-xs uppercase font-bold text-purple-400 mb-2 flex items-center gap-2">
                        <i data-lucide="check-check" class="w-4 h-4"></i> Your Response
                    </h4>
                    <p class="text-white">"${workout.coach_feedback}"</p>
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
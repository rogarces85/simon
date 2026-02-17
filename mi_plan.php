<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'models/User.php';
require_once 'models/Workout.php';
require_once 'models/Team.php';
require_once 'models/Notification.php';

Auth::init();
Auth::requireRole('athlete');

$user = Auth::user();
$db = Database::getInstance();

$team = Team::findByCoach($user['coach_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'complete_workout') {
        $workoutId = $_POST['workout_id'];
        $updateData = [
            'status' => 'completed',
            'actual_distance' => $_POST['actual_distance'] ?: null,
            'actual_time' => $_POST['actual_time'] ?: null,
            'rpe' => $_POST['rpe'] ?: null,
            'feedback' => $_POST['feedback'] ?: null,
            'completed_at' => date('Y-m-d H:i:s'),
            'delivery_status' => 'received'
        ];

        if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/evidence/';
            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0777, true);
            $ext = pathinfo($_FILES['evidence']['name'], PATHINFO_EXTENSION);
            $filename = 'evidence_' . $workoutId . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['evidence']['tmp_name'], $uploadDir . $filename)) {
                $updateData['evidence_path'] = $uploadDir . $filename;
            }
        }

        Workout::update($workoutId, $updateData);

        if ($user['coach_id']) {
            $workout = Workout::getById($workoutId);
            $message = "✅ {$user['name']} completó entrenamiento: " . ($workout['type'] ?? 'Actividad');
            Notification::create($user['coach_id'], $message, 'info');
        }

        header('Location: mi_plan.php?success=1');
        exit;
    }
}

$monthParam = $_GET['month'] ?? date('Y-m');
$currentMonth = new DateTime($monthParam . '-01');
$monthStart = clone $currentMonth;
$monthEnd = (clone $currentMonth)->modify('last day of this month');
$prevMonth = (clone $currentMonth)->modify('-1 month')->format('Y-m');
$nextMonth = (clone $currentMonth)->modify('+1 month')->format('Y-m');
$today = new DateTime();

$workouts = Workout::getByAthlete($user['id'], $monthStart->format('Y-m-d 00:00:00'), $monthEnd->format('Y-m-d 23:59:59'));
$workoutsByDate = [];
foreach ($workouts as $w) {
    $workoutsByDate[(new DateTime($w['date']))->format('Y-m-d')] = $w;
}

$firstDayOfMonth = (int) $monthStart->format('N');
$daysInMonth = (int) $monthEnd->format('d');

include 'views/layout/header.php';
?>

<!-- Header with Month Nav -->
<div
    style="background: var(--bg-card); border-radius: 12px; border: 1px solid var(--border); padding: 1.5rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="font-weight: 800; font-size: 1.5rem; color: var(--text-main); margin: 0;">Mi Programación</h2>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
            <?php
            $months_es = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
            echo $months_es[(int) $currentMonth->format('n') - 1] . ' ' . $currentMonth->format('Y');
            ?>
        </p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="mi_plan.php?month=<?php echo $prevMonth; ?>" class="btn btn-secondary" style="padding: 0.5rem;"><i
                data-lucide="chevron-left"></i></a>
        <a href="mi_plan.php" class="btn btn-secondary" style="font-size: 0.8rem;">HOY</a>
        <a href="mi_plan.php?month=<?php echo $nextMonth; ?>" class="btn btn-secondary" style="padding: 0.5rem;"><i
                data-lucide="chevron-right"></i></a>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="card" style="border-color: var(--primary); background: rgba(13, 242, 128, 0.05); margin-bottom: 2rem;">
        <p style="margin: 0; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
            <i data-lucide="check-circle" style="color: var(--primary);"></i> Entrenamiento registrado. ¡Buen trabajo!
        </p>
    </div>
<?php endif; ?>

<!-- Calendar Grid -->
<div
    style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--card-radius); overflow: hidden;">
    <!-- Headers -->
    <div
        style="display: grid; grid-template-columns: repeat(7, 1fr); background: var(--bg-main); border-bottom: 1px solid var(--border);">
        <?php foreach (['LUN', 'MAR', 'MIE', 'JUE', 'VIE', 'SAB', 'DOM'] as $head): ?>
            <div
                style="padding: 0.75rem; text-align: center; font-size: 0.7rem; font-weight: 800; color: var(--text-muted);">
                <?php echo $head; ?></div>
        <?php endforeach; ?>
    </div>

    <!-- Days -->
    <div style="display: grid; grid-template-columns: repeat(7, 1fr);">
        <?php
        for ($i = 1; $i < $firstDayOfMonth; $i++)
            echo '<div style="min-height: 140px; border-bottom: 1px solid var(--border); border-right: 1px solid var(--border); background: var(--bg-main); opacity: 0.3;"></div>';

        for ($day = 1; $day <= $daysInMonth; $day++):
            $dateStr = $currentMonth->format('Y-m') . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
            $workout = $workoutsByDate[$dateStr] ?? null;
            $isToday = $dateStr === $today->format('Y-m-d');
            ?>
            <div
                style="min-height: 140px; border-bottom: 1px solid var(--border); border-right: 1px solid var(--border); padding: 0.75rem; <?php echo $isToday ? 'background: rgba(13, 242, 128, 0.05); position: relative;' : ''; ?>">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                    <span
                        style="font-size: 0.8rem; font-weight: 800; <?php echo $isToday ? 'color: var(--primary);' : 'color: var(--text-muted);'; ?>">
                        <?php echo $day; ?>
                    </span>
                    <?php if ($workout && $workout['status'] === 'completed'): ?>
                        <i data-lucide="check-circle-2" style="width: 14px; height: 14px; color: var(--primary);"></i>
                    <?php endif; ?>
                </div>

                <?php if ($workout): ?>
                    <?php if ($workout['type'] === 'Descanso'): ?>
                        <div style="text-align: center; margin-top: 1rem; opacity: 0.5;">
                            <i data-lucide="bed" style="width: 24px; height: 24px; color: var(--text-muted);"></i>
                        </div>
                    <?php else: ?>
                        <div onclick='openWorkoutModal(<?php echo json_encode($workout); ?>)'
                            style="background: var(--bg-main); border: 1px solid var(--border); padding: 0.5rem; border-radius: 8px; cursor: pointer; transition: transform 0.2s;"
                            onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                            <p
                                style="font-size: 0.7rem; font-weight: 700; color: var(--text-main); margin-bottom: 2px; text-transform: uppercase;">
                                <?php echo htmlspecialchars($workout['type']); ?></p>
                            <?php if ($workout['status'] === 'completed' && $workout['actual_distance']): ?>
                                <p style="font-size: 0.75rem; font-weight: 800; color: var(--primary);">
                                    <?php echo $workout['actual_distance']; ?> km</p>
                            <?php else: ?>
                                <p style="font-size: 0.65rem; color: var(--text-muted); line-height: 1.1;">
                                    <?php echo mb_strimwidth($workout['description'], 0, 30, '...'); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endfor; ?>
    </div>
</div>

<!-- Modal -->
<div id="workoutModal"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 100; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="width: 100%; max-width: 500px; margin: 1rem;">
        <div id="modalHeader"
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 id="modalTitle" style="font-weight: 800; font-size: 1.25rem;">Detalle de Sesión</h3>
            <button onclick="closeWorkoutModal()"
                style="background: none; border: none; color: var(--text-muted); cursor: pointer;"><i
                    data-lucide="x"></i></button>
        </div>
        <div id="modalBody" style="display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- Content here -->
        </div>
    </div>
</div>

<script>
    function openWorkoutModal(workout) {
        const modal = document.getElementById('workoutModal');
        const body = document.getElementById('modalBody');

        let html = `
            <div style="background: var(--bg-main); padding: 1rem; border-radius: 8px; border-left: 4px solid var(--primary);">
                <p style="font-size: 0.75rem; font-weight: 800; color: var(--primary); margin-bottom: 0.5rem; text-transform: uppercase;">${workout.type}</p>
                <div style="font-size: 0.9rem; font-weight: 500; color: var(--text-main); white-space: pre-wrap;">${workout.structure || 'Sin instrucciones adicionales.'}</div>
            </div>
        `;

        if (workout.status === 'completed') {
            html += `
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; text-align: center; border-top: 1px solid var(--border); padding-top: 1.5rem;">
                    <div><p style="font-size:0.7rem; color: var(--text-muted);">KM</p><p style="font-weight:800; font-size: 1.1rem;">${workout.actual_distance || '--'}</p></div>
                    <div><p style="font-size:0.7rem; color: var(--text-muted);">MIN</p><p style="font-weight:800; font-size: 1.1rem;">${workout.actual_time || '--'}</p></div>
                    <div><p style="font-size:0.7rem; color: var(--text-muted);">RPE</p><p style="font-weight:800; font-size: 1.1rem;">${workout.rpe || '--'}/10</p></div>
                </div>
            `;
        } else {
            html += `
                <form method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 1rem; border-top: 1px solid var(--border); padding-top: 1.5rem;">
                    <input type="hidden" name="action" value="complete_workout">
                    <input type="hidden" name="workout_id" value="${workout.id}">
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.5rem;">
                        <input type="number" name="actual_distance" step="0.01" placeholder="KM" required style="padding: 0.6rem; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-main); color: var(--text-main); font-family: inherit; font-size: 0.8rem;">
                        <input type="number" name="actual_time" placeholder="MIN" required style="padding: 0.6rem; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-main); color: var(--text-main); font-family: inherit; font-size: 0.8rem;">
                        <select name="rpe" required style="padding: 0.6rem; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-main); color: var(--text-main); font-family: inherit; font-size: 0.8rem;">
                            <option value="">RPE</option>
                            ${[1, 2, 3, 4, 5, 6, 7, 8, 9, 10].map(n => `<option value="${n}">${n}</option>`).join('')}
                        </select>
                    </div>
                    <textarea name="feedback" rows="2" placeholder="Feedback para el entrenador..." style="padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-main); color: var(--text-main); font-family: inherit; font-size: 0.85rem;"></textarea>
                    <button type="submit" class="btn btn-primary" style="padding: 0.8rem;">REPORTAR ENTRENAMIENTO</button>
                </form>
            `;
        }

        body.innerHTML = html;
        modal.style.display = 'flex';
        lucide.createIcons();
    }

    function closeWorkoutModal() {
        document.getElementById('workoutModal').style.display = 'none';
    }
</script>

<?php include 'views/layout/footer.php'; ?>
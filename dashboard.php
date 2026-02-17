<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'models/User.php';
require_once 'models/Notification.php';
require_once 'models/Team.php';
require_once 'models/Workout.php';

Auth::init();

if (!Auth::check()) {
    header('Location: login.php');
    exit;
}

$user = Auth::user();

if ($user['role'] === 'admin') {
    header('Location: admin_dashboard.php');
    exit;
} elseif ($user['role'] !== 'coach') {
    header('Location: mi_plan.php');
    exit;
}

$db = Database::getInstance();

// 1. Athletes Count
$athletes = User::getByCoachId($user['id']);
$athleteCount = count($athletes);

// 2. Templates Count
$stmt = $db->prepare("SELECT COUNT(*) FROM templates WHERE coach_id = ?");
$stmt->execute([$user['id']]);
$templateCount = $stmt->fetchColumn();

// 3. Active Plans (Workouts this week)
$monday = date('Y-m-d', strtotime('monday this week'));
$sunday = date('Y-m-d', strtotime('sunday this week'));
$stmt = $db->prepare("SELECT COUNT(DISTINCT athlete_id) FROM workouts WHERE date BETWEEN ? AND ? AND athlete_id IN (SELECT id FROM users WHERE coach_id = ?)");
$stmt->execute([$monday, $sunday, $user['id']]);
$activeAthletesThisWeek = $stmt->fetchColumn();

// 4. Global Performance (Example metric)
$stmt = $db->prepare("SELECT AVG(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) * 100 FROM workouts WHERE date < CURDATE() AND athlete_id IN (SELECT id FROM users WHERE coach_id = ?)");
$stmt->execute([$user['id']]);
$complianceRate = round($stmt->fetchColumn() ?? 0);

$team = Team::findByCoach($user['id']);

include 'views/layout/header.php';
?>

<!-- Top Welcome Bar -->
<div
    style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--card-radius); padding: 2rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; background-image: linear-gradient(135deg, rgba(13, 242, 128, 0.05) 0%, transparent 100%);">
    <div>
        <span class="badge badge-emerald" style="margin-bottom: 0.75rem;">PANEL DE CONTROL</span>
        <h2 style="font-size: 2rem; font-weight: 800; color: var(--text-main); line-height: 1.1;">Buen día,
            <?php echo explode(' ', $user['name'])[0]; ?> ⚡</h2>
        <p style="color: var(--text-muted); margin-top: 0.5rem;">Tu equipo tiene un <?php echo $complianceRate; ?>% de
            cumplimiento esta semana.</p>
    </div>
    <?php if ($team): ?>
        <div
            style="display: flex; align-items: center; gap: 1rem; background: var(--bg-main); padding: 0.75rem 1.5rem; border-radius: 99px; border: 1px solid var(--border);">
            <?php if ($team['logo_url']): ?>
                <img src="<?php echo htmlspecialchars($team['logo_url']); ?>"
                    style="width: 32px; height: 32px; border-radius: 50%;">
            <?php endif; ?>
            <span style="font-weight: 700; font-size: 0.9rem;"><?php echo htmlspecialchars($team['name']); ?></span>
        </div>
    <?php endif; ?>
</div>

<!-- Stats Grid -->
<div
    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <div class="stat-card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div
                style="width: 40px; height: 40px; background: rgba(59, 130, 246, 0.1); border-radius: 8px; color: #3b82f6; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="users" style="width: 20px; height: 20px;"></i>
            </div>
            <span class="badge badge-blue">Atletas</span>
        </div>
        <div class="stat-value"><?php echo $athleteCount; ?></div>
        <div class="stat-label">Corredores vinculados</div>
    </div>

    <div class="stat-card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div
                style="width: 40px; height: 40px; background: rgba(168, 85, 247, 0.1); border-radius: 8px; color: #a855f7; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="file-text" style="width: 20px; height: 20px;"></i>
            </div>
            <span class="badge" style="background: rgba(168, 85, 247, 0.1); color: #a855f7;">Plantillas</span>
        </div>
        <div class="stat-value"><?php echo $templateCount; ?></div>
        <div class="stat-label">Modelos de entrenamiento</div>
    </div>

    <div class="stat-card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div
                style="width: 40px; height: 40px; background: rgba(13, 242, 128, 0.1); border-radius: 8px; color: var(--primary); display: flex; align-items: center; justify-content: center;">
                <i data-lucide="calendar-check" style="width: 20px; height: 20px;"></i>
            </div>
            <span class="badge badge-emerald">Activos</span>
        </div>
        <div class="stat-value"><?php echo $activeAthletesThisWeek; ?></div>
        <div class="stat-label">Planes esta semana</div>
    </div>

    <div class="stat-card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div
                style="width: 40px; height: 40px; background: rgba(245, 158, 11, 0.1); border-radius: 8px; color: #f59e0b; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="trending-up" style="width: 20px; height: 20px;"></i>
            </div>
            <span class="badge" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">Target</span>
        </div>
        <div class="stat-value"><?php echo $complianceRate; ?>%</div>
        <div class="stat-label">Cumplimiento global</div>
    </div>
</div>

<!-- Main Content Area -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
    <!-- Recent Activity -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 1.25rem; font-weight: 700;">Actividad Reciente</h3>
            <a href="entrenamientos.php"
                style="font-size: 0.875rem; color: var(--primary); font-weight: 600; text-decoration: none;">Ver todos
                los reportes</a>
        </div>

        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <?php
            // Fetch literal recent workouts with feedback
            $stmt = $db->prepare("SELECT w.*, u.name as athlete_name 
                                 FROM workouts w 
                                 JOIN users u ON w.athlete_id = u.id 
                                 WHERE u.coach_id = ? AND w.status = 'completed'
                                 ORDER BY w.date DESC LIMIT 5");
            $stmt->execute([$user['id']]);
            $recentActivities = $stmt->fetchAll();

            if (empty($recentActivities)): ?>
                <div class="card" style="text-align: center; padding: 3rem;">
                    <i data-lucide="activity"
                        style="width: 48px; height: 48px; color: var(--border); margin-bottom: 1rem;"></i>
                    <p style="color: var(--text-muted);">No hay actividades registradas recientemente.</p>
                </div>
            <?php else: ?>
                <?php foreach ($recentActivities as $act): ?>
                    <div class="card" style="padding: 1rem; display: flex; align-items: center; gap: 1rem;">
                        <div
                            style="width: 48px; height: 48px; background: var(--bg-main); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--primary); border: 2px solid var(--border);">
                            <?php echo strtoupper(substr($act['athlete_name'], 0, 1)); ?>
                        </div>
                        <div style="flex: 1;">
                            <h4 style="font-size: 0.9rem; font-weight: 700; margin: 0;">
                                <?php echo htmlspecialchars($act['athlete_name']); ?></h4>
                            <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">
                                <?php echo htmlspecialchars($act['type']); ?> -
                                <?php echo (new DateTime($act['date']))->format('d M'); ?></p>
                        </div>
                        <div style="text-align: right;">
                            <span class="badge badge-emerald">Completado</span>
                        </div>
                        <i data-lucide="chevron-right" style="width: 18px; height: 18px; color: var(--border);"></i>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions & Notifications -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <h3 style="font-size: 1.25rem; font-weight: 700;">Herramientas Rápidas</h3>

        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <a href="generar_plan.php" class="card"
                style="text-decoration: none; padding: 1.25rem; background: var(--primary); color: #0f172a; border: none;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <i data-lucide="plus-circle" style="width: 24px; height: 24px;"></i>
                    <div>
                        <h4 style="font-weight: 700;">Crear Nuevo Plan</h4>
                        <p style="font-size: 0.75rem; opacity: 0.8;">Asignar semana a un atleta</p>
                    </div>
                </div>
            </a>

            <div class="card" style="padding: 1.25rem;">
                <h4 style="font-weight: 700; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i data-lucide="bell" style="width: 18px; height: 18px; color: var(--primary);"></i>
                    Notificaciones
                </h4>
                <?php
                $notifs = Notification::getUnread($user['id']);
                if (empty($notifs)): ?>
                    <p style="font-size: 0.8rem; color: var(--text-muted);">Sin avisos pendientes.</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <?php foreach (array_slice($notifs, 0, 3) as $n): ?>
                            <div style="font-size: 0.8rem; border-left: 2px solid var(--primary); padding-left: 0.75rem;">
                                <p style="margin: 0; font-weight: 500;"><?php echo htmlspecialchars($n['message']); ?></p>
                                <span
                                    style="font-size: 0.7rem; color: var(--text-muted); opacity: 0.6;"><?php echo (new DateTime($n['created_at']))->format('H:i'); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <a href="notificaciones.php"
                            style="font-size: 0.75rem; color: var(--primary); font-weight: 600; text-decoration: none; margin-top: 0.5rem;">Ver
                            todas</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'views/layout/footer.php'; ?>
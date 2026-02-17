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

<!-- Stitch-inspired Header -->
<header style="display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem; margin-bottom: 2rem;">
    <div style="display: flex; flex-direction: column; gap: 0.25rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem; color: var(--text-muted); font-size: 0.875rem; font-weight: 500;">
            <span>Management</span>
            <i data-lucide="chevron-right" style="width: 14px; height: 14px;"></i>
            <span style="color: var(--text-main);">Dashboard</span>
        </div>
        <h1 style="font-size: 2.25rem; font-weight: 800; color: var(--text-main); letter-spacing: -0.025em; line-height: 1.1; margin-top: 0.25rem;">
            Hola, <?php echo explode(' ', $user['name'])[0]; ?>
        </h1>
        <p style="color: var(--text-muted); font-size: 1rem;">Visión general del rendimiento de tu equipo.</p>
    </div>
    <div>
        <a href="generar_plan.php" class="btn btn-primary" style="box-shadow: 0 0 15px rgba(13, 242, 128, 0.3);">
            <i data-lucide="plus" style="width: 20px; height: 20px; margin-right: 0.5rem;"></i>
            Nuevo Plan
        </a>
    </div>
</header>

<!-- Stats Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
    <!-- Active Athletes -->
    <div class="card" style="padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
            <div style="width: 40px; height: 40px; border-radius: 8px; background: rgba(13, 242, 128, 0.1); color: var(--primary); display: flex; align-items: center; justify-content: center;">
                <i data-lucide="users" style="width: 20px; height: 20px;"></i>
            </div>
            <span class="badge badge-emerald">+<?php echo $activeAthletesThisWeek; ?> activos</span>
        </div>
        <div>
            <div style="font-size: 2rem; font-weight: 700; color: var(--text-main); line-height: 1;"><?php echo $athleteCount; ?></div>
            <div style="font-size: 0.875rem; color: var(--text-muted); font-weight: 500; margin-top: 0.25rem;">Atletas Totales</div>
        </div>
    </div>

    <!-- Compliance -->
    <div class="card" style="padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
            <div style="width: 40px; height: 40px; border-radius: 8px; background: rgba(59, 130, 246, 0.1); color: #3b82f6; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="activity" style="width: 20px; height: 20px;"></i>
            </div>
            <span class="badge badge-blue">Semanal</span>
        </div>
        <div>
            <div style="font-size: 2rem; font-weight: 700; color: var(--text-main); line-height: 1;"><?php echo $complianceRate; ?>%</div>
            <div style="font-size: 0.875rem; color: var(--text-muted); font-weight: 500; margin-top: 0.25rem;">Cumplimiento</div>
        </div>
    </div>

    <!-- Templates -->
    <div class="card" style="padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
            <div style="width: 40px; height: 40px; border-radius: 8px; background: rgba(168, 85, 247, 0.1); color: #a855f7; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="file-text" style="width: 20px; height: 20px;"></i>
            </div>
        </div>
        <div>
            <div style="font-size: 2rem; font-weight: 700; color: var(--text-main); line-height: 1;"><?php echo $templateCount; ?></div>
            <div style="font-size: 0.875rem; color: var(--text-muted); font-weight: 500; margin-top: 0.25rem;">Plantillas</div>
        </div>
    </div>
    
    <!-- Pending Reviews (Mockup/Placeholder logic for now or count unread notifications) -->
    <div class="card" style="padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
            <div style="width: 40px; height: 40px; border-radius: 8px; background: rgba(245, 158, 11, 0.1); color: #f59e0b; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="bell" style="width: 20px; height: 20px;"></i>
            </div>
        </div>
        <div>
            <div style="font-size: 2rem; font-weight: 700; color: var(--text-main); line-height: 1;"><?php echo count(Notification::getUnread($user['id'])); ?></div>
            <div style="font-size: 0.875rem; color: var(--text-muted); font-weight: 500; margin-top: 0.25rem;">Notificaciones</div>
        </div>
    </div>
</div>

<!-- Main Section: Activity & Quick Access -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
    
    <!-- Recent Activity Table (Stitch Style) -->
    <div class="card" style="padding: 0; overflow: hidden; display: flex; flex-direction: column;">
        <div style="padding: 1.25rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-main); margin: 0;">Actividad Reciente</h3>
            <div style="display: flex; gap: 0.5rem;">
                <button class="btn btn-secondary" style="padding: 0.4rem 0.75rem; font-size: 0.75rem;"><i data-lucide="filter" style="width: 14px; height: 14px; margin-right: 4px;"></i> Filtrar</button>
            </div>
        </div>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead style="background: rgba(0,0,0,0.2);">
                    <tr>
                        <th style="padding: 1rem 1.5rem; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted);">Atleta</th>
                        <th style="padding: 1rem 1.5rem; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted);">Sesión</th>
                        <th style="padding: 1rem 1.5rem; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted);">Fecha</th>
                        <th style="padding: 1rem 1.5rem; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); text-align: right;">Estado</th>
                    </tr>
                </thead>
                <tbody style="font-size: 0.875rem;">
                    <?php
                    $stmt = $db->prepare("SELECT w.*, u.name as athlete_name, u.avatar_url 
                                         FROM workouts w 
                                         JOIN users u ON w.athlete_id = u.id 
                                         WHERE u.coach_id = ? AND w.status = 'completed'
                                         ORDER BY w.date DESC LIMIT 5");
                    $stmt->execute([$user['id']]);
                    $recentActivities = $stmt->fetchAll();

                    if (empty($recentActivities)): ?>
                        <tr><td colspan="4" style="padding: 2rem; text-align: center; color: var(--text-muted);">No hay actividad reciente.</td></tr>
                    <?php else: foreach ($recentActivities as $act): ?>
                        <tr style="border-bottom: 1px solid var(--border); transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.03)'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 1rem 1.5rem;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <?php if ($act['avatar_url']): ?>
                                        <img src="<?php echo htmlspecialchars($act['avatar_url']); ?>" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="width: 32px; height: 32px; background: var(--border); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.75rem;">
                                            <?php echo strtoupper(substr($act['athlete_name'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                    <span style="font-weight: 600; color: var(--text-main);"><?php echo htmlspecialchars($act['athlete_name']); ?></span>
                                </div>
                            </td>
                            <td style="padding: 1rem 1.5rem; color: var(--text-muted); font-weight: 500;">
                                <?php echo htmlspecialchars($act['type']); ?>
                            </td>
                            <td style="padding: 1rem 1.5rem; color: var(--text-muted);">
                                <?php echo (new DateTime($act['date']))->format('d M, Y'); ?>
                            </td>
                            <td style="padding: 1rem 1.5rem; text-align: right;">
                                <span class="badge badge-emerald">Completado</span>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div style="padding: 1rem; border-top: 1px solid var(--border); text-align: center;">
            <a href="entrenamientos.php" style="font-size: 0.875rem; font-weight: 600; color: var(--text-muted); text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 0.5rem; transition: color 0.2s;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">
                Ver todo el historial <i data-lucide="arrow-right" style="width: 16px; height: 16px;"></i>
            </a>
        </div>
    </div>

    <!-- Side Actions -->
    <div style="display: flex; flex-direction: column; gap: 1rem;">
        <div class="card" style="padding: 1.25rem;">
            <h3 style="font-size: 1rem; font-weight: 700; color: var(--text-main); margin-bottom: 1rem;">Accesos Rápidos</h3>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <a href="atletas.php" class="btn btn-secondary" style="justify-content: start; border: 1px solid var(--border); color: var(--text-muted);">
                    <i data-lucide="user-plus" style="width: 18px; height: 18px; margin-right: 0.5rem;"></i> Gestionar Atletas
                </a>
                <a href="config_team.php" class="btn btn-secondary" style="justify-content: start; border: 1px solid var(--border); color: var(--text-muted);">
                    <i data-lucide="settings" style="width: 18px; height: 18px; margin-right: 0.5rem;"></i> Configurar Team
                </a>
            </div>
        </div>

        <!-- Team Branding Preview -->
        <?php if ($team): ?>
            <div class="card" style="padding: 1.5rem; text-align: center; background: linear-gradient(180deg, var(--bg-card) 0%, rgba(13, 242, 128, 0.02) 100%);">
                <?php if ($team['logo_url']): ?>
                    <img src="<?php echo htmlspecialchars($team['logo_url']); ?>" style="width: 64px; height: 64px; border-radius: 50%; margin-bottom: 1rem; border: 4px solid var(--bg-main);">
                <?php endif; ?>
                <h4 style="font-weight: 800; font-size: 1.1rem; margin-bottom: 0.25rem;"><?php echo htmlspecialchars($team['name']); ?></h4>
                <p style="font-size: 0.8rem; color: var(--text-muted);">Team ID: #<?php echo $team['id']; ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'views/layout/footer.php'; ?>
<?php
require_once 'includes/auth.php';
require_once 'models/Notification.php';
require_once 'models/User.php';

Auth::init();
Auth::requireRoleLike(['admin', 'coach', 'athlete']);

$user = Auth::user();
$userId = $user['id'];

// Handle Send Notification (Coach Only)
$success = null;
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_notification') {
    if ($user['role'] !== 'coach') {
        $error = "No tienes permisos para realizar esta acción.";
    } else {
        $targetId = $_POST['target_id'];
        $message = $_POST['message'];

        if ($targetId === 'all') {
            $athletes = User::getAthletesByCoach($userId);
            foreach ($athletes as $athlete) {
                Notification::create($athlete['id'], $message, 'info');
            }
            $success = "Notificación enviada a todo el equipo.";
        } else {
            Notification::create($targetId, $message, 'info');
            $success = "Notificación enviada correctamente.";
        }
    }
}

// Mark specific as read
if (isset($_GET['read'])) {
    Notification::markAsRead($_GET['read']);
    header('Location: notificaciones.php');
    exit;
}

// Mark all as read
if (isset($_GET['read_all'])) {
    Notification::markAllAsRead($userId);
    header('Location: notificaciones.php');
    exit;
}

$notifications = Notification::getUnread($userId); // Actually we might want ALL, but let's stick to unread or recent?
// Model getUnread returns unread. Let's add getAll or just show unread for now + recent read?
// For simplicity, let's just show Unread and maybe last 10 Read?
// I'll stick to getUnread for now or update model later.
// Let's rely on getUnread and maybe a separate query for history if needed.
// Check Notification model... it has getUnread.

include 'views/layout/header.php';
?>

<div style="max-width: 900px; margin: 0 auto;">
    <div
        style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 2rem; font-weight: 800; color: var(--text-main); margin: 0;">NOTIFICACIONES</h1>
            <p style="color: var(--text-muted); margin-top: 0.25rem;">Mantente al día con tu entrenamiento y equipo</p>
        </div>
        <?php if (!empty($notifications)): ?>
            <a href="?read_all=1"
                style="font-size: 0.85rem; font-weight: 700; color: var(--primary); text-decoration: none; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                <i data-lucide="check-check" style="width: 16px; height: 16px;"></i>
                Marcar todas como leídas
            </a>
        <?php endif; ?>
    </div>

    <?php if ($success): ?>
        <div class="card"
            style="border-color: var(--primary); background: rgba(13, 242, 128, 0.05); margin-bottom: 2rem; display: flex; align-items: center; gap: 0.75rem;">
            <i data-lucide="check-circle" style="color: var(--primary);"></i>
            <span><?php echo $success; ?></span>
        </div>
    <?php endif; ?>

    <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 3rem;">
        <?php if (empty($notifications)): ?>
            <div class="card" style="text-align: center; padding: 4rem 2rem;">
                <div
                    style="width: 64px; height: 64px; border-radius: 50%; background: var(--bg-main); display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; color: var(--text-muted);">
                    <i data-lucide="bell-off" style="width: 32px; height: 32px;"></i>
                </div>
                <h3 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 0.5rem;">Sin novedades</h3>
                <p style="color: var(--text-muted);">No tienes notificaciones pendientes en este momento.</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notif): ?>
                <div class="card"
                    style="display: flex; gap: 1.25rem; align-items: flex-start; transition: transform 0.2s ease;">
                    <div
                        style="width: 44px; height: 44px; border-radius: 12px; background: rgba(13, 242, 128, 0.1); color: var(--primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i data-lucide="info" style="width: 22px; height: 22px;"></i>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-size: 0.95rem; font-weight: 500; color: var(--text-main); line-height: 1.5;">
                            <?php echo htmlspecialchars($notif['message']); ?>
                        </div>
                        <div
                            style="margin-top: 0.5rem; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            <?php echo (new DateTime($notif['created_at']))->format('d M — H:i'); ?>
                        </div>
                    </div>
                    <a href="?read=<?php echo $notif['id']; ?>"
                        style="padding: 0.5rem; border-radius: 50%; background: var(--bg-main); border: 1px solid var(--border); color: var(--text-muted); display: flex; align-items: center; justify-content: center; transition: all 0.2s ease;"
                        onmouseover="this.style.color='var(--primary)'; this.style.borderColor='var(--primary)';"
                        onmouseout="this.style.color='var(--text-muted)'; this.style.borderColor='var(--border)';"
                        title="Marcar como leída">
                        <i data-lucide="check" style="width: 16px; height: 16px;"></i>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Coach Tool: Send System Notification -->
    <?php if ($user['role'] === 'coach'):
        $myAthletes = User::getAthletesByCoach($userId);
        ?>
        <div style="margin-top: 4rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;">
                <h2 style="font-size: 1.25rem; font-weight: 800; margin: 0;">ENVIAR COMUNICADO</h2>
                <div style="height: 2px; flex: 1; background: var(--border);"></div>
            </div>

            <div class="card">
                <form method="POST" style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <input type="hidden" name="action" value="send_notification">

                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem;">
                        <div>
                            <label
                                style="display: block; font-size: 0.75rem; font-weight: 800; color: var(--text-muted); margin-bottom: 0.5rem; text-transform: uppercase;">Destinatario</label>
                            <select name="target_id"
                                style="width: 100%; padding: 0.85rem; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-main); color: var(--text-main); font-family: inherit; font-weight: 600;">
                                <option value="all">Todo el Equipo</option>
                                <?php foreach ($myAthletes as $athlete): ?>
                                    <option value="<?php echo $athlete['id']; ?>">
                                        <?php echo htmlspecialchars($athlete['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: 0.75rem; font-weight: 800; color: var(--text-muted); margin-bottom: 0.5rem; text-transform: uppercase;">Mensaje</label>
                            <input type="text" name="message" required
                                style="width: 100%; padding: 0.85rem; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-main); color: var(--text-main); font-family: inherit;"
                                placeholder="Escribe el aviso para tus atletas...">
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn btn-primary" style="padding: 0.85rem 2rem; gap: 0.75rem;">
                            <i data-lucide="send" style="width: 18px; height: 18px;"></i>
                            Enviar Aviso
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'views/layout/footer.php'; ?>

<?php include 'views/layout/footer.php'; ?>
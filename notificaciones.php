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

<div class="max-w-4xl mx-auto">
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Notificaciones</h1>
            <p class="text-slate-500 mt-1">Mantente al día con tu entrenamiento y equipo.</p>
        </div>
        <?php if (!empty($notifications)): ?>
            <a href="?read_all=1" class="text-sm font-semibold text-blue-600 hover:text-blue-700 hover:underline">
                Marcar todas como leídas
            </a>
        <?php endif; ?>
    </div>

    <?php if ($success): ?>
        <div class="bg-green-50 text-green-600 p-4 rounded-xl mb-6 flex items-center gap-3">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <div class="space-y-4">
        <?php if (empty($notifications)): ?>
            <div class="text-center py-12 bg-white rounded-2xl border border-slate-100">
                <div
                    class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300">
                    <i data-lucide="bell-off" class="w-8 h-8"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-900">Sin novedades</h3>
                <p class="text-slate-500">No tienes notificaciones pendientes.</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notif): ?>
                <div
                    class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex gap-4 transition-all hover:shadow-md">
                    <div class="shrink-0">
                        <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center">
                            <i data-lucide="info" class="w-6 h-6"></i>
                        </div>
                    </div>
                    <div class="flex-1">
                        <p class="text-slate-900 font-medium leading-relaxed">
                            <?php echo htmlspecialchars($notif['message']); ?>
                        </p>
                        <span class="text-xs text-slate-400 font-semibold mt-2 block">
                            <?php echo (new DateTime($notif['created_at']))->format('d M H:i'); ?>
                        </span>
                    </div>
                    <a href="?read=<?php echo $notif['id']; ?>" class="text-slate-400 hover:text-blue-600 self-start p-2"
                        title="Marcar como leída">
                        <i data-lucide="check" class="w-5 h-5"></i>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Coach: Send Notification Section -->
    <?php if ($user['role'] === 'coach'):
        $myAthletes = User::getAthletesByCoach($userId);
        ?>
        <div class="mt-12 pt-12 border-t border-slate-200">
            <h2 class="text-xl font-bold text-slate-900 mb-6 flex items-center gap-2">
                <i data-lucide="send" class="w-5 h-5 text-blue-600"></i>
                Enviar Notificación
            </h2>
            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="send_notification">

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Destinatario</label>
                        <select name="target_id"
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 outline-none bg-slate-50">
                            <option value="all">Todo el Equipo</option>
                            <?php foreach ($myAthletes as $athlete): ?>
                                <option value="<?php echo $athlete['id']; ?>">
                                    <?php echo htmlspecialchars($athlete['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Mensaje</label>
                        <textarea name="message" rows="3" required
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 outline-none"
                            placeholder="Escribe tu mensaje importante aquí..."></textarea>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl transition-colors flex items-center gap-2">
                            <i data-lucide="send" class="w-4 h-4"></i>
                            Enviar Mensaje
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php include 'views/layout/footer.php'; ?>
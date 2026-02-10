<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'models/User.php';
require_once 'models/Notification.php';
Auth::init();

if (!Auth::check()) {
    header('Location: login.php');
    exit;
}

$user = Auth::user();

// Redirect based on role
// Redirect based on role
if ($user['role'] === 'admin') {
    header('Location: admin_dashboard.php');
    exit;
} elseif ($user['role'] === 'coach') {
    require_once 'models/Team.php';
    $athletes = User::getByCoachId($user['id']);
    $athleteCount = count($athletes);
    $team = Team::findByCoach($user['id']);
} else {
    header('Location: mi_plan.php'); // Athlete view
    exit;
}

include 'views/layout/header.php';
?>

<!-- Page Header -->
<div class="mb-8">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">PANEL PRINCIPAL</h1>
            <p class="text-slate-500 mt-1">Bienvenido de nuevo, <?php echo htmlspecialchars($user['name']); ?></p>
        </div>
        <?php if (isset($team) && $team): ?>
            <div
                class="bg-blue-600 text-white px-6 py-2 rounded-full font-bold shadow-lg shadow-blue-200 flex items-center gap-3">
                <?php if ($team['logo_url']): ?>
                    <img src="<?php echo htmlspecialchars($team['logo_url']); ?>"
                        class="w-8 h-8 rounded-full bg-white border-2 border-white">
                <?php endif; ?>
                Team <?php echo htmlspecialchars($team['name']); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Stats -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <a href="atletas.php"
        class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-blue-100 rounded-2xl flex items-center justify-center">
                <i data-lucide="users" class="w-7 h-7 text-blue-600"></i>
            </div>
            <div>
                <p class="text-3xl font-bold text-slate-900"><?php echo $athleteCount; ?></p>
                <p class="text-sm text-slate-500">Atletas</p>
            </div>
        </div>
    </a>

    <a href="plantillas.php"
        class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-purple-100 rounded-2xl flex items-center justify-center">
                <i data-lucide="file-text" class="w-7 h-7 text-purple-600"></i>
            </div>
            <div>
                <p class="text-3xl font-bold text-slate-900">0</p>
                <p class="text-sm text-slate-500">Plantillas</p>
            </div>
        </div>
    </a>

    <a href="generar_plan.php"
        class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-green-100 rounded-2xl flex items-center justify-center">
                <i data-lucide="calendar" class="w-7 h-7 text-green-600"></i>
            </div>
            <div>
                <p class="text-3xl font-bold text-slate-900">0</p>
                <p class="text-sm text-slate-500">Planes Activos</p>
            </div>
        </div>
    </a>

    <a href="metricas.php"
        class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-orange-100 rounded-2xl flex items-center justify-center">
                <i data-lucide="trending-up" class="w-7 h-7 text-orange-600"></i>
            </div>
            <div>
                <p class="text-3xl font-bold text-slate-900">--%</p>
                <p class="text-sm text-slate-500">Cumplimiento</p>
            </div>
        </div>
    </a>
</div>

<!-- Quick Actions -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl p-8 text-white">
        <h3 class="text-xl font-bold mb-3">¿Nuevo atleta?</h3>
        <p class="text-blue-100 mb-6">Agrega un atleta a tu equipo y comienza a diseñar su plan de entrenamiento
            personalizado.</p>
        <a href="atletas.php"
            class="inline-flex items-center gap-2 bg-white text-blue-600 px-5 py-3 rounded-xl font-semibold hover:bg-blue-50 transition-colors">
            <i data-lucide="user-plus" class="w-5 h-5"></i>
            Agregar Atleta
        </a>
    </div>

    <div class="bg-gradient-to-br from-purple-600 to-purple-700 rounded-2xl p-8 text-white">
        <h3 class="text-xl font-bold mb-3">Crea tu primera plantilla</h3>
        <p class="text-purple-100 mb-6">Define sesiones de entrenamiento reutilizables: intervalos, fondos, series y
            más.</p>
        <a href="plantillas.php"
            class="inline-flex items-center gap-2 bg-white text-purple-600 px-5 py-3 rounded-xl font-semibold hover:bg-purple-50 transition-colors">
            <i data-lucide="plus-circle" class="w-5 h-5"></i>
            Crear Plantilla
        </a>
    </div>
    </div>
</div>

<!-- Recent Notifications -->
<?php
$recentNotifications = Notification::getUnread($user['id']);
$notifCount = count($recentNotifications);
if ($notifCount > 5) {
    $recentNotifications = array_slice($recentNotifications, 0, 5);
}
?>
<?php if ($notifCount > 0): ?>
<div class="mt-8">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2">
            <i data-lucide="bell" class="w-5 h-5 text-blue-600"></i>
            Notificaciones Recientes
            <span class="bg-blue-100 text-blue-700 text-xs font-bold px-2 py-1 rounded-full"><?php echo $notifCount; ?></span>
        </h2>
        <a href="notificaciones.php" class="text-sm font-semibold text-blue-600 hover:text-blue-700 hover:underline">
            Ver todas →
        </a>
    </div>
    <div class="space-y-3">
        <?php foreach ($recentNotifications as $notif): ?>
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex gap-3 hover:shadow-md transition-all">
                <div class="shrink-0">
                    <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center">
                        <i data-lucide="info" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-slate-900 font-medium text-sm leading-relaxed">
                        <?php echo htmlspecialchars($notif['message']); ?>
                    </p>
                    <span class="text-xs text-slate-400 font-semibold mt-1 block">
                        <?php echo (new DateTime($notif['created_at']))->format('d M H:i'); ?>
                    </span>
                </div>
                <a href="notificaciones.php?read=<?php echo $notif['id']; ?>" 
                   class="text-slate-400 hover:text-blue-600 self-start p-1"
                   title="Marcar como leída">
                    <i data-lucide="check" class="w-4 h-4"></i>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php include 'views/layout/footer.php'; ?>
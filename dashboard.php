<?php
require_once 'includes/auth.php';
require_once 'models/User.php';
require_once 'models/Workout.php';
require_once 'config/config.php';

Auth::init();
if (!Auth::check()) {
    header('Location: login.php');
    exit;
}

$currentUser = Auth::user();
$userData = User::getById($currentUser['id']);

include 'views/layout/header.php';
?>

<div class="space-y-8">
    <!-- Header Section -->
    <header class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Panel de Control</h1>
            <p class="text-slate-500 mt-1">Bienvenido de nuevo,
                <?php echo htmlspecialchars($userData['name']); ?>
            </p>
        </div>

        <?php if ($userData['role'] === 'coach'): ?>
            <button
                class="bg-primary text-white px-6 py-2.5 rounded-xl font-semibold shadow-lg shadow-blue-200 hover:bg-blue-600 transition-all flex items-center gap-2">
                <i data-lucide="plus" class="w-5 h-5"></i>
                Crear Nuevo Atleta
            </button>
        <?php endif; ?>
    </header>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                    <i data-lucide="users" class="w-6 h-6"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">
                        <?php echo $userData['role'] === 'coach' ? 'Atletas Activos' : 'Entrenamientos'; ?>
                    </p>
                    <p class="text-2xl font-bold text-slate-900">--</p>
                </div>
            </div>
        </div>
        <!-- Add more stat cards here -->
    </div>

    <!-- Main Content Area -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
            <h2 class="font-bold text-slate-900">
                <?php echo $userData['role'] === 'coach' ? 'Lista de Atletas' : 'Próximos Entrenamientos'; ?>
            </h2>
        </div>
        <div class="p-6">
            <div class="text-center py-12 text-slate-400">
                <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-4 opacity-20"></i>
                <p>No hay datos disponibles en este momento</p>
            </div>
        </div>
    </div>
</div>

<?php include 'views/layout/footer.php'; ?>
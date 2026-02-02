<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'models/User.php';
Auth::init();
Auth::requireRole('coach');

$coach = Auth::user();
$athletes = User::getByCoachId($coach['id']);

include 'views/layout/header.php';
?>

<!-- Page Header -->
<div class="mb-8">
    <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">MÉTRICAS</h1>
    <p class="text-slate-500 mt-1">Analiza el progreso de tu equipo</p>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                <i data-lucide="users" class="w-6 h-6 text-blue-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900">
                    <?php echo count($athletes); ?>
                </p>
                <p class="text-sm text-slate-500">Atletas Activos</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                <i data-lucide="check-circle" class="w-6 h-6 text-green-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900">0</p>
                <p class="text-sm text-slate-500">Entrenamientos Completados</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
                <i data-lucide="clock" class="w-6 h-6 text-orange-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900">0</p>
                <p class="text-sm text-slate-500">Pendientes Esta Semana</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                <i data-lucide="trending-up" class="w-6 h-6 text-purple-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900">--%</p>
                <p class="text-sm text-slate-500">Tasa de Cumplimiento</p>
            </div>
        </div>
    </div>
</div>

<!-- Charts Placeholder -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 text-center">
    <div class="w-20 h-20 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
        <i data-lucide="bar-chart-3" class="w-10 h-10 text-slate-400"></i>
    </div>
    <h3 class="text-lg font-semibold text-slate-900 mb-2">Gráficos de Rendimiento</h3>
    <p class="text-slate-500 max-w-md mx-auto">Los gráficos de rendimiento estarán disponibles cuando haya suficientes
        datos de entrenamientos completados.</p>
</div>

<?php include 'views/layout/footer.php'; ?>
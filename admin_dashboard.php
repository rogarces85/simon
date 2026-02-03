<?php
require_once 'includes/auth.php';
require_once 'models/User.php';
require_once 'models/Team.php';

Auth::init();
Auth::requireRole('admin');

$user = Auth::user();
$coaches = User::getByRole('coach');

include 'views/layout/header.php';
?>

<div class="mb-8 flex justify-between items-center">
    <div>
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">ADMINISTRACIÓN</h1>
        <p class="text-slate-500 mt-1">Gestión global del sistema</p>
    </div>
    <a href="crear_entrenador.php"
        class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-semibold transition-colors flex items-center gap-2">
        <i data-lucide="user-plus" class="w-5 h-5"></i>
        Nuevo Entrenador
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-6 border-b border-slate-100">
        <h2 class="text-lg font-bold text-slate-900">Entrenadores Registrados</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="text-left py-4 px-6 text-xs font-semibold text-slate-500 uppercase tracking-wider">Nombre
                    </th>
                    <th class="text-left py-4 px-6 text-xs font-semibold text-slate-500 uppercase tracking-wider">Email
                    </th>
                    <th class="text-left py-4 px-6 text-xs font-semibold text-slate-500 uppercase tracking-wider">Team
                    </th>
                    <th class="text-left py-4 px-6 text-xs font-semibold text-slate-500 uppercase tracking-wider">Estado
                    </th>
                    <th class="text-right py-4 px-6 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($coaches as $coach):
                    $team = Team::findByCoach($coach['id']);
                    ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-4 px-6">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 font-bold">
                                    <?php echo strtoupper(substr($coach['name'], 0, 1)); ?>
                                </div>
                                <span class="font-medium text-slate-900">
                                    <?php echo htmlspecialchars($coach['name']); ?>
                                </span>
                            </div>
                        </td>
                        <td class="py-4 px-6 text-slate-600">
                            <?php echo htmlspecialchars($coach['username']); ?>
                        </td>
                        <td class="py-4 px-6">
                            <?php if ($team): ?>
                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                    <?php echo htmlspecialchars($team['name']); ?>
                                </span>
                            <?php else: ?>
                                <span class="text-slate-400 text-sm italic">Sin Team</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 px-6">
                            <span
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                Activo
                            </span>
                        </td>
                        <td class="py-4 px-6 text-right">
                            <button class="text-slate-400 hover:text-blue-600 transition-colors p-2">
                                <i data-lucide="edit-2" class="w-4 h-4"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'views/layout/footer.php'; ?>
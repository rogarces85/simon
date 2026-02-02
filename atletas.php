<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'models/User.php';
Auth::init();
Auth::requireRole('coach');

$coach = Auth::user();
$athletes = User::getByCoachId($coach['id']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_athlete') {
        $athleteData = [
            'username' => $_POST['email'],
            'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
            'role' => 'athlete',
            'name' => $_POST['name'],
            'coach_id' => $coach['id'],
            'goal_date' => $_POST['goal_date'] ?: null,
            'goal_pace' => $_POST['goal_pace'] ?: null,
            'level' => $_POST['level'] ?: 'Principiante',
            'available_days' => json_encode(['lunes', 'martes', 'miercoles', 'jueves', 'viernes']),
            'preferred_long_run_day' => $_POST['preferred_long_run_day'] ?: 'Domingo',
            'max_time_per_session' => $_POST['max_time_per_session'] ?: 60,
            'observations' => $_POST['observations'] ?: ''
        ];

        User::create($athleteData);
        header('Location: atletas.php?success=1');
        exit;
    }

    if ($_POST['action'] === 'delete_athlete' && isset($_POST['athlete_id'])) {
        User::delete($_POST['athlete_id']);
        header('Location: atletas.php?deleted=1');
        exit;
    }
}

include 'views/layout/header.php';
?>

<!-- Page Header -->
<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">MIS ATLETAS</h1>
        <p class="text-slate-500 mt-1">Gestiona y monitorea a tu equipo</p>
    </div>
    <button onclick="openModal()"
        class="flex items-center gap-2 bg-blue-600 text-white px-5 py-3 rounded-xl font-semibold hover:bg-blue-700 transition-all shadow-lg shadow-blue-100">
        <i data-lucide="plus" class="w-5 h-5"></i>
        Nuevo Atleta
    </button>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
        ✅ Atleta creado exitosamente
    </div>
<?php endif; ?>

<!-- Athletes Table -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-6 border-b border-slate-100 flex justify-between items-center">
        <h2 class="text-xl font-bold text-slate-900">Listado de Atletas</h2>
        <div class="relative">
            <i data-lucide="search" class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
            <input type="text" id="searchAtleta" placeholder="Buscar atleta..."
                class="pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none w-64">
        </div>
    </div>

    <table class="w-full">
        <thead class="bg-slate-50">
            <tr>
                <th class="text-left px-6 py-4 text-sm font-semibold text-slate-600">Nombre</th>
                <th class="text-left px-6 py-4 text-sm font-semibold text-slate-600">Email</th>
                <th class="text-left px-6 py-4 text-sm font-semibold text-slate-600">Nivel</th>
                <th class="text-left px-6 py-4 text-sm font-semibold text-slate-600">Ritmo Objetivo</th>
                <th class="text-left px-6 py-4 text-sm font-semibold text-slate-600">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php if (empty($athletes)): ?>
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                        No hay atletas registrados. <button onclick="openModal()" class="text-blue-600 font-semibold">Crea
                            el primero</button>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($athletes as $athlete): ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 font-medium text-slate-900">
                            <?php echo htmlspecialchars($athlete['name']); ?>
                        </td>
                        <td class="px-6 py-4 text-blue-600">
                            <?php echo htmlspecialchars($athlete['username']); ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php
                            $levelColors = [
                                'Principiante' => 'bg-orange-100 text-orange-700',
                                'Intermedio' => 'bg-yellow-100 text-yellow-700',
                                'Avanzado' => 'bg-green-100 text-green-700'
                            ];
                            $level = $athlete['level'] ?? 'Principiante';
                            $colorClass = $levelColors[$level] ?? 'bg-slate-100 text-slate-700';
                            ?>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $colorClass; ?>">
                                <?php echo htmlspecialchars($level); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-slate-600">
                            <?php echo htmlspecialchars($athlete['goal_pace'] ?? '-'); ?>/km
                        </td>
                        <td class="px-6 py-4">
                            <button class="text-slate-400 hover:text-slate-600 p-2 rounded-lg hover:bg-slate-100">
                                <i data-lucide="more-horizontal" class="w-5 h-5"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Nuevo Atleta -->
<div id="modalAtleta" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto m-4">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center">
            <div>
                <h3 class="text-xl font-bold text-slate-900">REGISTRAR NUEVO ATLETA</h3>
                <p class="text-slate-500 text-sm mt-1">Completa la información del atleta para agregarlo a tu equipo.
                </p>
            </div>
            <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>

        <form method="POST" class="p-6 space-y-6">
            <input type="hidden" name="action" value="create_athlete">

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Nombre Completo *</label>
                    <input type="text" name="name" placeholder="Juan Pérez" required
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Email *</label>
                    <input type="email" name="email" placeholder="atleta@email.com" required
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Contraseña Generada *</label>
                <div class="flex gap-2">
                    <input type="text" name="password" id="passwordField" readonly
                        class="flex-1 px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-mono">
                    <button type="button" onclick="togglePassword()"
                        class="px-4 py-3 bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">
                        <i data-lucide="eye-off" class="w-5 h-5 text-slate-600"></i>
                    </button>
                    <button type="button" onclick="copyPassword()"
                        class="px-4 py-3 bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">
                        <i data-lucide="copy" class="w-5 h-5 text-slate-600"></i>
                    </button>
                    <button type="button" onclick="generatePassword()"
                        class="px-4 py-3 bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors">
                        <i data-lucide="refresh-cw" class="w-5 h-5 text-white"></i>
                    </button>
                </div>
                <p class="text-xs text-slate-500 mt-2">La contraseña se genera automáticamente. El atleta podrá
                    cambiarla al iniciar sesión.</p>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Nivel</label>
                    <select name="level"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="Principiante">Principiante</option>
                        <option value="Intermedio">Intermedio</option>
                        <option value="Avanzado">Avanzado</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Ritmo Objetivo (min/km)</label>
                    <input type="text" name="goal_pace" placeholder="5:30"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Fecha Objetivo (Competencia)</label>
                    <input type="date" name="goal_date"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Día Preferido para Fondo</label>
                    <select name="preferred_long_run_day"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="Domingo">Domingo</option>
                        <option value="Sábado">Sábado</option>
                        <option value="Viernes">Viernes</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Tiempo Máximo por Sesión
                    (minutos)</label>
                <input type="number" name="max_time_per_session" placeholder="90" value="90"
                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Observaciones</label>
                <textarea name="observations" rows="3"
                    placeholder="Lesiones previas, preferencias, notas importantes..."
                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none resize-none"></textarea>
            </div>

            <div class="flex justify-end gap-4 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal()"
                    class="px-6 py-3 border border-slate-200 rounded-xl font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                    Cancelar
                </button>
                <button type="submit"
                    class="px-6 py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition-colors">
                    Guardar Atleta
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal() {
        document.getElementById('modalAtleta').classList.remove('hidden');
        document.getElementById('modalAtleta').classList.add('flex');
        generatePassword();
        lucide.createIcons();
    }

    function closeModal() {
        document.getElementById('modalAtleta').classList.add('hidden');
        document.getElementById('modalAtleta').classList.remove('flex');
    }

    function generatePassword() {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        let password = '';
        for (let i = 0; i < 10; i++) {
            password += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        document.getElementById('passwordField').value = password;
    }

    function copyPassword() {
        const field = document.getElementById('passwordField');
        field.select();
        document.execCommand('copy');
        alert('Contraseña copiada!');
    }

    function togglePassword() {
        const field = document.getElementById('passwordField');
        field.type = field.type === 'password' ? 'text' : 'password';
    }
</script>

<?php include 'views/layout/footer.php'; ?>
<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'models/User.php';
Auth::init();
Auth::requireRole('coach');

$coach = Auth::user();

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

    if ($_POST['action'] === 'update_athlete' && isset($_POST['athlete_id'])) {
        $athleteData = [
            'name' => $_POST['name'],
            'username' => $_POST['email'],
            'goal_date' => $_POST['goal_date'] ?: null,
            'goal_pace' => $_POST['goal_pace'] ?: null,
            'level' => $_POST['level'] ?: 'Principiante',
            'preferred_long_run_day' => $_POST['preferred_long_run_day'] ?: 'Domingo',
            'max_time_per_session' => $_POST['max_time_per_session'] ?: 60,
            'observations' => $_POST['observations'] ?: ''
        ];

        // Optional: Update password if provided
        if (!empty($_POST['password']) && $_POST['password'] !== '********') {
            $athleteData['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }

        User::update($_POST['athlete_id'], $athleteData);
        header('Location: atletas.php?updated=1');
        exit;
    }

    if ($_POST['action'] === 'delete_athlete' && isset($_POST['athlete_id'])) {
        User::delete($_POST['athlete_id']);
        header('Location: atletas.php?deleted=1');
        exit;
    }
}

$athletes = User::getByCoachId($coach['id']);
include 'views/layout/header.php';
?>

<!-- Page Header -->
<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">MIS ATLETAS</h1>
        <p class="text-slate-500 mt-1">Gestiona y monitorea a tu equipo</p>
    </div>
    <button onclick="openCreateModal()"
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

<?php if (isset($_GET['updated'])): ?>
    <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-xl mb-6">
        ✅ Atleta actualizado exitosamente
    </div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6">
        ✅ Atleta eliminado correctamente
    </div>
<?php endif; ?>

<!-- Athletes Table -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-6 border-b border-slate-100 flex justify-between items-center">
        <h2 class="text-xl font-bold text-slate-900">Listado de Atletas</h2>
        <div class="relative">
            <i data-lucide="search" class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
            <input type="text" id="searchAtleta" onkeyup="filterAthletes()" placeholder="Buscar atleta..."
                class="pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none w-64">
        </div>
    </div>

    <table class="w-full" id="athleteTable">
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
                        No hay atletas registrados. <button onclick="openCreateModal()"
                            class="text-blue-600 font-semibold">Crea
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
                        <td class="px-6 py-4 flex gap-2">
                            <button onclick='openEditModal(<?php echo json_encode($athlete); ?>)'
                                class="text-blue-500 hover:text-blue-700 p-2 rounded-lg hover:bg-blue-50 transition-all"
                                title="Editar">
                                <i data-lucide="pencil" class="w-5 h-5"></i>
                            </button>
                            <form method="POST" onsubmit="return confirm('¿Estás seguro de eliminar a este atleta?')"
                                class="inline">
                                <input type="hidden" name="action" value="delete_athlete">
                                <input type="hidden" name="athlete_id" value="<?php echo $athlete['id']; ?>">
                                <button type="submit"
                                    class="text-red-400 hover:text-red-600 p-2 rounded-lg hover:bg-red-50 transition-all"
                                    title="Eliminar">
                                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Atleta (Create/Edit) -->
<div id="modalAtleta" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto m-4">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center">
            <div>
                <h3 class="text-xl font-bold text-slate-900" id="modalTitle">REGISTRAR NUEVO ATLETA</h3>
                <p class="text-slate-500 text-sm mt-1" id="modalSubtitle">Completa la información del atleta para
                    agregarlo a tu equipo.</p>
            </div>
            <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>

        <form method="POST" class="p-6 space-y-6" id="athleteForm">
            <input type="hidden" name="action" id="formAction" value="create_athlete">
            <input type="hidden" name="athlete_id" id="athleteId">

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Nombre Completo *</label>
                    <input type="text" name="name" id="fieldName" placeholder="Juan Pérez" required
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Email *</label>
                    <input type="email" name="email" id="fieldEmail" placeholder="atleta@email.com" required
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Contraseña <span
                        id="pwdLabelExtra">*</span></label>

                <!-- Password Mode Selection -->
                <div class="flex gap-4 mb-3" id="passwordModeSection">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="password_mode" value="auto" checked onchange="togglePasswordMode()"
                            class="w-4 h-4 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-slate-700">Generar automáticamente</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="password_mode" value="manual" onchange="togglePasswordMode()"
                            class="w-4 h-4 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-slate-700">Ingresar manualmente</span>
                    </label>
                </div>

                <div class="flex gap-2">
                    <input type="text" name="password" id="passwordField" oninput="checkPasswordStrength()"
                        class="flex-1 px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-mono outline-none focus:ring-2 focus:ring-blue-500">
                    <button type="button" onclick="togglePassword()"
                        class="px-4 py-3 bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">
                        <i data-lucide="eye-off" class="w-5 h-5 text-slate-600" id="togglePwdIcon"></i>
                    </button>
                    <button type="button" onclick="generatePassword()" id="generateBtn"
                        class="px-4 py-3 bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors">
                        <i data-lucide="refresh-cw" class="w-5 h-5 text-white"></i>
                    </button>
                </div>

                <!-- Strength Meter -->
                <div id="strengthMeter" class="mt-3 hidden">
                    <div class="flex items-center gap-2 mb-1">
                        <div class="flex-1 h-2 bg-slate-200 rounded-full overflow-hidden">
                            <div id="strengthBar" class="h-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                        <span id="strengthText" class="text-xs font-semibold">-</span>
                    </div>
                </div>

                <!-- Password Criteria (shown for manual mode) -->
                <div id="passwordCriteria" class="mt-3 p-4 bg-slate-50 rounded-xl border border-slate-200 hidden">
                    <p class="text-xs font-semibold text-slate-700 mb-2">Criterios de contraseña segura:</p>
                    <ul class="space-y-1 text-xs">
                        <li id="criteria-length" class="flex items-center gap-2 text-slate-500">
                            <i data-lucide="circle" class="w-3 h-3"></i>
                            Mínimo 8 caracteres
                        </li>
                        <li id="criteria-uppercase" class="flex items-center gap-2 text-slate-500">
                            <i data-lucide="circle" class="w-3 h-3"></i>
                            Al menos una letra mayúscula (A-Z)
                        </li>
                        <li id="criteria-lowercase" class="flex items-center gap-2 text-slate-500">
                            <i data-lucide="circle" class="w-3 h-3"></i>
                            Al menos una letra minúscula (a-z)
                        </li>
                        <li id="criteria-number" class="flex items-center gap-2 text-slate-500">
                            <i data-lucide="circle" class="w-3 h-3"></i>
                            Al menos un número (0-9)
                        </li>
                        <li id="criteria-special" class="flex items-center gap-2 text-slate-500">
                            <i data-lucide="circle" class="w-3 h-3"></i>
                            Al menos un carácter especial (!@#$%^&*)
                        </li>
                    </ul>
                </div>

                <p class="text-xs text-slate-500 mt-2" id="pwdHelp">La contraseña se genera automáticamente. El atleta
                    podrá cambiarla al iniciar sesión.</p>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Nivel</label>
                    <select name="level" id="fieldLevel"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="Principiante">Principiante</option>
                        <option value="Intermedio">Intermedio</option>
                        <option value="Avanzado">Avanzado</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Ritmo Objetivo (min/km)</label>
                    <input type="text" name="goal_pace" id="fieldGoalPace" placeholder="5:30"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Fecha Objetivo (Competencia)</label>
                    <input type="date" name="goal_date" id="fieldGoalDate"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Día Preferido para Fondo</label>
                    <select name="preferred_long_run_day" id="fieldLongRunDay"
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
                <input type="number" name="max_time_per_session" id="fieldMaxTime" placeholder="90" value="90"
                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Observaciones</label>
                <textarea name="observations" id="fieldObservations" rows="3"
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
    function openCreateModal() {
        resetForm();
        document.getElementById('modalTitle').innerText = 'REGISTRAR NUEVO ATLETA';
        document.getElementById('modalSubtitle').innerText = 'Completa la información del atleta para agregarlo a tu equipo.';
        document.getElementById('formAction').value = 'create_athlete';
        document.getElementById('passwordField').required = true;
        document.getElementById('passwordField').placeholder = '';
        document.getElementById('pwdLabelExtra').innerText = '*';
        document.getElementById('pwdHelp').innerText = 'La contraseña se genera automáticamente. El atleta podrá cambiarla al iniciar sesión.';

        document.getElementById('modalAtleta').classList.remove('hidden');
        document.getElementById('modalAtleta').classList.add('flex');
        generatePassword();
        lucide.createIcons();
    }

    function openEditModal(athlete) {
        resetForm();
        document.getElementById('modalTitle').innerText = 'EDITAR ATLETA';
        document.getElementById('modalSubtitle').innerText = 'Modifica la información del atleta seleccionado.';
        document.getElementById('formAction').value = 'update_athlete';
        document.getElementById('athleteId').value = athlete.id;

        document.getElementById('fieldName').value = athlete.name;
        document.getElementById('fieldEmail').value = athlete.username;
        document.getElementById('fieldLevel').value = athlete.level || 'Principiante';
        document.getElementById('fieldGoalPace').value = athlete.goal_pace || '';
        document.getElementById('fieldGoalDate').value = athlete.goal_date ? athlete.goal_date.split(' ')[0] : '';
        document.getElementById('fieldLongRunDay').value = athlete.preferred_long_run_day || 'Domingo';
        document.getElementById('fieldMaxTime').value = athlete.max_time_per_session || 90;
        document.getElementById('fieldObservations').value = athlete.observations || '';

        document.getElementById('passwordField').required = false;
        document.getElementById('passwordField').value = '';
        document.getElementById('passwordField').placeholder = 'Dejar en blanco para mantener actual';
        document.getElementById('pwdLabelExtra').innerText = '(Opcional)';
        document.getElementById('pwdHelp').innerText = 'Solo ingresa una contraseña si deseas cambiar la actual.';

        document.getElementById('modalAtleta').classList.remove('hidden');
        document.getElementById('modalAtleta').classList.add('flex');
        lucide.createIcons();
    }

    function resetForm() {
        document.getElementById('athleteForm').reset();
        document.getElementById('athleteId').value = '';
    }

    function closeModal() {
        document.getElementById('modalAtleta').classList.add('hidden');
        document.getElementById('modalAtleta').classList.remove('flex');
    }

    function generatePassword() {
        const upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        const lower = 'abcdefghijkmnpqrstuvwxyz';
        const numbers = '23456789';
        const special = '!@#$%^&*';

        // Ensure at least one of each type
        let password = '';
        password += upper.charAt(Math.floor(Math.random() * upper.length));
        password += lower.charAt(Math.floor(Math.random() * lower.length));
        password += numbers.charAt(Math.floor(Math.random() * numbers.length));
        password += special.charAt(Math.floor(Math.random() * special.length));

        // Fill remaining with random chars
        const allChars = upper + lower + numbers + special;
        for (let i = 0; i < 6; i++) {
            password += allChars.charAt(Math.floor(Math.random() * allChars.length));
        }

        // Shuffle the password
        password = password.split('').sort(() => Math.random() - 0.5).join('');

        document.getElementById('passwordField').value = password;
        document.getElementById('passwordField').type = 'text';
        checkPasswordStrength();
    }

    function togglePasswordMode() {
        const mode = document.querySelector('input[name="password_mode"]:checked').value;
        const generateBtn = document.getElementById('generateBtn');
        const strengthMeter = document.getElementById('strengthMeter');
        const criteria = document.getElementById('passwordCriteria');
        const pwdHelp = document.getElementById('pwdHelp');
        const passwordField = document.getElementById('passwordField');

        if (mode === 'auto') {
            generateBtn.classList.remove('hidden');
            strengthMeter.classList.add('hidden');
            criteria.classList.add('hidden');
            pwdHelp.innerText = 'La contraseña se genera automáticamente. El atleta podrá cambiarla al iniciar sesión.';
            passwordField.readOnly = true;
            generatePassword();
        } else {
            generateBtn.classList.add('hidden');
            strengthMeter.classList.remove('hidden');
            criteria.classList.remove('hidden');
            pwdHelp.innerText = 'Ingresa una contraseña que cumpla con los criterios de seguridad.';
            passwordField.readOnly = false;
            passwordField.value = '';
            passwordField.focus();
        }
        lucide.createIcons();
    }

    function checkPasswordStrength() {
        const password = document.getElementById('passwordField').value;
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');

        // Check criteria
        const criteria = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[!@#$%^&*]/.test(password)
        };

        // Update criteria indicators
        Object.keys(criteria).forEach(key => {
            const el = document.getElementById('criteria-' + key);
            if (criteria[key]) {
                el.classList.remove('text-slate-500');
                el.classList.add('text-green-600');
                el.querySelector('i').setAttribute('data-lucide', 'check-circle');
            } else {
                el.classList.add('text-slate-500');
                el.classList.remove('text-green-600');
                el.querySelector('i').setAttribute('data-lucide', 'circle');
            }
        });
        lucide.createIcons();

        // Calculate strength
        const passedCriteria = Object.values(criteria).filter(Boolean).length;
        const percentage = (passedCriteria / 5) * 100;

        strengthBar.style.width = percentage + '%';

        if (passedCriteria <= 2) {
            strengthBar.className = 'h-full transition-all duration-300 bg-red-500';
            strengthText.innerText = 'Débil';
            strengthText.className = 'text-xs font-semibold text-red-500';
        } else if (passedCriteria <= 4) {
            strengthBar.className = 'h-full transition-all duration-300 bg-amber-500';
            strengthText.innerText = 'Media';
            strengthText.className = 'text-xs font-semibold text-amber-500';
        } else {
            strengthBar.className = 'h-full transition-all duration-300 bg-green-500';
            strengthText.innerText = 'Fuerte';
            strengthText.className = 'text-xs font-semibold text-green-500';
        }
    }

    function togglePassword() {
        const field = document.getElementById('passwordField');
        field.type = field.type === 'password' ? 'text' : 'password';
    }

    function filterAthletes() {
        const input = document.getElementById('searchAtleta');
        const filter = input.value.toLowerCase();
        const table = document.getElementById('athleteTable');
        const tr = table.getElementsByTagName('tr');

        for (let i = 1; i < tr.length; i++) {
            const tdName = tr[i].getElementsByTagName('td')[0];
            const tdEmail = tr[i].getElementsByTagName('td')[1];
            if (tdName || tdEmail) {
                const txtValue = (tdName.textContent || tdName.innerText) + ' ' + (tdEmail.textContent || tdEmail.innerText);
                if (txtValue.toLowerCase().indexOf(filter) > -1) {
                    tr[i].style.display = '';
                } else {
                    tr[i].style.display = 'none';
                }
            }
        }
    }
</script>

<?php include 'views/layout/footer.php'; ?>
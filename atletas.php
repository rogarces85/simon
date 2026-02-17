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
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 style="font-size: 2rem; font-weight: 800; color: var(--text-main); margin: 0;">GESTIÓN DE EQUIPO</h1>
        <p style="color: var(--text-muted); margin-top: 0.25rem;">Administra tus atletas y monitorea su rendimiento</p>
    </div>
    <button onclick="openCreateModal()" class="btn btn-primary" style="gap: 0.5rem; padding: 0.75rem 1.5rem;">
        <i data-lucide="user-plus" style="width: 18px; height: 18px;"></i>
        Nuevo Atleta
    </button>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="card"
        style="border-color: var(--primary); background: rgba(13, 242, 128, 0.05); margin-bottom: 2rem; display: flex; align-items: center; gap: 0.75rem;">
        <i data-lucide="check-circle" style="color: var(--primary);"></i>
        <span>Atleta registrado con éxito.</span>
    </div>
<?php endif; ?>

<?php if (isset($_GET['updated'])): ?>
    <div class="card"
        style="border-color: #3b82f6; background: rgba(59, 130, 246, 0.05); margin-bottom: 2rem; display: flex; align-items: center; gap: 0.75rem;">
        <i data-lucide="info" style="color: #3b82f6;"></i>
        <span>Información actualizada correctamente.</span>
    </div>
<?php endif; ?>

<!-- Athletes Table Card -->
<div class="card" style="padding: 0; overflow: hidden;">
    <div
        style="padding: 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
        <h2 style="font-size: 1.125rem; font-weight: 700; margin: 0;">Listado de Atletas</h2>
        <div style="position: relative; width: 280px;">
            <i data-lucide="search"
                style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: var(--text-muted);"></i>
            <input type="text" id="searchAtleta" onkeyup="filterAthletes()" placeholder="Buscar por nombre o email..."
                style="width: 100%; padding: 0.6rem 1rem 0.6rem 2.5rem; background: var(--bg-main); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 0.875rem; outline: none;">
        </div>
    </div>

    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;" id="athleteTable">
            <thead
                style="background: var(--bg-main); color: var(--text-muted); font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;">
                <tr>
                    <th style="padding: 1rem 1.5rem;">ATLETA</th>
                    <th style="padding: 1rem 1.5rem;">ESTADO / NIVEL</th>
                    <th style="padding: 1rem 1.5rem;">RITMO OBJETIVO</th>
                    <th style="padding: 1rem 1.5rem; text-align: right;">ACCIONES</th>
                </tr>
            </thead>
            <tbody style="color: var(--text-main); font-size: 0.9rem;">
                <?php if (empty($athletes)): ?>
                    <tr>
                        <td colspan="4" style="padding: 3rem; text-align: center; color: var(--text-muted);">
                            No hay atletas registrados aún. <span onclick="openCreateModal()"
                                style="color: var(--primary); cursor: pointer; font-weight: 700;">Empieza aquí</span>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($athletes as $athlete): ?>
                        <tr style="border-bottom: 1px solid var(--border);" class="athlete-row">
                            <td style="padding: 1.25rem 1.5rem;">
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <div
                                        style="width: 40px; height: 40px; border-radius: 50%; background: var(--bg-main); display: flex; align-items: center; justify-content: center; border: 1px solid var(--border);">
                                        <i data-lucide="user" style="width: 18px; height: 18px; color: var(--text-muted);"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 700;"><?php echo htmlspecialchars($athlete['name']); ?></div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);">
                                            <?php echo htmlspecialchars($athlete['username']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding: 1.25rem 1.5rem;">
                                        <?php
                                        $level = $athlete['level'] ?? 'Principiante';
                                        $badgeStyle = $level === 'Avanzado' ? 'badge-emerald' : ($level === 'Intermedio' ? 'background: #fef3c7; color: #92400e;' : 'background: #f3f4f6; color: #374151;');
                                        ?>
                                        <span class="badge"
                                            style="<?php echo $badgeStyle; ?> font-size: 0.7rem;"><?php echo htmlspecialchars(strtoupper($level)); ?></span>
                                    </td>
                                    <td style="padding: 1.25rem 1.5rem; font-weight: 700;">
                                        <?php echo htmlspecialchars($athlete['goal_pace'] ?? '-'); ?> <span
                                            style="font-size: 0.75rem; font-weight: 500; color: var(--text-muted);">min/km</span>
                                    </td>
                                    <td style="padding: 1.25rem 1.5rem; text-align: right;">
                                        <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                            <button onclick='openEditModal(<?php echo json_encode($athlete); ?>)'
                                                class="btn btn-secondary" style="padding: 0.5rem; border-radius: 6px;" title="Editar">
                                                <i data-lucide="edit-3" style="width: 16px; height: 16px;"></i>
                                            </button>
                                            <form method="POST" onsubmit="return confirm('¿Eliminar atleta permanentemente?')"
                                                style="display: inline;">
                                                <input type="hidden" name="action" value="delete_athlete">
                                                <input type="hidden" name="athlete_id" value="<?php echo $athlete['id']; ?>">
                                                <button type="submit" class="btn btn-secondary"
                                                    style="padding: 0.5rem; border-radius: 6px; color: #ef4444;" title="Eliminar">
                                                    <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                        <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Atleta (Create/Edit) -->
<div id="modalAtleta" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 100; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="width: 100%; max-width: 600px; margin: 1rem; max-h-[90vh] overflow-y-auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <div>
                <h3 id="modalTitle" style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin: 0;">NUEVO ATLETA</h3>
                <p id="modalSubtitle" style="color: var(--text-muted); font-size: 0.875rem; margin-top: 0.25rem;">Completa el perfil del corredor</p>
            </div>
            <button onclick="closeModal()" style="background: none; border: none; color: var(--text-muted); cursor: pointer;"><i data-lucide="x"></i></button>
        </div>

        <form method="POST" id="athleteForm" style="display: flex; flex-direction: column; gap: 1.5rem;">
            <input type="hidden" name="action" id="formAction" value="create_athlete">
            <input type="hidden" name="athlete_id" id="athleteId">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 800; color: var(--text-muted); margin-bottom: 0.5rem; text-transform: uppercase;">Nombre Completo</label>
                    <input type="text" name="name" id="fieldName" required style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-main); color: var(--text-main); font-family: inherit;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 800; color: var(--text-muted); margin-bottom: 0.5rem; text-transform: uppercase;">Email</label>
                    <input type="email" name="email" id="fieldEmail" required style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-main); color: var(--text-main); font-family: inherit;">
                </div>
            </div>

            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 800; color: var(--text-muted); margin-bottom: 0.5rem; text-transform: uppercase;">Contraseña <span id="pwdLabelExtra"></span></label>
                <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <input type="password" name="password" id="passwordField" style="flex: 1; padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-main); color: var(--text-main); font-family: inherit;">
                    <button type="button" onclick="generatePassword()" class="btn btn-secondary" style="padding: 0.5rem;"><i data-lucide="refresh-cw" style="width: 18px; height: 18px;"></i></button>
                </div>
                <p id="pwdHelp" style="font-size: 0.7rem; color: var(--text-muted); margin: 0;"></p>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 800; color: var(--text-muted); margin-bottom: 0.5rem; text-transform: uppercase;">Nivel</label>
                    <select name="level" id="fieldLevel" style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-main); color: var(--text-main); font-family: inherit;">
                        <option value="Principiante">Principiante</option>
                        <option value="Intermedio">Intermedio</option>
                        <option value="Avanzado">Avanzado</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 800; color: var(--text-muted); margin-bottom: 0.5rem; text-transform: uppercase;">Ritmo Obj (min/km)</label>
                    <input type="text" name="goal_pace" id="fieldGoalPace" placeholder="5:30" style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-main); color: var(--text-main); font-family: inherit;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 800; color: var(--text-muted); margin-bottom: 0.5rem; text-transform: uppercase;">Fecha Objetivo</label>
                    <input type="date" name="goal_date" id="fieldGoalDate" style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-main); color: var(--text-main); font-family: inherit;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 800; color: var(--text-muted); margin-bottom: 0.5rem; text-transform: uppercase;">Día de Fondo</label>
                    <select name="preferred_long_run_day" id="fieldLongRunDay" style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-main); color: var(--text-main); font-family: inherit;">
                        <option value="Domingo">Domingo</option>
                        <option value="Sábado">Sábado</option>
                        <option value="Viernes">Viernes</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="padding: 1rem; margin-top: 1rem;">Guardar Atleta</button>
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
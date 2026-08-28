<?php
require_once 'includes/auth.php';
require_once 'includes/Csrf.php';
require_once 'models/User.php';

Auth::init();
Auth::requireRole('admin');

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        $error = 'Sesión expirada, intenta nuevamente.';
    } else {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($name && $email && $password) {
            // Validaciones de contraseña
            if (strlen($password) < 8) {
                $error = "La contraseña debe tener al menos 8 caracteres.";
            } elseif (!preg_match('/[A-Z]/', $password)) {
                $error = "La contraseña debe incluir al menos una letra mayúscula.";
            } elseif (!preg_match('/[a-z]/', $password)) {
                $error = "La contraseña debe incluir al menos una letra minúscula.";
            } elseif (!preg_match('/[0-9]/', $password)) {
                $error = "La contraseña debe incluir al menos un número.";
            } elseif ($password !== $confirmPassword) {
                $error = "Las contraseñas no coinciden.";
            } else {
                try {
                    $db = Database::getInstance();
                    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        $error = "El correo electrónico ya está registrado.";
                    } else {
                        User::create([
                            'username' => $email,
                            'password' => $password,
                            'role' => 'coach',
                            'name' => $name
                        ]);
                        $success = "Entrenador creado con éxito.";
                    }
                } catch (Exception $e) {
                    $error = "Error al crear entrenador. Intenta nuevamente.";
                }
            }
        } else {
            $error = "Todos los campos son obligatorios.";
        }
    }
}

$pageTitle = 'Nuevo Entrenador';
include 'views/layout/header.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="mb-8 flex items-center gap-4">
        <a href="admin_dashboard.php" class="btn-secondary btn-sm p-2 rounded-xl hover:bg-slate-100 text-slate-500 transition-colors" aria-label="Volver a administración">
            <i data-lucide="arrow-left" class="w-6 h-6"></i>
        </a>
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Nuevo Entrenador</h1>
            <p class="text-slate-500 mt-1">Registra un nuevo entrenador en la plataforma</p>
        </div>
    </div>

    <?php echo flash_render(); ?>

    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl mb-6 flex items-center gap-3" role="alert">
            <i data-lucide="alert-circle" class="w-5 h-5"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 p-4 rounded-xl mb-6 flex items-center gap-3" role="alert">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
        <form method="POST" class="space-y-6">
            <?php echo Csrf::field(); ?>
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 mb-2">Nombre Completo</label>
                <input type="text" id="name" name="name" required
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-all font-inter"
                    placeholder="Ej. Juan Pérez">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-2">Correo Electrónico</label>
                <input type="email" id="email" name="email" required
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-all font-inter"
                    placeholder="entrenador@ejemplo.com">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 mb-2">Contraseña</label>
                <input type="password" id="password" name="password" required minlength="8"
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-all font-inter"
                    placeholder="Mínimo 8 caracteres">
                <p class="text-xs text-slate-500 mt-1">Debe incluir: mayúscula, minúscula y número.</p>
            </div>

            <div>
                <label for="confirm_password" class="block text-sm font-medium text-slate-700 mb-2">Confirmar Contraseña</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="8"
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-all font-inter"
                    placeholder="Repite la contraseña">
            </div>

            <div class="pt-4">
                <button type="submit"
                    class="btn-primary w-full justify-center">
                    <i data-lucide="save" class="w-5 h-5"></i>
                    Crear Entrenador
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'views/layout/footer.php'; ?>

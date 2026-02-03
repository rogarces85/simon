<?php
require_once 'includes/auth.php';
require_once 'models/User.php';

Auth::init();
Auth::requireRole('admin');

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($name && $email && $password) {
        try {
            // Check if user exists
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "El correo electrónico ya está registrado.";
            } else {
                User::create([
                    'username' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => 'coach',
                    'name' => $name
                ]);
                $success = "Entrenador creado con éxito.";
            }
        } catch (Exception $e) {
            $error = "Error al crear entrenador: " . $e->getMessage();
        }
    } else {
        $error = "Todos los campos son obligatorios.";
    }
}

include 'views/layout/header.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="mb-8 flex items-center gap-4">
        <a href="admin_dashboard.php" class="p-2 rounded-xl hover:bg-slate-100 text-slate-500 transition-colors">
            <i data-lucide="arrow-left" class="w-6 h-6"></i>
        </a>
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Nuevo Entrenador</h1>
            <p class="text-slate-500 mt-1">Registra un nuevo entrenador en la plataforma</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-50 text-red-600 p-4 rounded-xl mb-6 flex items-center gap-3">
            <i data-lucide="alert-circle" class="w-5 h-5"></i>
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-50 text-green-600 p-4 rounded-xl mb-6 flex items-center gap-3">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Nombre Completo</label>
                <input type="text" name="name" required
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-all font-inter"
                    placeholder="Ej. Juan Pérez">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Correo Electrónico</label>
                <input type="email" name="email" required
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-all font-inter"
                    placeholder="entrenador@ejemplo.com">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Contraseña</label>
                <input type="password" name="password" required
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-all font-inter"
                    placeholder="••••••••">
            </div>

            <div class="pt-4">
                <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-xl transition-colors flex items-center justify-center gap-2">
                    <i data-lucide="save" class="w-5 h-5"></i>
                    Crear Entrenador
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'views/layout/footer.php'; ?>
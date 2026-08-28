<?php
require_once 'includes/auth.php';
require_once 'includes/Csrf.php';
require_once 'includes/FileUpload.php';
require_once 'models/User.php';
require_once 'models/Team.php';

Auth::init();
if (!Auth::check()) {
    header('Location: login.php');
    exit;
}

$userSession = Auth::user();
$db = Database::getInstance();
$user = User::getById($userSession['id']);

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !Csrf::verify($_POST['csrf_token'] ?? '')) {
    $error = 'Sesión expirada, intenta nuevamente.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // Avatar Upload (sin auto-submit)
    $avatarUrl = $user['avatar_url'];
    if (isset($_FILES['avatar']) && FileUpload::validate($_FILES['avatar'], ['jpg', 'jpeg', 'png', 'webp'])) {
        $uploadDir = __DIR__ . '/uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        // Eliminar avatar anterior si existe
        if (!empty($user['avatar_url']) && file_exists(__DIR__ . '/' . $user['avatar_url'])) {
            @unlink(__DIR__ . '/' . $user['avatar_url']);
        }
        $fileExt = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $fileName = 'avatar_' . $user['id'] . '_' . time() . '.' . $fileExt;
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadDir . $fileName)) {
            $avatarUrl = 'uploads/avatars/' . $fileName;
        }
    }

    // Validation
    if (!empty($password) && $password !== $confirmPassword) {
        $error = "Las contraseñas no coinciden.";
    } else {
        // Update user
        $sql = "UPDATE users SET name = ?, email = ?, avatar_url = ? WHERE id = ?";
        $params = [$name, $email, $avatarUrl, $user['id']];

        if (!empty($password)) {
            $sql = "UPDATE users SET name = ?, email = ?, avatar_url = ?, password = ? WHERE id = ?";
            $params = [$name, $email, $avatarUrl, password_hash($password, PASSWORD_DEFAULT), $user['id']];
        }

        $stmt = $db->prepare($sql);
        if ($stmt->execute($params)) {
            $success = "Perfil actualizado correctamente.";
            Auth::refreshSession();
            $user['name'] = $name;
            $user['email'] = $email;
            $user['avatar_url'] = $avatarUrl;
        } else {
            $error = "Error al actualizar el perfil.";
        }
    }
}

$pageTitle = 'Mi Perfil';
include 'views/layout/header.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Mi Perfil</h1>
        <p class="text-slate-500 mt-1">Gestiona tu información personal</p>
    </div>

    <?php echo flash_render(); ?>

    <?php if ($success): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 p-4 rounded-xl mb-6 flex items-center gap-2">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl mb-6 flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-5 h-5"></i>
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <!-- Cover/Header -->
        <div class="h-32 bg-gradient-to-r from-blue-600 to-indigo-700"></div>

        <div class="px-8 pb-8">
            <div class="relative flex justify-between items-end -mt-12 mb-6">
                <div class="relative">
                    <img id="avatarMain" src="<?php echo !empty($user['avatar_url']) ? htmlspecialchars($user['avatar_url']) : ''; ?>"
                        class="w-24 h-24 rounded-full border-4 border-white shadow-md <?php echo empty($user['avatar_url']) ? 'hidden' : ''; ?> object-cover"
                        alt="Avatar actual">
                    <?php if (empty($user['avatar_url'])): ?>
                        <div id="avatarPlaceholder" class="w-24 h-24 rounded-full border-4 border-white shadow-md bg-slate-200 flex items-center justify-center text-slate-400">
                            <i data-lucide="user" class="w-10 h-10"></i>
                        </div>
                    <?php endif; ?>
                    <img id="avatarPreview" class="hidden w-24 h-24 rounded-full border-4 border-white shadow-md object-cover" alt="Vista previa del nuevo avatar">
                    <label class="absolute bottom-0 right-0 bg-white p-1.5 rounded-full shadow-sm border border-slate-200 cursor-pointer hover:bg-slate-50 text-slate-600" aria-label="Cambiar foto de perfil">
                        <i data-lucide="camera" class="w-4 h-4"></i>
                        <input type="file" form="profileForm" name="avatar" accept="image/*" class="hidden" id="avatarInput" onchange="previewAvatar(this)">
                    </label>
                </div>
                <div class="mb-2">
                    <span class="px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-bold uppercase tracking-wider">
                        <?php echo htmlspecialchars($user['role']); ?>
                    </span>
                </div>
            </div>

            <form id="profileForm" method="POST" enctype="multipart/form-data" class="space-y-6">
                <?php echo Csrf::field(); ?>
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-semibold text-slate-700 mb-2">Nombre Completo</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">Correo Electrónico</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>

                    <div class="border-t border-slate-100 pt-6">
                        <h4 class="text-sm font-bold text-slate-900 mb-4">Cambiar Contraseña</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="password" class="block text-xs font-semibold text-slate-500 mb-2">Nueva Contraseña</label>
                                <input type="password" id="password" name="password" placeholder="Mín. 8 caracteres, mayúscula, minúscula, número"
                                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none placeholder:text-slate-400">
                            </div>
                            <div>
                                <label for="confirm_password" class="block text-xs font-semibold text-slate-500 mb-2">Confirmar Contraseña</label>
                                <input type="password" id="confirm_password" name="confirm_password" placeholder="Repite la contraseña"
                                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none placeholder:text-slate-400">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-4">
                    <button type="submit"
                        class="btn-primary">
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var preview = document.getElementById('avatarPreview');
            var main = document.getElementById('avatarMain');
            var placeholder = document.getElementById('avatarPlaceholder');
            preview.src = e.target.result;
            preview.classList.remove('hidden');
            if (main) main.classList.add('hidden');
            if (placeholder) placeholder.classList.add('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include 'views/layout/footer.php'; ?>

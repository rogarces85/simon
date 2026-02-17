<?php
require_once 'includes/auth.php';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // Avatar Upload
    $avatarUrl = $user['avatar_url'];
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileExt = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($fileExt, $allowed)) {
            $fileName = 'avatar_' . $user['id'] . '_' . time() . '.' . $fileExt;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadDir . $fileName)) {
                $avatarUrl = 'uploads/avatars/' . $fileName;
            }
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
            // Refresh session user data
            $_SESSION['user_name'] = $name; // Or however Auth stores it. Auth::user() usually fetches from session or DB.
            // If Auth stores in session, update it.
            // Assuming Auth methods might need refresh or re-login. 
            // For now let's just reload page.
            $user['name'] = $name;
            $user['email'] = $email;
            $user['avatar_url'] = $avatarUrl;
        } else {
            $error = "Error al actualizar el perfil.";
        }
    }
}

include 'views/layout/header.php';
?>

<div style="max-width: 800px; margin: 0 auto;">
    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 2rem; font-weight: 800; color: var(--text-main); margin: 0;">MI PERFIL</h1>
        <p style="color: var(--text-muted); margin-top: 0.25rem;">Gestiona tu información personal y seguridad</p>
    </div>

    <?php if ($success): ?>
        <div class="card"
            style="border-color: var(--primary); background: rgba(13, 242, 128, 0.05); margin-bottom: 2rem; display: flex; align-items: center; gap: 0.75rem;">
            <i data-lucide="check-circle" style="color: var(--primary);"></i>
            <span><?php echo $success; ?></span>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="card"
            style="border-color: #ef4444; background: rgba(239, 68, 68, 0.05); margin-bottom: 2rem; display: flex; align-items: center; gap: 0.75rem;">
            <i data-lucide="alert-circle" style="color: #ef4444;"></i>
            <span><?php echo $error; ?></span>
        </div>
    <?php endif; ?>

    <div class="card" style="padding: 0; overflow: hidden;">
        <!-- Perfil Header / Gradient -->
        <div
            style="height: 120px; background: linear-gradient(135deg, var(--primary) 0%, #0bc568 100%); position: relative;">
        </div>

        <div style="padding: 0 2rem 2rem 2rem;">
            <div
                style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: -3rem; margin-bottom: 2rem;">
                <div style="position: relative;">
                    <?php if (!empty($user['avatar_url'])): ?>
                        <img src="<?php echo htmlspecialchars($user['avatar_url']); ?>"
                            style="width: 100px; height: 100px; rounded-circle: 100%; border: 4px solid var(--bg-card); background: var(--bg-card); object-cover: cover; border-radius: 50%; box-shadow: var(--shadow);">
                    <?php else: ?>
                        <div
                            style="width: 100px; height: 100px; border-radius: 50%; border: 4px solid var(--bg-card); background: var(--bg-main); display: flex; align-items: center; justify-content: center; color: var(--text-muted); box-shadow: var(--shadow);">
                            <i data-lucide="user" style="width: 40px; height: 40px;"></i>
                        </div>
                    <?php endif; ?>
                    <label
                        style="position: absolute; bottom: 0; right: 0; background: var(--bg-card); padding: 0.5rem; border-radius: 50%; box-shadow: var(--shadow); cursor: pointer; border: 1px solid var(--border);">
                        <i data-lucide="camera" style="width: 16px; height: 16px; color: var(--text-main);"></i>
                        <input type="file" form="profileForm" name="avatar" accept="image/*" style="display: none;"
                            onchange="document.getElementById('profileForm').submit()">
                    </label>
                </div>
                <div style="margin-bottom: 0.5rem;">
                    <span class="badge badge-emerald" style="font-size: 0.75rem;">
                        <?php echo htmlspecialchars(strtoupper($user['role'])); ?>
                    </span>
                </div>
            </div>

            <form id="profileForm" method="POST" enctype="multipart/form-data"
                style="display: flex; flex-direction: column; gap: 1.5rem;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div>
                        <label
                            style="display: block; font-size: 0.75rem; font-weight: 800; color: var(--text-muted); margin-bottom: 0.5rem; text-transform: uppercase;">Nombre
                            Completo</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required
                            style="width: 100%; padding: 0.85rem; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-main); color: var(--text-main); font-family: inherit;">
                    </div>
                    <div>
                        <label
                            style="display: block; font-size: 0.75rem; font-weight: 800; color: var(--text-muted); margin-bottom: 0.5rem; text-transform: uppercase;">Correo
                            Electrónico</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                            style="width: 100%; padding: 0.85rem; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-main); color: var(--text-main); font-family: inherit;">
                    </div>
                </div>

                <div style="border-top: 1px solid var(--border); pt: 1.5rem; margin-top: 0.5rem;">
                    <h4
                        style="font-size: 0.9rem; font-weight: 800; color: var(--text-main); margin-bottom: 1rem; margin-top: 1.5rem; text-transform: uppercase;">
                        Seguridad</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div>
                            <label
                                style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem;">Nueva
                                Contraseña</label>
                            <input type="password" name="password" placeholder="Dejar en blanco para mantener"
                                style="width: 100%; padding: 0.85rem; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-main); color: var(--text-main); font-family: inherit;">
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem;">Confirmar
                                Contraseña</label>
                            <input type="password" name="confirm_password" placeholder="Confirmar nueva contraseña"
                                style="width: 100%; padding: 0.85rem; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-main); color: var(--text-main); font-family: inherit;">
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; pt: 1rem;">
                    <button type="submit" class="btn btn-primary" style="padding: 0.85rem 2rem;">
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'views/layout/footer.php'; ?>
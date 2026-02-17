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

<div style="max-width: 600px; margin: 0 auto;">
    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
        <a href="admin_dashboard.php"
            style="display: flex; align-items: center; justify-content: center; width: 44px; height: 44px; border-radius: 12px; border: 1px solid var(--border); color: var(--text-muted); background: var(--bg-card); transition: all 0.2s ease;"
            onmouseover="this.style.color='var(--primary)'; this.style.borderColor='var(--primary)';"
            onmouseout="this.style.color='var(--text-muted)'; this.style.borderColor='var(--border)';"
            title="Volver al Dashboard">
            <i data-lucide="arrow-left" style="width: 20px; height: 20px;"></i>
        </a>
        <div>
            <h1 style="font-size: 1.75rem; font-weight: 800; color: var(--text-main); margin: 0;">NUEVO ENTRENADOR</h1>
            <p style="color: var(--text-muted); margin-top: 0.15rem;">Registra un nuevo usuario con rol de Coach</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="card"
            style="border-color: #ef4444; background: rgba(239, 68, 68, 0.05); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
            <i data-lucide="alert-circle" style="color: #ef4444;"></i>
            <span><?php echo $error; ?></span>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="card"
            style="border-color: var(--primary); background: rgba(13, 242, 128, 0.05); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
            <i data-lucide="check-circle" style="color: var(--primary);"></i>
            <span><?php echo $success; ?></span>
        </div>
    <?php endif; ?>

    <div class="card">
        <form method="POST" style="display: flex; flex-direction: column; gap: 1.5rem;">
            <div>
                <label
                    style="display: block; font-size: 0.75rem; font-weight: 800; color: var(--text-muted); margin-bottom: 0.5rem; text-transform: uppercase;">Nombre
                    Completo</label>
                <input type="text" name="name" required
                    style="width: 100%; padding: 0.85rem; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-main); color: var(--text-main); font-family: inherit;"
                    placeholder="Ej. Juan Pérez">
            </div>

            <div>
                <label
                    style="display: block; font-size: 0.75rem; font-weight: 800; color: var(--text-muted); margin-bottom: 0.5rem; text-transform: uppercase;">Correo
                    Electrónico (Usuario)</label>
                <input type="email" name="email" required
                    style="width: 100%; padding: 0.85rem; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-main); color: var(--text-main); font-family: inherit;"
                    placeholder="coach@ejemplo.com">
            </div>

            <div>
                <label
                    style="display: block; font-size: 0.75rem; font-weight: 800; color: var(--text-muted); margin-bottom: 0.5rem; text-transform: uppercase;">Contraseña
                    Inicial</label>
                <input type="password" name="password" required
                    style="width: 100%; padding: 0.85rem; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-main); color: var(--text-main); font-family: inherit;"
                    placeholder="••••••••">
                <p style="font-size: 0.7rem; color: var(--text-muted); mt: 0.5rem;">El entrenador podrá cambiar su
                    contraseña desde su perfil.</p>
            </div>

            <div style="margin-top: 1rem;">
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; gap: 0.75rem;">
                    <i data-lucide="user-plus" style="width: 18px; height: 18px;"></i>
                    Registrar Entrenador
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'views/layout/footer.php'; ?>
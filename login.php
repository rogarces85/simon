<?php
require_once 'includes/auth.php';
require_once 'models/User.php';
require_once 'config/config.php';
Auth::init();

if (Auth::check()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if (Auth::login($username, $password)) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Credenciales inválidas';
    }
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIMON</title>
    <link rel="stylesheet" href="assets/css/theme.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        const currentTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', currentTheme);
    </script>
</head>

<body
    style="display: flex; align-items: center; justify-content: center; min-height: 100vh; background-color: var(--bg-main); padding: 1rem;">

    <div class="card" style="width: 100%; max-width: 440px; padding: 3rem; border-radius: 2rem;">
        <div style="text-align: center; margin-bottom: 2.5rem;">
            <div
                style="display: inline-flex; align-items: center; justify-content: center; w: 64px; height: 64px; background: var(--primary); border-radius: 1rem; margin-bottom: 1.5rem; color: #0f172a;">
                <i data-lucide="zap" style="width: 32px; height: 32px;"></i>
            </div>
            <h1 style="font-size: 2.5rem; font-weight: 800; color: var(--text-main); margin: 0; tracking: -0.05em;">
                SIMON</h1>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.5rem;">Bienvenido de nuevo a tu
                plataforma</p>
        </div>

        <?php if ($error): ?>
            <div
                style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 1rem; border-radius: 12px; font-size: 0.875rem; font-weight: 600; margin-bottom: 1.5rem; text-align: center;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" style="display: flex; flex-direction: column; gap: 1.25rem;">
            <div>
                <label
                    style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem; text-transform: uppercase;">Usuario
                    o Email</label>
                <input type="text" name="username" placeholder="tu@ejemplo.com" required
                    style="width: 100%; padding: 1rem; border-radius: 12px; border: 1px solid var(--border); background: var(--bg-main); color: var(--text-main); font-family: inherit; font-size: 1rem; outline: none;">
            </div>
            <div>
                <label
                    style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem; text-transform: uppercase;">Contraseña</label>
                <input type="password" name="password" placeholder="••••••••" required
                    style="width: 100%; padding: 1rem; border-radius: 12px; border: 1px solid var(--border); background: var(--bg-main); color: var(--text-main); font-family: inherit; font-size: 1rem; outline: none;">
            </div>
            <button type="submit" class="btn btn-primary"
                style="padding: 1rem; font-size: 1.1rem; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(13, 242, 128, 0.2); margin-top: 0.5rem;">
                Iniciar Sesión
            </button>
        </form>

        <div style="text-align: center; margin-top: 2rem;">
            <p style="font-size: 0.9rem; color: var(--text-muted);">¿Olvidaste tu contraseña? <a href="#"
                    style="color: var(--primary); font-weight: 700; text-decoration: none;">Recupérala aquí</a></p>
            <hr style="border: none; border-top: 1px solid var(--border); margin: 1.5rem 0;">
            <a href="index.php"
                style="font-size: 0.9rem; color: var(--text-muted); text-decoration: none; font-weight: 600;">← Volver
                al inicio</a>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>
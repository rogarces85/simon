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
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RUNCOACH</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .login-card {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body class="bg-slate-50 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white p-10 rounded-[2rem] login-card w-full max-w-[440px]">
        <div class="text-center mb-10">
            <div
                class="inline-flex items-center justify-center w-16 h-16 bg-blue-600 rounded-2xl mb-6 shadow-lg shadow-blue-200">
                <i data-lucide="dumbbell" class="w-8 h-8 text-white rotate-[-45deg]"></i>
            </div>
            <h1 class="text-4xl font-bold text-slate-900 tracking-tight">RUNCOACH</h1>
            <p class="text-slate-500 mt-2 font-medium">Ingresa a tu plataforma de entrenamiento</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg text-sm">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Usuario</label>
                <input type="text" name="username" placeholder="usuario@ejemplo.com" required
                    class="w-full px-4 py-3.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-900 placeholder:text-slate-400">
            </div>
            <div>
                <div class="flex justify-between mb-2">
                    <label class="text-sm font-semibold text-slate-700">Contraseña</label>
                    <a href="#" class="text-sm font-medium text-blue-600 hover:text-blue-700">¿Olvidaste tu
                        contraseña?</a>
                </div>
                <input type="password" name="password" placeholder="••••••••" required
                    class="w-full px-4 py-3.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-900 placeholder:text-slate-400">
            </div>
            <button type="submit"
                class="w-full bg-blue-600 text-white py-4 px-4 rounded-xl font-bold text-lg hover:bg-blue-700 transition-all shadow-lg shadow-blue-100 active:scale-[0.98]">
                Iniciar Sesión
            </button>
        </form>

        <div class="text-center mt-10">
            <p class="text-slate-500 font-medium">
                ¿No tienes cuenta?
                <a href="#" class="text-blue-600 hover:text-blue-700 font-bold ml-1">Regístrate</a>
            </p>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>
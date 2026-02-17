<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/config.php';
Auth::init();

if (!Auth::check()) {
    header('Location: login.php');
    exit;
}

$currentUser = Auth::user();
require_once __DIR__ . '/../../models/User.php';
$dbUser = User::getById($currentUser['id']);
if ($dbUser) {
    $currentUser = array_merge($currentUser, $dbUser);
}

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/theme.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        // Init theme before render to avoid flash
        const currentTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', currentTheme);
    </script>
</head>

<body>
    <div class="flex">
        <!-- Sidebar -->
        <!-- Sidebar -->
        <aside class="sidebar">
            <!-- Profile Section (Top) -->
            <div style="padding: 1.5rem; display: flex; align-items: center; gap: 0.75rem; border-bottom: 1px solid var(--border);">
                 <?php if (!empty($currentUser['avatar_url'])): ?>
                    <img src="<?php echo htmlspecialchars($currentUser['avatar_url']); ?>" style="width: 48px; height: 48px; border-radius: 50%; border: 2px solid var(--border); object-fit: cover;">
                <?php else: ?>
                    <div style="width: 48px; height: 48px; background: var(--bg-card); border: 2px solid var(--border); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--text-muted);">
                        <i data-lucide="user"></i>
                    </div>
                <?php endif; ?>
                <div style="display: flex; flex-direction: column;">
                    <h1 style="font-size: 1rem; font-weight: 700; color: var(--text-main); line-height: 1.2;"><?php echo htmlspecialchars($currentUser['name']); ?></h1>
                    <p style="font-size: 0.75rem; color: var(--text-muted); font-weight: 500;"><?php echo ucfirst($currentUser['role']); ?></p>
                </div>
            </div>

            <!-- Navigation -->
            <nav style="flex: 1; padding: 1rem; overflow-y: auto; display: flex; flex-direction: column; gap: 0.5rem;">
                <?php if ($currentUser['role'] === 'admin'): ?>
                    <a href="admin_dashboard.php"
                        class="nav-link <?php echo $currentPage === 'admin_dashboard' ? 'active' : ''; ?>">
                        <i data-lucide="layout-dashboard"></i> Panel Admin
                    </a>
                <?php elseif ($currentUser['role'] === 'coach'): ?>
                    <a href="dashboard.php" class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                        <i data-lucide="layout-dashboard"></i> Dashboard
                    </a>
                    <a href="atletas.php" class="nav-link <?php echo $currentPage === 'atletas' ? 'active' : ''; ?>">
                        <i data-lucide="users"></i> Atletas
                    </a>
                    <a href="generar_plan.php"
                        class="nav-link <?php echo $currentPage === 'generar_plan' ? 'active' : ''; ?>">
                        <i data-lucide="calendar-plus"></i> Generar Plan
                    </a>
                    <a href="mis_planes.php" class="nav-link <?php echo $currentPage === 'mis_planes' ? 'active' : ''; ?>">
                        <i data-lucide="folder-open"></i> Planes
                    </a>
                    <a href="entrenamientos.php"
                        class="nav-link <?php echo $currentPage === 'entrenamientos.php' ? 'active' : ''; ?>">
                        <i data-lucide="clipboard-list"></i> Reportes
                    </a>
                    <a href="metricas.php" class="nav-link <?php echo $currentPage === 'metricas' ? 'active' : ''; ?>">
                        <i data-lucide="bar-chart-big"></i> Métricas
                    </a>
                    <a href="config_team.php"
                        class="nav-link <?php echo $currentPage === 'config_team' ? 'active' : ''; ?>">
                        <i data-lucide="settings"></i> Team
                    </a>
                <?php else: ?>
                    <a href="mi_plan.php" class="nav-link <?php echo $currentPage === 'mi_plan' ? 'active' : ''; ?>">
                        <i data-lucide="calendar-range"></i> Mi Plan
                    </a>
                    <a href="mi_progreso.php"
                        class="nav-link <?php echo $currentPage === 'mi_progreso' ? 'active' : ''; ?>">
                        <i data-lucide="activity"></i> Mi Progreso
                    </a>
                <?php endif; ?>
            </nav>

            <!-- Bottom Actions -->
            <div style="padding: 1.5rem; border-top: 1px solid var(--border);">
                 <a href="<?php echo $currentUser['role'] === 'coach' ? 'generar_plan.php' : 'perfil.php'; ?>" class="btn btn-secondary" style="width: 100%; justify-content: start; gap: 0.5rem; color: var(--text-main); border: 1px solid var(--border); background: var(--bg-card); margin-bottom: 1rem; padding: 0.75rem;">
                    <i data-lucide="<?php echo $currentUser['role'] === 'coach' ? 'plus-circle' : 'user-cog'; ?>" style="color: var(--primary);"></i>
                    <span><?php echo $currentUser['role'] === 'coach' ? 'Nuevo Plan' : 'Mi Perfil'; ?></span>
                 </a>
                 
                 <!-- Footer Links (Logout/Theme) in a row -->
                 <div style="display: flex; justify-content: space-between; align-items: center;">
                     <!-- Theme Switch -->
                     <label class="theme-switch" for="themeCheckbox" title="Cambiar Tema">
                        <input type="checkbox" id="themeCheckbox" />
                        <div class="slider"></div>
                    </label>
                    
                    <a href="logout.php" style="color: #ef4444; display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; font-weight: 600; text-decoration: none;">
                        <i data-lucide="log-out" style="width: 16px; height: 16px;"></i> Salir
                    </a>
                 </div>
            </div>
        </aside>

        <!-- Main Content Wrapper -->
        <main class="main-content">
            <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h1 id="pageTitle" style="font-size: 1.5rem; font-weight: 700; color: var(--text-main);">
                        <?php
                        $titles = [
                            'dashboard' => 'Dashboard Overview',
                            'atletas' => 'Gestión de Atletas',
                            'generar_plan' => 'Planificador de Entrenamientos',
                            'mis_planes' => 'Archivo de Planes',
                            'entrenamientos' => 'Reportes de Actividad',
                            'metricas' => 'Análisis de Rendimiento',
                            'config_team' => 'Configuración del Equipo',
                            'mi_plan' => 'Mi Programación Semanal',
                            'mi_progreso' => 'Mi Análisis Personal',
                            'perfil' => 'Ajustes de Perfil'
                        ];
                        echo $titles[$currentPage] ?? ucfirst($currentPage);
                        ?>
                    </h1>
                </div>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <a href="notificaciones.php"
                        style="position: relative; color: var(--text-muted); padding: 0.5rem; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-card);">
                        <i data-lucide="bell" style="width: 20px; height: 20px;"></i>
                    </a>
                </div>
            </header>

            <script>
                // Theme Toggle Script
                const themeCheckbox = document.getElementById('themeCheckbox');
                if (localStorage.getItem('theme') === 'dark') {
                    themeCheckbox.checked = true;
                }

                themeCheckbox.addEventListener('change', (e) => {
                    const theme = e.target.checked ? 'dark' : 'light';
                    document.documentElement.setAttribute('data-theme', theme);
                    localStorage.setItem('theme', theme);
                });
            </script>
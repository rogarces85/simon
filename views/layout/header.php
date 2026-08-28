<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';
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
$pageTitles = [
    'dashboard' => 'Panel Principal',
    'admin_dashboard' => 'Administración',
    'atletas' => 'Mis Atletas',
    'generar_plan' => 'Generar Plan',
    'mis_planes' => 'Mis Planes Generados',
    'entrenamientos' => 'Entrenamientos y Reportes',
    'metricas' => 'Métricas',
    'config_team' => 'Configurar Team',
    'mi_plan' => 'Mi Programación',
    'mi_progreso' => 'Mi Progreso',
    'notificaciones' => 'Notificaciones',
    'perfil' => 'Mi Perfil',
    'crear_entrenador' => 'Nuevo Entrenador',
    'plantillas' => 'Biblioteca de Plantillas',
];
$pageTitle = $pageTitles[$currentPage] ?? SITE_NAME;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: 'var(--primary)',
                        'primary-hover': 'var(--primary-hover)',
                        sidebar: '#1e293b',
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f8fafc;
            --surface: #ffffff;
            --surface-alt: #f1f5f9;
            --text: #0f172a;
            --text-muted: #64748b;
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --success: #16a34a;
            --warning: #d97706;
            --error: #dc2626;
            --border: #e2e8f0;
        }
        body { font-family: 'Inter', sans-serif; }

        .sidebar-link { transition: all 0.2s ease; }
        .sidebar-link:hover { background: rgba(59, 130, 246, 0.1); }
        .sidebar-link.active { background: #3b82f6; color: white; }

        /* Componentes reutilizables compatibles con Tailwind CDN */
        .btn-primary,
        .btn-secondary,
        .btn-danger {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, opacity 0.2s ease;
        }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-secondary { background: #fff; color: #334155; border: 1px solid #e2e8f0; }
        .btn-secondary:hover { background: #f8fafc; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-sm { padding: 0.5rem 1rem; font-size: 0.875rem; }
        .card-base { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05); overflow: hidden; }
        .card-stat { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05); padding: 1.25rem; }
        .badge-info,
        .badge-success,
        .badge-warning,
        .badge-error {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .badge-info { background: #eff6ff; color: #1d4ed8; }
        .badge-success { background: #f0fdf4; color: #15803d; }
        .badge-warning { background: #fffbeb; color: #b45309; }
        .badge-error { background: #fef2f2; color: #b91c1c; }
    </style>
</head>

<body class="bg-slate-50 min-h-screen">
    <!-- Skip Navigation -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-blue-600 text-white px-4 py-2 rounded-lg z-[100]">Saltar al contenido</a>

    <!-- Overlay movil -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden" onclick="toggleSidebar()" aria-hidden="true"></div>

    <!-- Boton hamburguesa movil -->
    <button id="menuToggle" onclick="toggleSidebar()" class="md:hidden fixed top-4 left-4 z-50 p-2.5 bg-white rounded-xl shadow-lg border border-slate-200 hover:bg-slate-50 transition-colors" aria-label="Abrir menú de navegación">
        <i data-lucide="menu" class="w-6 h-6 text-slate-700"></i>
    </button>

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside id="sidebar" class="w-64 bg-white border-r border-slate-200 flex flex-col fixed h-full -translate-x-full md:translate-x-0 transition-transform duration-300 z-50" role="navigation" aria-label="Menú principal">
            <!-- Logo -->
            <div class="p-6 border-b border-slate-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center">
                            <i data-lucide="dumbbell" class="w-5 h-5 text-white rotate-[-45deg]"></i>
                        </div>
                        <span class="text-xl font-bold text-slate-900">RUNCOACH</span>
                    </div>
                    <button onclick="toggleSidebar()" class="md:hidden p-1 text-slate-400 hover:text-slate-600" aria-label="Cerrar menú">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 p-4 space-y-1">
                <?php if ($currentUser['role'] === 'admin'): ?>
                    <a href="admin_dashboard.php"
                        class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 font-medium <?php echo $currentPage === 'admin_dashboard' ? 'active' : ''; ?>"
                        <?php echo $currentPage === 'admin_dashboard' ? 'aria-current="page"' : ''; ?>>
                        <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                        Panel Admin
                    </a>
                    <a href="crear_entrenador.php"
                        class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 font-medium <?php echo $currentPage === 'crear_entrenador' ? 'active' : ''; ?>"
                        <?php echo $currentPage === 'crear_entrenador' ? 'aria-current="page"' : ''; ?>>
                        <i data-lucide="user-plus" class="w-5 h-5"></i>
                        Crear Entrenador
                    </a>
                <?php elseif ($currentUser['role'] === 'coach'): ?>
                    <a href="dashboard.php"
                        class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 font-medium <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>"
                        <?php echo $currentPage === 'dashboard' ? 'aria-current="page"' : ''; ?>>
                        <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                        Panel Principal
                    </a>
                    <a href="atletas.php"
                        class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 font-medium <?php echo $currentPage === 'atletas' ? 'active' : ''; ?>"
                        <?php echo $currentPage === 'atletas' ? 'aria-current="page"' : ''; ?>>
                        <i data-lucide="users" class="w-5 h-5"></i>
                        Atletas
                    </a>
                    <a href="generar_plan.php"
                        class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 font-medium <?php echo $currentPage === 'generar_plan' ? 'active' : ''; ?>"
                        <?php echo $currentPage === 'generar_plan' ? 'aria-current="page"' : ''; ?>>
                        <i data-lucide="calendar" class="w-5 h-5"></i>
                        Generar Plan
                    </a>
                    <a href="mis_planes.php"
                        class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 font-medium <?php echo $currentPage === 'mis_planes' ? 'active' : ''; ?>"
                        <?php echo $currentPage === 'mis_planes' ? 'aria-current="page"' : ''; ?>>
                        <i data-lucide="folder-open" class="w-5 h-5"></i>
                        Mis Planes
                    </a>
                    <a href="entrenamientos.php"
                        class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 font-medium <?php echo $currentPage === 'entrenamientos' ? 'active' : ''; ?>"
                        <?php echo $currentPage === 'entrenamientos' ? 'aria-current="page"' : ''; ?>>
                        <i data-lucide="clipboard-check" class="w-5 h-5"></i>
                        Entrenamientos
                    </a>
                    <a href="metricas.php"
                        class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 font-medium <?php echo $currentPage === 'metricas' ? 'active' : ''; ?>"
                        <?php echo $currentPage === 'metricas' ? 'aria-current="page"' : ''; ?>>
                        <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
                        Métricas
                    </a>
                    <a href="config_team.php"
                        class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 font-medium <?php echo $currentPage === 'config_team' ? 'active' : ''; ?>"
                        <?php echo $currentPage === 'config_team' ? 'aria-current="page"' : ''; ?>>
                        <i data-lucide="settings" class="w-5 h-5"></i>
                        Configurar Team
                    </a>
                <?php else: ?>
                    <a href="mi_plan.php"
                        class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 font-medium <?php echo $currentPage === 'mi_plan' ? 'active' : ''; ?>"
                        <?php echo $currentPage === 'mi_plan' ? 'aria-current="page"' : ''; ?>>
                        <i data-lucide="calendar-check" class="w-5 h-5"></i>
                        Mi Programación
                    </a>
                    <a href="mi_progreso.php"
                        class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 font-medium <?php echo $currentPage === 'mi_progreso' ? 'active' : ''; ?>"
                        <?php echo $currentPage === 'mi_progreso' ? 'aria-current="page"' : ''; ?>>
                        <i data-lucide="trending-up" class="w-5 h-5"></i>
                        Mi Progreso
                    </a>
                <?php endif; ?>
            </nav>

            <!-- User Section -->
            <div class="p-4 border-t border-slate-100">
                <a href="notificaciones.php"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 font-medium hover:bg-slate-50 transition-all mb-2"
                    aria-label="Ver notificaciones">
                    <i data-lucide="bell" class="w-5 h-5"></i>
                    Notificaciones
                </a>

                <a href="perfil.php"
                    class="flex items-center gap-3 px-4 py-3 group hover:bg-slate-50 rounded-xl transition-all"
                    aria-label="Ver perfil de <?php echo htmlspecialchars($currentUser['name']); ?>">
                    <?php if (!empty($currentUser['avatar_url'])): ?>
                        <img src="<?php echo htmlspecialchars($currentUser['avatar_url']); ?>"
                            class="w-10 h-10 rounded-full object-cover border border-slate-200" alt="Avatar">
                    <?php else: ?>
                        <div
                            class="w-10 h-10 bg-slate-200 rounded-full flex items-center justify-center text-slate-600 font-bold group-hover:bg-blue-100 group-hover:text-blue-600 transition-colors"
                            aria-hidden="true">
                            <?php echo strtoupper(substr($currentUser['name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <div class="flex-1 overflow-hidden">
                        <p
                            class="text-sm font-semibold text-slate-900 truncate group-hover:text-blue-600 transition-colors">
                            <?php echo htmlspecialchars($currentUser['name']); ?>
                        </p>
                        <p class="text-xs text-slate-500">
                            <?php echo $currentUser['role'] === 'admin' ? 'Admin' : ($currentUser['role'] === 'coach' ? 'Entrenador' : 'Atleta'); ?>
                        </p>
                    </div>
                </a>
                <a href="logout.php"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl text-red-500 font-medium hover:bg-red-50 transition-all">
                    <i data-lucide="log-out" class="w-5 h-5"></i>
                    Cerrar Sesión
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main id="main-content" class="flex-1 ml-0 md:ml-64 p-4 md:p-8 pt-16 md:pt-8">

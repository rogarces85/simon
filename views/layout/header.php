<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/config.php';
Auth::init();

if (!Auth::check()) {
    header('Location: login.php');
    exit;
}

$currentUser = Auth::user();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Tailwind Config for CDN usage
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3b82f6',
                        sidebar: '#1e293b',
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .sidebar-link {
            transition: all 0.2s ease;
        }

        .sidebar-link:hover {
            background: rgba(59, 130, 246, 0.1);
        }

        .sidebar-link.active {
            background: #3b82f6;
            color: white;
        }
    </style>
</head>

<body class="bg-slate-50 min-h-screen">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-white border-r border-slate-200 flex flex-col fixed h-full">
            <!-- Logo -->
            <div class="p-6 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center">
                        <i data-lucide="dumbbell" class="w-5 h-5 text-white rotate-[-45deg]"></i>
                    </div>
                    <span class="text-xl font-bold text-slate-900">RUNCOACH</span>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 p-4 space-y-1">
                <?php if ($currentUser['role'] === 'admin'): ?>
                    <a href="admin_dashboard.php"
                        class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 font-medium <?php echo $currentPage === 'admin_dashboard' ? 'active' : ''; ?>">
                        <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                        Panel Admin
                    </a>
                    <a href="crear_entrenador.php"
                        class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 font-medium <?php echo $currentPage === 'crear_entrenador' ? 'active' : ''; ?>">
                        <i data-lucide="user-plus" class="w-5 h-5"></i>
                        Crear Entrenador
                    </a>
                <?php elseif ($currentUser['role'] === 'coach'): ?>
                    <a href="dashboard.php"
                        class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 font-medium <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                        <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                        Panel Principal
                    </a>
                    <a href="atletas.php"
                        class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 font-medium <?php echo $currentPage === 'atletas' ? 'active' : ''; ?>">
                        <i data-lucide="users" class="w-5 h-5"></i>
                        Atletas
                    </a>
                    <a href="plantillas.php"
                        class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 font-medium <?php echo $currentPage === 'plantillas' ? 'active' : ''; ?>">
                        <i data-lucide="file-text" class="w-5 h-5"></i>
                        Plantillas
                    </a>
                    <a href="generar_plan.php"
                        class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 font-medium <?php echo $currentPage === 'generar_plan' ? 'active' : ''; ?>">
                        <i data-lucide="calendar" class="w-5 h-5"></i>
                        Generar Plan
                    </a>
                    <a href="reportes.php"
                        class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 font-medium <?php echo $currentPage === 'reportes' ? 'active' : ''; ?>">
                        <i data-lucide="clipboard-list" class="w-5 h-5"></i>
                        Reportes
                    </a>
                    <a href="metricas.php"
                        class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 font-medium <?php echo $currentPage === 'metricas' ? 'active' : ''; ?>">
                        <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
                        Métricas
                    </a>
                    <a href="config_team.php"
                        class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 font-medium <?php echo $currentPage === 'config_team' ? 'active' : ''; ?>">
                        <i data-lucide="settings" class="w-5 h-5"></i>
                        Configurar Team
                    </a>
                <?php else: ?>
                    <a href="mi_plan.php"
                        class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 font-medium <?php echo $currentPage === 'mi_plan' ? 'active' : ''; ?>">
                        <i data-lucide="calendar-check" class="w-5 h-5"></i>
                        Mi Plan Semanal
                    </a>
                    <a href="metricas.php"
                        class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 font-medium <?php echo $currentPage === 'metricas' ? 'active' : ''; ?>">
                        <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
                        Mi Progreso
                    </a>
                <?php endif; ?>
            </nav>

            <!-- User Section -->
            <div class="p-4 border-t border-slate-100">
                <a href="notificaciones.php"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 font-medium hover:bg-slate-50 transition-all mb-2">
                    <i data-lucide="bell" class="w-5 h-5"></i>
                    Notificaciones
                </a>

                <a href="perfil.php"
                    class="flex items-center gap-3 px-4 py-3 group hover:bg-slate-50 rounded-xl transition-all">
                    <?php if (!empty($currentUser['avatar_url'])): ?>
                        <img src="<?php echo htmlspecialchars($currentUser['avatar_url']); ?>"
                            class="w-10 h-10 rounded-full object-cover border border-slate-200">
                    <?php else: ?>
                        <div
                            class="w-10 h-10 bg-slate-200 rounded-full flex items-center justify-center text-slate-600 font-bold group-hover:bg-blue-100 group-hover:text-blue-600 transition-colors">
                            <?php echo strtoupper(substr($currentUser['name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <div class="flex-1 overflow-hidden">
                        <p
                            class="text-sm font-semibold text-slate-900 truncate group-hover:text-blue-600 transition-colors">
                            <?php echo htmlspecialchars($currentUser['name']); ?>
                        </p>
                        <p class="text-xs text-slate-500">
                            <?php echo $currentUser['role'] === 'coach' ? 'Entrenador' : 'Atleta'; ?>
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
        <main class="flex-1 ml-64 p-8">
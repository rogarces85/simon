<!DOCTYPE html>
<html lang="es" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIMON - Sistema de Gestión de Entrenamiento</title>
    <link rel="stylesheet" href="assets/css/theme.css">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body>

    <!-- Navigation -->
    <nav class="container" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <div
                style="width: 40px; height: 40px; background: var(--primary); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="zap" style="color: #0f172a; width: 24px; height: 24px;"></i>
            </div>
            <span style="font-size: 1.5rem; font-weight: 700; tracking: -0.05em;">SIMON</span>
        </div>

        <div style="display: flex; align-items: center; gap: 2rem;">
            <div class="theme-switch-wrapper">
                <i data-lucide="sun"
                    style="width: 18px; height: 18px; margin-right: 8px; color: var(--text-muted);"></i>
                <label class="theme-switch" for="checkbox">
                    <input type="checkbox" id="checkbox" />
                    <div class="slider"></div>
                </label>
                <i data-lucide="moon"
                    style="width: 18px; height: 18px; margin-left: 8px; color: var(--text-muted);"></i>
            </div>
            <a href="login.php" class="btn btn-secondary">Iniciar Sesión</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="container" style="padding: 100px 1.5rem; text-align: center;">
        <h1 style="font-size: 4rem; font-weight: 800; line-height: 1.1; margin-bottom: 1.5rem;">
            Entrena con <span style="color: var(--primary);">Inteligencia</span>,<br>Corre con Propósito.
        </h1>
        <p style="font-size: 1.25rem; color: var(--text-muted); max-width: 700px; margin: 0 auto 2.5rem;">
            La plataforma definitiva para coaches y atletas de running. Planificación profesional, métricas en tiempo
            real y comunicación sin fricciones.
        </p>
        <div style="display: flex; gap: 1rem; justify-content: center;">
            <a href="login.php" class="btn btn-primary" style="padding: 1rem 2.5rem; font-size: 1.1rem;">Comenzar
                Ahora</a>
            <a href="#features" class="btn btn-secondary" style="padding: 1rem 2.5rem; font-size: 1.1rem;">Saber Más</a>
        </div>
    </header>

    <!-- Features Section -->
    <section id="features" class="container" style="padding: 80px 1.5rem;">
        <div style="text-align: center; margin-bottom: 60px;">
            <h2 style="font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem;">Todo lo que necesitas</h2>
            <p style="color: var(--text-muted);">Diseñado específicamente para las demandas del running moderno.</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
            <div class="card">
                <div
                    style="width: 48px; height: 48px; background: rgba(13, 242, 128, 0.1); border-radius: 8px; color: var(--primary); display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
                    <i data-lucide="calendar"></i>
                </div>
                <h3 style="font-size: 1.25rem; margin-bottom: 1rem;">Planificación Modular</h3>
                <p style="color: var(--text-muted);">Crea planes semanales personalizados. Ajusta entrenamientos día a
                    día según la necesidad de cada atleta.</p>
            </div>

            <div class="card">
                <div
                    style="width: 48px; height: 48px; background: rgba(13, 242, 128, 0.1); border-radius: 8px; color: var(--primary); display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
                    <i data-lucide="bar-chart-2"></i>
                </div>
                <h3 style="font-size: 1.25rem; margin-bottom: 1rem;">Análisis de Progreso</h3>
                <p style="color: var(--text-muted);">Visualiza el cumplimiento de tus corredores. Gráficos de volumen e
                    intensidad listos para actuar.</p>
            </div>

            <div class="card">
                <div
                    style="width: 48px; height: 48px; background: rgba(13, 242, 128, 0.1); border-radius: 8px; color: var(--primary); display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
                    <i data-lucide="message-square"></i>
                </div>
                <h3 style="font-size: 1.25rem; margin-bottom: 1rem;">Feedback Bidireccional</h3>
                <p style="color: var(--text-muted);">Recibe comentarios post-entrenamiento e interactúa con tus atletas
                    de forma inmediata.</p>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section
        style="background: var(--bg-card); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); padding: 100px 1.5rem; text-align: center;">
        <div class="container">
            <h2 style="font-size: 2.5rem; font-weight: 700; margin-bottom: 1.5rem;">¿Listo para llevar tu equipo al
                siguiente nivel?</h2>
            <p
                style="color: var(--text-muted); margin-bottom: 2.5rem; max-width: 600px; margin-left: auto; margin-right: auto;">
                Únete a SIMON hoy y experimenta el poder de una organización deportiva profesional.</p>
            <a href="login.php" class="btn btn-primary" style="padding: 1rem 3rem;">Registrar mi Club</a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="container"
        style="padding: 40px 1.5rem; text-align: center; color: var(--text-muted); font-size: 0.9rem;">
        <p>&copy; 2026 SIMON Running Management. Todos los derechos reservados.</p>
    </footer>

    <script>
        // Lucide Icons Initialization
        lucide.createIcons();

        // Theme Toggle Logic
        const toggleSwitch = document.querySelector('.theme-switch input[type="checkbox"]');
        const currentTheme = localStorage.getItem('theme');

        if (currentTheme) {
            document.documentElement.setAttribute('data-theme', currentTheme);
            if (currentTheme === 'dark') {
                toggleSwitch.checked = true;
            }
        }

        function switchTheme(e) {
            if (e.target.checked) {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            } else {
                document.documentElement.setAttribute('data-theme', 'light');
                localStorage.setItem('theme', 'light');
            }
        }

        toggleSwitch.addEventListener('change', switchTheme, false);
    </script>
</body>

</html>
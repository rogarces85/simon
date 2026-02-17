<?php
require_once 'includes/auth.php';
require_once 'models/User.php';
require_once 'models/Team.php';

Auth::init();
Auth::requireRole('admin');

$user = Auth::user();
$coaches = User::getByRole('coach');

include 'views/layout/header.php';
?>

<div
    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 2rem; font-weight: 800; color: var(--text-main); margin: 0;">ADMINISTRACIÓN</h1>
        <p style="color: var(--text-muted); margin-top: 0.25rem;">Gestión global de entrenadores y equipos</p>
    </div>
    <a href="crear_entrenador.php" class="btn btn-primary" style="padding: 0.75rem 1.5rem; gap: 0.75rem;">
        <i data-lucide="user-plus" style="width: 18px; height: 18px;"></i>
        Nuevo Entrenador
    </a>
</div>

<div class="card" style="padding: 0; overflow: hidden;">
    <div style="padding: 1.5rem; border-bottom: 1px solid var(--border);">
        <h2 style="font-size: 1.125rem; font-weight: 700; margin: 0;">Entrenadores Registrados</h2>
    </div>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead
                style="background: var(--bg-main); color: var(--text-muted); font-size: 0.75rem; font-weight: 800; text-transform: uppercase;">
                <tr>
                    <th style="padding: 1rem 1.5rem;">NOMBRE</th>
                    <th style="padding: 1rem 1.5rem;">USUARIO / EMAIL</th>
                    <th style="padding: 1rem 1.5rem;">EQUIPO</th>
                    <th style="padding: 1rem 1.5rem; text-align: center;">ESTADO</th>
                    <th style="padding: 1rem 1.5rem; text-align: right;">ACCIONES</th>
                </tr>
            </thead>
            <tbody style="color: var(--text-main); font-size: 0.9rem;">
                <?php foreach ($coaches as $coach):
                    $team = Team::findByCoach($coach['id']);
                    ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 1.25rem 1.5rem;">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <div
                                    style="width: 36px; height: 36px; border-radius: 50%; background: var(--primary); color: #0f172a; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.8rem;">
                                    <?php echo strtoupper(substr($coach['name'], 0, 1)); ?>
                                </div>
                                <span style="font-weight: 700;"><?php echo htmlspecialchars($coach['name']); ?></span>
                            </div>
                        </td>
                        <td style="padding: 1.25rem 1.5rem; color: var(--text-muted); font-family: monospace;">
                            <?php echo htmlspecialchars($coach['username']); ?>
                        </td>
                        <td style="padding: 1.25rem 1.5rem;">
                            <?php if ($team): ?>
                                <span class="badge badge-emerald" style="font-size: 0.75rem;">
                                    <?php echo htmlspecialchars($team['name']); ?>
                                </span>
                            <?php else: ?>
                                <span style="color: var(--text-muted); font-size: 0.8rem; font-style: italic;">Sin Equipo</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 1.25rem 1.5rem; text-align: center;">
                            <span
                                style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.25rem 0.75rem; border-radius: 100px; background: rgba(13, 242, 128, 0.1); color: #065f46; font-size: 0.75rem; font-weight: 700;">
                                <span
                                    style="width: 6px; height: 6px; border-radius: 50%; background: var(--primary);"></span>
                                Activo
                            </span>
                        </td>
                        <td style="padding: 1.25rem 1.5rem; text-align: right;">
                            <button class="btn btn-secondary" style="padding: 0.4rem; border-radius: 8px;">
                                <i data-lucide="edit-2" style="width: 14px; height: 14px;"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'views/layout/footer.php'; ?>
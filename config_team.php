<?php
require_once 'includes/auth.php';
require_once 'models/Team.php';

Auth::init();
Auth::requireRole('coach');

$user = Auth::user();
$team = Team::findByCoach($user['id']);
$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $color = $_POST['primary_color'] ?? '#3b82f6';
    $logoUrl = $team['logo_url'] ?? null;

    // Handle File Upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads/teams/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileExt = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($fileExt, $allowed)) {
            $fileName = 'team_' . $user['id'] . '_' . time() . '.' . $fileExt;
            $uploadPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadPath)) {
                $logoUrl = 'uploads/teams/' . $fileName;
            } else {
                $error = "Error al subir la imagen.";
            }
        } else {
            $error = "Tipo de archivo no permitido (solo JPG, PNG, WEBP).";
        }
    }

    if (!$error) {
        if ($team) {
            Team::update($team['id'], [
                'name' => $name,
                'logo_url' => $logoUrl,
                'primary_color' => $color
            ]);
            $success = "Team actualizado correctamente.";
        } else {
            Team::create([
                'coach_id' => $user['id'],
                'name' => $name,
                'logo_url' => $logoUrl,
                'primary_color' => $color
            ]);
            $success = "Team creado correctamente.";
        }
        // Refresh data
        $team = Team::findByCoach($user['id']);
    }
}

include 'views/layout/header.php';
?>

<div style="max-width: 800px; margin: 0 auto;">
    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 2rem; font-weight: 800; color: var(--text-main); margin: 0;">CONFIGURACIÓN DEL TEAM</h1>
        <p style="color: var(--text-muted); margin-top: 0.25rem;">Personaliza la identidad visual de tu equipo</p>
    </div>

    <?php if ($success): ?>
        <div class="card"
            style="border-color: var(--primary); background: rgba(13, 242, 128, 0.05); margin-bottom: 2rem; display: flex; align-items: center; gap: 0.75rem;">
            <i data-lucide="check-circle" style="color: var(--primary);"></i>
            <span><?php echo $success; ?></span>
        </div>
    <?php endif; ?>

    <div class="card">
        <form method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 2rem;">

            <!-- Logo Upload Section -->
            <div style="display: flex; align-items: center; gap: 2rem; flex-wrap: wrap;">
                <div style="position: relative;">
                    <?php if (isset($team['logo_url']) && $team['logo_url']): ?>
                        <img src="<?php echo htmlspecialchars($team['logo_url']); ?>"
                            style="width: 100px; height: 100px; border-radius: 16px; border: 2px solid var(--border); object-fit: cover;">
                    <?php else: ?>
                        <div
                            style="width: 100px; height: 100px; border-radius: 16px; background: var(--bg-main); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: var(--text-muted);">
                            <i data-lucide="image" style="width: 32px; height: 32px;"></i>
                        </div>
                    <?php endif; ?>
                    <label
                        style="position: absolute; bottom: -10px; right: -10px; background: var(--primary); color: #0f172a; padding: 0.4rem; border-radius: 50%; cursor: pointer; box-shadow: var(--shadow);">
                        <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
                        <input type="file" name="logo" accept="image/*" style="display: none;">
                    </label>
                </div>
                <div style="flex: 1;">
                    <h4 style="font-size: 0.9rem; font-weight: 700; margin-bottom: 0.25rem;">Logo del Equipo</h4>
                    <p style="font-size: 0.75rem; color: var(--text-muted);">Formatos aceptados: JPG, PNG, WEBP. Máximo
                        5MB.</p>
                </div>
            </div>

            <hr style="border: none; border-top: 1px solid var(--border);">

            <!-- Form Content -->
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <div>
                    <label
                        style="display: block; font-size: 0.75rem; font-weight: 800; color: var(--text-muted); margin-bottom: 0.5rem; text-transform: uppercase;">Nombre
                        del Team</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($team['name'] ?? ''); ?>" required
                        style="width: 100%; padding: 0.85rem; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-main); color: var(--text-main); font-family: inherit;"
                        placeholder="Ej. RunFast Team">
                </div>

                <div>
                    <label
                        style="display: block; font-size: 0.75rem; font-weight: 800; color: var(--text-muted); margin-bottom: 0.5rem; text-transform: uppercase;">Color
                        Identificativo</label>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <input type="color" name="primary_color"
                            value="<?php echo htmlspecialchars($team['primary_color'] ?? '#0df280'); ?>"
                            style="width: 60px; height: 40px; border: none; border-radius: 6px; cursor: pointer; padding: 0; background: none;">
                        <span style="font-size: 0.8rem; color: var(--text-muted);">Este color se usará para acentos
                            visuales en la app.</span>
                    </div>
                </div>
            </div>

            <div style="margin-top: 1rem;">
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; gap: 0.75rem;">
                    <i data-lucide="save" style="width: 18px; height: 18px;"></i>
                    Guardar Configuración
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'views/layout/footer.php'; ?>
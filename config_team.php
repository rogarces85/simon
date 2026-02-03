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

<div class="max-w-2xl mx-auto">
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Configuración del Team</h1>
        <p class="text-slate-500 mt-1">Personaliza la identidad visual de tu equipo.</p>
    </div>

    <?php if ($success): ?>
        <div class="bg-green-50 text-green-600 p-4 rounded-xl mb-6 flex items-center gap-3">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-50 text-red-600 p-4 rounded-xl mb-6 flex items-center gap-3">
            <i data-lucide="alert-circle" class="w-5 h-5"></i>
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
        <form method="POST" enctype="multipart/form-data" class="space-y-8">

            <!-- Logo Upload -->
            <div class="flex items-center gap-6">
                <div class="shrink-0">
                    <?php if (isset($team['logo_url']) && $team['logo_url']): ?>
                        <img class="h-24 w-24 object-cover rounded-full border-2 border-slate-100"
                            src="<?php echo htmlspecialchars($team['logo_url']); ?>" alt="Team Logo" />
                    <?php else: ?>
                        <div class="h-24 w-24 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                            <i data-lucide="image" class="w-10 h-10"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <label class="block">
                    <span class="sr-only">Elegir logo</span>
                    <input type="file" name="logo" accept="image/*" class="block w-full text-sm text-slate-500
                        file:mr-4 file:py-2 file:px-4
                        file:rounded-full file:border-0
                        file:text-sm file:font-semibold
                        file:bg-blue-50 file:text-blue-700
                        hover:file:bg-blue-100
                    " />
                    <p class="mt-2 text-xs text-slate-500">JPG, PNG o WEBP. Máx 5MB.</p>
                </label>
            </div>

            <hr class="border-slate-100 my-6">

            <!-- Team Name -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Nombre del Team</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($team['name'] ?? ''); ?>" required
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-all font-inter"
                    placeholder="Ej. RunFast Team">
            </div>

            <!-- Primary Color -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Color del Team</label>
                <div class="flex items-center gap-4">
                    <input type="color" name="primary_color"
                        value="<?php echo htmlspecialchars($team['primary_color'] ?? '#3b82f6'); ?>"
                        class="h-12 w-24 rounded cursor-pointer border-0 p-0">
                    <span class="text-slate-500 text-sm">Color principal para botones y encabezados de tus
                        atletas.</span>
                </div>
            </div>

            <div class="pt-4">
                <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-xl transition-colors flex items-center justify-center gap-2">
                    <i data-lucide="save" class="w-5 h-5"></i>
                    Guardar Configuración
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'views/layout/footer.php'; ?>
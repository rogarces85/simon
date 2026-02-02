<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'models/User.php';
Auth::init();
Auth::requireRole('coach');

$coach = Auth::user();
$db = Database::getInstance();

// Get templates
$stmt = $db->prepare("SELECT * FROM templates WHERE coach_id = ? ORDER BY type, name");
$stmt->execute([$coach['id']]);
$templates = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_template') {
        $sql = "INSERT INTO templates (coach_id, name, type, block_type, structure) VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $coach['id'],
            $_POST['name'],
            $_POST['type'],
            $_POST['block_type'],
            $_POST['description'] ?? ''
        ]);
        header('Location: plantillas.php?success=1');
        exit;
    }

    if ($_POST['action'] === 'delete_template' && isset($_POST['template_id'])) {
        $stmt = $db->prepare("DELETE FROM templates WHERE id = ? AND coach_id = ?");
        $stmt->execute([$_POST['template_id'], $coach['id']]);
        header('Location: plantillas.php?deleted=1');
        exit;
    }
}

// Group templates by type
$groupedTemplates = [];
foreach ($templates as $template) {
    $type = $template['type'] ?? 'Otros';
    if (!isset($groupedTemplates[$type])) {
        $groupedTemplates[$type] = [];
    }
    $groupedTemplates[$type][] = $template;
}

include 'views/layout/header.php';
?>

<!-- Page Header -->
<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">PLANTILLAS DE ENTRENAMIENTO</h1>
        <p class="text-slate-500 mt-1">Crea y reutiliza sesiones de entrenamiento</p>
    </div>
    <button onclick="openModal()"
        class="flex items-center gap-2 bg-blue-600 text-white px-5 py-3 rounded-xl font-semibold hover:bg-blue-700 transition-all shadow-lg shadow-blue-100">
        <i data-lucide="plus" class="w-5 h-5"></i>
        Nueva Plantilla
    </button>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
        ✅ Plantilla creada exitosamente
    </div>
<?php endif; ?>

<!-- Templates Grid -->
<?php if (empty($templates)): ?>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-12 text-center">
        <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <i data-lucide="file-plus" class="w-8 h-8 text-slate-400"></i>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 mb-2">No hay plantillas</h3>
        <p class="text-slate-500 mb-6">Crea tu primera plantilla de entrenamiento</p>
        <button onclick="openModal()"
            class="inline-flex items-center gap-2 bg-blue-600 text-white px-5 py-3 rounded-xl font-semibold hover:bg-blue-700 transition-all">
            <i data-lucide="plus" class="w-5 h-5"></i>
            Crear Plantilla
        </button>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($templates as $template): ?>
            <?php
            $typeColors = [
                'Intervalos' => 'bg-blue-500',
                'Series' => 'bg-red-500',
                'Fondo' => 'bg-green-500',
                'Tempo' => 'bg-purple-500',
                'Descanso' => 'bg-slate-400',
                'Recuperación' => 'bg-orange-500'
            ];
            $blockColors = [
                'Construcción' => 'border-slate-300 text-slate-600',
                'Pico' => 'border-blue-400 text-blue-600',
                'Base' => 'border-green-400 text-green-600'
            ];
            $typeColor = $typeColors[$template['type']] ?? 'bg-slate-500';
            $blockColor = $blockColors[$template['block_type']] ?? 'border-slate-300 text-slate-600';
            ?>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start mb-4">
                    <span class="px-3 py-1 <?php echo $typeColor; ?> text-white text-xs font-semibold rounded-full">
                        <?php echo htmlspecialchars($template['type']); ?>
                    </span>
                    <span class="px-3 py-1 border <?php echo $blockColor; ?> text-xs font-medium rounded-full">
                        <?php echo htmlspecialchars($template['block_type'] ?? 'General'); ?>
                    </span>
                </div>

                <h3 class="text-lg font-bold text-slate-900 mb-2">
                    <?php echo htmlspecialchars($template['name']); ?>
                </h3>
                <p class="text-sm text-slate-500 mb-4">
                    <?php echo htmlspecialchars($template['structure'] ?? 'Sin descripción detallada'); ?>
                </p>

                <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                    <div class="flex items-center gap-2 text-slate-500 text-sm">
                        <i data-lucide="clock" class="w-4 h-4"></i>
                        60 min
                    </div>
                    <div class="flex items-center gap-2">
                        <button class="flex items-center gap-1 text-slate-600 hover:text-blue-600 text-sm font-medium">
                            <i data-lucide="pencil" class="w-4 h-4"></i>
                            Editar
                        </button>
                        <form method="POST" class="inline" onsubmit="return confirm('¿Eliminar esta plantilla?')">
                            <input type="hidden" name="action" value="delete_template">
                            <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                            <button type="submit" class="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Modal Nueva Plantilla -->
<div id="modalPlantilla" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl w-full max-w-lg m-4">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center">
            <div>
                <h3 class="text-xl font-bold text-slate-900">NUEVA PLANTILLA</h3>
                <p class="text-slate-500 text-sm mt-1">Define una sesión de entrenamiento reutilizable</p>
            </div>
            <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>

        <form method="POST" class="p-6 space-y-6">
            <input type="hidden" name="action" value="create_template">

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Nombre de la Plantilla *</label>
                <input type="text" name="name" placeholder="8x400m ritmo 1:30" required
                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Tipo *</label>
                    <select name="type" required
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="Intervalos">Intervalos</option>
                        <option value="Series">Series</option>
                        <option value="Fondo">Fondo</option>
                        <option value="Tempo">Tempo</option>
                        <option value="Recuperación">Recuperación</option>
                        <option value="Descanso">Descanso</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Bloque</label>
                    <select name="block_type"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="Base">Base</option>
                        <option value="Construcción">Construcción</option>
                        <option value="Pico">Pico</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Descripción</label>
                <textarea name="description" rows="3" placeholder="Descripción detallada del entrenamiento..."
                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none resize-none"></textarea>
            </div>

            <div class="flex justify-end gap-4 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal()"
                    class="px-6 py-3 border border-slate-200 rounded-xl font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                    Cancelar
                </button>
                <button type="submit"
                    class="px-6 py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition-colors">
                    Guardar Plantilla
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal() {
        document.getElementById('modalPlantilla').classList.remove('hidden');
        document.getElementById('modalPlantilla').classList.add('flex');
        lucide.createIcons();
    }

    function closeModal() {
        document.getElementById('modalPlantilla').classList.add('hidden');
        document.getElementById('modalPlantilla').classList.remove('flex');
    }
</script>

<?php include 'views/layout/footer.php'; ?>
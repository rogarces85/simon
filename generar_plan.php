<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/Mailer.php';
require_once 'models/User.php';
require_once 'models/Workout.php';
require_once 'models/Notification.php';
Auth::init();
Auth::requireRole('coach');

$coach = Auth::user();
$db = Database::getInstance();

// Get athletes for dropdown
$athletes = User::getByCoachId($coach['id']);

// Handle form submissions
$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Generate plan
    if ($_POST['action'] === 'generate_plan') {
        $athleteId = $_POST['athlete_id'];
        $weekStart = $_POST['week_start'];
        $days = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
        $createdWorkouts = [];

        foreach ($days as $i => $day) {
            $templateId = $_POST['template_' . $day] ?? null;
            if ($templateId) {
                $stmt = $db->prepare("SELECT * FROM templates WHERE id = ?");
                $stmt->execute([$templateId]);
                $template = $stmt->fetch();

                if ($template) {
                    $workoutDate = date('Y-m-d', strtotime($weekStart . " + $i days"));
                    $workoutData = [
                        'athlete_id' => $athleteId,
                        'date' => $workoutDate,
                        'type' => $template['type'],
                        'description' => $template['name'],
                        'status' => 'pending',
                        'structure' => $template['structure'],
                        'delivery_status' => 'sent'
                    ];
                    Workout::create($workoutData);
                    $createdWorkouts[] = [
                        'date' => $workoutDate,
                        'type' => $template['type'],
                        'description' => $template['name']
                    ];
                }
            }
        }

        if (!empty($createdWorkouts)) {
            $athlete = User::getById($athleteId);
            if ($athlete && $athlete['username']) {
                Mailer::sendNewPlanNotification(
                    $athlete['username'],
                    $athlete['name'],
                    $coach['name'],
                    $weekStart,
                    $createdWorkouts
                );
                $msg = "📋 Tu entrenador ha generado un nuevo plan de entrenamiento para la semana del " . (new DateTime($weekStart))->format('d/m/Y');
                Notification::create($athleteId, $msg, 'info');
            }
        }
        header('Location: generar_plan.php?success=plan');
        exit;
    }

    // Template CRUD
    if ($_POST['action'] === 'create_template') {
        $sql = "INSERT INTO templates (coach_id, name, type, block_type, structure) VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $coach['id'],
            $_POST['name'],
            $_POST['type'],
            $_POST['block_type'] ?? null,
            $_POST['structure'] ?? null
        ]);
        header('Location: generar_plan.php?tab=plantillas&success=template_created');
        exit;
    }

    if ($_POST['action'] === 'update_template') {
        $sql = "UPDATE templates SET name = ?, type = ?, block_type = ?, structure = ? WHERE id = ? AND coach_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $_POST['name'],
            $_POST['type'],
            $_POST['block_type'] ?? null,
            $_POST['structure'] ?? null,
            $_POST['template_id'],
            $coach['id']
        ]);
        header('Location: generar_plan.php?tab=plantillas&success=template_updated');
        exit;
    }

    if ($_POST['action'] === 'delete_template') {
        $sql = "DELETE FROM templates WHERE id = ? AND coach_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$_POST['template_id'], $coach['id']]);
        header('Location: generar_plan.php?tab=plantillas&success=template_deleted');
        exit;
    }
}

// Get templates
$stmt = $db->prepare("SELECT * FROM templates WHERE coach_id = ? ORDER BY type, name");
$stmt->execute([$coach['id']]);
$templates = $stmt->fetchAll();

$activeTab = $_GET['tab'] ?? 'plan';

include 'views/layout/header.php';
?>

<!-- Page Header -->
<div class="mb-8">
    <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">GENERAR PLAN DE ENTRENAMIENTO</h1>
    <p class="text-slate-500 mt-1">Crea planes semanales y gestiona tus plantillas de entrenamiento</p>
</div>

<?php if (isset($_GET['success'])): ?>
    <?php
    $msgs = [
        'plan' => '✅ Plan semanal generado exitosamente',
        'template_created' => '✅ Plantilla creada exitosamente',
        'template_updated' => '✅ Plantilla actualizada exitosamente',
        'template_deleted' => '✅ Plantilla eliminada exitosamente'
    ];
    $msg = $msgs[$_GET['success']] ?? '✅ Operación exitosa';
    ?>
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
        <?php echo $msg; ?>
    </div>
<?php endif; ?>

<!-- Tabs -->
<div class="flex gap-1 mb-8 bg-slate-100 rounded-xl p-1 w-fit">
    <a href="generar_plan.php?tab=plan"
        class="px-6 py-3 rounded-lg font-semibold text-sm transition-all <?php echo $activeTab === 'plan' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'; ?>">
        <i data-lucide="calendar-plus" class="w-4 h-4 inline mr-2"></i>Generar Plan
    </a>
    <a href="generar_plan.php?tab=plantillas"
        class="px-6 py-3 rounded-lg font-semibold text-sm transition-all <?php echo $activeTab === 'plantillas' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'; ?>">
        <i data-lucide="file-text" class="w-4 h-4 inline mr-2"></i>Mis Plantillas
        <span
            class="ml-1 px-2 py-0.5 bg-slate-200 text-slate-600 rounded-full text-xs"><?php echo count($templates); ?></span>
    </a>
</div>

<?php if ($activeTab === 'plan'): ?>
    <!-- ========== TAB: GENERAR PLAN ========== -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Form -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                        <i data-lucide="calendar" class="w-5 h-5 text-blue-600"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Configuración del Plan</h2>
                        <p class="text-sm text-slate-500">Selecciona el atleta y la semana para el plan</p>
                    </div>
                </div>

                <form method="POST" id="planForm">
                    <input type="hidden" name="action" value="generate_plan">
                    <div class="grid grid-cols-2 gap-6 mb-8">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Atleta</label>
                            <select name="athlete_id" required
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                                <option value="">Seleccionar atleta...</option>
                                <?php foreach ($athletes as $athlete): ?>
                                    <option value="<?php echo $athlete['id']; ?>">
                                        <?php echo htmlspecialchars($athlete['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Semana desde (Lunes)</label>
                            <input type="date" name="week_start" required
                                value="<?php echo date('Y-m-d', strtotime('next monday')); ?>"
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        </div>
                    </div>
            </div>

            <!-- Weekly Plan Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <div class="mb-6">
                    <h2 class="text-lg font-bold text-slate-900">Plan Semanal</h2>
                    <p class="text-sm text-slate-500">Asigna entrenamientos a cada día de la semana</p>
                </div>

                <div class="space-y-4">
                    <?php
                    $days = [
                        'lunes' => 'Lunes',
                        'martes' => 'Martes',
                        'miercoles' => 'Miércoles',
                        'jueves' => 'Jueves',
                        'viernes' => 'Viernes',
                        'sabado' => 'Sábado',
                        'domingo' => 'Domingo'
                    ];
                    foreach ($days as $key => $label):
                        ?>
                        <div class="flex items-center gap-4 py-3 border-b border-slate-100 last:border-0">
                            <div class="w-24">
                                <span class="font-semibold text-slate-900"><?php echo $label; ?></span>
                            </div>
                            <select name="template_<?php echo $key; ?>"
                                class="flex-1 px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                                <option value="">Seleccionar entrenamiento...</option>
                                <option value="" disabled>──────────────</option>
                                <?php
                                $currentType = '';
                                foreach ($templates as $template):
                                    if ($template['type'] !== $currentType):
                                        if ($currentType !== '')
                                            echo '</optgroup>';
                                        $currentType = $template['type'];
                                        echo '<optgroup label="' . htmlspecialchars($currentType) . '">';
                                    endif;
                                    ?>
                                    <option value="<?php echo $template['id']; ?>">
                                        <?php echo htmlspecialchars($template['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                                <?php if ($currentType !== '')
                                    echo '</optgroup>'; ?>
                            </select>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-8 pt-6 border-t border-slate-100">
                    <button type="submit"
                        class="w-full bg-blue-600 text-white py-4 rounded-xl font-bold text-lg hover:bg-blue-700 transition-all shadow-lg shadow-blue-100">
                        Generar Plan Semanal
                    </button>
                </div>
                </form>
            </div>
        </div>

        <!-- Sidebar: Templates Preview -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sticky top-8">
                <h3 class="text-lg font-bold text-slate-900 mb-2">Plantillas Disponibles</h3>
                <p class="text-sm text-slate-500 mb-6">Selecciona para ver detalles</p>

                <?php if (empty($templates)): ?>
                    <div class="text-center py-8">
                        <p class="text-slate-500 mb-4">No hay plantillas creadas</p>
                        <a href="generar_plan.php?tab=plantillas" class="text-blue-600 font-semibold">Crear plantillas</a>
                    </div>
                <?php else: ?>
                    <div class="space-y-3 max-h-[500px] overflow-y-auto pr-2">
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
                            $typeColor = $typeColors[$template['type']] ?? 'bg-slate-500';
                            ?>
                            <div
                                class="p-3 border border-slate-200 rounded-xl hover:border-blue-300 hover:bg-blue-50/50 transition-all cursor-pointer">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="px-2 py-0.5 <?php echo $typeColor; ?> text-white text-xs font-semibold rounded">
                                        <?php echo htmlspecialchars($template['type']); ?>
                                    </span>
                                </div>
                                <p class="text-sm font-medium text-slate-900">
                                    <?php echo htmlspecialchars($template['name']); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="text-center text-sm text-slate-400 mt-4">+
                        <?php echo count($templates); ?> plantillas disponibles
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- ========== TAB: MIS PLANTILLAS ========== -->
    <div class="space-y-6">
        <!-- Create Template Button -->
        <div class="flex justify-end">
            <button onclick="openTemplateModal()"
                class="bg-blue-600 text-white px-6 py-3 rounded-xl font-semibold hover:bg-blue-700 transition-all shadow-lg shadow-blue-100 flex items-center gap-2">
                <i data-lucide="plus" class="w-5 h-5"></i>
                Nueva Plantilla
            </button>
        </div>

        <!-- Templates Grid -->
        <?php if (empty($templates)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-12 text-center">
                <i data-lucide="file-plus" class="w-16 h-16 mx-auto mb-4 text-slate-300"></i>
                <h3 class="text-xl font-bold text-slate-700 mb-2">No hay plantillas creadas</h3>
                <p class="text-slate-500 mb-6">Crea tu primera plantilla de entrenamiento para empezar</p>
                <button onclick="openTemplateModal()"
                    class="bg-blue-600 text-white px-6 py-3 rounded-xl font-semibold hover:bg-blue-700 transition-all">
                    Crear Primera Plantilla
                </button>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($templates as $template): ?>
                    <?php
                    $typeColors = [
                        'Intervalos' => ['bg-blue-500', 'bg-blue-50', 'border-blue-200'],
                        'Series' => ['bg-red-500', 'bg-red-50', 'border-red-200'],
                        'Fondo' => ['bg-green-500', 'bg-green-50', 'border-green-200'],
                        'Tempo' => ['bg-purple-500', 'bg-purple-50', 'border-purple-200'],
                        'Descanso' => ['bg-slate-400', 'bg-slate-50', 'border-slate-200'],
                        'Recuperación' => ['bg-orange-500', 'bg-orange-50', 'border-orange-200']
                    ];
                    $colors = $typeColors[$template['type']] ?? ['bg-slate-500', 'bg-slate-50', 'border-slate-200'];
                    ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-md transition-all">
                        <div class="flex items-start justify-between mb-3">
                            <span class="px-3 py-1 <?php echo $colors[0]; ?> text-white text-xs font-bold rounded-lg">
                                <?php echo htmlspecialchars($template['type']); ?>
                            </span>
                            <div class="flex gap-1">
                                <button onclick='openEditModal(<?php echo json_encode($template); ?>)'
                                    class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all"
                                    title="Editar">
                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                </button>
                                <button onclick="deleteTemplate(<?php echo $template['id']; ?>)"
                                    class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all"
                                    title="Eliminar">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                        <h3 class="font-bold text-slate-900 text-lg mb-1"><?php echo htmlspecialchars($template['name']); ?></h3>
                        <?php if ($template['block_type']): ?>
                            <span
                                class="text-xs font-medium px-2 py-1 <?php echo $colors[1]; ?> <?php echo $colors[2]; ?> border rounded-full">
                                Bloque: <?php echo htmlspecialchars($template['block_type']); ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($template['structure']): ?>
                            <p class="text-slate-500 text-sm mt-3 line-clamp-2">
                                <?php echo htmlspecialchars(substr($template['structure'], 0, 120)); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Template Create/Edit Modal -->
    <div id="templateModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto m-4">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                <h3 class="text-xl font-bold text-slate-900" id="templateModalTitle">Nueva Plantilla</h3>
                <button onclick="closeTemplateModal()" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            <form method="POST" class="p-6 space-y-5">
                <input type="hidden" name="action" id="templateAction" value="create_template">
                <input type="hidden" name="template_id" id="templateId" value="">

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Nombre de la Plantilla</label>
                    <input type="text" name="name" id="templateName" required placeholder="Ej: Intervalos 5x1000m"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Tipo</label>
                        <select name="type" id="templateType" required
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
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
                        <select name="block_type" id="templateBlock"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="">Sin bloque</option>
                            <option value="Base">Base</option>
                            <option value="Construcción">Construcción</option>
                            <option value="Pico">Pico</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Estructura / Instrucciones</label>
                    <textarea name="structure" id="templateStructure" rows="4"
                        placeholder="Describe la estructura del entrenamiento..."
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none"></textarea>
                </div>

                <button type="submit"
                    class="w-full bg-blue-600 text-white py-3 rounded-xl font-bold hover:bg-blue-700 transition-all">
                    <span id="templateSubmitText">Crear Plantilla</span>
                </button>
            </form>
        </div>
    </div>

    <script>
        function openTemplateModal() {
            document.getElementById('templateModalTitle').textContent = 'Nueva Plantilla';
            document.getElementById('templateAction').value = 'create_template';
            document.getElementById('templateId').value = '';
            document.getElementById('templateName').value = '';
            document.getElementById('templateType').value = 'Intervalos';
            document.getElementById('templateBlock').value = '';
            document.getElementById('templateStructure').value = '';
            document.getElementById('templateSubmitText').textContent = 'Crear Plantilla';
            document.getElementById('templateModal').classList.remove('hidden');
            document.getElementById('templateModal').classList.add('flex');
        }

        function openEditModal(template) {
            document.getElementById('templateModalTitle').textContent = 'Editar Plantilla';
            document.getElementById('templateAction').value = 'update_template';
            document.getElementById('templateId').value = template.id;
            document.getElementById('templateName').value = template.name;
            document.getElementById('templateType').value = template.type;
            document.getElementById('templateBlock').value = template.block_type || '';
            document.getElementById('templateStructure').value = template.structure || '';
            document.getElementById('templateSubmitText').textContent = 'Guardar Cambios';
            document.getElementById('templateModal').classList.remove('hidden');
            document.getElementById('templateModal').classList.add('flex');
        }

        function closeTemplateModal() {
            document.getElementById('templateModal').classList.add('hidden');
            document.getElementById('templateModal').classList.remove('flex');
        }

        function deleteTemplate(id) {
            if (confirm('¿Estás seguro de eliminar esta plantilla?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="action" value="delete_template"><input type="hidden" name="template_id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
<?php endif; ?>

<?php include 'views/layout/footer.php'; ?>
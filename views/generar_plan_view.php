<!-- Tab Navigation -->
<div
    style="display: flex; gap: 0.5rem; margin-bottom: 2rem; background: var(--bg-card); padding: 0.5rem; border-radius: 12px; border: 1px solid var(--border); width: fit-content;">
    <a href="generar_plan.php?tab=plan" style="text-decoration: none;"
        class="btn <?php echo $activeTab === 'plan' ? 'btn-primary' : 'btn-secondary'; ?>">
        <i data-lucide="calendar-range" style="width: 18px; height: 18px; margin-right: 8px;"></i> Asignar Plan
    </a>
    <a href="generar_plan.php?tab=plantillas" style="text-decoration: none;"
        class="btn <?php echo $activeTab === 'plantillas' ? 'btn-primary' : 'btn-secondary'; ?>">
        <i data-lucide="layers" style="width: 18px; height: 18px; margin-right: 8px;"></i> Biblioteca
        <span
            style="margin-left: 8px; font-size: 0.75rem; opacity: 0.7; background: rgba(0,0,0,0.1); padding: 2px 8px; border-radius: 99px;">
            <?php echo count($templates); ?>
        </span>
    </a>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="card"
        style="border-color: var(--primary); background: rgba(13, 242, 128, 0.05); color: var(--text-main); margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem;">
        <i data-lucide="check-circle" style="color: var(--primary);"></i>
        <?php
        $msgs = ['plan' => 'Plan semanal generado con éxito', 'template_created' => 'Plantilla guardada', 'template_updated' => 'Plantilla actualizada', 'template_deleted' => 'Plantilla eliminada'];
        echo $msgs[$_GET['success']] ?? 'Operación completada';
        ?>
    </div>
<?php endif; ?>

<?php if ($activeTab === 'plan'): ?>
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
        <!-- Assignment Form -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <div class="card">
                <h3
                    style="font-size: 1.125rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i data-lucide="user-check" style="color: var(--primary);"></i> Selección de Atleta
                </h3>

                <form method="POST" id="planForm">
                    <input type="hidden" name="action" value="generate_plan">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1rem;">
                        <div>
                            <label
                                style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem;">ATLETA</label>
                            <select name="athlete_id" required
                                style="width: 100%; padding: 0.75rem; border-radius: var(--card-radius); border: 1px solid var(--border); background: var(--bg-main); color: var(--text-main); font-family: inherit;">
                                <option value="">Elegir corredor...</option>
                                <?php foreach ($athletes as $atlet): ?>
                                    <option value="<?php echo $atlet['id']; ?>"><?php echo htmlspecialchars($atlet['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem;">SEMANA
                                INICIO</label>
                            <input type="date" name="week_start" required
                                value="<?php echo date('Y-m-d', strtotime('next monday')); ?>"
                                style="width: 100%; padding: 0.75rem; border-radius: var(--card-radius); border: 1px solid var(--border); background: var(--bg-main); color: var(--text-main); font-family: inherit;">
                        </div>
                    </div>
            </div>

            <div class="card">
                <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 1.5rem;">Cronograma Semanal</h3>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php
                    $days = ['lunes' => 'Lunes', 'martes' => 'Martes', 'miercoles' => 'Miércoles', 'jueves' => 'Jueves', 'viernes' => 'Viernes', 'sabado' => 'Sábado', 'domingo' => 'Domingo'];
                    foreach ($days as $key => $label):
                        ?>
                        <div style="background: var(--bg-main); border-radius: 12px; padding: 1.25rem;">
                            <div style="display: flex; align-items: center; gap: 1.5rem;">
                                <div style="width: 100px;">
                                    <span style="font-weight: 700; color: var(--text-main);"><?php echo $label; ?></span>
                                </div>
                                <div style="flex: 1;">
                                    <select name="template_<?php echo $key; ?>" id="select_<?php echo $key; ?>"
                                        onchange="updateStructure('<?php echo $key; ?>')"
                                        style="width: 100%; padding: 0.625rem; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-card); color: var(--text-main); font-size: 0.9rem;">
                                        <option value="">-- Sin entrenamiento --</option>
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
                                            <option value="<?php echo $template['id']; ?>"
                                                data-structure="<?php echo htmlspecialchars($template['structure'] ?? ''); ?>">
                                                <?php echo htmlspecialchars($template['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="button" onclick="toggleEdit('<?php echo $key; ?>')"
                                    id="btn_edit_<?php echo $key; ?>"
                                    style="display: none; background: none; border: none; color: var(--primary); cursor: pointer; padding: 0.5rem;">
                                    <i data-lucide="pencil-line" style="width: 18px; height: 18px;"></i>
                                </button>
                            </div>
                            <div id="editor_<?php echo $key; ?>"
                                style="display: none; margin-top: 1rem; border-top: 1px dashed var(--border); padding-top: 1rem;">
                                <textarea name="structure_<?php echo $key; ?>" id="textarea_<?php echo $key; ?>" rows="3"
                                    style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-card); color: var(--text-main); font-size: 0.85rem; font-family: inherit;"
                                    placeholder="Instrucciones específicas..."></textarea>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="margin-top: 2rem; border-top: 1px solid var(--border); padding-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary"
                        style="width: 100%; padding: 1.25rem; font-size: 1.1rem; box-shadow: 0 10px 15px -3px rgba(13, 242, 128, 0.2);">
                        Publicar Plan Semanal
                    </button>
                </div>
                </form>
            </div>
        </div>

        <!-- Sidebar Activity -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <div class="card" style="position: sticky; top: 1rem;">
                <h4
                    style="font-weight: 700; margin-bottom: 1rem; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
                    BIBLIOTECA RÁPIDA</h4>
                <div
                    style="display: flex; flex-direction: column; gap: 0.75rem; max-height: 60vh; overflow-y: auto; padding-right: 0.5rem;">
                    <?php foreach ($templates as $t): ?>
                        <div
                            style="padding: 0.75rem; border-radius: 8px; background: var(--bg-main); border: 1px solid var(--border);">
                            <span
                                style="font-size: 0.7rem; font-weight: 700; color: var(--primary); text-transform: uppercase;"><?php echo htmlspecialchars($t['type']); ?></span>
                            <p style="font-size: 0.875rem; font-weight: 600; margin: 2px 0;">
                                <?php echo htmlspecialchars($t['name']); ?></p>
                            <?php if ($t['block_type']): ?>
                                <span class="badge"
                                    style="padding: 1px 6px; font-size: 0.65rem; background: rgba(0,0,0,0.05); color: var(--text-muted);"><?php echo $t['block_type']; ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- ========== TAB: MIS PLANTILLAS ========== -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 1.5rem; font-weight: 800;">Biblioteca de Entrenamientos</h3>
            <button onclick="openTemplateModal()" class="btn btn-primary">
                <i data-lucide="plus" style="width: 18px; height: 18px; margin-right: 8px;"></i> Nueva sesión
            </button>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
            <?php foreach ($templates as $template): ?>
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                        <span class="badge badge-emerald"><?php echo htmlspecialchars($template['type']); ?></span>
                        <div style="display: flex; gap: 0.25rem;">
                            <button onclick='openEditModal(<?php echo json_encode($template); ?>)'
                                style="background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 4px;"><i
                                    data-lucide="edit-2" style="width: 16px; height: 16px;"></i></button>
                            <button onclick="deleteTemplate(<?php echo $template['id']; ?>)"
                                style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 4px;"><i
                                    data-lucide="trash-2" style="width: 16px; height: 16px;"></i></button>
                        </div>
                    </div>
                    <h4 style="font-weight: 700; font-size: 1.1rem; margin-bottom: 0.5rem;">
                        <?php echo htmlspecialchars($template['name']); ?></h4>
                    <p
                        style="color: var(--text-muted); font-size: 0.875rem; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                        <?php echo htmlspecialchars($template['structure']); ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal implementation remain similar but with Stitch styles -->
    <div id="templateModal"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 100; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="width: 100%; max-width: 600px; margin: 1rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h3 id="templateModalTitle" style="font-size: 1.5rem; font-weight: 800;">Nueva Plantilla</h3>
                <button onclick="closeTemplateModal()"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer;"><i
                        data-lucide="x"></i></button>
            </div>

            <form method="POST" style="display: flex; flex-direction: column; gap: 1.25rem;">
                <input type="hidden" name="action" id="templateAction" value="create_template">
                <input type="hidden" name="template_id" id="templateId" value="">

                <div>
                    <label
                        style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem;">NOMBRE</label>
                    <input type="text" name="name" id="templateName" required
                        style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-main); color: var(--text-main); font-family: inherit;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label
                            style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem;">TIPO</label>
                        <select name="type" id="templateType" required
                            style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-main); color: var(--text-main); font-family: inherit;">
                            <option value="Intervalos">Intervalos</option>
                            <option value="Series">Series</option>
                            <option value="Fondo">Fondo</option>
                            <option value="Tempo">Tempo</option>
                            <option value="Descanso">Descanso</option>
                        </select>
                    </div>
                    <div>
                        <label
                            style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem;">BLOQUE</label>
                        <select name="block_type" id="templateBlock"
                            style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-main); color: var(--text-main); font-family: inherit;">
                            <option value="">Sin bloque</option>
                            <option value="Base">Base</option>
                            <option value="Construcción">Construcción</option>
                            <option value="Pico">Pico</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label
                        style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem;">ESTRUCTURA</label>
                    <textarea name="structure" id="templateStructure" rows="6"
                        style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-main); color: var(--text-main); font-family: inherit; resize: vertical;"></textarea>
                </div>

                <button type="submit" class="btn btn-primary" id="templateSubmitText" style="padding: 1rem;">Guardar
                    Sesión</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
    function updateStructure(day) {
        const select = document.getElementById('select_' + day);
        const editor = document.getElementById('editor_' + day);
        const textarea = document.getElementById('textarea_' + day);
        const btnEdit = document.getElementById('btn_edit_' + day);

        const option = select.options[select.selectedIndex];
        const structure = option.getAttribute('data-structure');

        if (select.value) {
            textarea.value = structure || '';
            btnEdit.style.display = 'block';
        } else {
            textarea.value = '';
            btnEdit.style.display = 'none';
            editor.style.display = 'none';
        }
    }

    function toggleEdit(day) {
        const editor = document.getElementById('editor_' + day);
        editor.style.display = (editor.style.display === 'none') ? 'block' : 'none';
    }

    function openTemplateModal() {
        document.getElementById('templateModalTitle').textContent = 'Nueva Plantilla';
        document.getElementById('templateAction').value = 'create_template';
        document.getElementById('templateId').value = '';
        document.getElementById('templateName').value = '';
        document.getElementById('templateType').value = 'Intervalos';
        document.getElementById('templateBlock').value = '';
        document.getElementById('templateStructure').value = '';
        document.getElementById('templateSubmitText').textContent = 'Crear Plantilla';
        document.getElementById('templateModal').style.display = 'flex';
        lucide.createIcons();
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
        document.getElementById('templateModal').style.display = 'flex';
        lucide.createIcons();
    }

    function closeTemplateModal() {
        document.getElementById('templateModal').style.display = 'none';
    }

    function deleteTemplate(id) {
        if (confirm('¿Eliminar esta plantilla permanentemente?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input type="hidden" name="action" value="delete_template"><input type="hidden" name="template_id" value="${id}">`;
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>
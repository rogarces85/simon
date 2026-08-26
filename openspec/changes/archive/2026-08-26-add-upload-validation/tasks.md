## 1. Helper de validación

- [x] 1.1 Crear `includes/FileUpload.php` con `FileUpload::validate($file, array $allowedExtensions, int $maxBytes = 5242880)` (error de subida, tamaño, extensión, MIME real vía `finfo`) y verificar `php -l`

## 2. Aplicar el helper en los 3 puntos de subida

- [x] 2.1 `mi_plan.php`: validar `$_FILES['evidence']` con el helper antes de guardar `evidence_path`
- [x] 2.2 `perfil.php`: reemplazar la validación de solo-extensión del avatar por el helper
- [x] 2.3 `config_team.php`: reemplazar la validación de solo-extensión del logo por el helper
- [x] 2.4 `php -l` sobre los 3 archivos

## 3. Verificación

- [x] 3.1 Grep de `pathinfo(...PATHINFO_EXTENSION)` en los 3 archivos para confirmar que la validación pasa por el helper y no quedó lógica duplicada suelta
- [x] 3.2 Actualizar la descripción de los campos `evidence`/`avatar`/`logo` en `openapi.yaml` para reflejar la validación de tipo MIME real

## Why

Los 3 puntos de subida de archivos del sistema (evidencia de entrenamiento en `mi_plan.php`, avatar en `perfil.php`, logo de team en `config_team.php`) tienen validación inconsistente: `mi_plan.php` no valida absolutamente nada (ni extensión, ni tipo, ni tamaño); `perfil.php` y `config_team.php` solo validan la extensión del nombre de archivo, que un atacante puede falsificar fácilmente subiendo un archivo malicioso con extensión `.jpg`.

## What Changes

- Nuevo helper `includes/FileUpload.php` (`FileUpload::validate($file, $allowedExtensions, $maxBytes)`) que verifica: error de subida, tamaño máximo (5MB), extensión permitida, y tipo MIME real vía `finfo` (no el `Content-Type` declarado por el navegador).
- `mi_plan.php` (evidencia), `perfil.php` (avatar) y `config_team.php` (logo) usan el mismo helper con [jpg, jpeg, png, webp] y 5MB de límite.

## Capabilities

### New Capabilities
(ninguna — se agrega al Requirement de protección CSRF/seguridad transversal ya creado por `add-csrf-protection`)

### Modified Capabilities
- `security`: se agrega el requisito de validación de archivos subidos (nuevo Requirement dentro de la capacidad `security` ya existente).
- `workout-tracking`: el requisito de completar un entrenamiento pasa a validar la evidencia fotográfica antes de guardarla.
- `profile-team`: los requisitos de edición de perfil y configuración de team pasan a validar el tipo MIME real del avatar/logo, no solo la extensión.

## Impact

- Afecta: `includes/FileUpload.php` (nuevo), `mi_plan.php`, `perfil.php`, `config_team.php`.
- `openapi.yaml` no necesita cambios de forma (los campos `evidence`/`avatar`/`logo` ya estaban documentados como `format: binary`); se actualiza solo la descripción para reflejar la validación real.

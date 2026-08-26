## Why

El repositorio remoto `rogarces85/simon` en GitHub es público y `config/config.php` —con las credenciales reales de la base de datos de producción en texto plano (host, usuario y contraseña)— está versionado a propósito (el propio `.gitignore` trae la línea comentada: "config/config.php (Removed to allow deployment via GitHub)"). Las credenciales están expuestas públicamente ahora mismo. Esto no cambia ningún comportamiento observable del sistema para sus usuarios (`skip_specs: true`): es un cambio de infraestructura/configuración, no de negocio.

## What Changes

- `config/config.php` deja de contener valores hardcodeados: pasa a leer `config/config.local.php` si existe (no versionado), con fallback a variables de entorno.
- Se añade `config/config.example.php` como plantilla versionada, sin secretos reales.
- `.gitignore` ignora de verdad `config/config.local.php` (se quita el comentario engañoso sobre `config/config.php`).
- **BREAKING** (solo para el proceso de deploy, no para usuarios finales): el primer deploy en un servidor nuevo requiere crear manualmente `config/config.local.php` con los valores reales, ya que git push ya no los trae.

## Capabilities

(sin capacidades de negocio afectadas — cambio de infraestructura pura)

## Impact

- Afecta: `config/config.php`, `.gitignore`, `config/config.example.php` (nuevo).
- Requiere una acción manual del usuario fuera de este repo: rotar la contraseña de la base de datos en el panel de Hostinger (las credenciales actuales quedan invalidadas independientemente de este cambio, porque ya estuvieron expuestas en el historial de git).
- Requiere crear `config/config.local.php` en el servidor de producción la próxima vez que se despliegue.

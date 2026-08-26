## Why

Ninguno de los formularios POST del sistema tiene protección CSRF. Un atacante podría, mediante un sitio malicioso, inducir al navegador de un usuario autenticado a enviar una petición POST no deseada (crear/editar/borrar un atleta, generar un plan, responder feedback, cambiar la contraseña de perfil, etc.) aprovechando su sesión activa.

## What Changes

- Nuevo helper `includes/Csrf.php` (`Csrf::token()`, `Csrf::verify()`, `Csrf::field()`) basado en un token aleatorio guardado en sesión.
- Cada `<form method="POST">` del sistema incluye un campo oculto `csrf_token`.
- Cada bloque `if ($_SERVER['REQUEST_METHOD'] === 'POST')` valida el token antes de ejecutar cualquier acción; si no es válido, la petición se rechaza sin aplicar ningún cambio.
- Los formularios generados dinámicamente por JavaScript (modales de `entrenamientos.php` y `mi_plan.php`) reciben el token vía una constante JS renderizada por PHP.
- **BREAKING** para integraciones externas hipotéticas que hoy enviaran POST directo sin pasar por el HTML del sistema (no existe ninguna conocida: no hay API pública ni clientes externos documentados).

## Capabilities

### New Capabilities
- `security`: comportamientos de seguridad transversales a todo el sistema (empieza con protección CSRF; `add-login-throttling` y `add-upload-validation` la ampliarán).

## Impact

- Afecta: `includes/Csrf.php` (nuevo), y el `<form>`/bloque POST de `login.php`, `crear_entrenador.php`, `atletas.php`, `generar_plan.php`, `entrenamientos.php`, `mi_plan.php`, `notificaciones.php`, `perfil.php`, `config_team.php`.
- Afecta el contrato HTTP: `openapi.yaml` debe actualizarse para incluir `csrf_token` como campo requerido en cada `requestBody` de formulario POST, y documentar la respuesta de rechazo.

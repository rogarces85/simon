## Why

`login.php` no tiene ningún límite de intentos: un atacante puede probar contraseñas indefinidamente contra cualquier `username` conocido (fuerza bruta / credential stuffing) sin que el sistema reaccione.

## What Changes

- Nueva tabla `login_attempts` (username, ip_address, created_at) para registrar intentos fallidos.
- `Auth::isLoginLocked($username)` bloquea el login si hay 5 o más intentos fallidos para ese `username` en los últimos 15 minutos.
- `Auth::login()` registra cada intento fallido y limpia el historial de ese `username` al iniciar sesión con éxito.
- `login.php` verifica el bloqueo antes de intentar autenticar y muestra un mensaje específico.

## Capabilities

### Modified Capabilities
- `auth`: el requisito de inicio de sesión pasa a bloquear temporalmente los intentos tras 5 fallos consecutivos en 15 minutos para el mismo `username`.

## Impact

- Afecta: `includes/auth.php`, `login.php`, `scripts/setup.php` (nueva tabla), nuevo `scripts/migrate_login_attempts.php` para bases de datos ya existentes.
- No verificable end-to-end contra la base de datos de producción real desde este entorno (misma limitación de conectividad ya documentada en `secure-credentials` y `align-workout-schema`).
- `openapi.yaml` se actualiza para documentar la respuesta de bloqueo en `POST /login.php`.

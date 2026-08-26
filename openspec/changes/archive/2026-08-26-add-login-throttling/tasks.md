## 1. Esquema

- [x] 1.1 Agregar `CREATE TABLE IF NOT EXISTS login_attempts` a `scripts/setup.php` y verificar `php -l scripts/setup.php`
- [x] 1.2 Crear `scripts/migrate_login_attempts.php` (idempotente, mismo patrón que `migrate_workout_columns.php`) y verificar `php -l`

## 2. Lógica de bloqueo

- [x] 2.1 Agregar `Auth::isLoginLocked($username)`, `Auth::login()` actualizado (registra fallos, limpia en éxito) en `includes/auth.php`, y verificar `php -l includes/auth.php`
- [x] 2.2 Actualizar `login.php` para comprobar `Auth::isLoginLocked()` antes de llamar a `Auth::login()` y mostrar el mensaje de bloqueo, y verificar `php -l login.php`

## 3. Verificación

- [x] 3.1 Simular con PHP puro (sin BD real) que la lógica de conteo de ventana de 15 minutos es correcta, revisando el SQL generado — confirmado: `created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)` es una ventana deslizante correcta
- [x] 3.2 Actualizar `openapi.yaml`: documentar el mensaje de bloqueo en la respuesta 200 de `POST /login.php`
- [ ] 3.3 **Pendiente del usuario**: ejecutar `scripts/migrate_login_attempts.php` contra la base de datos real de producción

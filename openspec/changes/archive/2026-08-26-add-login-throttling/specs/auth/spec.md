## MODIFIED Requirements

### Requirement: Inicio de sesión por credenciales
El sistema SHALL autenticar a un usuario verificando `username` y `password` contra la tabla `users` con `password_verify`, y SHALL crear una sesión con `user_id`, `role` y `name` cuando las credenciales son correctas. El sistema SHALL bloquear temporalmente los intentos de login para un `username` que acumule 5 o más intentos fallidos en los últimos 15 minutos, y SHALL limpiar ese historial de fallos al iniciar sesión con éxito.

#### Scenario: Credenciales correctas
- **WHEN** un usuario envía un `username` y `password` que coinciden con un registro de `users`, y no está bloqueado por intentos previos
- **THEN** el sistema crea la sesión con su `user_id`, `role` y `name`, limpia el historial de intentos fallidos de ese `username`, y redirige a `dashboard.php`

#### Scenario: Credenciales incorrectas
- **WHEN** un usuario envía un `username`/`password` que no coincide con ningún registro o cuyo hash no verifica, y no está bloqueado
- **THEN** el sistema registra el intento fallido, no crea sesión, y vuelve a mostrar el formulario de login con el mensaje "Credenciales inválidas"

#### Scenario: Bloqueo por intentos fallidos
- **WHEN** un `username` acumula 5 intentos fallidos en los últimos 15 minutos y llega un nuevo intento (con cualquier `password`)
- **THEN** el sistema rechaza el intento sin verificar la contraseña y muestra un mensaje indicando que debe esperar antes de reintentar

#### Scenario: El bloqueo expira
- **WHEN** pasan más de 15 minutos desde el último intento fallido contabilizado dentro de la ventana de bloqueo
- **THEN** ese intento deja de contar para el límite y el usuario puede volver a intentar normalmente

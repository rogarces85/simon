## Purpose

Autenticar usuarios contra la sesión PHP nativa y controlar qué rol (admin, coach o athlete) puede acceder a cada página del sistema, sin jerarquía entre roles.

## ADDED Requirements

### Requirement: Inicio de sesión por credenciales
El sistema SHALL autenticar a un usuario verificando `username` y `password` contra la tabla `users` con `password_verify`, y SHALL crear una sesión con `user_id`, `role` y `name` cuando las credenciales son correctas.

#### Scenario: Credenciales correctas
- **WHEN** un usuario envía un `username` y `password` que coinciden con un registro de `users`
- **THEN** el sistema crea la sesión con su `user_id`, `role` y `name`, y redirige a `dashboard.php`

#### Scenario: Credenciales incorrectas
- **WHEN** un usuario envía un `username`/`password` que no coincide con ningún registro o cuyo hash no verifica
- **THEN** el sistema no crea sesión y vuelve a mostrar el formulario de login con el mensaje "Credenciales inválidas"

### Requirement: Redirección si ya hay sesión activa
El sistema SHALL redirigir a `dashboard.php` a cualquier usuario con sesión activa que intente acceder a `login.php` o `index.php`.

#### Scenario: Usuario ya autenticado visita login
- **WHEN** un usuario con sesión activa hace GET a `login.php`
- **THEN** el sistema lo redirige a `dashboard.php` sin mostrar el formulario

### Requirement: Cierre de sesión
El sistema SHALL destruir la sesión activa y redirigir a `login.php` cuando el usuario visita `logout.php`.

#### Scenario: Logout
- **WHEN** un usuario con sesión activa hace GET a `logout.php`
- **THEN** la sesión se destruye y el usuario es redirigido a `login.php`

### Requirement: Control de acceso por rol exacto
El sistema SHALL exigir coincidencia exacta de rol en cada página protegida (`Auth::requireRole`), sin jerarquía entre `admin`, `coach` y `athlete`: un rol no hereda los permisos de otro.

#### Scenario: Rol no coincide
- **WHEN** un usuario autenticado con `role=coach` intenta acceder a una página que exige `role=admin`
- **THEN** el sistema lo redirige a `login.php` sin ejecutar la lógica de la página

#### Scenario: Sin sesión activa
- **WHEN** un usuario sin sesión activa intenta acceder a cualquier página protegida
- **THEN** el sistema lo redirige a `login.php`

### Requirement: Control de acceso multi-rol
El sistema SHALL permitir declarar páginas accesibles por más de un rol (`Auth::requireRoleLike`), donde basta que el rol del usuario esté en la lista permitida.

#### Scenario: Rol incluido en la lista
- **WHEN** un usuario autenticado con `role=athlete` accede a una página que permite `[admin, coach, athlete]`
- **THEN** el sistema permite el acceso

## MODIFIED Requirements

### Requirement: Edición de perfil propio
El sistema SHALL permitir a cualquier usuario autenticado actualizar su `name`, `email`, `password` (opcional) y `avatar_url` (imagen opcional). SHALL rechazar el cambio de contraseña si `password` y `confirm_password` no coinciden. SHALL reflejar el `name` actualizado en la sesión activa inmediatamente, sin requerir un nuevo inicio de sesión.

#### Scenario: Contraseñas no coinciden
- **WHEN** el usuario envía `password` y `confirm_password` con valores distintos
- **THEN** el sistema no aplica ningún cambio y muestra "Las contraseñas no coinciden."

#### Scenario: Avatar con extensión no permitida
- **WHEN** el usuario sube un archivo de avatar cuya extensión no está en [jpg, jpeg, png, webp]
- **THEN** el sistema no reemplaza el avatar y conserva el anterior

#### Scenario: Nombre actualizado se refleja sin re-login
- **WHEN** el usuario actualiza su `name` y luego navega a cualquier otra página protegida en la misma sesión
- **THEN** el sistema muestra el nombre nuevo, sin exigir que el usuario cierre e inicie sesión de nuevo

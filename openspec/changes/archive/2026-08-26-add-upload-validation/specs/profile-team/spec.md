## MODIFIED Requirements

### Requirement: Edición de perfil propio
El sistema SHALL permitir a cualquier usuario autenticado actualizar su `name`, `email`, `password` (opcional) y `avatar_url` (imagen opcional). SHALL rechazar el cambio de contraseña si `password` y `confirm_password` no coinciden. SHALL reflejar el `name` actualizado en la sesión activa inmediatamente, sin requerir un nuevo inicio de sesión. SHALL validar el avatar (extensión, tamaño máximo y tipo MIME real) antes de reemplazarlo.

#### Scenario: Contraseñas no coinciden
- **WHEN** el usuario envía `password` y `confirm_password` con valores distintos
- **THEN** el sistema no aplica ningún cambio y muestra "Las contraseñas no coinciden."

#### Scenario: Avatar con extensión no permitida
- **WHEN** el usuario sube un archivo de avatar cuya extensión no está en [jpg, jpeg, png, webp]
- **THEN** el sistema no reemplaza el avatar y conserva el anterior

#### Scenario: Avatar con tipo MIME falsificado
- **WHEN** el usuario sube un archivo con extensión permitida pero cuyo contenido real no corresponde a una imagen de ese tipo
- **THEN** el sistema no reemplaza el avatar y conserva el anterior

#### Scenario: Nombre actualizado se refleja sin re-login
- **WHEN** el usuario actualiza su `name` y luego navega a cualquier otra página protegida en la misma sesión
- **THEN** el sistema muestra el nombre nuevo, sin exigir que el usuario cierre e inicie sesión de nuevo

### Requirement: Configuración del team por el coach
El sistema SHALL permitir a un coach crear o actualizar (relación 1:1) el `Team` asociado a su `coach_id`, con `name`, `primary_color` y `logo` opcional. SHALL validar el logo (extensión, tamaño máximo y tipo MIME real) antes de reemplazarlo.

#### Scenario: Primer team del coach
- **WHEN** un coach sin `Team` previo envía el formulario de configuración
- **THEN** el sistema crea un nuevo `Team` asociado a su `coach_id`

#### Scenario: Team ya existente
- **WHEN** un coach con `Team` previo envía el formulario de configuración
- **THEN** el sistema actualiza el `Team` existente en vez de crear uno nuevo

#### Scenario: Logo con extensión no permitida
- **WHEN** el coach sube un logo cuya extensión no está en [jpg, jpeg, png, webp]
- **THEN** el sistema no reemplaza el logo, conserva el anterior y muestra un mensaje de error

#### Scenario: Logo con tipo MIME falsificado
- **WHEN** el coach sube un logo con extensión permitida pero cuyo contenido real no corresponde a una imagen de ese tipo
- **THEN** el sistema no reemplaza el logo, conserva el anterior y muestra un mensaje de error

## Purpose

Permitir que cualquier usuario mantenga sus propios datos de cuenta y que un coach configure la identidad visual (nombre, logo, color) de su equipo.

## ADDED Requirements

### Requirement: Edición de perfil propio
El sistema SHALL permitir a cualquier usuario autenticado actualizar su `name`, `email`, `password` (opcional) y `avatar_url` (imagen opcional). SHALL rechazar el cambio de contraseña si `password` y `confirm_password` no coinciden.

#### Scenario: Contraseñas no coinciden
- **WHEN** el usuario envía `password` y `confirm_password` con valores distintos
- **THEN** el sistema no aplica ningún cambio y muestra "Las contraseñas no coinciden."

#### Scenario: Avatar con extensión no permitida
- **WHEN** el usuario sube un archivo de avatar cuya extensión no está en [jpg, jpeg, png, webp]
- **THEN** el sistema no reemplaza el avatar y conserva el anterior

### Requirement: Configuración del team por el coach
El sistema SHALL permitir a un coach crear o actualizar (relación 1:1) el `Team` asociado a su `coach_id`, con `name`, `primary_color` y `logo` opcional.

#### Scenario: Primer team del coach
- **WHEN** un coach sin `Team` previo envía el formulario de configuración
- **THEN** el sistema crea un nuevo `Team` asociado a su `coach_id`

#### Scenario: Team ya existente
- **WHEN** un coach con `Team` previo envía el formulario de configuración
- **THEN** el sistema actualiza el `Team` existente en vez de crear uno nuevo

#### Scenario: Logo con extensión no permitida
- **WHEN** el coach sube un logo cuya extensión no está en [jpg, jpeg, png, webp]
- **THEN** el sistema no reemplaza el logo, conserva el anterior y muestra un mensaje de error

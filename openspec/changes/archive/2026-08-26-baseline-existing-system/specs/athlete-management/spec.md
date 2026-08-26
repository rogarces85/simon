## Purpose

Permitir que un administrador dé de alta entrenadores, y que cada entrenador gestione (alta, edición, baja) el ciclo de vida de sus propios atletas.

## ADDED Requirements

### Requirement: Alta de entrenador por el admin
El sistema SHALL permitir a un usuario con `role=admin` crear un nuevo usuario con `role=coach`, exigiendo `name`, `email` y `password`, y SHALL rechazar el alta si el `email` ya existe como `username` en `users`.

#### Scenario: Alta exitosa
- **WHEN** el admin envía `name`, `email` y `password` válidos, y el `email` no existe aún en `users`
- **THEN** el sistema crea un `User` con `role=coach` y muestra un mensaje de éxito

#### Scenario: Email duplicado
- **WHEN** el admin envía un `email` que ya existe como `username` en `users`
- **THEN** el sistema no crea el usuario y muestra el error "El correo electrónico ya está registrado."

#### Scenario: Campos incompletos
- **WHEN** el admin envía el formulario sin `name`, `email` o `password`
- **THEN** el sistema no crea el usuario y muestra el error "Todos los campos son obligatorios."

### Requirement: Alta de atleta por el coach
El sistema SHALL permitir a un usuario con `role=coach` crear un nuevo usuario con `role=athlete` vinculado a él mismo mediante `coach_id`, aplicando los valores por defecto `level=Principiante`, `preferred_long_run_day=Domingo`, `max_time_per_session=60` cuando no se especifican.

#### Scenario: Alta exitosa de atleta
- **WHEN** el coach envía `name`, `email` y `password` para un nuevo atleta
- **THEN** el sistema crea un `User` con `role=athlete` y `coach_id` igual al del coach autenticado

### Requirement: Un atleta pertenece a un único coach
El sistema SHALL fijar el `coach_id` de un atleta en el momento de su creación y SHALL no ofrecer ningún flujo para reasignarlo a otro coach.

#### Scenario: El coach solo ve sus propios atletas
- **WHEN** un coach solicita el listado de atletas
- **THEN** el sistema devuelve únicamente los usuarios con `role=athlete` y `coach_id` igual al del coach autenticado

### Requirement: Edición de atleta por su coach
El sistema SHALL permitir a un coach actualizar los datos de un atleta propio, y SHALL solo re-hashear y reemplazar la contraseña cuando se envía un valor no vacío y distinto del placeholder `"********"`.

#### Scenario: Edición sin cambiar contraseña
- **WHEN** el coach edita un atleta dejando el campo de contraseña vacío o con el placeholder
- **THEN** el sistema actualiza el resto de los campos y conserva la contraseña existente

### Requirement: Baja de atleta por su coach
El sistema SHALL permitir a un coach eliminar físicamente un registro de atleta mediante `athlete_id`.

#### Scenario: Eliminación de atleta
- **WHEN** el coach envía `action=delete_athlete` con un `athlete_id`
- **THEN** el sistema borra el registro de `users` correspondiente y redirige al listado con confirmación

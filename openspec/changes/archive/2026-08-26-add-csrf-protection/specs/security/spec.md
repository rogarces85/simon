## Purpose

Reunir los comportamientos de seguridad transversales del sistema (protección CSRF, límite de intentos de login, validación de archivos subidos) que no pertenecen a una única capacidad de negocio sino que aplican a varias de ellas por igual.

## ADDED Requirements

### Requirement: Protección CSRF en formularios POST
El sistema SHALL exigir un token CSRF válido, ligado a la sesión del usuario, en cada petición POST que modifique datos, y SHALL rechazar la petición sin aplicar ningún cambio si el token falta o no coincide con el de la sesión.

#### Scenario: Token válido
- **WHEN** un usuario autenticado envía un formulario POST incluyendo el `csrf_token` que el sistema le entregó al renderizar ese formulario
- **THEN** el sistema procesa la acción normalmente

#### Scenario: Token ausente o inválido
- **WHEN** se recibe una petición POST sin `csrf_token`, o con un valor que no coincide con el guardado en la sesión del usuario
- **THEN** el sistema rechaza la petición y no aplica ningún cambio en la base de datos

#### Scenario: Formularios generados dinámicamente
- **WHEN** el sistema genera un formulario POST mediante JavaScript (p. ej. el modal de completar entrenamiento o el de responder feedback)
- **THEN** ese formulario también incluye el `csrf_token` vigente de la sesión, obtenido de una variable renderizada por el servidor

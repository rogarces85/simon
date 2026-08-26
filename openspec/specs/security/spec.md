# security Specification

## Purpose
Reunir los comportamientos de seguridad transversales del sistema (protección CSRF, límite de intentos de login, validación de archivos subidos) que no pertenecen a una única capacidad de negocio sino que aplican a varias de ellas por igual.

## Requirements

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

### Requirement: Validación de archivos subidos
El sistema SHALL validar cualquier archivo subido por un usuario (evidencia de entrenamiento, avatar de perfil, logo de team) contra una lista de extensiones permitidas, un tamaño máximo de 5MB, y el tipo MIME real del contenido del archivo (no el declarado por el navegador), antes de guardarlo o de reemplazar el archivo anterior.

#### Scenario: Archivo válido
- **WHEN** un usuario sube una imagen jpg/jpeg/png/webp de menos de 5MB cuyo contenido real coincide con esa extensión
- **THEN** el sistema guarda el archivo y lo usa en lugar del anterior (si existía)

#### Scenario: Tipo MIME no coincide con la extensión
- **WHEN** un usuario sube un archivo cuyo nombre tiene extensión `.jpg` pero cuyo contenido real no es una imagen JPEG
- **THEN** el sistema rechaza el archivo y conserva el anterior

#### Scenario: Archivo demasiado grande
- **WHEN** un usuario sube un archivo de más de 5MB
- **THEN** el sistema rechaza el archivo y conserva el anterior

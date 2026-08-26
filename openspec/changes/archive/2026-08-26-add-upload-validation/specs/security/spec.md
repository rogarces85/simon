## ADDED Requirements

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

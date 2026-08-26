# training-plans Specification

## Purpose
Permitir que un coach mantenga una biblioteca de plantillas de sesiones de entrenamiento y las use para generar planes semanales personalizados para cada atleta.

## Requirements

### Requirement: Biblioteca de plantillas por coach
El sistema SHALL permitir a un coach crear, editar y eliminar plantillas de entrenamiento (`type`, `block_type` opcional, `structure` en texto libre) acotadas siempre a su propio `coach_id`.

#### Scenario: Crear plantilla
- **WHEN** el coach envía `name`, `type` y opcionalmente `block_type`/`structure`
- **THEN** el sistema crea la plantilla asociada a su `coach_id`

#### Scenario: Eliminar o editar plantilla ajena
- **WHEN** el coach intenta editar o eliminar un `template_id` que no le pertenece
- **THEN** el sistema no aplica el cambio, porque las consultas de actualización y borrado siempre filtran por `coach_id`

### Requirement: Generación de plan semanal
El sistema SHALL permitir a un coach generar un plan semanal para un atleta eligiendo, para cada uno de los 7 días, una plantilla opcional, y SHALL crear un entrenamiento (`status=pending`, `delivery_status=sent`) por cada día con plantilla seleccionada, copiando la `structure` de la plantilla como snapshot.

#### Scenario: Generación con varios días seleccionados
- **WHEN** el coach selecciona un atleta, una fecha de inicio de semana y plantillas para 3 de los 7 días
- **THEN** el sistema crea exactamente 3 entrenamientos nuevos, uno por cada día con plantilla, en las fechas correspondientes de esa semana

#### Scenario: Plantilla editada después de generar el plan
- **WHEN** el coach modifica una plantilla después de haberla usado para generar planes
- **THEN** los entrenamientos ya generados no cambian, porque la `structure` se copió por valor en el momento de la generación

### Requirement: Notificación automática al generar un plan
El sistema SHALL notificar al atleta (correo si tiene `username`/email válido, y notificación in-app siempre) cuando se generó al menos un entrenamiento nuevo para él.

#### Scenario: Atleta con email válido
- **WHEN** se genera al menos un entrenamiento para un atleta con `username` con formato de email
- **THEN** el sistema envía un correo con el resumen del plan y crea una notificación in-app

#### Scenario: Ningún día seleccionado
- **WHEN** el coach envía el formulario de generación sin seleccionar ninguna plantilla para ningún día
- **THEN** el sistema no crea entrenamientos y no envía correo ni notificación

### Requirement: Historial de planes generados
El sistema SHALL permitir a un coach consultar el historial completo de entrenamientos generados para su equipo, filtrable por atleta, estado (`pending`/`completed`) y período de fechas.

#### Scenario: Filtrado combinado
- **WHEN** el coach filtra el historial por un atleta específico, estado `completed` y período `this_month`
- **THEN** el sistema devuelve solo los entrenamientos de ese atleta, con ese estado, cuya fecha cae dentro del mes en curso

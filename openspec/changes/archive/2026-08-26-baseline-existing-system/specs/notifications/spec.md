## Purpose

Avisar a coaches y atletas de eventos relevantes del sistema (plan generado, entrenamiento completado, feedback respondido) y permitir al coach enviar avisos manuales a su equipo.

## ADDED Requirements

### Requirement: Notificaciones automáticas del sistema
El sistema SHALL crear una notificación in-app dirigida al usuario correspondiente en cada uno de estos eventos: generación de un plan semanal (al atleta), entrenamiento completado (al coach) y respuesta de feedback del coach (al atleta).

#### Scenario: Notificación tras generar plan
- **WHEN** un coach genera un plan con al menos un entrenamiento nuevo para un atleta
- **THEN** el sistema crea una notificación dirigida a ese atleta

### Requirement: Envío manual de notificaciones por el coach
El sistema SHALL permitir a un coach enviar un mensaje manual a un atleta específico de su equipo o a todos sus atletas a la vez, y SHALL rechazar el envío si quien lo solicita no tiene `role=coach`.

#### Scenario: Envío a todo el equipo
- **WHEN** un coach envía un mensaje con destinatario "todo el equipo"
- **THEN** el sistema crea una notificación individual para cada atleta con `coach_id` igual al del coach

#### Scenario: Intento de envío por un rol distinto de coach
- **WHEN** un usuario con `role=athlete` o `role=admin` intenta enviar una notificación manual
- **THEN** el sistema no crea ninguna notificación y responde con un mensaje de error de permisos

### Requirement: Consulta y marcado de notificaciones propias
El sistema SHALL permitir a cualquier usuario autenticado ver sus notificaciones no leídas y marcarlas como leídas, individualmente o todas a la vez.

#### Scenario: Marcar todas como leídas
- **WHEN** un usuario visita `notificaciones.php?read_all=1`
- **THEN** el sistema marca como leídas todas las notificaciones de ese usuario y lo redirige al listado, que queda vacío

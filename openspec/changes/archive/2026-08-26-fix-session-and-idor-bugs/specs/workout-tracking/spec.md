## MODIFIED Requirements

### Requirement: Respuesta del coach al feedback del atleta
El sistema SHALL permitir a un coach responder con `coach_feedback` al feedback que un atleta dejó en un entrenamiento completado, y SHALL notificar al atleta dueño del entrenamiento cuando el coach responde. El sistema SHALL rechazar la respuesta si el entrenamiento no pertenece a un atleta cuyo `coach_id` sea el del coach autenticado.

#### Scenario: Coach responde feedback pendiente
- **WHEN** el coach envía una respuesta de texto para un entrenamiento completado que tiene `feedback` del atleta y aún no tiene `coach_feedback`, y ese entrenamiento pertenece a un atleta propio
- **THEN** el sistema guarda la respuesta con su fecha y notifica al atleta

#### Scenario: Intento de responder feedback de un entrenamiento ajeno
- **WHEN** un coach envía una respuesta para un `workout_id` cuyo atleta tiene `coach_id` distinto del coach autenticado
- **THEN** el sistema no aplica ningún cambio al entrenamiento

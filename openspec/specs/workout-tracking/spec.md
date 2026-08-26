# workout-tracking Specification

## Purpose
Permitir que un atleta registre los resultados reales de un entrenamiento planificado y que el coach responda al feedback que el atleta deja al completarlo.

## Requirements

### Requirement: Calendario mensual del atleta
El sistema SHALL mostrar a cada atleta un calendario del mes solicitado (por defecto el mes en curso) con los entrenamientos planificados para él en ese rango de fechas.

#### Scenario: Navegación entre meses
- **WHEN** el atleta solicita `mi_plan.php?month=2026-09`
- **THEN** el sistema muestra únicamente los entrenamientos de ese atleta cuya fecha cae en septiembre de 2026

### Requirement: Completar un entrenamiento
El sistema SHALL permitir a un atleta registrar los resultados de un entrenamiento propio (distancia, tiempo, RPE de 1 a 10, feedback opcional, evidencia fotográfica opcional), marcándolo como `status=completed`, `delivery_status=received` y registrando `completed_at`. El sistema SHALL almacenar la distancia y el tiempo reales con precisión decimal, sin truncarlos a números enteros. El sistema SHALL validar la imagen de evidencia (extensión, tamaño máximo y tipo MIME real) antes de guardarla.

#### Scenario: Completar sin evidencia
- **WHEN** el atleta completa un entrenamiento enviando solo distancia, tiempo y RPE
- **THEN** el sistema guarda los resultados, marca el entrenamiento como completado y no exige ninguna imagen

#### Scenario: Completar con evidencia
- **WHEN** el atleta adjunta una imagen válida (jpg/jpeg/png/webp, menos de 5MB, tipo MIME real coincidente) al completar el entrenamiento
- **THEN** el sistema guarda la imagen en el almacenamiento de evidencia y registra su ruta en el entrenamiento

#### Scenario: Completar con evidencia inválida
- **WHEN** el atleta adjunta un archivo que no pasa la validación de tipo/tamaño al completar el entrenamiento
- **THEN** el sistema guarda igualmente los resultados del entrenamiento (distancia, tiempo, RPE, feedback) pero no guarda ninguna ruta de evidencia

#### Scenario: Distancia y tiempo con decimales
- **WHEN** el atleta registra `actual_distance=8.25` y `actual_time=42.5`
- **THEN** el sistema guarda esos valores con sus decimales exactos, sin redondear a un número entero

### Requirement: Notificación al coach al completar
El sistema SHALL notificar al coach del atleta cuando este completa un entrenamiento, indicando si dejó feedback o no.

#### Scenario: Completar con feedback
- **WHEN** el atleta completa un entrenamiento e incluye texto de feedback
- **THEN** el coach recibe una notificación que menciona que el atleta dejó feedback para revisar

### Requirement: Respuesta del coach al feedback del atleta
El sistema SHALL permitir a un coach responder con `coach_feedback` al feedback que un atleta dejó en un entrenamiento completado, y SHALL notificar al atleta dueño del entrenamiento cuando el coach responde. El sistema SHALL rechazar la respuesta si el entrenamiento no pertenece a un atleta cuyo `coach_id` sea el del coach autenticado.

#### Scenario: Coach responde feedback pendiente
- **WHEN** el coach envía una respuesta de texto para un entrenamiento completado que tiene `feedback` del atleta y aún no tiene `coach_feedback`, y ese entrenamiento pertenece a un atleta propio
- **THEN** el sistema guarda la respuesta con su fecha y notifica al atleta

#### Scenario: Intento de responder feedback de un entrenamiento ajeno
- **WHEN** un coach envía una respuesta para un `workout_id` cuyo atleta tiene `coach_id` distinto del coach autenticado
- **THEN** el sistema no aplica ningún cambio al entrenamiento

### Requirement: Listado de entrenamientos completados con estado de entrega de feedback
El sistema SHALL permitir a un coach ver sus entrenamientos completados clasificados en total, completados, esperando respuesta (con `feedback` del atleta pero sin `coach_feedback`) y respondidos.

#### Scenario: Conteo de esperando respuesta
- **WHEN** un coach abre el listado de entrenamientos completados y tiene 2 entrenamientos con `feedback` del atleta pero sin `coach_feedback`
- **THEN** el sistema muestra "2" en el contador de "esperando respuesta"

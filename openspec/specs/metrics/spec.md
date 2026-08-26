# metrics Specification

## Purpose
Dar visibilidad al atleta sobre su propio progreso y al coach sobre el cumplimiento y desempeño de todo su equipo.

## Requirements

### Requirement: Progreso individual del atleta
El sistema SHALL calcular para cada atleta, a partir de sus entrenamientos completados: distancia y tiempo totales, ritmo promedio, RPE promedio, racha de días consecutivos con entrenamiento completado, y progresión semanal de las últimas 8 semanas.

#### Scenario: Racha se corta
- **WHEN** el entrenamiento completado más reciente de un atleta tiene más de 1 día de antigüedad respecto a hoy
- **THEN** el sistema reporta la racha como 0

### Requirement: Métricas individuales de un atleta para su coach
El sistema SHALL permitir a un coach consultar las mismas métricas de progreso de un atleta específico de su equipo.

#### Scenario: Coach consulta métricas de un atleta propio
- **WHEN** un coach solicita `metricas.php?athlete_id=<id de un atleta propio>`
- **THEN** el sistema muestra las métricas de ese atleta y su progresión de 8 semanas

### Requirement: Comparativa de todo el equipo
El sistema SHALL mostrar, cuando no se filtra por un atleta específico, una tabla comparativa de todos los atletas de un coach con distancia total, tiempo total, ritmo promedio, RPE promedio y tasa de cumplimiento (`completados / total * 100`).

#### Scenario: Atleta sin entrenamientos
- **WHEN** un atleta del equipo no tiene ningún entrenamiento registrado
- **THEN** su tasa de cumplimiento se muestra como 0% en la tabla comparativa

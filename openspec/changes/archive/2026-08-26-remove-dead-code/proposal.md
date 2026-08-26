## Why

La auditoría de código muerto (previa a este change) confirmó, mediante análisis del grafo de código y búsqueda de texto en todo el repositorio, que 4 métodos no tienen ningún caller: quedaron huérfanos tras ser reemplazados por versiones más completas. Mantenerlos aumenta la superficie de mantenimiento sin aportar valor. No cambia comportamiento observable (`skip_specs: true`): nada los invoca hoy.

## What Changes

- Eliminar `Team::find($id)` de `models/Team.php` (reemplazado de facto por `Team::findByCoach`).
- Eliminar `Workout::getByCoach($coachId, $from, $to)` de `models/Workout.php` (reemplazado de facto por `Workout::getAllByCoach`).
- Eliminar `Workout::markAsReceived($athleteId)` de `models/Workout.php` (nunca tuvo caller).
- Eliminar `Workout::getPlanStatsByCoach($coachId)` de `models/Workout.php` (reemplazado de facto por `Workout::getPlansSummaryByCoach`).

## Capabilities

(sin capacidades de negocio afectadas — ningún flujo ejecutable usa estos métodos)

## Impact

- Afecta únicamente `models/Team.php` y `models/Workout.php`.
- Sin impacto en `openapi.yaml` (esos métodos no correspondían a ningún endpoint documentado, ya se excluyeron en la Fase 3).

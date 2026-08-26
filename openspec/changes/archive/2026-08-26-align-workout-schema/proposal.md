## Why

`scripts/setup.php` (instalación en una base de datos nueva) quedó desincronizado del esquema que el código realmente usa en producción: no crea `delivery_status`, `viewed_at`, `coach_feedback_at` (solo las agrega `scripts/migrate_workout_columns.php`, un script aparte y ya ejecutado), crea `evidence_url` (columna que ningún código lee ni escribe — el código usa `evidence_path`, ausente de ambos scripts), y define `actual_distance`/`actual_time` como `INT` aunque el formulario de `mi_plan.php` envía decimales (`step="0.01"`/`step="0.1"`), que hoy se truncan silenciosamente al guardar.

## What Changes

- `scripts/setup.php` pasa a ser la única fuente de verdad del esquema de `workouts`: incluye `delivery_status`, `viewed_at`, `coach_feedback_at`, `evidence_path`; deja de crear `evidence_url`.
- `workouts.actual_distance` y `workouts.actual_time` cambian de `INT` a `DECIMAL(6,2)`. **BREAKING** para la precisión de datos ya almacenados como entero: los valores futuros ya no se truncan.
- `scripts/migrate_workout_columns.php` se actualiza para quedar idempotente y alineado con el nuevo esquema (agrega `evidence_path`, ya no depende de `evidence_url`).

## Capabilities

### Modified Capabilities
- `workout-tracking`: el requisito de completar un entrenamiento pasa a garantizar que la distancia y el tiempo reales se guardan con precisión decimal, sin truncarse.

## Impact

- Afecta: `scripts/setup.php`, `scripts/migrate_workout_columns.php`, `models/Workout.php` (sin cambios de lógica, solo se beneficia del tipo de columna correcto).
- Requiere ejecutar la migración contra la base de datos de producción real (Hostinger) — no puede verificarse end-to-end desde este entorno de desarrollo por falta de conectividad de red hacia ese host (misma limitación que en `secure-credentials`).
- `openapi.yaml` ya documentaba `actual_distance`/`actual_time` como `number` (no `integer`) con una nota sobre el riesgo de truncamiento; ese `openapi.yaml` no necesita cambios, la nota queda obsoleta y se retira.

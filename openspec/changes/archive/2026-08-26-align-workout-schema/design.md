## Context

Ver `proposal.md` - Why. La base de datos de producción ya tiene, según el código que la usa, las columnas `delivery_status`, `viewed_at`, `coach_feedback_at` (agregadas alguna vez por `scripts/migrate_workout_columns.php`) y `evidence_path` (usada por el código pero de origen desconocido — no está en ningún script del repo, probablemente una migración manual no versionada). No hay conectividad de red desde este entorno de desarrollo hacia el host de producción, así que ningún `ALTER TABLE` puede verificarse end-to-end aquí.

## Goals / Non-Goals

**Goals**: que `scripts/setup.php` sea instalable de cero y produzca exactamente el esquema que el código espera; que los decimales de distancia/tiempo dejen de truncarse.
**Non-Goals**: no se migran datos históricos ya truncados (fuera de alcance); no se automatiza la ejecución de la migración contra producción (se documenta como paso manual).

## Decisions

- **`DECIMAL(6,2)` en vez de `FLOAT`** para `actual_distance`/`actual_time`: precisión exacta para valores con hasta 2 decimales (evita errores de redondeo binario de `FLOAT`), rango suficiente (hasta 9999.99 km/min).
- **`evidence_path` reemplaza a `evidence_url` en el esquema versionado**, en vez de mantener ambas: `evidence_url` no tiene ningún lector/escritor en el código; mantenerla solo agregaría confusión.
- **`scripts/migrate_workout_columns.php` se mantiene como script de migración idempotente** (en vez de fusionarlo dentro de `setup.php`), porque sirve para alinear una base de datos ya existente sin recrear tablas — se actualiza su contenido para cubrir también `evidence_path` y el cambio de tipo de columna.

## Risks / Trade-offs

- [Riesgo] Si la producción tiene datos existentes en `actual_distance`/`actual_time` como enteros truncados, el `ALTER TABLE ... MODIFY` no los "recupera" (el dato truncado ya se perdió) — solo evita que se sigan truncando datos futuros. Mitigación: se documenta explícitamente, no se promete una recuperación retroactiva.
- [Riesgo] Ejecutar `ALTER TABLE workouts DROP COLUMN evidence_url` en producción es irreversible si esa columna tuviera datos. Mitigación: el `DROP COLUMN` se deja como paso opcional y explícito en el script de migración, no automático.
- [Riesgo] No se puede verificar la migración contra la base de datos real desde este entorno. Mitigación: se documenta como tarea manual pendiente del usuario, igual que en `secure-credentials`.

## Migration Plan

1. Actualizar `scripts/setup.php` (instalaciones nuevas) y `scripts/migrate_workout_columns.php` (bases ya existentes) en este repo.
2. El usuario ejecuta `scripts/migrate_workout_columns.php` contra la base de datos de producción real (vía navegador o CLI en el servidor de Hostinger, con conectividad real a la BD).
3. Verificar manualmente en producción que `SHOW COLUMNS FROM workouts` incluye `evidence_path` y que `actual_distance`/`actual_time` son `DECIMAL(6,2)`.

## 1. Actualizar scripts/setup.php

- [x] 1.1 Agregar `delivery_status`, `viewed_at`, `coach_feedback_at`, `evidence_path` al `CREATE TABLE workouts` y quitar `evidence_url` (incluyendo el bloque de `ALTER TABLE` legacy que la agregaba)
- [x] 1.2 Cambiar el tipo de `actual_distance` y `actual_time` de `INT` a `DECIMAL(6,2)` en el `CREATE TABLE`
- [x] 1.3 Verificar `php -l scripts/setup.php`

## 2. Actualizar scripts/migrate_workout_columns.php para bases ya existentes

- [x] 2.1 Agregar `evidence_path` a la lista de columnas a verificar/crear (idempotente, igual que las demás)
- [x] 2.2 Agregar los `ALTER TABLE ... MODIFY COLUMN` para `actual_distance`/`actual_time` a `DECIMAL(6,2)` (idempotente: `MODIFY` no falla si el tipo ya es el correcto)
- [x] 2.3 Agregar, comentado y opcional, el `DROP COLUMN evidence_url` con advertencia explícita de que es irreversible
- [x] 2.4 Verificar `php -l scripts/migrate_workout_columns.php`

## 3. Verificación

- [x] 3.1 Confirmar que `models/Workout.php` no necesita cambios de lógica (ya trata `actual_distance`/`actual_time` como valores numéricos genéricos)
- [ ] 3.2 **Pendiente del usuario**: ejecutar `scripts/migrate_workout_columns.php` contra la base de datos real de producción — no verificable desde este entorno por falta de conectividad de red

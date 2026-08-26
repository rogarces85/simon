## 1. Verificación de fidelidad del baseline

- [ ] 1.1 Confirmar que cada Requirement de `specs/auth/spec.md` corresponde a comportamiento real de `includes/auth.php` y `login.php`/`logout.php`/`index.php` (sin implementación nueva; solo lectura y contraste)
- [ ] 1.2 Confirmar que cada Requirement de `specs/athlete-management/spec.md` corresponde a `crear_entrenador.php` y `atletas.php`
- [ ] 1.3 Confirmar que cada Requirement de `specs/training-plans/spec.md` corresponde a `generar_plan.php` y `mis_planes.php`
- [ ] 1.4 Confirmar que cada Requirement de `specs/workout-tracking/spec.md` corresponde a `mi_plan.php` y `entrenamientos.php`
- [ ] 1.5 Confirmar que cada Requirement de `specs/metrics/spec.md` corresponde a `mi_progreso.php` y `metricas.php`
- [ ] 1.6 Confirmar que cada Requirement de `specs/notifications/spec.md` corresponde a `notificaciones.php`
- [ ] 1.7 Confirmar que cada Requirement de `specs/profile-team/spec.md` corresponde a `perfil.php` y `config_team.php`

## 2. Cierre del baseline

- [ ] 2.1 Ejecutar `openspec validate baseline-existing-system --strict` y corregir cualquier problema estructural reportado
- [ ] 2.2 Ejecutar `openspec archive baseline-existing-system --yes` para fusionar los 7 deltas en `openspec/specs/` como especificación canónica
- [ ] 2.3 Verificar con `openspec spec list` que las 7 capacidades quedaron archivadas correctamente

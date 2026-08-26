## Why

RUNCOACH ya está en producción pero no tenía ninguna especificación formal: el comportamiento del sistema solo existía en el código PHP. Antes de tocar nada (código muerto, bugs, seguridad, hardening) se necesita una fotografía fiel de la "Verdad Actual" en formato Requirement/Scenario, para que cualquier cambio futuro se proponga como un delta verificable contra un comportamiento documentado, en vez de contra el código como única fuente de verdad.

## What Changes

- Se documentan como especificación OpenSpec las 7 capacidades de negocio que ya existen y funcionan en producción hoy. No se modifica ningún comportamiento: es una captura de línea base (baseline), no una propuesta de cambio funcional.
- No incluye los métodos identificados como código muerto (`Team::find`, `Workout::getByCoach`, `Workout::markAsReceived`, `Workout::getPlanStatsByCoach`) por no formar parte de ningún flujo ejecutable.
- No incluye correcciones de bugs ni hardening — esos son changes separados que se proponen a continuación de este baseline (`fix-session-and-idor-bugs`, `add-csrf-protection`, etc.), cada uno como delta sobre las specs que este change crea.

## Capabilities

### New Capabilities
- `auth`: inicio/cierre de sesión y control de acceso por rol (admin/coach/athlete), sin jerarquía entre roles.
- `athlete-management`: alta de entrenadores (admin) y alta/edición/baja de atletas (coach).
- `training-plans`: biblioteca de plantillas de sesiones y generación de planes semanales a partir de ellas.
- `workout-tracking`: registro de resultados de un entrenamiento por el atleta y ciclo de feedback con el coach.
- `metrics`: métricas individuales de un atleta y comparativa de todo el equipo de un coach.
- `notifications`: notificaciones in-app automáticas y envío manual por el coach.
- `profile-team`: edición del perfil propio y configuración de la identidad visual del team de un coach.

### Modified Capabilities
(ninguna — es la primera especificación del sistema)

## Impact

- Ningún archivo de código se modifica en este change.
- Afecta únicamente `openspec/specs/**` (7 carpetas nuevas) tras el archive.
- Sirve de base para todos los changes de remediación y hardening planificados a continuación (ver `LINEA_BASE_SISTEMA.md` y `openapi.yaml` para el detalle técnico complementario ya existente en el repo).

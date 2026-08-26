## Context

Ver `proposal.md` - Why. El login ocurre antes de que exista una sesión autenticada, así que no se puede confiar en `$_SESSION` para contar intentos (un atacante simplemente no reenvía cookies).

## Goals / Non-Goals

**Goals**: frenar fuerza bruta/credential stuffing contra un `username` conocido, sin dependencias externas (Redis, memcached).
**Non-Goals**: no se implementa CAPTCHA ni bloqueo por IP (fuera de alcance de este change; el bloqueo es por `username`, que es el vector de riesgo principal aquí).

## Decisions

- **Tabla `login_attempts` en MySQL** (en vez de archivo en disco o caché en memoria): es el único almacenamiento persistente y compartido entre requests que ya usa el sistema; consistente con el resto del stack.
- **Umbral por `username`, no por IP**: bloquear por IP es más fácil de sortear (IPs rotativas/NAT compartido) y puede bloquear a usuarios legítimos detrás de la misma IP; por `username` protege directamente la cuenta objetivo.
- **5 intentos / 15 minutos**: valor conservador estándar (OWASP sugiere rangos similares) que no requiere configuración adicional para el tamaño actual del sistema.
- **Se registra también `ip_address`** aunque el bloqueo no se calcule por IP, para tener trazabilidad si en el futuro se quiere agregar bloqueo combinado.

## Risks / Trade-offs

- [Riesgo] Un atacante podría usar el bloqueo para negar el acceso a un usuario legítimo enviando 5 intentos fallidos con su `username` (DoS dirigido a una cuenta). Mitigación: aceptado como trade-off estándar de este patrón; el bloqueo expira solo en 15 minutos y no afecta a otras cuentas.
- [Riesgo] Tabla `login_attempts` crece indefinidamente si nadie hace login exitoso. Mitigación: fuera de alcance de este change (limpieza periódica quedaría como mejora futura); el impacto de almacenamiento es bajo para el volumen de este sistema.

## Migration Plan

1. Agregar `CREATE TABLE login_attempts` a `scripts/setup.php` (instalaciones nuevas).
2. Crear `scripts/migrate_login_attempts.php` para bases de datos ya existentes (mismo patrón que `scripts/migrate_workout_columns.php`).
3. El usuario ejecuta el script de migración contra producción (no verificable desde este entorno, sin conectividad de red).

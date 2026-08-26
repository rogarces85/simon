## Context

Ver `proposal.md` - Why. El deploy actual es `git push` a un remoto que Hostinger sincroniza directamente; no hay pipeline de CI/CD ni variables de entorno gestionadas por el hosting.

## Goals / Non-Goals

**Goals**: sacar los secretos reales del control de versiones sin romper el `require_once 'config/config.php'` que ya usan todos los archivos del proyecto.
**Non-Goals**: no se introduce gestor de secretos externo, no se reescribe el historial de git (fuera de alcance de este change; ver proposal.md).

## Decisions

- **`config/config.php` hace `require` condicional de `config/config.local.php`, con fallback a `getenv()`**, en vez de mover todo a variables de entorno puras: Hostinger (hosting compartido típico) no siempre expone un panel cómodo para variables de entorno por proceso PHP-FPM, mientras que subir un archivo por FTP/git es el flujo que el usuario ya conoce. `getenv()` como fallback deja la puerta abierta a un hosting mejor en el futuro sin otro cambio de código.
- **`config/config.example.php` sí se versiona**, con placeholders, para que cualquiera que clone el repo sepa qué variables necesita definir.

## Risks / Trade-offs

- [Riesgo] El servidor de producción no tiene hoy `config/config.local.php` → el sitio caería tras el próximo deploy si no se crea antes. Mitigación: `tasks.md` incluye crear ese archivo en el servidor como parte del propio change, antes de hacer merge/deploy.
- [Riesgo] La contraseña vieja sigue en el historial de git aunque se rote. Mitigación: se documenta como aceptable (rotar es suficiente en la práctica) en `proposal.md`; reescribir historial queda fuera de alcance.

## Migration Plan

1. Rotar la contraseña de la base de datos en Hostinger (fuera del repo, acción manual del usuario).
2. Aplicar este change en local y confirmar que `config/config.local.php` (con la contraseña nueva) está en `.gitignore` real.
3. Antes de hacer push, crear `config/config.local.php` en el servidor de producción con los valores reales (vía FTP/SSH, no vía git).
4. Hacer push del código (ya sin secretos) y confirmar que el sitio sigue funcionando.

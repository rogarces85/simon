## Context

Ver `proposal.md` - Why. No hay framework ni middleware: cada página gestiona su propio bloque `if ($_SERVER['REQUEST_METHOD'] === 'POST')`.

## Goals / Non-Goals

**Goals**: bloquear peticiones POST forjadas desde fuera del sistema, sin romper ningún flujo legítimo existente.
**Non-Goals**: no se implementa CSRF por-formulario/de un solo uso (token rotativo); un token por sesión es suficiente para el nivel de riesgo actual y evita problemas de pestañas múltiples abiertas a la vez.

## Decisions

- **Un único token por sesión** (`$_SESSION['csrf_token']`), generado una vez con `random_bytes(32)` y reutilizado en todos los formularios mientras la sesión viva, en vez de un token distinto por formulario/request: mucho más simple de implementar de forma consistente en 9 archivos sin estado compartido entre pestañas, y suficiente contra el escenario real (un sitio externo no puede leer ni predecir el token).
- **Comparación con `hash_equals`** en vez de `===`, para evitar timing attacks triviales.
- **Los formularios generados por JavaScript reciben el token vía una constante `CSRF_TOKEN`** declarada una vez en el `<script>` de la página (`const CSRF_TOKEN = "<?php echo htmlspecialchars(Csrf::token()); ?>";`), en vez de hacer una petición adicional para obtenerlo.
- **Rechazo silencioso con redirección al listado con mensaje de error**, en vez de un HTTP 403 crudo, para mantener consistencia con el resto del sistema (que no tiene páginas de error dedicadas).

## Risks / Trade-offs

- [Riesgo] Un usuario con la página abierta en una pestaña durante mucho tiempo, tras la cual la sesión expira/se destruye por otro medio, vería su token rechazado. Mitigación: el error de CSRF redirige a la misma página, donde se genera un token nuevo — el usuario reintenta sin perder más que el último submit.
- [Riesgo] Olvidar agregar el campo o la validación en algún formulario nuevo en el futuro. Mitigación: se documenta la convención en `openspec/config.yaml` (contexto del proyecto) para que futuros changes la seleccionen automáticamente.

## Migration Plan

Sin pasos de despliegue especiales: es código nuevo que no depende de cambios de esquema ni de datos existentes. Se aplica y despliega junto con el resto de los archivos PHP modificados.

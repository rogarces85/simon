# Architecture Decision Records (ADR)

## ADR-001: PHP sin framework ni Composer

**Estado:** Aceptado
**Fecha:** 2026-08-28

### Contexto
Proyecto RUNCOACH debe ejecutarse en XAMPP estándar sin herramientas de build, npm, ni composer. Entorno de hosting compartido típico.

### Decisión
- PHP 8.1+ nativo con PDO
- Autoload manual via `require_once`
- Tailwind y Chart.js via CDN
- Sin vendor/, sin node_modules/, sin build step

### Consecuencias
- ✅ Despliegue trivial (copiar archivos)
- ✅ Zero dependencias externas
- ❌ Sin autoload PSR-4 automático
- ❌ Sin tests de integración con PHPUnit (tests propios)

---

## ADR-002: Estructura de Entrenamiento JSON v2

**Estado:** Aceptado
**Fecha:** 2026-08-28

### Contexto
Versión 1 guardaba texto libre en `workouts.structure`. Los PDFs de planes (5K/10K/21K/42K) revelan que toda sesión tiene la misma anatomía: entrada en calor, movilidad, técnicas, rectas, trabajo principal, fortalecimiento, vuelta a la calma, elongación, notas.

### Decisión
Estructura v2 con bloques fijos:
```json
{
  "v": 2,
  "warm_up": "",
  "mobility": "",
  "drills": "",
  "strides": "",
  "main_set": "",
  "strength": "",
  "cool_down": "",
  "elongation": "",
  "notes": "",
  "estimated_minutes": null,
  "estimated_km": null,
  "tip_ids": []
}
```

### Migración
`TrainingStructure::parse()` normaliza:
- v2 (array) → se usa tal cual
- v1 (string JSON) → va a `main_set`
- legacy (texto plano) → va a `main_set`
- null/vacío → estructura vacía con versión 2

### Consecuencias
- ✅ UI consistente (siempre 9 bloques)
- ✅ Búsqueda/filtro por bloques
- ✅ Estimaciones de tiempo/distancia
- ✅ Tips asociados por sesión
- ⚠️ Requiere migración de datos legacy (hecha en parser)

---

## ADR-003: Seed idempotente por coach

**Estado:** Aceptado
**Fecha:** 2026-08-28

### Contexto
Seeds de desarrollo deben ser re-ejecutables sin duplicar datos. Cada coach debe tener su propia biblioteca de plantillas aislada.

### Decisión
- Seeds usan `SELECT ... WHERE coach_id = ? AND name = ? AND type = ?` antes de INSERT
- Plantillas se prefijan por distancia: "5K -", "10K -", "21K -", "42K -"
- Tip IDs en plantillas referencian tabla `tips` (FK lógica, no FK real para flexibilidad)

### Consecuencias
- ✅ `php seed.php` ejecutable N veces
- ✅ Aislamiento por coach garantizado
- ✅ Filtro por distancia trivial (`LIKE '5K -%'`)

---

## ADR-004: Generación automática de planes por patrón rotativo

**Estado:** Aceptado
**Fecha:** 2026-08-28

### Contexto
Coaches necesitan crear planes de 8-16 semanas rápido. Los PDFs muestran patrones semanales repetitivos.

### Decisión
Patrón de 4 semanas que rota:
- Semana 1, 5, 9... = patrón A
- Semana 2, 6, 10... = patrón B
- Semana 3, 7, 11... = patrón C
- Semana 4, 8, 12... = patrón D

Dentro de cada semana, cada día mapea a un tipo (Series, Fondo, Recuperación, Descanso). El sistema rota plantillas del mismo tipo para evitar repetición exacta.

### Consecuencias
- ✅ Plan completo en 1 click
- ✅ Variedad automática (rotación)
- ✅ Control total del coach (usa sus plantillas)
- ⚠️ Requiere mínimo N plantillas por tipo por distancia

---

## ADR-005: Tips como entidad separada

**Estado:** Aceptado
**Fecha:** 2026-08-28

### Contexto
PDF "Tips para el plan de 42km" contiene 19 consejos reutilizables. Deben asociarse a plantillas y mostrarse en vista previa.

### Decisión
Tabla `tips` independiente con:
- `category`: salud, recuperacion, nutricion, general
- `applicable_distances`: CSV (5K,10K,21K,42K)
- `tip_ids` array en `TrainingStructure`

### Consecuencias
- ✅ Reutilización cross-plantillas
- ✅ Filtrado por distancia/categoría
- ✅ Carga lazy en modal preview (AJAX)
- ⚠️ Sin FK real (flexibilidad > integridad referencial estricta)

---

## ADR-006: Autenticación endurecida

**Estado:** Aceptado
**Fecha:** 2026-08-28

### Contexto
Fase 0 requería: session_regenerate_id, cookies seguras, timeout, logout seguro, hash automático.

### Decisión
- `Auth::init()` en cada request
- `session_regenerate_id(true)` post-login
- Cookies: `HttpOnly`, `SameSite=Lax`, `Secure` (solo HTTPS)
- `session.gc_maxlifetime = 1800` (30 min)
- `User::create()` hashea password si no viene hasheado
- `Database` lanza `PDOException` con modo `ERRMODE_EXCEPTION`

### Consecuencias
- ✅ Cumple OWASP session management
- ✅ Protección fixation/hijacking
- ✅ Logout invalida sesión servidor + cliente

---

## ADR-007: UI con Tailwind CDN + tokens CSS propios

**Estado:** Aceptado
**Fecha:** 2026-08-28

### Contexto
Fase 1 UX Pilot: design tokens, componentes, accesibilidad, responsive. Sin build step → no `@apply`.

### Decisión
- Tailwind CDN para utilidades
- CSS custom properties + clases propias en `header.php`:
  - `.btn-primary`, `.btn-secondary`, `.btn-danger`
  - `.card-base`, `.card-stat`
  - `.badge-*`, `.form-input`, `.form-select`
- Sidebar responsive con hamburguesa + overlay
- Skip link, ARIA en modales/tablas/botones
- Lucide icons CDN
- Loading state global en formularios

### Consecuencias
- ✅ Zero build, zero config
- ✅ Design system consistente
- ✅ Accesibilidad WCAG 2.1 AA base
- ⚠️ CSS duplicado entre Tailwind y clases propias (trade-off aceptado)

---

## ADR-008: Tests unitarios sin framework

**Estado:** Aceptado
**Fecha:** 2026-08-28

### Contexto
Validar `TrainingStructure`, seeds, models sin PHPUnit ni composer.

### Decisión
`tests/unit_tests.php` autocontenido:
- 33 tests: TrainingStructure (23), Notification (4), User (3), Seed (2), Workout (1)
- Helpers `check()`, `assertEquals()`, etc.
- Ejecutable: `php tests/unit_tests.php`
- Exit code 0/1 para CI

### Consecuencias
- ✅ Zero deps, zero config
- ✅ Rápido (<1s)
- ✅ Cubre parsers críticos y models
- ⚠️ No mocks, no coverage, no parallel
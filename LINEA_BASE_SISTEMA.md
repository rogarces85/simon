# Línea Base del Sistema — RUNCOACH

> Documento complementario a `openapi.yaml`. Ambos conforman la fuente única de verdad (Single Source of Truth) del sistema a partir de este punto. Todo cambio futuro debe actualizar primero este par de documentos y luego el código — no al revés.

**Versión de línea base:** 2.0.0
**Origen:** auditoría de código muerto + extracción de la Verdad Actual, realizadas sobre el código PHP en producción (ver Fases 1 y 2 del proceso de documentación).

---

## 1. Propósito del Sistema

RUNCOACH es una plataforma web de gestión de entrenamiento de running que conecta a **entrenadores (coach)** con sus **atletas**, bajo la supervisión de un **administrador (admin)** que gestiona el alta de entrenadores.

El sistema resuelve tres necesidades:

1. **Planificación**: el coach diseña una biblioteca de plantillas de sesiones (series, intervalos, fondos, tempo, recuperación, descanso) y genera con ellas planes semanales personalizados para cada atleta.
2. **Ejecución y registro**: el atleta consulta su plan en un calendario mensual, completa cada sesión con sus resultados reales (distancia, tiempo, esfuerzo percibido) y opcionalmente adjunta evidencia fotográfica.
3. **Seguimiento y comunicación**: el coach revisa el cumplimiento y las métricas de su equipo, responde al feedback que dejan los atletas, y ambas partes se comunican mediante notificaciones in-app y correo electrónico automático.

No es una aplicación de tiempo real ni expone una API pública: es un sistema de páginas PHP renderizadas en servidor, con autenticación por sesión, pensado para uso directo vía navegador por los tres roles.

---

## 2. Diccionario de Datos

Explicación en lenguaje natural de las entidades principales (definición técnica completa en `openapi.yaml`).

### Usuario (`users`)
Tabla única para los tres roles del sistema: **admin**, **coach** y **athlete**. Un atleta siempre queda vinculado a un coach (`coach_id`) — es quien lo dio de alta y es, en la práctica, su único punto de referencia dentro del sistema (no hay traspaso de atletas entre coaches). Guarda también preferencias de entrenamiento del atleta (nivel, ritmo objetivo, fecha objetivo de competencia, día preferido para el fondo largo, tiempo máximo disponible por sesión) que hoy se capturan al crearlo pero **no se usan** para automatizar la generación de planes — la selección de qué entrenamiento va cada día la hace el coach manualmente.

### Team (`teams`)
Identidad visual (nombre, logo, color) que un coach puede configurar para su propio equipo. La relación es de **un coach por team** (no existen equipos compartidos entre coaches ni un coach con más de un team). Si el coach no configura nada, el sistema simplemente no muestra branding.

### Plantilla (`templates`)
La "receta" reutilizable de una sesión de entrenamiento: un tipo (Series, Intervalos, Fondo, Tempo, Recuperación, Descanso), opcionalmente una fase de bloque (Base, Construcción, Pico) y una descripción libre de la estructura (ej. "10x400m @1:35, rec 75s"). Pertenece siempre a un coach — cada coach mantiene su propia biblioteca, no hay plantillas compartidas ni una biblioteca global.

### Entrenamiento (`workouts`)
La entidad central del sistema. Nace cuando el coach genera un plan semanal a partir de una plantilla, y se copia a un día concreto del calendario de un atleta. A partir de ahí evoluciona en dos planos independientes:

- **Estado de cumplimiento** (`status`): `pending` → `completed`, cuando el atleta registra sus resultados.
- **Estado de entrega** (`delivery_status`): `pending` → `sent` (al generarse el plan) → `received` (cuando el atleta lo completa). Este campo es informativo hoy: nada en el sistema actúa de forma distinta según su valor.

Cada entrenamiento completado puede llevar **dos conversaciones de feedback superpuestas pero independientes**: el `feedback` que escribe el atleta al completar la sesión, y el `coach_feedback` con el que el coach responde después. Ambos son campos de texto libre, sin límite de longitud ni de intercambios (no hay hilo de mensajes, solo un feedback y una respuesta).

### Notificación (`notifications`)
Mensaje corto dirigido a un usuario. Se generan automáticamente en tres momentos (plan generado, entrenamiento completado, feedback respondido) o manualmente por el coach hacia un atleta o hacia todo su equipo. No tienen categorías reales: el campo `type` existe pero en la práctica siempre vale `info`.

### Relaciones clave
```
users (coach) 1 ──── 1 teams
users (coach) 1 ──── N users (athlete)   [coach_id]
users (coach) 1 ──── N templates
users (athlete) 1 ── N workouts
users (cualquiera) 1 ─ N notifications
```

---

## 3. Flujos Críticos

### 3.1 Autenticación y control de acceso por rol
1. El usuario envía `username` + `password` a `login.php`.
2. El sistema verifica el hash con `password_verify` y, si es correcto, guarda `user_id`, `role` y `name` en la sesión PHP.
3. Cada página protegida llama a `Auth::requireRole($rol)` o `Auth::requireRoleLike([...])` al inicio; si la sesión no existe o el rol no coincide **exactamente**, se redirige a `login.php` sin ejecutar nada más.
4. No hay jerarquía de roles: un `admin` no puede acceder a páginas de `coach`, y viceversa, aunque conceptualmente el admin esté "por encima".

### 3.2 Generación de un plan semanal (coach → atleta)
1. El coach entra a `generar_plan.php`, elige un atleta y la fecha del lunes de inicio (`week_start`).
2. Para cada uno de los 7 días, opcionalmente selecciona una plantilla de su biblioteca.
3. Al enviar, por cada día con plantilla seleccionada se crea un `Workout` con `status='pending'`, `delivery_status='sent'` y la `structure` de la plantilla copiada como snapshot (cambios posteriores a la plantilla **no** afectan planes ya generados).
4. Si se creó al menos un entrenamiento, el sistema envía un correo al atleta (si tiene email válido) y crea una `Notification` in-app — ambos best-effort, sin reintentos ni cola.

### 3.3 Registro de resultados por el atleta (completar entrenamiento)
1. El atleta abre `mi_plan.php`, navega al mes correspondiente y hace clic en un entrenamiento pendiente de su calendario.
2. Completa distancia, tiempo, RPE (1-10) y feedback opcional; opcionalmente adjunta una foto de evidencia.
3. Al enviar, el `Workout` pasa a `status='completed'`, `delivery_status='received'`, se registra `completed_at`, y si hubo imagen válida se guarda su ruta en `evidence_path`.
4. Se notifica automáticamente al coach del atleta, indicando si dejó feedback o no.

### 3.4 Ciclo de feedback (atleta → coach → atleta)
1. Un entrenamiento completado con `feedback` no vacío y sin `coach_feedback` aparece para el coach en `entrenamientos.php` como "esperando respuesta".
2. El coach abre el detalle y envía su respuesta (`add_feedback`), que se guarda en `coach_feedback` + `coach_feedback_at`.
3. Se notifica al atleta dueño del entrenamiento.
4. El atleta ve la respuesta del coach la próxima vez que abre el detalle de ese entrenamiento en `mi_plan.php`. No hay notificación push en tiempo real: el atleta se entera al revisar notificaciones o al volver a entrar al calendario.

---

## 3.5 Procedimiento de deploy y configuración de credenciales

Desde el change `secure-credentials`, `config/config.php` **ya no contiene credenciales reales**: carga `config/config.local.php` si existe (no versionado, ver `.gitignore`), con fallback a variables de entorno (`getenv`). `config/config.example.php` es la plantilla versionada.

**Al desplegar en un servidor nuevo (o si `config.local.php` no existe aún en el servidor actual):**
1. Copiar `config/config.example.php` a `config/config.local.php` en el servidor (vía FTP/SSH — nunca por `git push`).
2. Completar `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` con los valores reales.
3. Verificar que el archivo quedó fuera del árbol que sirve `git pull`/deploy automático, o que al menos coincide con la entrada de `.gitignore`.

**Regla de oro derivada:** ninguna credencial real vuelve a hardcodearse en un archivo versionado. Cualquier nuevo secreto (API key, SMTP, etc.) sigue el mismo patrón: constante con fallback a `getenv()`, valor real solo en `config.local.php`.

## 4. Reglas de Oro

Restricciones de negocio que **no deben romperse** en cambios futuros, porque el comportamiento actual del sistema completo depende de ellas:

1. **Un atleta pertenece a un único coach para siempre.** `users.coach_id` se fija al crear el atleta y ningún flujo actual lo reasigna. Cualquier feature de "transferir atleta entre coaches" es funcionalidad nueva, no un ajuste.
2. **`Auth::requireRole()` exige coincidencia exacta de rol, sin herencia.** Si se agrega un rol nuevo o se cambia la semántica de `admin`, hay que revisar manualmente cada página — no hay un punto central de jerarquía de permisos que propague el cambio.
3. **La autenticación vive enteramente en `$_SESSION`.** No hay tokens, expiración configurable ni invalidación remota de sesiones. Un cambio a autenticación stateless (JWT, API tokens) es un rediseño, no una extensión.
4. **`delivery_status` y `viewed_at` no gobiernan ninguna lógica hoy.** Son metadatos informativos; ningún flujo bloquea o habilita una acción según su valor. No asumir que "received" implica algo distinto de "completed" en ninguna validación nueva sin verificarlo primero.
5. **La columna real de evidencia es `evidence_path`, no `evidence_url`.** Cualquier migración, backup o reinstalación de esquema debe crear `evidence_path` explícitamente — no está en los scripts de instalación del repositorio (`scripts/setup.php` solo crea `evidence_url`, que el código nunca toca).
6. **`Workout::addCoachFeedback` no valida ownership por coach.** Hasta que se corrija, cualquier cambio en el flujo de feedback debe asumir que el control de acceso real ocurre solo en la capa de UI (listados filtrados por `coach_id`), no en el modelo. No construir features nuevas sobre este método asumiendo que ya es seguro a nivel de datos.
7. **Las plantillas se copian por valor al generar un plan, nunca por referencia.** Editar o borrar una `Template` no debe (y hoy no puede) alterar workouts ya generados a partir de ella.
8. **Los formularios no llevan protección CSRF ni las contraseñas throttling de intentos.** Cualquier endpoint nuevo que modifique datos debe replicar el patrón POST + `action` existente; no asumir que agregar un endpoint JSON/AJAX hereda protecciones que no existen — hay que añadirlas explícitamente.
9. **Todas las consultas a base de datos deben seguir usando sentencias preparadas PDO.** Es el único mecanismo de protección contra inyección SQL en todo el sistema; no hay una capa ORM ni un sanitizador central que lo garantice por otro medio.
10. **No introducir código muerto sin marcarlo.** Antes de esta línea base ya existían métodos huérfanos (`Team::find`, `Workout::getByCoach`, `Workout::markAsReceived`, `Workout::getPlanStatsByCoach`) sin ningún caller. Todo método nuevo debe tener al menos un punto de uso real en el mismo cambio que lo introduce, o quedar explícitamente documentado como pendiente de integración.

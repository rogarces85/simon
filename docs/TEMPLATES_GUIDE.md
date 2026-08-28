# Guía de Plantillas de Entrenamiento RUNCOACH

## Visión General

Las plantillas son bloques reutilizables que definen la estructura de una sesión de entrenamiento. Cada coach tiene su biblioteca privada. Al generar un plan (manual o automático), se copia la estructura completa al workout del atleta.

## Formato JSON v2

```json
{
  "v": 2,
  "warm_up": "20' entrada en calor a 6:50/7:30 min/km + movilidad articular + tecnicas: 2x30m (skipping alto, talon a la cola, skipping corto, carioca)",
  "mobility": "Cadera, isquiotibiales y tobillos",
  "drills": "Skipping alto, talon a la cola, carioca, skipping ruso",
  "strides": "6x100m al 70/75% capacidad, rec. 40\" caminando",
  "main_set": "6x1000m a 5' los 1000m, rec. 2' trote suave",
  "strength": "Circuito 2 vueltas: 20 abdominales, 12 espalda natacion, 10 fuerza brazos, 10 media sentadillas, 10 punta de pie, 20 oblicuos, 10 subir al banco, 10 espalda alternado, 20 paso al frente, 20 rodilla al pecho",
  "cool_down": "15' vuelta a la calma a 7:00/7:30 min/km",
  "elongation": "20/25' elongacion estatica: isquiotibiales, cuadraces, gemelos",
  "notes": "Ritmo facil 6:50-7:25 min/km en zonas continuas. Recuperar completo entre series.",
  "estimated_minutes": 55,
  "estimated_km": 12.5,
  "tip_ids": [1, 5]
}
```

## Los 9 Bloques Obligatorios

| Bloque | Clave | Descripción | Ejemplo |
|--------|-------|-------------|---------|
| Entrada en calor | `warm_up` | Trota + movilidad + técnica | `"20' trote a 6:50 + movilidad"` |
| Movilidad | `mobility` | Articulaciones específicas | `"Cadera, isquiotibiales, tobillos"` |
| Técnicas | `drills` | Ejercicios de forma | `"Skipping, talón-cola, carioca"` |
| Rectas/Progresivos | `strides` | Aceleraciones controladas | `"6x100m al 75%, rec. 40\""` |
| **Trabajo principal** | `main_set` | **El core de la sesión** | `"6x1000m a 5', rec. 2' trote"` |
| Fortalecimiento | `strength` | Core, pesas, circuitos | `"Circuito 2 vueltas: 20 abs..."` |
| Vuelta a la calma | `cool_down` | Trota suave final | `"15' trote a 7:00/7:30"` |
| Elongación | `elongation` | Estiramientos estáticos | `"20' elongación completa"` |
| Notas | `notes` | Consejos, ritmos, avisos | `"Recuperar completo entre series"` |

> **Nota:** `main_set` es el único bloque técnicamente obligatorio para que la sesión tenga sentido. Los demás pueden estar vacíos (`""`).

## Metadatos Opcionales

| Campo | Tipo | Uso |
|-------|------|-----|
| `estimated_minutes` | integer | Duración estimada total (para UI) |
| `estimated_km` | float | Kilometraje estimado (para UI) |
| `tip_ids` | array[int] | IDs de tips asociados (ver tabla `tips`) |

## Convenciones de Nombrado

Para que el **generador automático** encuentre las plantillas, usa prefijos por distancia:

```
5K - Serie 6x1000
5K - Fondo Largo 8km
5K - Interval 5x1000
5K - Regenerativo
5K - Bajas y Cambios de Ritmo

10K - Interval 8x1000
10K - Fondo Largo 14km
10K - Series 6x2000
10K - Fondo + Fortalecimiento
10K - Regenerativo

21K - Media Maraton 18km
21K - Interval 5x2000
21K - Series 10x1000
21K - Fondo Largo 10km + Fuerte
21K - Regenerativo

42K - Maraton 30km
42K - Interval 6x2000
42K - Series 10x1000
42K - Fondo Largo 28km
42K - Series 10x400m
42K - Regenerativo
```

**Tipos válidos:** `Series`, `Fondo`, `Intervalos`, `Tempo`, `Recuperación`, `Descanso`

**Bloques de periodo (opcional):** `Base`, `Construcción`, `Pico`

## Paces de Referencia (extraídos de PDFs oficiales)

| Distancia | Trota fácil | Series/Intervalos | Fondo largo | Recuperación |
|-----------|-------------|-------------------|-------------|--------------|
| **5K** | 6:50-7:30 min/km | 5:00 min/km | 6:50-7:25 min/km | 7:00-7:30 min/km |
| **10K** | 6:40-7:20 min/km | 5:25-5:30 min/km | 6:40-7:20 min/km | 5:10 min/km |
| **21K** | 6:00-6:30 min/km | 5:00 min/km | 6:00-7:10 min/km | 5:00 min/km |
| **42K** | 6:00-6:50 min/km | 5:00 min/km | 6:20-7:30 min/km | 5:00 min/km |

> Usa estos rangos en `warm_up`, `cool_down`, `main_set` y `notes` para coherencia.

## Crear/Editar Plantillas (UI)

1. Ir a **Generar Plan → pestaña "Mis Plantillas"**
2. Click **"Nueva Plantilla"** (botón azul +)
3. Completa:
   - **Nombre**: ej. "5K - Serie 6x1000" (prefijo distancia obligatorio para auto-plan)
   - **Tipo**: Series / Fondo / Intervalos / Tempo / Recuperación / Descanso
   - **Bloque**: Base / Construcción / Pico (opcional)
   - **Estructura**: JSON v2 (ver formato arriba) — *se valida al guardar*
4. **Guardar** → aparece en la grilla con badges de tipo, tiempo, km, tips

### Vista Previa
Click en 👁️ (ojo) en cualquier tarjeta → modal con:
- Todos los 9 bloques renderizados con iconos
- Tips asociados cargados vía AJAX
- Metadatos (minutos, km, # tips)

## Generación Automática de Planes

Requisitos mínimos por distancia:

| Distancia | Semanas | Plantillas mínimas | Tipos requeridos |
|-----------|---------|-------------------|------------------|
| 5K | 8 | 3 | Series, Fondo, Recuperación |
| 10K | 10 | 4 | Series, Fondo, Intervalos, Recuperación |
| 21K | 12 | 4 | Series, Fondo, Intervalos, Recuperación |
| 42K | 16 | 5 | Series, Fondo, Intervalos, Recuperación, (Tempo) |

### Flujo
1. Pestaña **"Plan Semanal Auto"**
2. Selecciona atleta + distancia + lunes de inicio
3. Sistema verifica plantillas disponibles (badges verdes/rojos)
4. **Generar Plan Completo** → crea N semanas rotando patrón de 4 semanas:
   - Rota plantillas del mismo tipo para variar
   - Respeta `Descanso` los domingos
   - Notifica al atleta por email + notificación interna

## Seed de Datos de Desarrollo

```bash
# Plantillas (42 total, 21 por coach)
php db/seeds/seed_training_templates.php
# Opciones:
#   --coach=coach1@test.local    # Solo un coach
#   --dry-run                    # Simula sin insertar

# Tips (19 del PDF 42K)
php db/seeds/seed_tips.php
```

## Buenas Prácticas

1. **Siempre usa prefijo de distancia** en el nombre para que el auto-plan funcione
2. **Incluye `main_set` con ritmo explícito** (ej. "a 5' los 1000m")
3. **Añade `estimated_minutes`** para que el atleta vea duración en el calendario
4. **Asocia `tip_ids` relevantes** (1-2 por plantilla) → aparecen en vista previa y PDF
5. **Usa `notes` para avisos clave**: "Recuperar completo", "No salir rápido", "Hidratar cada 5km"
5. **Revisa ortografía**: los textos se muestran tal cual al atleta

## Ejemplos Completos

### 5K - Serie 6x1000 (Series)
```json
{
  "v": 2,
  "warm_up": "20' entrada en calor a 6:50/7:30 min/km + movilidad articular + tecnicas: 2x30m (skipping alto, talon a la cola, skipping corto, carioca)",
  "mobility": "Cadera, isquiotibiales y tobillos",
  "drills": "Skipping alto, talon a la cola, carioca, skipping ruso",
  "strides": "6x100m al 70/75% capacidad, rec. 40\" caminando",
  "main_set": "6x1000m a 5' los 1000m, rec. 2' trote suave",
  "strength": "Circuito 2 vueltas: 20 abdominales, 12 espalda natacion, 10 fuerza brazos, 10 media sentadillas, 10 punta de pie, 20 oblicuos, 10 subir al banco, 10 espalda alternado, 20 paso al frente, 20 rodilla al pecho",
  "cool_down": "15' vuelta a la calma a 7:00/7:30 min/km",
  "elongation": "20/25' elongacion estatica: isquiotibiales, cuadraces, gemelos",
  "notes": "Ritmo facil 6:50-7:25 min/km en zonas continuas. Recuperar completo entre series.",
  "estimated_minutes": 55,
  "estimated_km": 12.5,
  "tip_ids": [1, 5]
}
```

### 42K - Regenerativo (Recuperación)
```json
{
  "v": 2,
  "warm_up": "5' trote muy suave",
  "mobility": "Movilidad articular ligera",
  "drills": "",
  "strides": "",
  "main_set": "35' trote suave a 5:00/km (recuperacion)",
  "strength": "10' core ligero: plancha 2x30",
  "cool_down": "5' trote suave",
  "elongation": "10' elongacion suave",
  "notes": "Recuperacion activa post-fondo o post-series.",
  "estimated_minutes": 50,
  "estimated_km": 7.0,
  "tip_ids": [2, 10]
}
```

## Validación

Al guardar, el sistema:
1. Parsea JSON → debe ser válido
2. Normaliza a v2 vía `TrainingStructure::toJson()`
3. Guarda en `templates.structure` (TEXT)

Si el JSON es inválido → error "Estructura JSON inválida".

## Exportar/Importar (Futuro)

- Botón "Exportar JSON" en vista previa
- Botón "Importar" en modal crear → pega JSON → autollenar formulario
- Compartir plantillas entre coaches (copia manual por ahora)

---

*Versión 1.0 - Basada en PDFs oficiales 5K/10K/21K/42K + Tips 42K*
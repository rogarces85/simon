# RUNCOACH - Sistema de Entrenamiento para Corredores

Sistema completo de gestión de entrenamientos para coaches y atletas, desarrollado en PHP puro sin dependencias externas (solo Tailwind CDN y Lucide CDN para UI).

## Características Principales

- **Gestión de coaches y atletas** con roles y equipos
- **Plantillas de entrenamiento** estructuradas en formato JSON v2
- **Generación de planes semanales** manual y automática (8/10/12/16 semanas)
- **Sistema de tips** integrado con 19 consejos de nutrición, recuperación y salud
- **Panel de progreso** con gráficos Chart.js
- **Notificaciones** en tiempo real
- **Autenticación segura** (session regeneration, HttpOnly cookies, timeout 30min)

## Stack Tecnológico

- **PHP 8.1+** (sin Composer)
- **MySQL/MariaDB** (PDO)
- **Tailwind CSS** (CDN)
- **Lucide Icons** (CDN)
- **Chart.js** (CDN)
- **Arquitectura MVC** ligera

## Instalación

```bash
# 1. Clonar y configurar BD
cp config/config.example.php config/config.php
# Editar config/config.php con tus credenciales DB

# 2. Ejecutar migraciones
php db/migrations/001_hash_legacy_passwords.php
php db/migrations/002_create_tips_table.php

# 3. Poblar datos de desarrollo
php db/seeds/seed_dev_data.php
php db/seeds/seed_training_templates.php
php db/seeds/seed_tips.php

# 4. Servir con XAMPP/Apache
# DocumentRoot apuntando a /www/SIMON
```

## Usuarios de Prueba

| Usuario | Password | Rol |
|---------|----------|-----|
| admin | admin1234 | Admin |
| coach1@test.local | test1234 | Coach |
| coach2@test.local | test1234 | Coach |
| atleta1@test.local | test1234 | Atleta |
| atleta2@test.local | test1234 | Atleta |

## Estructura del Proyecto

```
SIMON/
├── api/                    # Endpoints AJAX
│   └── get_tips.php
├── config/
│   ├── config.php          # Configuración (no versionado)
│   └── config.example.php  # Plantilla
├── db/
│   ├── migrations/         # Migraciones SQL
│   └── seeds/              # Seeds de desarrollo
├── includes/
│   ├── auth.php           # Autenticación y sesiones
│   ├── Csrf.php           # Protección CSRF
│   ├── db.php             # Conexión PDO singleton
│   ├── flash.php          # Mensajes flash
│   └── Mailer.php         # Envío de emails
├── models/
│   ├── TrainingStructure.php  # Parser/serializador JSON v2
│   ├── User.php
│   ├── Workout.php
│   ├── Notification.php
│   └── Team.php
├── views/
│   ├── layout/
│   │   ├── header.php     # Layout global, sidebar, tokens
│   │   └── footer.php     # JS global, modales, loading
│   └── ...                # Vistas por funcionalidad
├── tests/
│   └── unit_tests.php     # Tests unitarios (33 tests)
└── *.php                  # Controladores principales
```

## Formato JSON v2 - TrainingStructure

```json
{
  "v": 2,
  "warm_up": "20' entrada en calor...",
  "mobility": "Cadera, isquiotibiales...",
  "drills": "Skipping alto, talón a la cola...",
  "strides": "6x100m al 70/75%...",
  "main_set": "6x1000m a 5' los 1000m...",
  "strength": "Circuito 2 vueltas: 20 abdominales...",
  "cool_down": "15' vuelta a la calma...",
  "elongation": "20/25' elongación estática...",
  "notes": "Ritmo objetivo 5K: 3:30/km...",
  "estimated_minutes": 55,
  "estimated_km": 12.5,
  "tip_ids": [1, 5]
}
```

**Bloques obligatorios:** `warm_up`, `mobility`, `drills`, `strides`, `main_set`, `strength`, `cool_down`, `elongation`, `notes`

## Flujo de Generación Automática de Planes

1. Coach selecciona atleta + distancia (5K/10K/21K/42K) + semana inicio
2. Sistema busca plantillas del coach para esa distancia (prefijo "5K -", "10K -", etc.)
3. Agrupa por tipo: Series, Fondo, Intervelos, Recuperación
4. Aplica patrón semanal de 4 semanas rotando plantillas
4. Repite patrón hasta completar semanas objetivo (8/10/12/16)
5. Crea workouts en BD y notifica al atleta

## Seeds Disponibles

```bash
# Datos base (coaches, atletas, teams)
php db/seeds/seed_dev_data.php

# 42 plantillas de entrenamiento (21 por coach)
php db/seeds/seed_training_templates.php [--coach=email] [--dry-run]

# 19 tips de nutrición/recuperación
php db/seeds/seed_tips.php [--dry-run]
```

## Tests

```bash
php tests/unit_tests.php
# 33 tests: TrainingStructure, Notification, User, Seed idempotencia
```

## Seguridad

- Password hashing automático (bcrypt via `password_hash`)
- CSRF tokens en todos los formularios
- Session regeneration post-login
- Cookies HttpOnly + SameSite=Lax
- Timeout 30 min inactividad
- Prepared statements en todas las consultas
- Validación de pertenencia coach-atleta en cada operación

## Licencia

Desarrollo interno - RUNCOACH Team
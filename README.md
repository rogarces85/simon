# 🏃‍♂️ SIMON – Sistema de Gestión de Entrenamiento

SIMON es una plataforma web modular diseñada para entrenadores y atletas de running, que facilita la planificación semanal, el seguimiento de métricas y la comunicación bidireccional.

## 🚀 Funcionalidades Principales

### Para Entrenadores
- **Gestión de Atletas**: Registro y seguimiento personalizado de corredores.
- **Plantillas de Entrenamiento**: Creación de sesiones reutilizables (Series, Fondo, Tempo, etc.).
- **Generación de Planes**: Creación de planes semanales con **personalización individual** de instrucciones para cada atleta sin necesidad de duplicar plantillas.
- **Landing Page**: Página de inicio de alta fidelidad con soporte para **Modo Oscuro (Dark) y Claro (Light)**.
- **Dashboard de Métricas**: Visualización del cumplimiento de los planes y feedback de los atletas.

### Para Atletas
- **Calendario Semanal**: Visualización clara de los entrenamientos asignados.
- **Interfaz Adaptativa**: Soporte para temas oscuro/claro según preferencia.
- **Registro de Resultados**: Carga de distancia, tiempo, ritmo y esfuerzo percibido (RPE).

## 🛠️ Arquitectura Técnica

El sistema está construido como un monolito PHP modular y escalable:
- **Backend**: PHP (MVC simplificado).
- **Base de Datos**: MySQL.
- **Frontend**: HTML5, Vanilla JS, CSS3 (Tailwind CSS vía CDN + Google Stitch Tokens).
- **Sistema de Diseño**:
  - **Tipografía**: Lexend (Google Fonts).
  - **Color Primario**: Emerald Green (#0df280).
  - **Bordes**: 8px (Round_Eight).
  - **Temas**: Soporte nativo para Dark y Light mode mediante variables CSS.


## 📋 PRD (Documento de Requerimientos del Producto)

### 1. Objetivo
Optimizar la comunicación entre coach y atleta, permitiendo una planificación profesional, realista y adaptable.

### 2. Estructura de Entrenamiento
El sistema adapta automáticamente los ritmos de referencia basados en el ritmo objetivo del atleta:
- **Suave**: Ritmo objetivo + 45-75 seg.
- **Maratón**: Ritmo objetivo ± 5 seg.
- **Tempo**: Ritmo objetivo - 10-25 seg.
- **Intervalos**: Ritmo objetivo - 25-45 seg.

### 3. Fases del Plan
Los planes se organizan en bloques:
- **Base**: Construcción de volumen aeróbico.
- **Construcción**: Introducción de trabajos de calidad.
- **Pico**: Máximo volumen e intensidad.
- **Taper**: Reducción de carga previa a la competencia.

## 🧹 Mantenimiento y Limpieza
Como parte de la mejora continua, se han eliminado archivos redundantes y scripts de configuración inicial obsoletos para mantener un repositorio limpio y enfocado.

## 🛡️ Instalación y Configuración
1. Configurar la base de datos MySQL usando el schema proporcionado.
2. Actualizar `config/config.php` (o `includes/db.php`) con las credenciales correspondientes.
3. Asegurarse de que el servidor web tenga permisos de escritura en la carpeta de subidas (si aplica).

---
*Desarrollado con enfoque en rendimiento y visuales premium por Antigravity AI.*

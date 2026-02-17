# 🏃‍♂️ SIMON – Sistema de Gestión de Entrenamiento

SIMON es una plataforma web modular diseñada para entrenadores y atletas de running, que facilita la planificación semanal, el seguimiento de métricas y la comunicación bidireccional.

## 🚀 Funcionalidades Principales

### Para Entrenadores
- **Gestión de Atletas**: Registro y seguimiento personalizado de corredores.
- **Plantillas de Entrenamiento**: Creación de sesiones reutilizables con estructura profesional.
- **Generación de Planes**: Asignación semanal con **edición dinámica** de instrucciones por día.
- **Coach Dashboard**: Panel de control con métricas en tiempo real, racha de los atletas y resumen semanal.

### Para Atletas
- **Calendario Semanal**: Interfaz tipo checklist para visualización clara de sesiones.
- **Registro de Resultados**: Carga de métricas (km, tiempo, RPE) con soporte para feedback al coach.
- **Analytics de Progreso**: Gráficos premium de volumen, ritmo y cumplimiento.
- **Interfaz Universal**: Soporte full para **Modo Oscuro (Dark) y Claro (Light)** en todas las vistas internas.

## 🛠️ Arquitectura Técnica

El sistema está construido como un monolito PHP modular y escalable:
- **Backend**: PHP (MVC simplificado).
- **Base de Datos**: MySQL.
- **Frontend**: HTML5, Vanilla JS, CSS3 (Google Stitch Inspired Design System).
- **Sistema de Diseño (Emerald)**:
  - **Tipografía**: Lexend (Google Fonts).
  - **Color Primario**: Emerald Green (`#0df280`).
  - **Componentes**: Botones, Tarjetas y Badges con 8px de redondeado (`Round_Eight`).
  - **Tematización**: Soporte nativo para Dark y Light mode centralizado en `theme.css`.


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

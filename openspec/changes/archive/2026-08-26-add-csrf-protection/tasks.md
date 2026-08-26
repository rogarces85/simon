## 1. Helper CSRF

- [x] 1.1 Crear `includes/Csrf.php` con `Csrf::token()`, `Csrf::verify($token)` y `Csrf::field()`, y verificar `php -l includes/Csrf.php`

## 2. Formularios estáticos (campo + validación)

- [x] 2.1 `login.php`
- [x] 2.2 `crear_entrenador.php`
- [x] 2.3 `atletas.php` (formulario del modal crear/editar + formulario de eliminar por fila)
- [x] 2.4 `generar_plan.php` (formulario de generar plan + formulario del modal de plantillas)
- [x] 2.5 `notificaciones.php`
- [x] 2.6 `perfil.php`
- [x] 2.7 `config_team.php`

## 3. Formularios generados por JavaScript

- [x] 3.1 `entrenamientos.php`: declarar `const CSRF_TOKEN` e incluirlo en el formulario de responder feedback generado en `openWorkoutModal()`, más validación en el bloque POST de `action=add_feedback`
- [x] 3.2 `mi_plan.php`: declarar `const CSRF_TOKEN` e incluirlo en el formulario de completar entrenamiento generado en `openWorkoutModal()`, más validación en el bloque POST de `action=complete_workout`

## 4. Verificación

- [x] 4.1 `php -l` sobre los 9 archivos modificados
- [x] 4.2 Grep de `method="POST"` en todo el repo para confirmar que ningún `<form>` quedó sin el campo `csrf_token`
- [x] 4.3 Actualizar `openapi.yaml`: agregar `csrf_token` como campo requerido en cada `requestBody` de formulario POST afectado

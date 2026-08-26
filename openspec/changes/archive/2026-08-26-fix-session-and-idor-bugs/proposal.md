## Why

La Fase 1-2 de auditoría encontró dos defectos de comportamiento ya en producción: (1) `perfil.php` escribe `$_SESSION['user_name']` tras guardar, pero `Auth::user()` lee `$_SESSION['name']`, así que el nombre actualizado no se refleja hasta el próximo login; (2) `Workout::addCoachFeedback` no valida que el `workout_id` pertenezca a un atleta del coach autenticado, permitiendo en teoría que un coach modifique el feedback de un entrenamiento de otro coach (IDOR) si envía un `workout_id` ajeno.

## What Changes

- `perfil.php` escribe la clave de sesión correcta (`$_SESSION['name']`) tras actualizar el nombre, para que se refleje sin re-login. **BREAKING**: ninguno para el usuario final — es la corrección del comportamiento esperado.
- `models/Workout.php::addCoachFeedback` exige y valida `coach_id` (vía `JOIN users`) antes de actualizar; si el workout no pertenece a un atleta de ese coach, no aplica el cambio.
- `entrenamientos.php` (único caller) pasa el `coach_id` del coach autenticado a `addCoachFeedback`.

## Capabilities

### Modified Capabilities
- `profile-team`: el requisito "Edición de perfil propio" pasa a garantizar que el nombre actualizado se refleja en la sesión activa sin necesidad de re-login.
- `workout-tracking`: el requisito "Respuesta del coach al feedback del atleta" pasa a exigir que el entrenamiento pertenezca a un atleta del coach autenticado.

## Impact

- Afecta: `perfil.php`, `models/Workout.php`, `entrenamientos.php`.
- No afecta el esquema de base de datos.
- `openapi.yaml` no necesita cambios de contrato (la validación de ownership es un cambio de comportamiento interno, no de forma del request/response; ambos endpoints ya devuelven la misma redirección 302).

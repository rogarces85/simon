## 1. Bug de sesión en perfil.php

- [x] 1.1 Cambiar `$_SESSION['user_name']` por `$_SESSION['name']` en `perfil.php` tras guardar y verificar `php -l perfil.php`

## 2. IDOR en Workout::addCoachFeedback

- [x] 2.1 Modificar `Workout::addCoachFeedback` en `models/Workout.php` para exigir `$coachId` y validar ownership vía `JOIN users` antes de actualizar (usar `execute` con `RETURN` de filas afectadas o un `SELECT` previo)
- [x] 2.2 Actualizar el único caller en `entrenamientos.php` para pasar `$coach['id']` a `addCoachFeedback`
- [x] 2.3 Verificar `php -l models/Workout.php` y `php -l entrenamientos.php`

## 3. Verificación

- [x] 3.1 Grep de otros callers de `addCoachFeedback(` en el repo para confirmar que solo `entrenamientos.php` lo usa y quedó actualizado

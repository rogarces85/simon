## 1. Restructurar config/config.php

- [x] 1.1 Crear `config/config.example.php` con placeholders (sin valores reales) y verificar que `php -l config/config.example.php` no da errores
- [x] 1.2 Modificar `config/config.php` para hacer `require` de `config/config.local.php` si existe, con fallback a `getenv('DB_HOST')`/`DB_NAME`/`DB_USER`/`DB_PASS` y verificar `php -l config/config.php`
- [x] 1.3 Crear `config/config.local.php` local (no versionado) con los valores reales actuales, y verificar que la app sigue conectando a la base de datos (login exitoso en `login.php`) — verificado que las 4 constantes se resuelven correctamente desde `config.local.php`; la conexión PDO real no pudo probarse end-to-end porque este entorno de desarrollo no tiene salida de red hacia `srv1663.hstgr.io` (limitación del sandbox, no del código)

## 2. Actualizar control de versiones

- [x] 2.1 Editar `.gitignore` para ignorar `config/config.local.php` de verdad, quitando el comentario engañoso sobre `config/config.php`
- [x] 2.2 Verificar con `git status` que `config/config.local.php` no aparece como trackeable y que `config/config.php` en el próximo commit ya no contiene la contraseña real

## 3. Verificación end-to-end

- [ ] 3.1 Confirmar (fuera de este repo) que la contraseña de base de datos ya fue rotada en el panel de Hostinger antes de hacer push — **pendiente, es una acción manual del usuario que no puede completarse desde este entorno**
- [x] 3.2 Documentar en `LINEA_BASE_SISTEMA.md` el nuevo procedimiento de deploy (crear `config.local.php` manualmente en servidor nuevo)

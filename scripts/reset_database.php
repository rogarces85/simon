<?php
// Elimina TODAS las tablas de la aplicación (borra todos los datos existentes).
// Uso: subir este archivo al servidor, visitarlo UNA VEZ en el navegador y
// luego BORRARLO del servidor inmediatamente (es destructivo e irreversible).
// Después de ejecutarlo, visitar scripts/setup.php para recrear el esquema
// y los datos base (admin/coach demo + plantillas).
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

$db = Database::getInstance();

echo "<h1>🗑️ Reset de Base de Datos RUNCOACH</h1>";

$tables = ['workouts', 'templates', 'notifications', 'login_attempts', 'teams', 'users'];

$db->exec("SET FOREIGN_KEY_CHECKS = 0");
foreach ($tables as $t) {
    $db->exec("DROP TABLE IF EXISTS `$t`");
    echo "✅ Tabla '$t' eliminada.<br>";
}
$db->exec("SET FOREIGN_KEY_CHECKS = 1");

echo "<h2 style='color:green;'>Listo. Ahora ejecuta <a href='setup.php'>setup.php</a> para recrear el esquema y los datos base.</h2>";
echo "<p style='color:red;font-weight:bold;'>⚠️ Borra este archivo (reset_database.php) del servidor ahora mismo.</p>";

<?php
/**
 * Diagnostico de login: verifica si hay usuarios y si sus contraseñas son validas.
 *
 * Uso:  php db/diagnose_login.php
 *       php db/diagnose_login.php --create-admin  (crea admin/admin1234)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

$db = Database::getInstance();
$createAdmin = in_array('--create-admin', $argv ?? [], true);

if ($createAdmin) {
    require_once __DIR__ . '/../models/User.php';
    $stmt = $db->prepare("SELECT id FROM users WHERE role = 'admin'");
    $stmt->execute();
    $existingAdmin = $stmt->fetch();
    if ($existingAdmin) {
        echo "Ya existe un usuario admin (id {$existingAdmin['id']}).\n";
    } else {
        $id = User::create([
            'username' => 'admin',
            'password' => 'admin1234',
            'role' => 'admin',
            'name' => 'Administrador',
        ]);
        echo "Usuario admin creado:\n";
        echo "  Username: admin\n";
        echo "  Password: admin1234\n";
        echo "  ID: $id\n";
    }
}

// Listar todos los usuarios
$stmt = $db->query("SELECT id, username, role, name, SUBSTRING(password, 1, 7) as pwd_start FROM users ORDER BY id");
$users = $stmt->fetchAll();

if (empty($users) && !$createAdmin) {
    echo "NO hay usuarios en la tabla 'users'.\n\n";
    echo "Para crear un usuario admin de prueba, ejecuta:\n";
    echo "  php db/diagnose_login.php --create-admin\n";
} elseif (!empty($users)) {
    echo "Usuarios encontrados (" . count($users) . "):\n\n";
    foreach ($users as $u) {
        $isHash = str_starts_with($u['pwd_start'], '$2y$') || str_starts_with($u['pwd_start'], '$2b$') || str_starts_with($u['pwd_start'], '$argon2');
        echo "  ID: {$u['id']} | Username: {$u['username']} | Role: {$u['role']} | Name: {$u['name']} | Password: " . ($isHash ? 'hasheada' : 'TEXTO PLANO!') . "\n";
    }
    echo "\n";
}

echo "Base de datos: " . DB_NAME . " en " . DB_HOST . "\n";

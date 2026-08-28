<?php
/**
 * Migracion 001: hashea en texto plano las contraseñas que aun no tengan hash.
 *
 * Los usuarios creados antes de User::create() con hash automatico tienen la
 * contraseña guardada sin hashear. Esta migracion los detecta y los corrige
 * para que Auth::login() funcione correctamente.
 *
 * Uso:  php db/migrate.php
 */

return [
    'name' => 'Hash de contraseñas legadas (users.password)',
    'up' => function (PDO $db): array {
        $log = [];

        $stmt = $db->query("SELECT id, username, password FROM users");
        $users = $stmt->fetchAll();

        $fixed = 0;
        foreach ($users as $u) {
            // Un hash bcrypt empieza con $2y$ o $2b$, argon2 con $argon2
            $isHashed = str_starts_with($u['password'], '$2y$')
                || str_starts_with($u['password'], '$2b$')
                || str_starts_with($u['password'], '$argon2');

            if (!$isHashed && !empty($u['password'])) {
                $hashed = password_hash($u['password'], PASSWORD_DEFAULT);
                $upd = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $upd->execute([$hashed, $u['id']]);
                $log[] = "Contraseña de '{$u['username']}' (id {$u['id']}) hasheada";
                $fixed++;
            }
        }

        if ($fixed === 0) {
            $log[] = 'Todas las contraseñas ya estaban hasheadas';
        } else {
            $log[] = "$fixed contraseñas hasheadas correctamente";
        }

        return $log;
    },
];

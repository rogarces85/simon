<?php
/**
 * Runner de migraciones de RUNCOACH.
 *
 * Uso:  php db/migrate.php            (aplica las pendientes)
 *       php db/migrate.php --status   (solo informa, no aplica nada)
 *
 * Cada archivo de db/migrations/ debe devolver un array con:
 *   'name'  => descripcion corta
 *   'up'    => function (PDO $db): array de mensajes
 * Las migraciones deben ser idempotentes: correrlas dos veces no debe fallar.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/Schema.php';

$isCli = (php_sapi_name() === 'cli');
$eol = $isCli ? "\n" : "<br>\n";
$statusOnly = $isCli && in_array('--status', $argv, true);

function out(string $msg, string $eol): void
{
    echo $msg . $eol;
    if (function_exists('flush')) {
        @flush();
    }
}

$db = Database::getInstance();

// Tabla de control de migraciones
$db->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
    version VARCHAR(20) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$applied = $db->query("SELECT version FROM schema_migrations")->fetchAll(PDO::FETCH_COLUMN);

$files = glob(__DIR__ . '/migrations/*.php');
sort($files);

if (!$files) {
    out('No hay migraciones en db/migrations/.', $eol);
    exit(0);
}

$pending = 0;
foreach ($files as $file) {
    $version = substr(basename($file), 0, 3);
    $migration = require $file;
    $label = $version . ' ' . $migration['name'];

    if (in_array($version, $applied, true)) {
        out('  [ya aplicada] ' . $label, $eol);
        continue;
    }

    $pending++;
    if ($statusOnly) {
        out('  [PENDIENTE]   ' . $label, $eol);
        continue;
    }

    out('  [aplicando]   ' . $label, $eol);
    try {
        $messages = $migration['up']($db);
        foreach ((array) $messages as $m) {
            out('       - ' . $m, $eol);
        }
        $stmt = $db->prepare("INSERT INTO schema_migrations (version, name) VALUES (?, ?)");
        $stmt->execute([$version, $migration['name']]);
        out('  [ok]          ' . $label, $eol);
    } catch (Throwable $e) {
        out('  [ERROR]       ' . $label . ' -> ' . $e->getMessage(), $eol);
        out('                ' . $e->getFile() . ':' . $e->getLine(), $eol);
        exit(1);
    }
}

if ($pending === 0) {
    out('Nada que hacer: la base de datos esta al dia.', $eol);
} elseif ($statusOnly) {
    out($pending . ' migracion(es) pendiente(s).', $eol);
} else {
    out('Listo: ' . $pending . ' migracion(es) aplicada(s).', $eol);
}

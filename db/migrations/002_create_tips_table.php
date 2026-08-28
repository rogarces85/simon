<?php
/**
 * Migración: crea tabla tips para consejos de entrenamiento.
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';

$db = Database::getInstance();

if (DB_HOST !== 'localhost' && DB_HOST !== '127.0.0.1') {
    fwrite(STDERR, "Abortado: DB_HOST es '" . DB_HOST . "'. Solo para base local.\n");
    exit(1);
}

$sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS tips (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    category VARCHAR(50) DEFAULT 'general',
    applicable_distances VARCHAR(50) DEFAULT '5K,10K,21K,42K',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_distances (applicable_distances)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

$db->exec($sql);
echo "Tabla tips creada/verificada\n";

// Verificar
$stmt = $db->query("DESCRIBE tips");
print_r($stmt->fetchAll());
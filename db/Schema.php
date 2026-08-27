<?php
/**
 * Helpers idempotentes para las migraciones.
 *
 * MySQL no soporta "ADD COLUMN IF NOT EXISTS" en todas las versiones, asi que
 * cada operacion consulta primero el estado real del esquema y solo actua si
 * hace falta. Devuelven un mensaje describiendo lo que hicieron (o lo que
 * omitieron), pensado para el log del runner.
 */
class Schema
{
    public static function hasTable(PDO $db, string $table): bool
    {
        $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.TABLES
                              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
        $stmt->execute([$table]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function hasColumn(PDO $db, string $table, string $column): bool
    {
        $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS
                              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $stmt->execute([$table, $column]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function hasIndex(PDO $db, string $table, string $index): bool
    {
        $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS
                              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?");
        $stmt->execute([$table, $index]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** Agrega una columna solo si no existe. $definition es el tipo, sin el nombre. */
    public static function addColumn(PDO $db, string $table, string $column, string $definition): string
    {
        if (self::hasColumn($db, $table, $column)) {
            return "columna $table.$column ya existia";
        }
        $db->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        return "columna $table.$column agregada";
    }

    /** Cambia la definicion de una columna existente. */
    public static function modifyColumn(PDO $db, string $table, string $column, string $definition): string
    {
        if (!self::hasColumn($db, $table, $column)) {
            return "columna $table.$column no existe, no se modifica";
        }
        $db->exec("ALTER TABLE `$table` MODIFY `$column` $definition");
        return "columna $table.$column modificada";
    }

    public static function addIndex(PDO $db, string $table, string $index, string $columns): string
    {
        if (self::hasIndex($db, $table, $index)) {
            return "indice $table.$index ya existia";
        }
        $db->exec("ALTER TABLE `$table` ADD INDEX `$index` ($columns)");
        return "indice $table.$index creado";
    }

    /** CREATE TABLE IF NOT EXISTS con mensaje segun si ya estaba. */
    public static function createTable(PDO $db, string $table, string $body): string
    {
        if (self::hasTable($db, $table)) {
            return "tabla $table ya existia";
        }
        $db->exec("CREATE TABLE `$table` ($body) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        return "tabla $table creada";
    }
}

<?php
namespace Meract\Core;

use Meract\Core\DatabaseDialectInterface;
use PDO;
use RuntimeException;
use Meract\Core\BluePrints\{MySQLBP, SQLiteBP, PostgreSQLBP};
class BlueprintFactory
{
    /**
     * Создает соответствующий диалект для СУБД
     *
     * @param PDO $pdo Объект PDO
     * @return DatabaseDialectInterface
     * @throws RuntimeException Если драйвер не поддерживается
     */
    public static function create(PDO $pdo, string $table): Blueprint
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        // echo "mess with factory";
        return match ($driver) {
            'mysql' => new MySQLBP($table),
            'pgsql' => new PostgreSQLBP($table),
            'sqlite' => new SQLiteBP($table),
            default => throw new RuntimeException("Unsupported database driver: {$driver}"),
        };
    }
}
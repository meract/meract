<?php
namespace Meract\Drivers;

use Meract\Core\DatabaseDialectInterface;
use PDO;
use RuntimeException;

class DialectFactory
{
    /**
     * Создает соответствующий диалект для СУБД
     *
     * @param PDO $pdo Объект PDO
     * @return DatabaseDialectInterface
     * @throws RuntimeException Если драйвер не поддерживается
     */
    public static function create(PDO $pdo): DatabaseDialectInterface
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        return match ($driver) {
            'mysql' => new MySqlDialect(),
            'pgsql' => new PostgreSqlDialect(),
            'sqlite' => new SqliteDialect(),
            default => throw new RuntimeException("Unsupported database driver: {$driver}"),
        };
    }
}
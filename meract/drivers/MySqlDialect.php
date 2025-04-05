<?php
namespace Meract\Drivers;

use Meract\Core\DatabaseDialectInterface;
use Meract\Core\Blueprint;

/**
 * Реализация диалекта MySQL
 */
class MySqlDialect implements DatabaseDialectInterface
{
    /**
     * {@inheritdoc}
     */
    public function compileCreateTable(string $table, array $columns, array $options = []): string
    {
        $sql = sprintf(
            'CREATE TABLE %s (%s) ENGINE=%s DEFAULT CHARSET=%s',
            $table,
            implode(', ', $columns),
            $options['engine'] ?? 'InnoDB',
            $options['charset'] ?? 'utf8mb4'
        );

        if (isset($options['comment'])) {
            $sql .= " COMMENT='" . addslashes($options['comment']) . "'";
        }

        return $sql;
    }

    public function compileColumnDefinition(string $columnDefinition, array $parameters = []): string
    {
        $definition = $columnDefinition;

        if ($parameters['unsigned'] ?? false) {
            $definition .= ' UNSIGNED';
        }

        if (!($parameters['nullable'] ?? true)) {
            $definition .= ' NOT NULL';
        }

        if (array_key_exists('default', $parameters)) {
            $default = is_string($parameters['default']) 
                ? "'" . addslashes($parameters['default']) . "'" 
                : $parameters['default'];
            $definition .= " DEFAULT {$default}";
        }

        if ($parameters['auto_increment'] ?? false) {
            $definition .= ' AUTO_INCREMENT';
        }

        if ($parameters['primary'] ?? false) {
            $definition .= ' PRIMARY KEY';
        }

        if ($parameters['unique'] ?? false) {
            $definition .= ' UNIQUE';
        }

        if (isset($parameters['comment'])) {
            $definition .= " COMMENT '" . addslashes($parameters['comment']) . "'";
        }

        return $definition;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastInsertId(\PDO $pdo, string $sequence = null): string
    {
        return $pdo->lastInsertId();
    }

    /**
     * {@inheritdoc}
     */
    public function supportsForeignKeys(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultPort(): int
    {
        return 3306;
    }

    /**
     * {@inheritdoc}
     */
    public function getDriverName(): string
    {
        return 'mysql';
    }

    /**
     * Компилирует условие для временных меток (специфично для MySQL)
     *
     * @param string $column Имя колонки
     * @param string $operation Операция
     * @return string SQL-выражение
     */
    public function compileTimestampExpression(string $column, string $operation): string
    {
        if ($operation === 'CURRENT_TIMESTAMP') {
            return "$column TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
        }

        if ($operation === 'UPDATE_TIMESTAMP') {
            return "$column TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        }

        return "$column TIMESTAMP";
    }

    /**
     * Компилирует условие для хранения JSON (специфично для MySQL)
     *
     * @param string $column Имя колонки
     * @return string SQL-выражение
     */
    public function compileJsonExpression(string $column): string
    {
        return "$column JSON";
    }
}
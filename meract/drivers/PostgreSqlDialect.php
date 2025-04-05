<?php
namespace Meract\Drivers;

use Meract\Core\DatabaseDialectInterface;
use Meract\Core\Blueprint;

/**
 * Реализация диалекта PostgreSQL.
 *
 * Предоставляет PostgreSQL-специфичные методы для компиляции SQL-запросов
 * и работы с особенностями PostgreSQL.
 */
class PostgreSqlDialect implements DatabaseDialectInterface
{
    private ?string $currentTable = null;

    /**
     * Устанавливает текущую таблицу для работы
     *
     * @param string $table
     */
    public function setCurrentTable(string $table): void
    {
        $this->currentTable = $table;
    }

    /**
     * Компилирует SQL-запрос для создания таблицы.
     *
     * @param Blueprint $blueprint Объект с определением таблицы
     * @return string SQL-запрос CREATE TABLE
     */
    public function compileCreateTable(string $table, array $columns, array $options = []): string
    {
        $sql = sprintf(
            'CREATE TABLE %s (%s)',
            $table,
            implode(', ', $columns)
        );

        if (isset($options['comment'])) {
            $sql .= "; COMMENT ON TABLE {$table} IS '" . addslashes($options['comment']) . "'";
        }

        return $sql;
    }

    public function compileColumnDefinition(string $columnDefinition, array $parameters = []): string
    {
        // Конвертируем MySQL-специфичные типы в PostgreSQL
        $definition = str_replace(
            ['TINYINT', 'MEDIUMINT', 'LONGTEXT', 'MEDIUMTEXT', 'TINYTEXT', 'DATETIME'],
            ['SMALLINT', 'INTEGER', 'TEXT', 'TEXT', 'TEXT', 'TIMESTAMP'],
            $columnDefinition
        );

        // SERIAL для автоинкремента
        if ($parameters['auto_increment'] ?? false) {
            if (str_contains($definition, 'INT')) {
                if (str_contains($definition, 'BIGINT')) {
                    return str_replace('BIGINT', 'BIGSERIAL', $definition) . ' PRIMARY KEY';
                }
                return str_replace('INT', 'SERIAL', $definition) . ' PRIMARY KEY';
            }
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

        if ($parameters['primary'] ?? false) {
            $definition .= ' PRIMARY KEY';
        }

        if ($parameters['unique'] ?? false) {
            $definition .= ' UNIQUE';
        }

        return $definition;
    }

    /**
     * Конвертирует типы колонок из MySQL-стиля в PostgreSQL-стиль.
     *
     * @param string $type Исходный тип
     * @return string Конвертированный тип
     */
    private function convertColumnTypes(string $type): string
    {
        $replacements = [
            'TINYINT' => 'SMALLINT',
            'MEDIUMINT' => 'INTEGER',
            'LONGTEXT' => 'TEXT',
            'MEDIUMTEXT' => 'TEXT',
            'TINYTEXT' => 'TEXT',
            'LONGBLOB' => 'BYTEA',
            'MEDIUMBLOB' => 'BYTEA',
            'TINYBLOB' => 'BYTEA',
            'DATETIME' => 'TIMESTAMP',
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $type
        );
    }

    /**
     * Возвращает ID последней вставленной записи.
     *
     * @param \PDO $pdo Объект PDO
     * @param string|null $sequence Имя последовательности
     * @return string ID последней вставленной записи
     */
    public function getLastInsertId(\PDO $pdo, string $sequence = null): string
    {
        return $pdo->lastInsertId($sequence);
    }

    /**
     * Проверяет поддержку внешних ключей.
     *
     * @return bool Всегда true для PostgreSQL
     */
    public function supportsForeignKeys(): bool
    {
        return true;
    }

    /**
     * Возвращает порт по умолчанию для PostgreSQL.
     *
     * @return int 5432
     */
    public function getDefaultPort(): int
    {
        return 5432;
    }

    /**
     * Возвращает имя драйвера PDO для PostgreSQL.
     *
     * @return string 'pgsql'
     */
    public function getDriverName(): string
    {
        return 'pgsql';
    }

    /**
     * Компилирует условие для временных меток (специфично для PostgreSQL).
     *
     * @param string $column Имя колонки
     * @param string $operation Операция
     * @return string SQL-выражение
     */
    public function compileTimestampExpression(string $column, string $operation): string
    {
        if ($operation === 'CURRENT_TIMESTAMP') {
            return "$column TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP";
        }

        if ($operation === 'UPDATE_TIMESTAMP') {
            return "$column TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP";
            // В PostgreSQL триггеры для ON UPDATE нужно создавать отдельно
        }

        return "$column TIMESTAMP WITH TIME ZONE";
    }

    /**
     * Компилирует условие для хранения JSON (специфично для PostgreSQL).
     *
     * @param string $column Имя колонки
     * @return string SQL-выражение
     */
    public function compileJsonExpression(string $column): string
    {
        return "$column JSONB";
    }



    /**
     * Компилирует условие для геометрических типов (специфично для PostgreSQL).
     *
     * @param string $column Имя колонки
     * @param string $type Тип геометрических данных
     * @return string SQL-выражение
     */
    public function compileSpatialExpression(string $column, string $type): string
    {
        $types = [
            'geometry' => 'GEOMETRY',
            'point' => 'POINT',
            'linestring' => 'LINESTRING',
            'polygon' => 'POLYGON',
            'multipoint' => 'MULTIPOINT',
            'multilinestring' => 'MULTILINESTRING',
            'multipolygon' => 'MULTIPOLYGON',
            'geometrycollection' => 'GEOMETRYCOLLECTION',
        ];

        $postgisType = $types[strtolower($type)] ?? 'GEOMETRY';
        return "$column {$postgisType}";
    }

    /**
     * Возвращает SQL для создания расширения PostGIS.
     *
     * @return string
     */
    public function createPostgisExtension(): string
    {
        return 'CREATE EXTENSION IF NOT EXISTS postgis';
    }
}
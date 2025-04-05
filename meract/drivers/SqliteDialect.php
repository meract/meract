<?php
namespace Meract\Drivers;

use Meract\Core\DatabaseDialectInterface;
use Meract\Core\Blueprint;

/**
 * Реализация диалекта SQLite.
 *
 * Предоставляет SQLite-специфичные методы для компиляции SQL-запросов
 * и работы с особенностями SQLite.
 */
class SqliteDialect implements DatabaseDialectInterface
{
    /**
     * Компилирует SQL-запрос для создания таблицы.
     *
     * @param Blueprint $blueprint Объект с определением таблицы
     * @return string SQL-запрос CREATE TABLE
     */
    public function compileCreateTable(string $table, array $columns, array $options = []): string
    {
        return sprintf(
            'CREATE TABLE %s (%s)',
            $table,
            implode(', ', $columns)
        );
    }


    /**
     * Компилирует определение колонки с учетом специфики SQLite.
     *
     * @param string $type Базовый тип данных
     * @param array $parameters Дополнительные параметры колонки
     * @return string Полное определение колонки для SQL-запроса
     */
    public function compileColumnDefinition(string $columnDefinition, array $parameters = []): string
    {
        $definition = $columnDefinition;
        
        // AUTOINCREMENT для SQLite
        if ($parameters['auto_increment'] ?? false) {
            if (str_contains($columnDefinition, 'INT')) {
                return str_replace('INT', 'INTEGER PRIMARY KEY AUTOINCREMENT', $columnDefinition);
            }
        }
        
        // PRIMARY KEY
        if ($parameters['primary'] ?? false) {
            $definition .= ' PRIMARY KEY';
        }
        
        // NOT NULL
        if (!($parameters['nullable'] ?? true)) {
            $definition .= ' NOT NULL';
        }
        
        // DEFAULT
        if (array_key_exists('default', $parameters)) {
            $default = is_string($parameters['default']) 
                ? "'" . addslashes($parameters['default']) . "'" 
                : $parameters['default'];
            $definition .= " DEFAULT {$default}";
        }
        
        return $definition;
    }

    /**
     * Возвращает ID последней вставленной записи.
     *
     * @param \PDO $pdo Объект PDO
     * @param string|null $sequence Имя последовательности (не используется в SQLite)
     * @return string ID последней вставленной записи
     */
    public function getLastInsertId(\PDO $pdo, string $sequence = null): string
    {
        return $pdo->lastInsertId();
    }

    /**
     * Проверяет поддержку внешних ключей.
     *
     * SQLite поддерживает внешние ключи, но они могут быть отключены
     *
     * @return bool
     */
    public function supportsForeignKeys(): bool
    {
        return true;
    }

    /**
     * Возвращает порт по умолчанию.
     *
     * @return int 0 (SQLite не использует порт)
     */
    public function getDefaultPort(): int
    {
        return 0;
    }

    /**
     * Возвращает имя драйвера PDO для SQLite.
     *
     * @return string 'sqlite'
     */
    public function getDriverName(): string
    {
        return 'sqlite';
    }

    /**
     * Компилирует условие для временных меток (специфично для SQLite).
     *
     * @param string $column Имя колонки
     * @param string $operation Операция
     * @return string SQL-выражение
     */
    public function compileTimestampExpression(string $column, string $operation): string
    {
        // SQLite не поддерживает ON UPDATE для TIMESTAMP
        if ($operation === 'CURRENT_TIMESTAMP') {
            return "$column TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
        }

        return "$column TIMESTAMP";
    }

    /**
     * Компилирует условие для хранения JSON (специфично для SQLite).
     *
     * SQLite не имеет специального типа JSON, используем TEXT
     *
     * @param string $column Имя колонки
     * @return string SQL-выражение
     */
    public function compileJsonExpression(string $column): string
    {
        return "$column TEXT";
    }

    /**
     * Компилирует условие для ENUM (специфично для SQLite).
     *
     * SQLite не поддерживает ENUM, эмулируем через CHECK
     *
     * @param string $column Имя колонки
     * @param array $values Допустимые значения
     * @return string SQL-выражение
     */
    public function compileEnumExpression(string $column, array $values): string
    {
        $escapedValues = array_map(fn($v) => "'" . addslashes($v) . "'", $values);
        $valuesList = implode(', ', $escapedValues);
        return "$column TEXT CHECK($column IN ($valuesList))";
    }

    /**
     * Возвращает SQL для включения внешних ключей (специфично для SQLite).
     *
     * @return string
     */
    public function enableForeignKeysSql(): string
    {
        return 'PRAGMA foreign_keys = ON;';
    }

    /**
     * Возвращает SQL для отключения внешних ключей (специфично для SQLite).
     *
     * @return string
     */
    public function disableForeignKeysSql(): string
    {
        return 'PRAGMA foreign_keys = OFF;';
    }
}
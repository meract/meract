<?php
namespace Meract\Core;

/**
 * Интерфейс для работы с разными СУБД
 */
interface DatabaseDialectInterface
{
    /**
     * Компилирует SQL-запрос для создания таблицы
     *
     * @param Blueprint $blueprint Объект с определением таблицы
     * @return string SQL-запрос CREATE TABLE
     */
    public function compileCreateTable(string $table, array $columns, array $options = []): string;

    /**
     * Компилирует определение колонки
     *
     * @param string $type Базовый тип данных
     * @param array $parameters Дополнительные параметры колонки
     * @return string Полное определение колонки для SQL-запроса
     */
    public function compileColumnDefinition(string $columnDefinition, array $parameters = []): string;

    /**
     * Возвращает ID последней вставленной записи
     *
     * @param \PDO $pdo Объект PDO
     * @param string|null $sequence Имя последовательности (для PostgreSQL)
     * @return string ID последней вставленной записи
     */
    public function getLastInsertId(\PDO $pdo, string $sequence = null): string;

    /**
     * Проверяет поддержку внешних ключей
     *
     * @return bool
     */
    public function supportsForeignKeys(): bool;

    /**
     * Возвращает порт по умолчанию
     *
     * @return int
     */
    public function getDefaultPort(): int;

    /**
     * Возвращает имя драйвера PDO
     *
     * @return string
     */
    public function getDriverName(): string;
}
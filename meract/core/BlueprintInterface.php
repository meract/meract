<?php
namespace Meract\Core;

/**
 * Интерфейс для построения SQL-запросов определения таблиц.
 * 
 * Определяет контракт для классов, реализующих построение структуры таблиц БД.
 * Все методы возвращают self для реализации Fluent Interface.
 */
interface BlueprintInterface
{
    /**
     * Добавляет первичный ключ (id) в таблицу
     * 
     * @return self
     */
    public function id(): self;

    // Строковые типы

    /**
     * Добавляет колонку типа VARCHAR
     * 
     * @param string $column Название колонки
     * @param int $length Длина строки (по умолчанию 255)
     * @return self
     */
    public function string(string $column, int $length = 255): self;

    /**
     * Добавляет колонку типа TEXT
     * 
     * @param string $column Название колонки
     * @return self
     */
    public function text(string $column): self;

    /**
     * Добавляет колонку типа CHAR
     * 
     * @param string $column Название колонки
     * @param int $length Фиксированная длина строки (по умолчанию 255)
     * @return self
     */
    public function char(string $column, int $length = 255): self;

    /**
     * Добавляет колонку типа ENUM
     * 
     * @param string $column Название колонки
     * @param array $values Допустимые значения перечисления
     * @return self
     */
    public function enum(string $column, array $values): self;

    // Числовые типы

    /**
     * Добавляет колонку типа INT
     * 
     * @param string $column Название колонки
     * @param int|null $length Длина числа (необязательно)
     * @return self
     */
    public function integer(string $column, ?int $length = null): self;

    /**
     * Добавляет колонку типа BIGINT
     * 
     * @param string $column Название колонки
     * @param int|null $length Длина числа (необязательно)
     * @return self
     */
    public function bigInteger(string $column, ?int $length = null): self;

    /**
     * Добавляет колонку типа FLOAT
     * 
     * @param string $column Название колонки
     * @param int|null $precision Точность (общее количество цифр)
     * @param int|null $scale Количество цифр после запятой
     * @return self
     */
    public function float(string $column, ?int $precision = null, ?int $scale = null): self;

    /**
     * Добавляет колонку типа DECIMAL
     * 
     * @param string $column Название колонки
     * @param int $precision Точность (общее количество цифр, по умолчанию 10)
     * @param int $scale Количество цифр после запятой (по умолчанию 2)
     * @return self
     */
    public function decimal(string $column, int $precision = 10, int $scale = 2): self;

    /**
     * Добавляет колонку типа BOOLEAN
     * 
     * @param string $column Название колонки
     * @return self
     */
    public function boolean(string $column): self;

    // Дата и время

    /**
     * Добавляет колонку типа DATE
     * 
     * @param string $column Название колонки
     * @return self
     */
    public function date(string $column): self;

    /**
     * Добавляет колонку типа DATETIME
     * 
     * @param string $column Название колонки
     * @param int|null $precision Точность времени в секундах
     * @return self
     */
    public function dateTime(string $column, ?int $precision = null): self;

    /**
     * Добавляет колонку типа TIMESTAMP
     * 
     * @param string $column Название колонки
     * @param int|null $precision Точность времени в секундах
     * @return self
     */
    public function timestamp(string $column, ?int $precision = null): self;

    /**
     * Добавляет колонку типа TIME
     * 
     * @param string $column Название колонки
     * @param int|null $precision Точность времени в секундах
     * @return self
     */
    public function time(string $column, ?int $precision = null): self;

    // JSON

    /**
     * Добавляет колонку типа JSON
     * 
     * @param string $column Название колонки
     * @return self
     */
    public function json(string $column): self;

    // Модификаторы

    /**
     * Делает последнюю добавленную колонку nullable
     * 
     * @return self
     */
    public function nullable(): self;

    /**
     * Устанавливает значение по умолчанию для последней добавленной колонки
     * 
     * @param mixed $value Значение по умолчанию
     * @return self
     */
    public function default($value): self;

    /**
     * Делает последнюю числовую колонку беззнаковой (UNSIGNED)
     * 
     * @return self
     */
    public function unsigned(): self;

    /**
     * Добавляет автоинкремент для последней числовой колонки
     * 
     * @return self
     */
    public function autoIncrement(): self;

    /**
     * Добавляет комментарий к последней добавленной колонке
     * 
     * @param string $comment Текст комментария
     * @return self
     */
    public function comment(string $comment): self;

    /**
     * Добавляет ограничение UNIQUE для последней добавленной колонки
     * 
     * @return self
     */
    public function unique(): self;

    /**
     * Делает последнюю добавленную колонку первичным ключом
     * 
     * @return self
     */
    public function primary(): self;

    /**
     * Добавляет индекс для последней добавленной колонки
     * 
     * @return self
     */
    public function index(): self;

    /**
     * Устанавливает позицию последней добавленной колонки после указанной
     * 
     * @param string $column Название колонки, после которой нужно разместить
     * @return self
     */
    public function after(string $column): self;

    /**
     * Устанавливает последнюю добавленную колонку первой в таблице
     * 
     * @return self
     */
    public function first(): self;

    // Специальные методы

    /**
     * Добавляет стандартные колонки created_at и updated_at
     */
    public function timestamps(): void;

    /**
     * Добавляет колонку deleted_at для "мягкого" удаления
     */
    public function softDeletes(): void;

    /**
     * Добавляет колонку remember_token для аутентификации
     */
    public function rememberToken(): void;

    /**
     * Компилирует SQL-запрос для создания таблицы
     * 
     * @return array Массив SQL-запросов
     */
    public function compileCreate(): array;
}

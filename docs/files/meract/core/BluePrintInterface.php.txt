<?php
namespace Meract\Core;

/**
 * Интерфейс для построения SQL-запросов определения таблиц
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
    public function string(string $column, int $length = 255): self;
    public function text(string $column): self;
    public function char(string $column, int $length = 255): self;
    public function enum(string $column, array $values): self;

    // Числовые типы
    public function integer(string $column, ?int $length = null): self;
    public function bigInteger(string $column, ?int $length = null): self;
    public function float(string $column, ?int $precision = null, ?int $scale = null): self;
    public function decimal(string $column, int $precision = 10, int $scale = 2): self;
    public function boolean(string $column): self;

    // Дата и время
    public function date(string $column): self;
    public function dateTime(string $column, ?int $precision = null): self;
    public function timestamp(string $column, ?int $precision = null): self;
    public function time(string $column, ?int $precision = null): self;

    // JSON
    public function json(string $column): self;

    // Модификаторы
    public function nullable(): self;
    public function default($value): self;
    public function unsigned(): self;
    public function autoIncrement(): self;
    public function comment(string $comment): self;
    public function unique(): self;
    public function primary(): self;
    public function index(): self;
    public function after(string $column): self;
    public function first(): self;

    // Специальные методы
    public function timestamps(): void;
    public function softDeletes(): void;
    public function rememberToken(): void;

    /**
     * Компилирует SQL-запрос для создания таблицы
     * 
     * @return array Массив SQL-запросов
     */
    public function compileCreate(): array;
}
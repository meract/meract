<?php
namespace Meract\Core;

/**
 * Класс для создания SQL-запросов определения таблиц (CREATE TABLE).
 *
 * Позволяет описать структуру таблицы через методы-строители (builder pattern).
 */
class Blueprint
{
	/** @var string Название таблицы */
	private string $table;

	/** @var array<string> Список SQL-определений колонок */
	private array $columns = [];

	/**
	 * Конструктор.
	 *
	 * @param string $table Название таблицы
	 */
	public function __construct(string $table)
	{
		$this->table = $table;
	}

	/**
	 * Добавляет первичный ключ (id) в таблицу.
	 *
	 * @return void
	 */
	public function id(): void
	{
		$this->columns[] = 'id INT AUTO_INCREMENT PRIMARY KEY';
	}

	/**
	 * Добавляет строковую колонку (VARCHAR).
	 *
	 * @param string $column Название колонки
	 * @param int $length Длина строки (по умолчанию 255)
	 * @return self Возвращает текущий объект для цепочки вызовов
	 */
	public function string(string $column, int $length = 255): self
	{
		$this->columns[] = "{$column} VARCHAR({$length})";
		return $this;
	}

	/**
	 * Добавляет ограничение UNIQUE к последней добавленной колонке.
	 *
	 * @return self Возвращает текущий объект для цепочки вызовов
	 */
	public function unique(): self
	{
		$lastColumn = array_pop($this->columns);
		$this->columns[] = "{$lastColumn} UNIQUE";
		return $this;
	}

	/**
	 * Добавляет временные метки (created_at и updated_at).
	 *
	 * @return void
	 */
	public function timestamps(): void
	{
		$this->columns[] = 'created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
		$this->columns[] = 'updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
	}

	/**
	 * Генерирует SQL-запрос для создания таблицы.
	 *
	 * @return string SQL-запрос CREATE TABLE
	 */
	public function compileCreate(): string
	{
		return sprintf(
			'CREATE TABLE %s (%s)',
			$this->table,
			implode(', ', $this->columns)
		);
	}
}

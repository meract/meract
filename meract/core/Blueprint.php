<?php
namespace Meract\Core;

/**
 * Базовый класс Blueprint с общей реализацией для создания структуры таблиц БД.
 * 
 * Предоставляет методы для определения колонок, их типов и модификаторов.
 * Реализует паттерн Fluent Interface для цепочки вызовов.
 */
abstract class Blueprint implements BlueprintInterface
{
	/**
	 * @var string Название таблицы
	 */
	protected string $table;

	/**
	 * @var array Массив SQL-определений колонок
	 */
	protected array $columns = [];

	/**
	 * @var array Дополнительные опции таблицы
	 */
	protected array $tableOptions = [];

	/**
	 * Конструктор класса
	 *
	 * @param string $table Название создаваемой/изменяемой таблицы
	 */
	public function __construct(string $table)
	{
		$this->table = $table;
	}

	// Строковые типы

	/**
	 * Добавляет колонку типа VARCHAR
	 *
	 * @param string $column Название колонки
	 * @param int $length Длина строки (по умолчанию 255)
	 * @return $this
	 */
	public function string(string $column, int $length = 255): self
	{
		$this->columns[] = "{$column} VARCHAR({$length})";
		return $this;
	}

	/**
	 * Добавляет колонку типа TEXT
	 *
	 * @param string $column Название колонки
	 * @return $this
	 */
	public function text(string $column): self
	{
		$this->columns[] = "{$column} TEXT";
		return $this;
	}

	/**
	 * Добавляет колонку типа CHAR
	 *
	 * @param string $column Название колонки
	 * @param int $length Фиксированная длина строки (по умолчанию 255)
	 * @return $this
	 */
	public function char(string $column, int $length = 255): self
	{
		$this->columns[] = "{$column} CHAR({$length})";
		return $this;
	}

	/**
	 * Добавляет колонку типа ENUM
	 *
	 * @param string $column Название колонки
	 * @param array $values Допустимые значения перечисления
	 * @return $this
	 */
	public function enum(string $column, array $values): self
	{
		$values = array_map(fn($v) => "'" . addslashes($v) . "'", $values);
		$this->columns[] = "{$column} ENUM(" . implode(', ', $values) . ")";
		return $this;
	}

	// Числовые типы

	/**
	 * Добавляет колонку типа INT
	 *
	 * @param string $column Название колонки
	 * @param int|null $length Длина числа (необязательно)
	 * @return $this
	 */
	public function integer(string $column, ?int $length = null): self
	{
		$length = $length ? "({$length})" : '';
		$this->columns[] = "{$column} INT{$length}";
		return $this;
	}

	/**
	 * Добавляет колонку типа BIGINT
	 *
	 * @param string $column Название колонки
	 * @param int|null $length Длина числа (необязательно)
	 * @return $this
	 */
	public function bigInteger(string $column, ?int $length = null): self
	{
		$length = $length ? "({$length})" : '';
		$this->columns[] = "{$column} BIGINT{$length}";
		return $this;
	}

	/**
	 * Добавляет колонку типа FLOAT
	 *
	 * @param string $column Название колонки
	 * @param int|null $precision Точность (общее количество цифр)
	 * @param int|null $scale Количество цифр после запятой
	 * @return $this
	 */
	public function float(string $column, ?int $precision = null, ?int $scale = null): self
	{
		if ($precision !== null && $scale !== null) {
			$this->columns[] = "{$column} FLOAT({$precision}, {$scale})";
		} elseif ($precision !== null) {
			$this->columns[] = "{$column} FLOAT({$precision})";
		} else {
			$this->columns[] = "{$column} FLOAT";
		}
		return $this;
	}

	/**
	 * Добавляет колонку типа DECIMAL
	 *
	 * @param string $column Название колонки
	 * @param int $precision Точность (общее количество цифр, по умолчанию 10)
	 * @param int $scale Количество цифр после запятой (по умолчанию 2)
	 * @return $this
	 */
	public function decimal(string $column, int $precision = 10, int $scale = 2): self
	{
		$this->columns[] = "{$column} DECIMAL({$precision}, {$scale})";
		return $this;
	}

	/**
	 * Добавляет колонку типа BOOLEAN
	 *
	 * @param string $column Название колонки
	 * @return $this
	 */
	public function boolean(string $column): self
	{
		$this->columns[] = "{$column} BOOLEAN";
		return $this;
	}

	// Дата и время

	/**
	 * Добавляет колонку типа DATE
	 *
	 * @param string $column Название колонки
	 * @return $this
	 */
	public function date(string $column): self
	{
		$this->columns[] = "{$column} DATE";
		return $this;
	}

	/**
	 * Добавляет колонку типа DATETIME
	 *
	 * @param string $column Название колонки
	 * @param int|null $precision Точность времени в секундах
	 * @return $this
	 */
	public function dateTime(string $column, ?int $precision = null): self
	{
		$precision = $precision ? "({$precision})" : '';
		$this->columns[] = "{$column} DATETIME{$precision}";
		return $this;
	}

	/**
	 * Добавляет колонку типа TIMESTAMP
	 *
	 * @param string $column Название колонки
	 * @param int|null $precision Точность времени в секундах
	 * @return $this
	 */
	public function timestamp(string $column, ?int $precision = null): self
	{
		$precision = $precision ? "({$precision})" : '';
		$this->columns[] = "{$column} TIMESTAMP{$precision}";
		return $this;
	}

	/**
	 * Добавляет колонку типа TIME
	 *
	 * @param string $column Название колонки
	 * @param int|null $precision Точность времени в секундах
	 * @return $this
	 */
	public function time(string $column, ?int $precision = null): self
	{
		$precision = $precision ? "({$precision})" : '';
		$this->columns[] = "{$column} TIME{$precision}";
		return $this;
	}

	// JSON

	/**
	 * Добавляет колонку типа JSON
	 *
	 * @param string $column Название колонки
	 * @return $this
	 */
	public function json(string $column): self
	{
		$this->columns[] = "{$column} JSON";
		return $this;
	}

	// Модификаторы

	/**
	 * Делает последнюю добавленную колонку nullable
	 *
	 * @return $this
	 */
	public function nullable(): self
	{
		$lastColumn = array_pop($this->columns);
		$this->columns[] = "{$lastColumn} NULL";
		return $this;
	}

	/**
	 * Устанавливает значение по умолчанию для последней добавленной колонки
	 *
	 * @param mixed $value Значение по умолчанию
	 * @return $this
	 */
	public function default($value): self
	{
		$lastColumn = array_pop($this->columns);
		$defaultValue = is_string($value) ? "'" . addslashes($value) . "'" : $value;
		$this->columns[] = "{$lastColumn} DEFAULT {$defaultValue}";
		return $this;
	}

	/**
	 * Делает последнюю числовую колонку беззнаковой (UNSIGNED)
	 *
	 * @return $this
	 */
	public function unsigned(): self
	{
		$lastColumn = array_pop($this->columns);
		$this->columns[] = "{$lastColumn} UNSIGNED";
		return $this;
	}

	/**
	 * Добавляет комментарий к последней добавленной колонке
	 *
	 * @param string $comment Текст комментария
	 * @return $this
	 */
	public function comment(string $comment): self
	{
		$lastColumn = array_pop($this->columns);
		$this->columns[] = "{$lastColumn} COMMENT '" . addslashes($comment) . "'";
		return $this;
	}

	/**
	 * Добавляет ограничение UNIQUE для последней добавленной колонки
	 *
	 * @return $this
	 */
	public function unique(): self
	{
		$lastColumn = array_pop($this->columns);
		$this->columns[] = "{$lastColumn} UNIQUE";
		return $this;
	}

	/**
	 * Делает последнюю добавленную колонку первичным ключом
	 *
	 * @return $this
	 */
	public function primary(): self
	{
		$lastColumn = array_pop($this->columns);
		$this->columns[] = "{$lastColumn} PRIMARY KEY";
		return $this;
	}

	/**
	 * Добавляет индекс для последней добавленной колонки
	 *
	 * @return $this
	 */
	public function index(): self
	{
		$lastColumn = array_pop($this->columns);
		$this->columns[] = "{$lastColumn} INDEX";
		return $this;
	}

	/**
	 * Устанавливает позицию последней добавленной колонки после указанной
	 *
	 * @param string $column Название колонки, после которой нужно разместить
	 * @return $this
	 */
	public function after(string $column): self
	{
		$lastColumn = array_pop($this->columns);
		$this->columns[] = "{$lastColumn} AFTER {$column}";
		return $this;
	}

	/**
	 * Устанавливает последнюю добавленную колонку первой в таблице
	 *
	 * @return $this
	 */
	public function first(): self
	{
		$lastColumn = array_pop($this->columns);
		$this->columns[] = "{$lastColumn} FIRST";
		return $this;
	}

	// Специальные методы

	/**
	 * Добавляет стандартные колонки created_at и updated_at
	 */
	public function timestamps(): void
	{
		$this->columns[] = 'created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
		$this->columns[] = 'updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
	}

	/**
	 * Добавляет колонку deleted_at для "мягкого" удаления
	 */
	public function softDeletes(): void
	{
		$this->columns[] = 'deleted_at TIMESTAMP NULL';
	}

	/**
	 * Добавляет колонку remember_token для аутентификации
	 */
	public function rememberToken(): void
	{
		$this->string('remember_token', 100)->nullable();
	}
}

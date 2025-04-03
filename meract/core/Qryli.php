<?php
namespace Meract\Core;

use PDO;
use PDOException;
use Exception;

/**
 * Класс для построения и выполнения SQL-запросов.
 *
 * Реализует fluent-интерфейс для удобного построения запросов.
 */
class Qryli
{
	/** @var PDO Подключение к базе данных */
	private static $pdo;

	/** @var string Текст SQL-запроса */
	private $query = '';

	/** @var array Параметры для подготовленного запроса */
	private $params = [];


	public function __construct() {
		self::setPdo($GLOBALS['pdo'] ?? null);
	}

	/**
	 * Устанавливает подключение PDO.
	 *
	 * @param PDO $pdo Объект PDO
	 * @return void
	 */
	public static function setPdo(PDO $pdo): void
	{
		self::$pdo = $pdo;
	}

	/**
	 * Начинает построение SELECT запроса.
	 *
	 * @param string $columns Список колонок (по умолчанию '*')
	 * @return self
	 */
	public static function select(string $columns = '*'): self
	{
		$instance = new self();
		$instance->query = "SELECT $columns ";
		return $instance;
	}

	/**
	 * Добавляет FROM часть в запрос.
	 *
	 * @param string $table Имя таблицы
	 * @return self
	 */
	public function from(string $table): self
	{
		$this->query .= "FROM $table ";
		return $this;
	}

	/**
	 * Добавляет WHERE условие в запрос.
	 *
	 * @param string $condition Условие WHERE
	 * @param array $params Параметры для подготовленного запроса
	 * @return self
	 */
	public function where(string $condition, array $params = []): self
	{
		$this->query .= "WHERE $condition ";
		$this->params = array_merge($this->params, $params);
		return $this;
	}

	/**
	 * Начинает построение INSERT запроса.
	 *
	 * @param string $table Имя таблицы
	 * @param array $data Данные для вставки (ключи - имена колонок)
	 * @return self
	 */
	public static function insert(string $table, array $data): self
	{
		$instance = new self();
		$columns = implode(', ', array_keys($data));
		$placeholders = implode(', ', array_fill(0, count($data), '?'));
		$instance->query = "INSERT INTO $table ($columns) VALUES ($placeholders) ";
		$instance->params = array_values($data);
		return $instance;
	}

	/**
	 * Начинает построение UPDATE запроса.
	 *
	 * @param string $table Имя таблицы
	 * @param array $data Данные для обновления (ключи - имена колонок)
	 * @return self
	 */
	public static function update(string $table, array $data): self
	{
		$instance = new self();
		$set = implode(', ', array_map(fn($key) => "$key = ?", array_keys($data)));
		$instance->query = "UPDATE $table SET $set ";
		$instance->params = array_values($data);
		return $instance;
	}

	/**
	 * Начинает построение DELETE запроса.
	 *
	 * @param string $table Имя таблицы
	 * @return self
	 */
	public static function delete(string $table): self
	{
		$instance = new self();
		$instance->query = "DELETE FROM $table ";
		return $instance;
	}

	/**
	 * Добавляет ORDER BY в запрос.
	 *
	 * @param string $column Колонка для сортировки
	 * @param string $order Направление сортировки (ASC/DESC)
	 * @return self
	 */
	public function orderBy(string $column, string $order = 'ASC'): self
	{
		$this->query .= "ORDER BY $column $order ";
		return $this;
	}

	/**
	 * Добавляет LIMIT в запрос.
	 *
	 * @param int $limit Количество записей
	 * @return self
	 */
	public function limit(int $limit): self
	{
		$this->query .= "LIMIT $limit ";
		return $this;
	}

	/**
	 * Добавляет JOIN в запрос.
	 *
	 * @param string $table Таблица для соединения
	 * @param string $on Условие соединения
	 * @param string $type Тип соединения (INNER, LEFT, RIGHT и т.д.)
	 * @return self
	 */
	public function join(string $table, string $on, string $type = 'INNER'): self
	{
		$this->query .= "$type JOIN $table ON $on ";
		return $this;
	}

	/**
	 * Добавляет GROUP BY в запрос.
	 *
	 * @param string $columns Колонки для группировки
	 * @return self
	 */
	public function groupBy(string $columns): self
	{
		$this->query .= "GROUP BY $columns ";
		return $this;
	}

	/**
	 * Выполняет построенный запрос.
	 *
	 * @return array Результат выполнения запроса:
	 *              - Для SELECT: массив записей
	 *              - Для INSERT/UPDATE/DELETE: массив с количеством затронутых строк
	 * @throws Exception Если PDO не установлен или произошла ошибка выполнения
	 */
	public function run(): array
	{
		if (!self::$pdo) {
			throw new Exception("PDO object is not set. Use QB::setPdo() to set it.");
		}

		try {
			$stmt = self::$pdo->prepare($this->query);
			$stmt->execute($this->params);

			if (stripos($this->query, 'SELECT') === 0) {
				return $stmt->fetchAll(PDO::FETCH_ASSOC);
			}

			return ['affected_rows' => $stmt->rowCount()];
		} catch (PDOException $e) {
			throw new Exception("Query execution failed: " . $e->getMessage());
		}
	}
}

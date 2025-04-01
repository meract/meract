<?php
namespace Meract\Core;

use PDO;
use PDOException;

/**
 * Класс для работы с базой данных (реализует паттерн Singleton).
 * 
 * Предоставляет единое соединение с базой данных через PDO.
 */
class Database
{
	/** @var Database|null Единственный экземпляр класса */
	private static $instance = null;

	/** @var PDO Объект PDO для работы с базой данных */
	private $pdo;

	/**
	 * Закрытый конструктор (реализация Singleton).
	 *
	 * @param array $config Конфигурация подключения к БД
	 */
	private function __construct(array $config)
	{
		$this->pdo = $this->createConnection($config);
	}

	/**
	 * Создает соединение с базой данных на основе конфигурации.
	 *
	 * Поддерживает драйверы: mysql, pgsql, sqlite.
	 *
	 * @param array $config Конфигурация подключения
	 * @return PDO Объект PDO
	 * @throws \InvalidArgumentException Если передан неподдерживаемый драйвер
	 */
	private function createConnection(array $config): PDO
	{
		$driver = $config['driver'];
		switch ($driver) {
		case 'mysql':
			$dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
			break;
		case 'pgsql':
			$dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}";
			break;
		case 'sqlite':
			$dsn = "sqlite:{$config['sqlite_path']}";
			break;
		default:
			throw new \InvalidArgumentException("Unsupported database driver: {$driver}");
		}

		try {
			$pdo = new PDO($dsn, $config['username'] ?? null, $config['password'] ?? null);
			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			return $pdo;
		} catch (PDOException $e) {
			die("Database connection failed: " . $e->getMessage());
		}
	}

	/**
	 * Возвращает единственный экземпляр класса (Singleton).
	 *
	 * @param array $config Конфигурация подключения
	 * @return Database Экземпляр класса
	 */
	public static function getInstance(array $config): self
	{
		if (self::$instance === null) {
			self::$instance = new self($config);
		}
		return self::$instance;
	}

	/**
	 * Возвращает объект PDO для работы с базой данных.
	 *
	 * @return PDO Объект PDO
	 */
	public function getPdo(): PDO
	{
		return $this->pdo;
	}
}

<?php

namespace Tests\Unit\Core;

use Meract\Core\Database;
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
	private $config = [
		'driver' => 'sqlite',
		'sqlite_path' => ':memory:',
	];

	protected function tearDown(): void
	{
		// Сбрасываем инстанс после каждого теста
		/* Database::resetInstance(); */
	}

	public function testConnection()
	{
		$db = Database::getInstance($this->config);
		$pdo = $db->getPdo();

		$this->assertInstanceOf(\PDO::class, $pdo);
	}

	public function testQueryExecution()
	{
		$db = Database::getInstance($this->config);
		$pdo = $db->getPdo();

		$result = $pdo->query('SELECT 1 + 1')->fetchColumn();
		$this->assertEquals(2, $result);
	}

	public function testTableCreation()
	{
		$db = Database::getInstance($this->config);
		$pdo = $db->getPdo();

		// Создаем тестовую таблицу
		$pdo->exec('CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT)');

		// Проверяем ее существование
		$stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='test'");
		$this->assertEquals('test', $stmt->fetchColumn());
	}
}

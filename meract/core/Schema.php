<?php
namespace Meract\Core;

use Meract\Core\Blueprint;

/**
 * Класс для управления структурой базы данных.
 *
 * Предоставляет методы для создания и удаления таблиц.
 */
class Schema
{
	/**
	 * Конструктор класса.
	 *
	 * @param \PDO $pdo Объект PDO для работы с базой данных
	 */
	public function __construct(
		private \PDO $pdo
	) {}

	/**
	 * Создает новую таблицу в базе данных.
	 *
	 * @param string $table Название таблицы
	 * @param callable $callback Функция-строитель, принимающая Blueprint
	 * @return void
	 *
	 * @example
	 * $schema->create('users', function($table) {
	 *     $table->id();
	 *     $table->string('name');
	 * });
	 */
	public function create(string $table, callable $callback): void
	{
		$blueprint = new Blueprint($table);
		$callback($blueprint);

		$sql = $blueprint->compileCreate();
		$this->pdo->prepare($sql)->execute();
	}

	/**
	 * Удаляет таблицу из базы данных.
	 *
	 * @param string $table Название таблицы для удаления
	 * @return void
	 *
	 * @warning Будьте осторожны, данные будут удалены безвозвратно!
	 */
	public function drop(string $table): void
	{
		$this->pdo->prepare("DROP TABLE $table;")->execute();
	}
}

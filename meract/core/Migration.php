<?php
namespace Meract\Core;

use Meract\Core\Schema;

/**
 * Абстрактный класс миграции базы данных.
 *
 * Предоставляет базовую структуру для создания и отмены миграций.
 */
abstract class Migration
{
	/** @var Schema Экземпляр класса для работы со схемой базы данных */
	protected Schema $schema;

	/**
	 * Устанавливает соединение с базой данных.
	 *
	 * @param \PDO $pdo Объект PDO для работы с базой данных
	 * @return void
	 */
	public function setPdo(\PDO $pdo): void
	{
		$this->schema = new Schema($pdo);
	}

	/**
	 * Применяет миграцию.
	 *
	 * @return void
	 */
	abstract public function up(): void;

	/**
	 * Отменяет миграцию.
	 *
	 * @return void
	 */
	abstract public function down(): void;
}

<?php

use Meract\Core\Migrator;

/**
 * Класс для отката миграций базы данных.
 * 
 * Обрабатывает аргументы командной строки и выполняет откат миграций:
 * - Без аргументов - откатывает последнюю миграцию
 * - С аргументом - откатывает до указанной миграции
 */
return new class 
{
	/**
	 * Выполняет откат миграций.
	 *
	 * @param array $argv Аргументы командной строки
	 * @param int $argc Количество аргументов
	 * @return void
	 *
	 * @example
	 * php migrate.php rollback       # Откатывает последнюю миграцию
	 * php migrate.php rollback 123  # Откатывает до миграции 123
	 */
	public function run(array $argv, int $argc): void
	{
		global $pdo;

		// Инициализация мигратора с указанием пути к миграциям и подключения к БД
		$migrator = new Migrator(
			PROJECT_DIR . '/app/migrations', 
			$pdo
		);

		if ($argc < 2) {
			// Откат последней примененной миграции
			$migrator->rollback();
		} else {
			// Откат до указанной миграции
			$migrator->rollback($argv[1]);
		}
	}
};

<?php
namespace Meract\Core;

/**
 * Класс для управления миграциями базы данных.
 *
 * Обеспечивает применение и откат миграций.
 */
class Migrator 
{
	/**
	 * @param string $migrationsPath Путь к директории с файлами миграций
	 * @param \PDO $pdo Объект PDO для работы с базой данных
	 */
	public function __construct(
		private string $migrationsPath,
		private \PDO $pdo
	) {}

	/**
	 * Применяет указанные миграции.
	 *
	 * @param string|null $migrationName Имя конкретной миграции (если null - применяются все)
	 * @return void
	 */
	public function migrate(?string $migrationName = null): void
	{
		$files = $this->getTargetFiles($migrationName);

		foreach ($files as $file) {
			$migration = $this->loadMigration($file);
			$migration->up();
			echo "Применена миграция: " . basename($file) . PHP_EOL;
		}
	}

	/**
	 * Откатывает указанные миграции.
	 *
	 * @param string|null $migrationName Имя конкретной миграции (если null - откатываются все)
	 * @return void
	 */
	public function rollback(?string $migrationName = null): void
	{
		$files = $this->getTargetFiles($migrationName, true);

		foreach ($files as $file) {
			$migration = $this->loadMigration($file);
			$migration->down();
			echo "Удалена миграция: " . basename($file) . PHP_EOL;
		}
	}

	/**
	 * Загружает миграцию из файла.
	 *
	 * @param string $file Путь к файлу миграции
	 * @return Migration Объект миграции
	 * @throws \RuntimeException Если формат миграции некорректен
	 */
	private function loadMigration(string $file): Migration
	{
		$migration = require $file;
		$migration->setPdo($this->pdo);

		if ($migration instanceof \Closure) {
			return $migration($this->pdo);
		}

		if ($migration instanceof Migration) {
			return $migration;
		}

		throw new \RuntimeException("Некорректный формат миграции в файле: " . basename($file));
	}

	/**
	 * Возвращает список файлов миграций для обработки.
	 *
	 * @param string|null $name Имя миграции (если нужна конкретная)
	 * @param bool $reverse Нужно ли обратить порядок файлов (для отката)
	 * @return array Массив путей к файлам
	 */
	private function getTargetFiles(?string $name, bool $reverse = false): array
	{
		$files = $name 
			? [$this->findMigrationFile($name)]
			: $this->getMigrationFiles();

		return $reverse ? array_reverse($files) : $files;
	}

	/**
	 * Возвращает все файлы миграций из директории.
	 *
	 * @return array Массив путей к файлам
	 */
	private function getMigrationFiles(): array
	{
		return glob($this->migrationsPath . '/*.php') ?: [];
	}

	/**
	 * Находит файл миграции по имени.
	 *
	 * @param string $name Часть имени файла миграции
	 * @return string Полный путь к файлу
	 * @throws \RuntimeException Если миграция не найдена
	 */
	private function findMigrationFile(string $name): string
	{
		foreach ($this->getMigrationFiles() as $file) {
			if (str_contains($file, $name)) {
				return $file;
			}
		}
		throw new \RuntimeException("Миграция '$name' не найдена!");
	}
}

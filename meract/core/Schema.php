<?php
namespace Meract\Core;

use Meract\Core\BlueprintFactory;
use PDO;

/**
 * Класс для работы со структурой базы данных.
 * 
 * Предоставляет методы для создания и удаления таблиц через PDO соединение.
 */
class Schema
{
    /**
     * @param PDO $pdo Объект PDO для работы с базой данных
     * @param DatabaseDialectInterface|null $dialect Диалект базы данных (опционально)
     */
    public function __construct(
        private PDO $pdo,
        private ?DatabaseDialectInterface $dialect = null
    ) {}

    /**
     * Создает новую таблицу в базе данных.
     * 
     * @param string $table Название создаваемой таблицы
     * @param callable $callback Функция для определения структуры таблицы через Blueprint
     * @throws \PDOException В случае ошибки выполнения SQL-запросов
     */
    public function create(string $table, callable $callback): void
    {
        $blueprint = BlueprintFactory::create($this->pdo, $table);
        $callback($blueprint);

        $queries = $blueprint->compileCreate();
        foreach ($queries as $query) {
            $this->pdo->exec($query);
        }
    }

    /**
     * Удаляет таблицу из базы данных.
     * 
     * @param string $table Название удаляемой таблицы
     * @throws \PDOException В случае ошибки выполнения SQL-запроса
     */
    public function drop(string $table): void
    {
        $this->pdo->exec("DROP TABLE IF EXISTS $table");
    }
}

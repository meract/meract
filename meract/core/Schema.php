<?php
namespace Meract\Core;

use Meract\Drivers\DialectFactory;
use PDO;

class Schema
{
    public function __construct(
        private PDO $pdo,
        private ?DatabaseDialectInterface $dialect = null
    ) {
        if ($this->dialect === null) {
            $this->dialect = DialectFactory::create($pdo);
        }
    }

    public function create(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table, $this->dialect);
        $callback($blueprint);

        $queries = $blueprint->compileCreate();
        foreach ($queries as $query) {
            $this->pdo->exec($query);
        }
    }

    public function drop(string $table): void
    {
        $this->pdo->exec("DROP TABLE IF EXISTS $table");
    }
}
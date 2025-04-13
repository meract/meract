<?php
namespace Meract\Core\BluePrints;
use Meract\Core\Blueprint;
/**
 * Реализация Blueprint для SQLite
 */
class SQLiteBP extends Blueprint
{
    public function id(): self
    {
        $this->columns[] = 'id INTEGER PRIMARY KEY AUTOINCREMENT';
        return $this;
    }

    public function autoIncrement(): self
    {
        $lastColumn = array_pop($this->columns);
        $this->columns[] = "{$lastColumn} AUTOINCREMENT";
        return $this;
    }

    public function compileCreate(): array
    {
        $columns = implode(",\n    ", $this->columns);
        return ["CREATE TABLE `{$this->table}` (\n    {$columns}\n);"];
    }

    // SQLite игнорирует UNSIGNED и AFTER
    public function unsigned(): self
    {
        return $this;
    }

    public function after(string $column): self
    {
        return $this;
    }
}
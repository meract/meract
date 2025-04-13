<?php
namespace Meract\Core\BluePrints;
use Meract\Core\Blueprint;
/**
 * Реализация Blueprint для PostgreSQL
 */
class PostgreSQLBP extends Blueprint
{
    public function id(): self
    {
        $this->columns[] = 'id SERIAL PRIMARY KEY';
        return $this;
    }

    public function autoIncrement(): self
    {
        $lastColumn = array_pop($this->columns);
        $this->columns[] = "{$lastColumn} SERIAL";
        return $this;
    }

    public function compileCreate(): array
    {
        $columns = implode(",\n    ", $this->columns);
        return ["CREATE TABLE \"{$this->table}\" (\n    {$columns}\n);"];
    }

    // PostgreSQL использует другой синтаксис для JSON
    public function json(string $column): self
    {
        $this->columns[] = "{$column} JSONB";
        return $this;
    }
}
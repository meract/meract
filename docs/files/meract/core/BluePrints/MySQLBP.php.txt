<?php
namespace Meract\Core\BluePrints;
use Meract\Core\Blueprint;
/**
 * Реализация Blueprint для MySQL
 */
class MySQLBP extends Blueprint
{
    public function id(): self
    {
        $this->columns[] = 'id INT AUTO_INCREMENT PRIMARY KEY';
        return $this;
    }

    public function autoIncrement(): self
    {
        $lastColumn = array_pop($this->columns);
        $this->columns[] = "{$lastColumn} AUTO_INCREMENT";
        return $this;
    }

    public function compileCreate(): array
    {
        $columns = implode(",\n    ", $this->columns);
        $options = !empty($this->tableOptions) ? ' ' . implode(' ', $this->tableOptions) : '';
        return ["CREATE TABLE `{$this->table}` (\n    {$columns}\n){$options};"];
    }

    // Специфичные для MySQL методы
    public function engine(string $engine): self
    {
        $this->tableOptions[] = "ENGINE={$engine}";
        return $this;
    }

    public function charset(string $charset): self
    {
        $this->tableOptions[] = "DEFAULT CHARSET={$charset}";
        return $this;
    }

    public function collation(string $collation): self
    {
        $this->tableOptions[] = "COLLATE={$collation}";
        return $this;
    }
}
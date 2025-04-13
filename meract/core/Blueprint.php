<?php
namespace Meract\Core;

/**
 * Базовый класс Blueprint с общей реализацией
 */
abstract class Blueprint implements BlueprintInterface
{
    protected string $table;
    protected array $columns = [];
    protected array $tableOptions = [];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    // Строковые типы
    public function string(string $column, int $length = 255): self
    {
        $this->columns[] = "{$column} VARCHAR({$length})";
        return $this;
    }

    public function text(string $column): self
    {
        $this->columns[] = "{$column} TEXT";
        return $this;
    }

    public function char(string $column, int $length = 255): self
    {
        $this->columns[] = "{$column} CHAR({$length})";
        return $this;
    }

    public function enum(string $column, array $values): self
    {
        $values = array_map(fn($v) => "'" . addslashes($v) . "'", $values);
        $this->columns[] = "{$column} ENUM(" . implode(', ', $values) . ")";
        return $this;
    }

    // Числовые типы
    public function integer(string $column, ?int $length = null): self
    {
        $length = $length ? "({$length})" : '';
        $this->columns[] = "{$column} INT{$length}";
        return $this;
    }

    public function bigInteger(string $column, ?int $length = null): self
    {
        $length = $length ? "({$length})" : '';
        $this->columns[] = "{$column} BIGINT{$length}";
        return $this;
    }

    public function float(string $column, ?int $precision = null, ?int $scale = null): self
    {
        if ($precision !== null && $scale !== null) {
            $this->columns[] = "{$column} FLOAT({$precision}, {$scale})";
        } elseif ($precision !== null) {
            $this->columns[] = "{$column} FLOAT({$precision})";
        } else {
            $this->columns[] = "{$column} FLOAT";
        }
        return $this;
    }

    public function decimal(string $column, int $precision = 10, int $scale = 2): self
    {
        $this->columns[] = "{$column} DECIMAL({$precision}, {$scale})";
        return $this;
    }

    public function boolean(string $column): self
    {
        $this->columns[] = "{$column} BOOLEAN";
        return $this;
    }

    // Дата и время
    public function date(string $column): self
    {
        $this->columns[] = "{$column} DATE";
        return $this;
    }

    public function dateTime(string $column, ?int $precision = null): self
    {
        $precision = $precision ? "({$precision})" : '';
        $this->columns[] = "{$column} DATETIME{$precision}";
        return $this;
    }

    public function timestamp(string $column, ?int $precision = null): self
    {
        $precision = $precision ? "({$precision})" : '';
        $this->columns[] = "{$column} TIMESTAMP{$precision}";
        return $this;
    }

    public function time(string $column, ?int $precision = null): self
    {
        $precision = $precision ? "({$precision})" : '';
        $this->columns[] = "{$column} TIME{$precision}";
        return $this;
    }

    // JSON
    public function json(string $column): self
    {
        $this->columns[] = "{$column} JSON";
        return $this;
    }

    // Модификаторы
    public function nullable(): self
    {
        $lastColumn = array_pop($this->columns);
        $this->columns[] = "{$lastColumn} NULL";
        return $this;
    }

    public function default($value): self
    {
        $lastColumn = array_pop($this->columns);
        $defaultValue = is_string($value) ? "'" . addslashes($value) . "'" : $value;
        $this->columns[] = "{$lastColumn} DEFAULT {$defaultValue}";
        return $this;
    }

    public function unsigned(): self
    {
        $lastColumn = array_pop($this->columns);
        $this->columns[] = "{$lastColumn} UNSIGNED";
        return $this;
    }

    public function comment(string $comment): self
    {
        $lastColumn = array_pop($this->columns);
        $this->columns[] = "{$lastColumn} COMMENT '" . addslashes($comment) . "'";
        return $this;
    }

    public function unique(): self
    {
        $lastColumn = array_pop($this->columns);
        $this->columns[] = "{$lastColumn} UNIQUE";
        return $this;
    }

    public function primary(): self
    {
        $lastColumn = array_pop($this->columns);
        $this->columns[] = "{$lastColumn} PRIMARY KEY";
        return $this;
    }

    public function index(): self
    {
        $lastColumn = array_pop($this->columns);
        $this->columns[] = "{$lastColumn} INDEX";
        return $this;
    }

    public function after(string $column): self
    {
        $lastColumn = array_pop($this->columns);
        $this->columns[] = "{$lastColumn} AFTER {$column}";
        return $this;
    }

    public function first(): self
    {
        $lastColumn = array_pop($this->columns);
        $this->columns[] = "{$lastColumn} FIRST";
        return $this;
    }

    // Специальные методы
    public function timestamps(): void
    {
        $this->columns[] = 'created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
        $this->columns[] = 'updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
    }

    public function softDeletes(): void
    {
        $this->columns[] = 'deleted_at TIMESTAMP NULL';
    }

    public function rememberToken(): void
    {
        $this->string('remember_token', 100)->nullable();
    }
}
<?php
namespace Meract\Core;

use Meract\Core\DatabaseDialectInterface;

/**
 * Класс для создания SQL-запросов определения таблиц
 */
class Blueprint
{
    private string $table;
    private array $columns = [];
    private DatabaseDialectInterface $dialect;
    private ?string $engine = null;
    private ?string $charset = null;
    private ?string $collation = null;
    private array $tableOptions = [];
    private ?string $tableComment = null;
    
    /**
     * Конструктор.
     *
     * @param string $table Название таблицы
     */
    public function __construct(string $table, DatabaseDialectInterface $dialect)
    {
        $this->table = $table;
        $this->dialect = $dialect;
    }

    /**
     * Добавляет первичный ключ (id) в таблицу.
     *
     * @return void
     */
    public function id(): void
    {
        $this->columns[] = 'id IDTYPE';
    }

    // Строковые типы
    public function string(string $column, int $length = 255): self
    {
        $this->columns[] = "{$column} VARCHAR({$length})";
        return $this;
    }

    public function char(string $column, int $length = 255): self
    {
        $this->columns[] = "{$column} CHAR({$length})";
        return $this;
    }

    public function text(string $column): self
    {
        $this->columns[] = "{$column} TEXT";
        return $this;
    }

    public function mediumText(string $column): self
    {
        $this->columns[] = "{$column} MEDIUMTEXT";
        return $this;
    }

    public function longText(string $column): self
    {
        $this->columns[] = "{$column} LONGTEXT";
        return $this;
    }

    public function binary(string $column, int $length = 255): self
    {
        $this->columns[] = "{$column} BINARY({$length})";
        return $this;
    }

    public function blob(string $column): self
    {
        $this->columns[] = "{$column} BLOB";
        return $this;
    }

    public function mediumBlob(string $column): self
    {
        $this->columns[] = "{$column} MEDIUMBLOB";
        return $this;
    }

    public function longBlob(string $column): self
    {
        $this->columns[] = "{$column} LONGBLOB";
        return $this;
    }

    public function enum(string $column, array $values): self
    {
        $values = array_map(fn($v) => "'{$v}'", $values);
        $this->columns[] = "{$column} ENUM(" . implode(', ', $values) . ")";
        return $this;
    }

    public function set(string $column, array $values): self
    {
        $values = array_map(fn($v) => "'{$v}'", $values);
        $this->columns[] = "{$column} SET(" . implode(', ', $values) . ")";
        return $this;
    }

    // Числовые типы
    public function integer(string $column, ?int $length = null): self
    {
        $length = $length ? "({$length})" : '';
        $this->columns[] = "{$column} INT{$length}";
        return $this;
    }

    public function tinyInteger(string $column, ?int $length = null): self
    {
        $length = $length ? "({$length})" : '';
        $this->columns[] = "{$column} TINYINT{$length}";
        return $this;
    }

    public function smallInteger(string $column, ?int $length = null): self
    {
        $length = $length ? "({$length})" : '';
        $this->columns[] = "{$column} SMALLINT{$length}";
        return $this;
    }

    public function mediumInteger(string $column, ?int $length = null): self
    {
        $length = $length ? "({$length})" : '';
        $this->columns[] = "{$column} MEDIUMINT{$length}";
        return $this;
    }

    public function bigInteger(string $column, ?int $length = null): self
    {
        $length = $length ? "({$length})" : '';
        $this->columns[] = "{$column} BIGINT{$length}";
        return $this;
    }

    public function unsignedInteger(string $column, ?int $length = null): self
    {
        $length = $length ? "({$length})" : '';
        $this->columns[] = "{$column} INT{$length} UNSIGNED";
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

    public function double(string $column, ?int $precision = null, ?int $scale = null): self
    {
        if ($precision !== null && $scale !== null) {
            $this->columns[] = "{$column} DOUBLE({$precision}, {$scale})";
        } elseif ($precision !== null) {
            $this->columns[] = "{$column} DOUBLE({$precision})";
        } else {
            $this->columns[] = "{$column} DOUBLE";
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

    public function time(string $column, ?int $precision = null): self
    {
        $precision = $precision ? "({$precision})" : '';
        $this->columns[] = "{$column} TIME{$precision}";
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

    public function year(string $column): self
    {
        $this->columns[] = "{$column} YEAR";
        return $this;
    }

    // JSON
    public function json(string $column): self
    {
        $this->columns[] = "{$column} JSON";
        return $this;
    }

    // Пространственные типы
    public function geometry(string $column): self
    {
        $this->columns[] = "{$column} GEOMETRY";
        return $this;
    }

    public function point(string $column): self
    {
        $this->columns[] = "{$column} POINT";
        return $this;
    }

    public function lineString(string $column): self
    {
        $this->columns[] = "{$column} LINESTRING";
        return $this;
    }

    public function polygon(string $column): self
    {
        $this->columns[] = "{$column} POLYGON";
        return $this;
    }

    public function multiPoint(string $column): self
    {
        $this->columns[] = "{$column} MULTIPOINT";
        return $this;
    }

    public function multiLineString(string $column): self
    {
        $this->columns[] = "{$column} MULTILINESTRING";
        return $this;
    }

    public function multiPolygon(string $column): self
    {
        $this->columns[] = "{$column} MULTIPOLYGON";
        return $this;
    }

    public function geometryCollection(string $column): self
    {
        $this->columns[] = "{$column} GEOMETRYCOLLECTION";
        return $this;
    }

    // Дополнительные методы
    public function nullable(): self
    {
        $lastColumn = array_pop($this->columns);
        $this->columns[] = "{$lastColumn} NULL";
        return $this;
    }

    public function default($value): self
    {
        $lastColumn = array_pop($this->columns);
        $defaultValue = is_string($value) ? "'{$value}'" : $value;
        $this->columns[] = "{$lastColumn} DEFAULT {$defaultValue}";
        return $this;
    }

    public function unsigned(): self
    {
        $lastColumn = array_pop($this->columns);
        $this->columns[] = "{$lastColumn} UNSIGNED";
        return $this;
    }

    public function autoIncrement(): self
    {
        $lastColumn = array_pop($this->columns);
        $this->columns[] = "{$lastColumn} AUTO_INCREMENT";
        return $this;
    }

    public function comment(string $comment): self
    {
        $lastColumn = array_pop($this->columns);
        $this->columns[] = "{$lastColumn} COMMENT '{$comment}'";
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

    /**
     * Генерирует SQL-запрос для создания таблицы.
     *
     * @return string SQL-запрос CREATE TABLE
     */
    public function compileCreate(): array
    {
        return [
            $this->dialect->compileCreateTable($this->table, $this->columns, $this->tableOptions)
        ];
    }
}

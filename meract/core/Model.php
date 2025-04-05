<?php
namespace Meract\Core;

use pdo;
use pdoexception;

/**
 * Абстрактный класс модели для работы с базой данных.
 *
 * Реализует базовые CRUD-операции и паттерн Active Record.
 */
abstract class Model
{
	/**
	 * @var string Имя таблицы в базе данных
	 */
	protected static $table;

	/**
	 * @var string Первичный ключ таблицы (по умолчанию 'id')
	 */
	protected $primarykey = 'id';

	/**
	 * @var array Поля, доступные для массового назначения
	 */
	protected $fillable = [];

	/**
	 * @var array Атрибуты модели
	 */
	protected $attributes = [];

	/**
	 * Конструктор модели.
	 *
	 * @param array $attributes Атрибуты для массового назначения
	 */
	public function __construct(array $attributes = [])
	{
		$this->fill($attributes);
	}

	/**
	 * Заполняет атрибуты модели.
	 *
	 * @param array $attributes Массив атрибутов
	 * @return void
	 */
	public function fill(array $attributes)
	{
		foreach ($attributes as $key => $value) {
			if (in_array($key, $this->fillable)) {
				$this->attributes[$key] = $value;
			}
		}
	}

	/**
	 * Возвращает первую запись из таблицы.
	 *
	 * @return static|null Объект модели или null, если записей нет
	 */
	public static function first()
	{
		$pdo = self::getpdo();
		$table = self::gettable();

		$stmt = $pdo->prepare("select id from {$table} order by id asc limit 1");
		$stmt->execute();
		$result = $stmt->fetch(pdo::FETCH_ASSOC);

		if ($result && isset($result['id'])) {
			return static::find($result['id']);
		}
		return null;
	}

	/**
	 * Возвращает последнюю запись из таблицы.
	 *
	 * @return static|null Объект модели или null, если записей нет
	 */
	public static function last()
	{
		$pdo = self::getpdo();
		$table = self::gettable();

		$stmt = $pdo->prepare("select id from {$table} order by id desc limit 1");
		$stmt->execute();
		$result = $stmt->fetch(pdo::FETCH_ASSOC);

		if ($result && isset($result['id'])) {
			return static::find($result['id']);
		}
		return null;
	}

	/**
	 * Получает подключение к базе данных.
	 *
	 * @return PDO Объект PDO
	 */
    protected static function getPdo(): PDO
    {
        return SDR::make("pdo.connection");
    }

	/**
	 * Определяет имя таблицы для модели.
	 *
	 * @return string Имя таблицы
	 */
	protected static function gettable()
	{
		if (static::$table === null) {
			$classname = (new \reflectionclass(static::class))->getshortname();
			static::$table = strtolower($classname) . 's';
		}
		return static::$table;
	}

	/**
	 * Находит запись по идентификатору.
	 *
	 * @param mixed $id Значение первичного ключа
	 * @return static|null Объект модели или null, если запись не найдена
	 */
	public static function find($id)
	{
		$pdo = self::getpdo();
		$table = self::gettable();
		$stmt = $pdo->prepare("select * from {$table} where id = :id");
		$stmt->execute(['id' => $id]);
		$result = $stmt->fetch(pdo::FETCH_ASSOC);

		if ($result) {
			return new static($result);
		}
		return null;
	}

	/**
	 * Возвращает все записи из таблицы.
	 *
	 * @return array Массив записей
	 */
	public static function all()
	{
		$pdo = self::getpdo();
		$table = self::gettable();
		$stmt = $pdo->query("select * from {$table}");
		return $stmt->fetchall(pdo::FETCH_ASSOC);
	}

	/**
	 * Сохраняет модель (создает новую запись или обновляет существующую).
	 *
	 * @return void
	 */
	public function save()
	{
		if (isset($this->attributes[$this->primarykey])) {
			$this->update();
		} else {
			$this->insert();
		}
	}

	/**
	 * Создает новую запись в базе данных.
	 *
	 * @return void
	 */
	protected function insert()
	{
		$pdo = self::getpdo();
		$table = self::gettable();

		$columns = implode(', ', array_keys($this->attributes));
		$values = ':' . implode(', :', array_keys($this->attributes));

		$sql = "insert into {$table} ({$columns}) values ({$values})";
		$stmt = $pdo->prepare($sql);
		$stmt->execute($this->attributes);

		$this->attributes[$this->primarykey] = $pdo->lastinsertid();
	}

	/**
	 * Обновляет существующую запись в базе данных.
	 *
	 * @return void
	 */
	protected function update()
	{
		$pdo = self::getpdo();
		$table = self::gettable();

		$set = [];
		foreach ($this->attributes as $key => $value) {
			if ($key !== $this->primarykey) {
				$set[] = "{$key} = :{$key}";
			}
		}
		$set = implode(', ', $set);

		$sql = "update {$table} set {$set} where {$this->primarykey} = :{$this->primarykey}";
		$stmt = $pdo->prepare($sql);
		$stmt->execute($this->attributes);
	}

	/**
	 * Удаляет запись из базы данных.
	 *
	 * @return void
	 */
	public function delete()
	{
		$pdo = self::getpdo();
		$table = self::gettable();

		$sql = "delete from {$table} where {$this->primarykey} = :{$this->primarykey}";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$this->primarykey => $this->attributes[$this->primarykey]]);
	}

	/**
	 * Магический метод для доступа к атрибутам.
	 *
	 * @param string $name Имя атрибута
	 * @return mixed Значение атрибута или null
	 */
	public function __get($name)
	{
		return $this->attributes[$name] ?? null;
	}

	/**
	 * Магический метод для установки атрибутов.
	 *
	 * @param string $name Имя атрибута
	 * @param mixed $value Значение атрибута
	 * @return void
	 */
	public function __set($name, $value)
	{
		if (in_array($name, $this->fillable)) {
			$this->attributes[$name] = $value;
		}
	}
}

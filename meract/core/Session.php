<?php
namespace Meract\Core;

use Meract\Core\Storage;

/**
 * Класс для работы с сессиями
 * 
 * @package Meract\Core
 */
class Session
{
	/**
	 * Идентификатор сессии
	 * 
	 * @var string|null
	 */
	private ?string $id = null;

	/**
	 * Данные сессии
	 * 
	 * @var array
	 */
	private array $data = [];

	/**
	 * Объект запроса
	 * 
	 * @var Request
	 */
	private Request $request;

	/**
	 * Конструктор сессии
	 * 
	 * @param Request $req Объект запроса
	 */
	public function __construct(Request $req)
	{
		$this->request = $req;
		$this->initialize();
	}

	/**
	 * Инициализация сессии
	 * 
	 * @return void
	 */
	private function initialize(): void
	{
		// Получаем идентификатор сессии из куки, если он есть
		$this->id = $this->request->cookie('LUMSESSID');

		if ($this->id && Storage::get($this->id, 'sessions')) {
			// Загружаем данные из хранилища
			$this->data = Storage::get($this->id, 'sessions');
		} else {
			// Создаем новую сессию
			$this->id = uniqid('sess_', true);
			$this->data = [];
		}
	}

	/**
	 * Статический метод для создания сессии
	 * 
	 * @param Request $req Объект запроса
	 * @return self
	 */
	public static function start(Request $req): self
	{
		return new self($req);
	}

	/**
	 * Завершает сессию и устанавливает куки
	 * 
	 * @param Response $resp Объект ответа
	 * @return Response
	 */
	public function end(Response $resp): Response
	{
		// Сохраняем данные в хранилище
		Storage::set($this->id, $this->data, 'sessions');

		// Устанавливаем куки
		$resp->cookie(
			'MERACTSESSID', 
			$this->id, 
			time() + 3600, // Время жизни куки (1 час)
			'/',          // Путь
			'',            // Домен
			false,         // Secure
			true           // HttpOnly
		);

		return $resp;
	}

	/**
	 * Магический метод для установки свойств
	 * 
	 * @param string $name Имя свойства
	 * @param mixed $value Значение
	 * @return void
	 */
	public function __set(string $name, mixed $value): void
	{
		$this->data[$name] = $value;
	}

	/**
	 * Магический метод для получения свойств
	 * 
	 * @param string $name Имя свойства
	 * @return mixed
	 */
	public function __get(string $name): mixed
	{
		return $this->data[$name] ?? null;
	}

	/**
	 * Магический метод для проверки существования свойства
	 * 
	 * @param string $name Имя свойства
	 * @return bool
	 */
	public function __isset(string $name): bool
	{
		return isset($this->data[$name]);
	}

	/**
	 * Магический метод для удаления свойства
	 * 
	 * @param string $name Имя свойства
	 * @return void
	 */
	public function __unset(string $name): void
	{
		unset($this->data[$name]);
	}
}

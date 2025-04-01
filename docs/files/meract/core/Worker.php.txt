<?php
namespace Meract\Core;

use Meract\Core\Model;

/**
 * Абстрактный класс для работы с фоновыми задачами (воркерами).
 *
 * Предоставляет базовые методы для регистрации задач и отправки данных на сервер.
 */
abstract class Worker
{
	/**
	 * Регистрирует новую задачу в системе.
	 *
	 * @param string $name Название задачи
	 * @param string $message Сообщение/данные задачи
	 * @return void
	 *
	 * @example
	 * Worker::register('email-sender', 'Send welcome email to user 42');
	 */
	public static function register(string $name, string $message): void
	{
		$work = new WorkerInstance(["name" => $name, "message" => $message]);
		$work->save();
	}

	/**
	 * Отправляет сообщение на сервер обработки задач.
	 *
	 * @param string $message Сообщение для отправки
	 * @return mixed Ответ сервера
	 *
	 * @global array $config Глобальная конфигурация приложения
	 *
	 * @example
	 * $response = Worker::sendToServer('task-completed:42');
	 */
	public static function sendToServer(string $message): mixed
	{
		global $config;
		$ch = curl_init("http://localhost:" . $config['server']['port'] . '/worker-' . $config['worker']['endpoint'] . '?data=' . urlencode($message));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
}

/**
 * Модель для хранения информации о задачах в базе данных.
 *
 * Наследует базовую функциональность модели (CRUD операции).
 */
class WorkerInstance extends Model
{
	/** @var string Название таблицы в БД */
	protected static $table = "meract_workers";

	/** @var array Список полей, доступных для массового назначения */
	protected $fillable = ['id', 'name', 'message'];
}

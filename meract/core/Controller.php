<?php
namespace Meract\Core;

/**
 * Абстрактный базовый контроллер.
 *
 * Содержит общие методы для работы с HTTP-ответами.
 */
abstract class Controller
{
	/**
	 * Подготавливает HTML-контент для HTTP-ответа.
	 *
	 * Создает объект Response с HTML-данными, статусом 200 (OK)
	 * и устанавливает заголовок Content-Type.
	 *
	 * @param string $html HTML-контент для отправки
	 * @param int $status - Необязательный параметр http статус
	 * @return Response Объект HTTP-ответа с подготовленными данными
	 */
	public static function html(string $html, int $status = 200): Response
	{
		$r = new Response($html, $status);
		$r->header("Content-Type", "text/html");
		return $r;
	}


	/**
	 * Подготавливает JSON-контент для HTTP-ответа.
	 *
	 * Создает объект Response с JSON-данными, статусом 200 (OK)
	 * и устанавливает заголовок Content-Type.
	 *
	 * @param mixed $json JSON-срока | объект | массив для отправки 
	 * @param int $status - Необязательный параметр http статус
	 * @return Response Объект HTTP-ответа с подготовленными данными
	 */
	public static function json(mixed $json, int $status = 200): Response
	{
		if (gettype($json) !== 'string') {
			$json = json_encode($json);
		}
		$r = new Response($json, $status);
		$r->header("Content-Type", "application/json");
		return $r;
	}

	/**
	 * Перенаправляет пользователя на другой адрес
	 *
	 * @param string $url адрес для перенаправления
	 * @param int $status - Необязательный параметр http статус
	 * @return Response Объект HTTP-ответа с подготовленными данными
	 */
	public static function redirect(string $url, int $status = 301) {
		$r = new Response("", $status);
		$r->header("Location", $url);
		return $r;
	}
}

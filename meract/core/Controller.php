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
	 * @return Response Объект HTTP-ответа с подготовленными данными
	 */
	public static function html(string $html): Response
	{
		$r = new Response($html, 200);
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
	 * @return Response Объект HTTP-ответа с подготовленными данными
	 */
	public static function json(mixed $json): Response
	{
		if (gettype($json) !== 'string') {
			$json = json_encode($json);
		}
		$r = new Response($json, 200);
		$r->header("Content-Type", "application/json");
		return $r;
	}
}

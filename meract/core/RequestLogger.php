<?php
namespace Meract\Core;

/**
 * Класс для логирования HTTP-запросов.
 *
 * Записывает в вывод информацию о входящих запросах:
 * - HTTP-метод
 * - URI запроса
 * - Временную метку
 */
class RequestLogger 
{
	/**
	 * Обрабатывает и логирует HTTP-запрос.
	 *
	 * @param Request $rq Объект запроса, содержащий:
	 *                   - uri (строка) - URI запроса
	 *                   - method (строка) - HTTP-метод (GET, POST и т.д.)
	 * @return void
	 */
	public function handle(Request $rq): void
	{
		$uri = $rq->uri;
		$method = $rq->method;
		$time = date('l jS \of F Y h:i:s A');

		echo "$method -> $uri\t|\t$time\n";
	}
}

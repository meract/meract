<?php
namespace Meract\Core;
/**
 * Объект запроса от клиента
 */
class Request 
{
	/**
	 * Метод запроса
	 *
	 * @var string 
	 */
	public $method = null;

	/**
	 * Запрошенный URI
	 *
	 * @var string
	 */
	public $uri = null;

	/**
	 * Параметры запроса
	 *
	 * @var array
	 */
	public $parameters = [];

	/**
	 * Заголовки запроса
	 *
	 * @var array
	 */
	public $headers = [];

	/**
	 * Cookies запроса
	 *
	 * @var array
	 */
	public $cookies = [];

	/**
	 * Создаёт объект Request из глобавльной переменной $_SERVER
	 * @return Request
	 */
	public static function fromGlobals(): Request
	{
		$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
		$uri = $_SERVER['REQUEST_URI'] ?? '/';
		$headers = getallheaders();

		$request = new self($method, $uri, $headers);
		$request->parameters = $_REQUEST;

		// Для JSON-запросов
		if (isset($headers['Content-Type']) && strpos($headers['Content-Type'], 'application/json') !== false) {
			$jsonData = json_decode(file_get_contents('php://input'), true);
			$request->parameters = array_merge($request->parameters, $jsonData ?? []);
		}

		return $request;
	}

	/**
	 * Создание нового экземпляра запроса с использованием строки заголовка
	 *
	 * @param string 			$header
	 * @return Request
	 */
	public static function withHeaderString($header)
	{
		// Разделяем заголовки и тело (учитываем \r\n\r\n)
		$parts = preg_split("/\r\n\r\n/", $header, 2);
		$headersPart = $parts[0];
		$body = isset($parts[1]) ? trim($parts[1]) : '';

		$lines = explode("\r\n", $headersPart);

		// Метод и URI из первой строки
		$firstLine = array_shift($lines);
		$method = '';
		$uri = '';
		if (preg_match('/^([A-Z]+)\s+(.*?)(?:\s+HTTP\/\d\.\d)?$/', $firstLine, $matches)) {
			$method = $matches[1];
			$uri = $matches[2];
		}

		$headers = [];
		$cookies = [];
		$parameters = [];

		foreach ($lines as $line) {
			if (strpos($line, ': ') !== false) {
				list($key, $value) = explode(': ', $line, 2);
				$headers[trim($key)] = trim($value);
			}
		}

		// Парсинг cookies
		if (isset($headers['Cookie'])) {
			$cookiePairs = explode(';', $headers['Cookie']);
			foreach ($cookiePairs as $cookiePair) {
				$parts = explode('=', trim($cookiePair), 2);
				if (count($parts) === 2) {
					$cookies[$parts[0]] = $parts[1];
				}
			}
		}

		// Обработка POST-данных
		if ($method === 'POST' && !empty($body)) {
			if (isset($headers['Content-Type']) && 
				strpos($headers['Content-Type'], 'application/x-www-form-urlencoded') !== false) {
				parse_str($body, $parameters);
			}
		}

		// Создание объекта запроса
		$request = new static($method, $uri, $headers);
		$request->parameters = $parameters;
		$request->cookies = $cookies;

		return $request;
	}
	/**
	 * Конструктор запроса
	 *
	 * @param string 			$method
	 * @param string 			$uri
	 * @param array 			$headers
	 * @return void
	 */
	public function __construct( $method, $uri, $headers = [] ) 
	{
		$this->headers = $headers;
		$this->method = strtoupper( $method );

		// разделение URI и строки параметров
		@list( $this->uri, $params ) = explode( '?', $uri );

		// разбор параметров
		parse_str($params ?? '', $this->parameters);

		// разбор cookies
		$this->parseCookies();
	}

	/**
	 * Разбор cookies из заголовков
	 *
	 * @return void
	 */
	protected function parseCookies()
	{
		if (isset($this->headers['Cookie'])) {
			$cookies = explode(';', $this->headers['Cookie']);
			foreach ($cookies as $cookie) {
				list($key, $value) = explode('=', trim($cookie));
				$this->cookies[$key] = $value;
			}
		}
	}

	/**
	 * Возвращает метод запроса
	 *
	 * @return string
	 */
	public function method()
	{
		return $this->method;
	}

	/**
	 * Возвращает URI запроса
	 *
	 * @return string
	 */
	public function uri()
	{
		return $this->uri;
	}

	/**
	 * Возвращает заголовок запроса
	 *
	 * @return string
	 */
	public function header( $key, $default = null )
	{
		if ( !isset( $this->headers[$key] ) )
		{
			return $default;
		}

		return $this->headers[$key];
	}

	/**
	 * Возвращает параметр запроса
	 *
	 * @return string
	 */
	public function param( $key, $default = null )
	{
		if ( !isset( $this->parameters[$key] ) )
		{
			return $default;
		}

		return $this->parameters[$key];
	}

	/**
	 * Возвращает cookie запроса
	 *
	 * @return string
	 */
	public function cookie( $key, $default = null )
	{
		if ( !isset( $this->cookies[$key] ) )
		{
			return $default;
		}

		return $this->cookies[$key];
	}
}

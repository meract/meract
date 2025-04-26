<?php
namespace Meract\Core;
/**
 * Класс формирует ответ пользователю
 */
class Response 
{
	/**
	 * Cookies
	 * @var array
	 */
	protected $cookies = [];
	/**
	 * Массив доступных HTTP-кодов ответов
	 *
	 * @var array
	 */
	protected static $statusCodes = [
		// Информационные 1xx
		100 => 'Continue',
		101 => 'Switching Protocols',

		// Успешные 2xx
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',

		// Перенаправления 3xx
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found', // 1.1
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		// 306 устарел, но зарезервирован
		307 => 'Temporary Redirect',

		// Ошибки клиента 4xx
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',

		// Ошибки сервера 5xx
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		509 => 'Bandwidth Limit Exceeded'
	];


	/**
	 * Отправляет ответ (в режиме customServer)
	 * @return void
	 */
	public function send(): void
    {
		header('Content-Type:');
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        
        foreach ($this->cookies as $name => $cookie) {
            setcookie(
                $name,
                $cookie['value'],
                $cookie['expire'],
                $cookie['path'],
                $cookie['domain'],
                $cookie['secure'],
                $cookie['httponly']
            );
        }
        
        echo $this->body;
    }

	/**
	 * Возвращает простой ответ на основе статусного кода
	 *
	 * @param int			$status
	 * @return Response
	 */
	public static function error( $status )
	{
		return new static( "<h1>PHPServer: ".$status." - ".static::$statusCodes[$status]."</h1>", $status );
	}

	/**
	 * Текущий статус ответа
	 *
	 * @var int
	 */
	protected $status = 200;

	/**
	 * Текущее тело ответа
	 *
	 * @var string
	 */
	protected $body = '';

	/**
	 * Текущие заголовки ответа
	 *
	 * @var array
	 */
	protected $headers = [];

	/**
	 * Конструктор нового объекта Response
	 *
	 * @param string 		$body
	 * @param int 			$status
	 * @return void
	 */
	public function __construct( $body = "", $status = null )
	{
		if ( !is_null( $status ) )
		{
			$this->status = $status;
		}

		$this->body = $body;

		// установка начальных заголовков
	}

	/**
	 * Возвращает тело ответа
	 *
	 * @return string
	 */
	public function body(string|null $body = null) : string|null
	{
		if ($body === null) {
			return $this->body;
		} else {
			$this->body = (string)$body;
			return null;
		}
	}

	/**
	 * Добавляет или перезаписывает параметр заголовка
	 *
	 * @param string 			$key
	 * @param string 			$value
	 * @return void
	 */
	public function header( $key, $value )
	{
		$this->headers[ucfirst($key)] = $value;
	}

	/**
	 * Создает строку заголовка на основе текущего объекта
	 *
	 * @return string
	 */
	public function buildHeaderString()
	{
		$lines = [];

		// статус ответа
		$lines[] = "HTTP/1.1 ".$this->status." ".static::$statusCodes[$this->status];

		// добавление заголовков
		foreach( $this->headers as $key => $value )
		{
			$lines[] = $key.": ".$value;
		}

		return implode( " \r\n", $lines )."\r\n\r\n";
	}

	/**
	 * Устанавливает cookie
	 *
	 * @param string $name Имя cookie
	 * @param string $value Значение cookie
	 * @param int $expire Время истечения срока действия cookie (timestamp)
	 * @param string $path Путь на сервере, на котором будет доступна cookie
	 * @param string $domain Домен, на котором будет доступна cookie
	 * @param bool $secure Указывает, что cookie должна передаваться только по HTTPS
	 * @param bool $httpOnly Указывает, что cookie доступна только через HTTP (не через JavaScript)
	 * @return void
	 */
	public function setCookie($name, $value, $expire = 0, $path = '', $domain = '', $secure = false, $httpOnly = false)
	{
		$cookie = urlencode($name) . '=' . urlencode($value);

		if ($expire > 0) {
			$cookie .= '; expires=' . gmdate('D, d-M-Y H:i:s T', $expire);
		}

		if (!empty($path)) {
			$cookie .= '; path=' . $path;
		}

		if (!empty($domain)) {
			$cookie .= '; domain=' . $domain;
		}

		if ($secure) {
			$cookie .= '; secure';
		}

		if ($httpOnly) {
			$cookie .= '; HttpOnly';
		}

		$this->header('Set-Cookie', $cookie);
	}

	/**
	 * Преобразует данные ответа в строку
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->buildHeaderString().$this->body();
	}
}

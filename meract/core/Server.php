<?php
namespace Meract\Core;
class Server 
{
	/**
	 * Текущий хост
	 *
	 * @var string
	 */
	protected $host = null;

	/**
	 * Текущий порт
	 *
	 * @var int
	 */
	protected $port = null;

	/**
	 * Привязанный сокет
	 * 
	 * @var resource
	 */
	protected $socket = null;

	/**
	 * Конструктор нового экземпляра Server
	 * 
	 * @param string       $host
	 * @param int         $port
	 * @return void
	 */
	public function __construct( $host, $port)
	{
		$this->host = $host;
		$this->port = (int) $port;

		// создаем сокет
		$this->createSocket();

		// привязываем сокет
		$this->bind();
	}

	/**
	 * Создание нового ресурса сокета
	 *
	 * @return void
	 */
	protected function createSocket()
	{
		$this->socket = socket_create( AF_INET, SOCK_STREAM, 0 );
	}

	/**
	 * Привязка ресурса сокета
	 *
	 * @throws ClanCats\Station\PHPServer\Exception
	 * @return void
	 */
	protected function bind()
	{
		if ( !socket_bind( $this->socket, $this->host, $this->port ) )
		{
			throw new Exception( 'Could not bind: '.$this->host.':'.$this->port.' - '.socket_strerror( socket_last_error() ) );
		}
	}

	/**
	 * Ожидание запросов
	 *
	 * @param callable         $callback
	 * @return void 
	 */
	public function listen( $callback, $init_callback )
	{
		if (is_callable($init_callback)) {
			$init_callback();
		}
		// проверяем, является ли callback допустимым
		if ( !is_callable( $callback ) )
		{
			throw new Exception( 'Переданный аргумент должен быть вызываемым.' );
		}

		while ( 1 ) 
		{
			// ожидаем соединений
			socket_listen( $this->socket );
			// пытаемся получить ресурс сокета клиента
			// если false, произошла ошибка, закрываем соединение и продолжаем
			if ( !$client = socket_accept( $this->socket ) ) 
			{
				socket_close( $client ); continue;
			}

			// создаем новый экземпляр запроса с заголовком клиента.
			// В реальном мире, конечно, нельзя просто фиксировать максимальный размер в 1024..
			$request = Request::withHeaderString( socket_read( $client, 1024 ) );

			// выполняем callback
			$response = call_user_func( $callback, $request );
			if ($response == null) {continue;}
			// проверяем, действительно ли мы получили объект Response
			// если нет, возвращаем объект Response с ошибкой 404
			if ( !$response || !$response instanceof Response )
			{
				$response = Response::error( 404 );
			}

			// преобразуем наш ответ в строку
			$response = (string) $response;

			// записываем ответ в сокет клиента
			socket_write( $client, $response, strlen( $response ) );

			// закрываем соединение, чтобы можно было принимать новые
			socket_close( $client );
		}
	}
}


class Response 
{
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
	public function __construct( $body, $status = null )
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
	public function body()
	{
		return $this->body;
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
	 * Создание нового экземпляра запроса с использованием строки заголовка
	 *
	 * @param string 			$header
	 * @return Request
	 */
	public static function withHeaderString( $header )
	{
		$lines = explode( "\n", $header );

		// метод и URI
		@list( $method, $uri ) = explode( ' ', array_shift( $lines ) );
		$headers = [];

		foreach( $lines as $line )
		{
			// очистка строки
			$line = trim( $line );

			if ( strpos( $line, ': ' ) !== false )
			{
				list( $key, $value ) = explode( ': ', $line );
				$headers[$key] = $value;
			}
		}	

		// создание нового объекта запроса
		return new static( $method, $uri, $headers );
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

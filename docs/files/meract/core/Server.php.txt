<?php
namespace Meract\Core;
use Meract\Core\Request;
/**
 * Класс запускающий сервер
 */
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
		while (1) {
			// ожидаем соединений
			socket_listen($this->socket);
			// пытаемся получить ресурс сокета клиента
			if (!$client = socket_accept($this->socket)) {
				socket_close($client); 
				continue;
			}

			// Читаем весь запрос (не только первые 1024 байт)
			$requestData = '';
			while ($buffer = socket_read($client, 1024)) {
				$requestData .= $buffer;
				// Проверяем, достигли ли конца заголовков
				if (strpos($requestData, "\r\n\r\n") !== false) {
					// Для POST запросов читаем тело
					if (preg_match('/Content-Length: (\d+)/i', $requestData, $matches)) {
						$contentLength = (int)$matches[1];
						$headersEndPos = strpos($requestData, "\r\n\r\n") + 4;
						$bodyLength = strlen($requestData) - $headersEndPos;

						// Добираем оставшиеся данные тела
						while ($bodyLength < $contentLength) {
							$buffer = socket_read($client, $contentLength - $bodyLength);
							$requestData .= $buffer;
							$bodyLength = strlen($requestData) - $headersEndPos;
						}
					}
					break;
				}
			}

			// создаем экземпляр запроса
			$request = Request::withHeaderString($requestData);

			// выполняем callback
			$response = call_user_func($callback, $request);
			if ($response == null) { continue; }

			if (!$response || !$response instanceof Response) {
				$response = Response::error(404);
			}

			$response = (string) $response;
			socket_write($client, $response, strlen($response));
			socket_close($client);
		}
	}
}



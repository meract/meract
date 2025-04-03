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



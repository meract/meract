<?php
namespace Meract\Core;
use Meract\Core\Morph;
/**
 * Класс для управления маршрутизацией HTTP-запросов с поддержкой именованных маршрутов,
 * middleware, групп маршрутов и обработки статических файлов.
 */
class Route
{
	/** @var array Массив зарегистрированных маршрутов */
	private static $routes = [];

	/** @var array Массив именованных маршрутов */
	private static $namedRoutes = [];

	/** @var Server|null Сервер для обработки запросов */
	private static $server = null;

	/** @var RequestLogger|null Логгер запросов */
	private static $requestLogger = null;

	/** @var string|null Путь к статическим файлам */
	private static $staticPath = null;

	/** @var callable|null Обработчик 404 ошибки */
	private static $notFoundCallback = null;

	/** @var array Глобальные middleware */
	private static $globalMiddlewares = [];

	/** @var array Стек групп маршрутов */
	private static $groupStack = [];


	/**
	 * Устанавливает сервер и логгер запросов
	 *
	 * @param Server $server Объект сервера
	 * @param RequestLogger $requestLogger Логгер запросов
	 * @return void
	 */
	public static function setServer(Server $server, RequestLogger $requestLogger): void
	{
		self::$server = $server;
		self::$requestLogger = $requestLogger;
	}

	/**
	 * Устанавливает путь к статическим файлам
	 *
	 * @param string $path Путь к папке со статическими файлами
	 * @return void
	 */
	public static function staticFolder(string $path): void
	{
		self::$staticPath = rtrim($path, '/');
	}

	/**
	 * Устанавливает обработчик 404 ошибки
	 *
	 * @param callable $callback Функция-обработчик
	 * @return void
	 */
	public static function notFound(callable $callback): void
	{
		self::$notFoundCallback = $callback;
	}

	/**
	 * Регистрирует GET-маршрут
	 *
	 * @param string $path Путь маршрута
	 * @param callable $callback Обработчик маршрута
	 * @param array $middlewares Массив middleware
	 * @param string|null $name Имя маршрута (для генерации URL)
	 * @return void
	 */
	public static function get(string $path, callable $callback, array $middlewares = [], ?string $name = null): void
	{
		self::addRoute('GET', $path, $callback, $middlewares, $name);
	}

	/**
	 * Регистрирует POST-маршрут
	 *
	 * @param string $path Путь маршрута
	 * @param callable $callback Обработчик маршрута
	 * @param array $middlewares Массив middleware
	 * @param string|null $name Имя маршрута (для генерации URL)
	 * @return void
	 */
	public static function post(string $path, callable $callback, array $middlewares = [], ?string $name = null): void
	{
		self::addRoute('POST', $path, $callback, $middlewares, $name);
	}

	/**
	 * Регистрирует PUT-маршрут
	 *
	 * @param string $path Путь маршрута
	 * @param callable $callback Обработчик маршрута
	 * @param array $middlewares Массив middleware
	 * @param string|null $name Имя маршрута (для генерации URL)
	 * @return void
	 */
	public static function put(string $path, callable $callback, array $middlewares = [], ?string $name = null): void
	{
		self::addRoute('PUT', $path, $callback, $middlewares, $name);
	}

	/**
	 * Регистрирует DELETE-маршрут
	 *
	 * @param string $path Путь маршрута
	 * @param callable $callback Обработчик маршрута
	 * @param array $middlewares Массив middleware
	 * @param string|null $name Имя маршрута (для генерации URL)
	 * @return void
	 */
	public static function delete(string $path, callable $callback, array $middlewares = [], ?string $name = null): void
	{
		self::addRoute('DELETE', $path, $callback, $middlewares, $name);
	}

	/**
	 * Регистрирует PATCH-маршрут
	 *
	 * @param string $path Путь маршрута
	 * @param callable $callback Обработчик маршрута
	 * @param array $middlewares Массив middleware
	 * @param string|null $name Имя маршрута (для генерации URL)
	 * @return void
	 */
	public static function patch(string $path, callable $callback, array $middlewares = [], ?string $name = null): void
	{
		self::addRoute('PATCH', $path, $callback, $middlewares, $name);
	}

	/**
	 * Регистрирует OPTIONS-маршрут
	 *
	 * @param string $path Путь маршрута
	 * @param callable $callback Обработчик маршрута
	 * @param array $middlewares Массив middleware
	 * @param string|null $name Имя маршрута (для генерации URL)
	 * @return void
	 */
	public static function options(string $path, callable $callback, array $middlewares = [], ?string $name = null): void
	{
		self::addRoute('OPTIONS', $path, $callback, $middlewares, $name);
	}

	/**
	 * Регистрирует HEAD-маршрут
	 *
	 * @param string $path Путь маршрута
	 * @param callable $callback Обработчик маршрута
	 * @param array $middlewares Массив middleware
	 * @param string|null $name Имя маршрута (для генерации URL)
	 * @return void
	 */
	public static function head(string $path, callable $callback, array $middlewares = [], ?string $name = null): void
	{
		self::addRoute('HEAD', $path, $callback, $middlewares, $name);
	}

	/**
	 * Автоматически регистрирует маршруты для компонентов Morph
	 *
	 * @param string|null $componentsPath Путь к компонентам Morph
	 * @return void
	 */
	public static function autoRegisterMorphComponents(?string $componentsPath = null): void
	{

		self::get("/morph-component/{name}", function(Request $rq, array $params) {
			$name = $params['name'];
			$view = new \Meract\Core\View("components/{$name}");
			return new Response($view, 200);
		});

		self::post("/morph-component/{name}", function(Request $rq, array $params) {
			$name = $params['name'];
			$view = new \Meract\Core\View("components/{$name}", (array)$rq->parameters);
			return new Response($view, 200);
		});



		$handler = function ($rq, $data) {
			$array = \Meract\Core\Morph::resolve($data['hash']);

			if (!isset($array[0]) || !is_callable($array[0])) {
				return;
			}

			$middleware = $array[1] ?? null;
			return $middleware ? $middleware($rq, $array[0]) : $array[0]($rq);
		};


		self::get("/morph-live/{hash}", $handler);
		self::post("/morph-live/{hash}", $handler);

		self::post("/morph-trigger/{trigger}", function ($request, $data) {
			if (file_exists(base_path("app/morph-triggers/". $data['trigger'] . '.php'))) {
				return (new Response())->body(json_encode((require base_path("app/morph-triggers/". $data['trigger'] . '.php'))($request->parameters)))->header("Content-Type", "application/json");
			} else {return (new Response('{"error" : "not found"', 404));}
			
		});

		// Регистрация маршрута для тем
		self::get("/morph-themes/{theme}", function($rq, $data) {
			try {
				$theme = file_get_contents(__DIR__.'/../../app/views/themes/'.$data['theme']);
			} catch(\Exception $e) {
				return new Response("", 404);
			}
			$resp = new Response($theme, 200);
			$resp->header('Content-Type', 'text/css');
			return $resp;
		});

		// Регистрация маршрута для цветовых схем
		self::get("/morph-colorschemes/{scheme}", function($rq, $data) {
			try {
				$theme = file_get_contents(__DIR__.'/../../app/views/colorschemes/'.$data['scheme']);
			} catch(\Exception $e) {
				return new Response("", 404);
			}
			$resp = new Response($theme, 200);
			$resp->header('Content-Type', 'text/css');
			return $resp;
		});

		self::get('/morph-scripts', function ($rq) {
			return (new Response(\Meract\Core\ScriptBuilder::getScriptsForPath($rq->parameters['path'])))->header("Content-Type", "application/javascript");
		});
	}

	/**
	 * Добавляет глобальный middleware
	 *
	 * @param callable|object $middleware Middleware (функция или объект с методом handle)
	 * @return void
	 * @throws \InvalidArgumentException Если middleware не callable и не имеет метода handle
	 */
	public static function middleware($middleware): void
	{
		if (is_callable($middleware)) {
			self::$globalMiddlewares[] = $middleware;
		} elseif (is_object($middleware) && method_exists($middleware, 'handle')) {
			self::$globalMiddlewares[] = $middleware;
		} else {
			throw new \InvalidArgumentException("Middleware must be callable or implement handle() method");
		}
	}

	/**
	 * Группирует маршруты с общим префиксом и middleware
	 *
	 * @param string $prefix Префикс пути для группы
	 * @param callable $callback Функция с определением маршрутов группы
	 * @param array $middlewares Массив middleware для группы
	 * @param string|null $namePrefix Префикс для имен маршрутов в группе
	 * @return void
	 */
	public static function group(string $prefix, callable $callback, array $middlewares = [], ?string $namePrefix = null): void
	{
		self::$groupStack[] = [
			'prefix' => $prefix,
			'middlewares' => $middlewares,
			'namePrefix' => $namePrefix
		];

		$callback();

		array_pop(self::$groupStack);
	}

	/**
	 * Добавляет маршрут в коллекцию
	 *
	 * @param string $method HTTP-метод
	 * @param string $path Путь маршрута
	 * @param callable $callback Обработчик маршрута
	 * @param array $middlewares Массив middleware
	 * @param string|null $name Имя маршрута
	 * @return void
	 */
	private static function addRoute(string $method, string $path, callable $callback, array $middlewares = [], ?string $name = null): void
	{
		$fullPath = self::applyGroupPrefix($path);
		$combinedMiddlewares = array_merge(
			self::getGroupMiddlewares(),
			$middlewares
		);
		$wrappedCallback = self::wrapWithMiddlewares($callback, $combinedMiddlewares);
		self::$routes[$method][$fullPath] = $wrappedCallback;

		// Регистрируем именованный маршрут, если имя указано
		if ($name !== null) {
			$fullName = self::applyGroupNamePrefix($name);
			self::$namedRoutes[$fullName] = [
				'method' => $method,
				'path' => $fullPath
			];
		}
	}

	/**
	 * Применяет префикс группы к пути маршрута
	 *
	 * @param string $path Исходный путь
	 * @return string Полный путь с учетом префиксов групп
	 */
	private static function applyGroupPrefix(string $path): string
	{
		if (empty(self::$groupStack)) {
			return $path;
		}

		$prefix = '';
		foreach (self::$groupStack as $group) {
			$prefix .= $group['prefix'];
		}

		return $prefix . $path;
	}

	/**
	 * Применяет префикс группы к имени маршрута
	 *
	 * @param string $name Исходное имя маршрута
	 * @return string Полное имя с учетом префиксов групп
	 */
	private static function applyGroupNamePrefix(string $name): string
	{
		if (empty(self::$groupStack)) {
			return $name;
		}

		$prefix = '';
		foreach (self::$groupStack as $group) {
			if ($group['namePrefix'] !== null) {
				$prefix .= $group['namePrefix'];
			}
		}

		return $prefix . $name;
	}

	/**
	 * Возвращает middleware текущей группы
	 *
	 * @return array Массив middleware
	 */
	private static function getGroupMiddlewares(): array
	{
		if (empty(self::$groupStack)) {
			return [];
		}

		$middlewares = [];
		foreach (self::$groupStack as $group) {
			$middlewares = array_merge($middlewares, $group['middlewares']);
		}

		return $middlewares;
	}

	/**
	 * Оборачивает обработчик в цепочку middleware
	 *
	 * @param callable $handler Исходный обработчик
	 * @param array $middlewares Массив middleware
	 * @return callable Обработанный обработчик
	 */
	private static function wrapWithMiddlewares(callable $handler, array $middlewares): callable
	{
		foreach (array_reverse($middlewares) as $middleware) {
			$handler = function (Request $req, array $params = []) use ($middleware, $handler) {
				if (is_callable($middleware)) {
					return $middleware($req, $handler, $params);
				} elseif (is_object($middleware) && method_exists($middleware, 'handle')) {
					return $middleware->handle($req, $handler, $params);
				}
				return $handler($req, $params);
			};
		}
		return $handler;
	}

	/**
	 * Генерирует URL по имени маршрута
	 *
	 * @param string $name Имя маршрута
	 * @param array $parameters Параметры для подстановки в URL
	 * @return string
	 * @throws \InvalidArgumentException Если маршрут не найден или не хватает параметров
	 */
	public static function route(string $name, array $parameters = []): string
	{
		if (!isset(self::$namedRoutes[$name])) {
			throw new \InvalidArgumentException("Route [{$name}] not found.");
		}

		$route = self::$namedRoutes[$name];
		$path = $route['path'];

		// Заменяем параметры в пути
		foreach ($parameters as $key => $value) {
			$path = str_replace('{' . $key . '}', $value, $path);
		}

		// Проверяем, остались ли незамененные параметры
		if (preg_match('/\{[a-z]+\}/', $path)) {
			throw new \InvalidArgumentException("Missing required parameters for route [{$name}].");
		}

		return $path;
	}

	/**
	 * Проверяет, существует ли маршрут с указанным именем
	 *
	 * @param string $name Имя маршрута
	 * @return bool
	 */
	public static function hasRoute(string $name): bool
	{
		return isset(self::$namedRoutes[$name]);
	}

	/**
	 * Возвращает список всех именованных маршрутов
	 *
	 * @return array
	 */
	public static function getNamedRoutes(): array
	{
		return self::$namedRoutes;
	}

	/**
	 * Запускает обработку маршрутов
	 *
	 * @param callable $onStartCallback Функция, вызываемая при старте сервера
	 * @return void
	 * @throws \Exception Если сервер не установлен
	 */
	public static function startHandling(callable $onStartCallback): void
	{
		if (!self::$server) {
			throw new \Exception("Server not set. Use Route::setServer()");
		}

		$handler = function (Request $request) {
			$method = $request->method;
			$uri = $request->uri;

			if ($uri === null || trim($uri) == "") {
				return null;
			}

			if (self::$requestLogger) {
				self::$requestLogger->handle($request);
			}

			// Обработка динамических маршрутов
			foreach (self::$routes[$method] ?? [] as $routePath => $callback) {
				$pattern = preg_replace('/\{([a-z]+)\}/', '(?P<$1>[^/]+)', $routePath);
				$pattern = "@^" . $pattern . "$@D";

				if (preg_match($pattern, $uri, $matches)) {
					$routeData = [];
					foreach ($matches as $key => $value) {
						if (is_string($key)) {
							$routeData[$key] = $value;
						}
					}

					return $callback($request, $routeData);
				}
			}

			// Обработка статических файлов
			if (self::$staticPath) {
				$filePath = self::$staticPath . '/' . ltrim($uri, '/');
				if (file_exists($filePath) && is_file($filePath)) {
					$response = new Response(file_get_contents($filePath), 200);
					$mime = self::getMimeType($filePath);
					$response->header("Content-Type", $mime);
					return $response;
				}
			}

			// Обработка 404 ошибки
			if (self::$notFoundCallback) {
				return call_user_func(self::$notFoundCallback, $request);
			}

			return new Response("Not Found", 404);
		};

		// Применяем глобальные Middleware
		$wrappedHandler = self::wrapWithMiddlewares($handler, self::$globalMiddlewares);

		self::$server->listen($wrappedHandler, $onStartCallback);
	}

	/**
	 * Обрабатывает входящий запрос
	 *
	 * @param Request $request Объект запроса
	 * @return Response Объект ответа
	 */
	public static function handleRequest(Request $request): Response
	{
		$method = $request->method();
		$uri = $request->uri();

		if (self::$requestLogger) {
			self::$requestLogger->handle($request);
		}

		// Обработка динамических маршрутов
		foreach (self::$routes[$method] ?? [] as $routePath => $callback) {
			$pattern = preg_replace('/\{([a-z]+)\}/', '(?P<$1>[^/]+)', $routePath);
			$pattern = "@^" . $pattern . "$@D";

			if (preg_match($pattern, $uri, $matches)) {
				$routeData = [];
				foreach ($matches as $key => $value) {
					if (is_string($key)) {
						$routeData[$key] = $value;
					}
				}

				return $callback($request, $routeData);
			}
		}

		// Обработка статических файлов
		if (self::$staticPath) {
			$filePath = self::$staticPath . '/' . ltrim($uri, '/');
			if (file_exists($filePath) && is_file($filePath)) {
				$response = new Response(file_get_contents($filePath), 200);
				$mime = self::getMimeType($filePath);
				$response->header("Content-Type", $mime);
				return $response;
			}
		}

		// Обработка 404 ошибки
		if (self::$notFoundCallback) {
			return call_user_func(self::$notFoundCallback, $request);
		}

		return new Response("Not Found", 404);
	}

	/**
	 * Определяет MIME-тип файла по его расширению
	 *
	 * @param string $filePath Путь к файлу
	 * @return string MIME-тип
	 */
	private static function getMimeType(string $filePath): string
	{
		$mimeTypes = [
			'css'  => 'text/css',
			'js'   => 'application/javascript',
			'json' => 'application/json',
			'html' => 'text/html',
			'txt'  => 'text/plain',
			'jpg'  => 'image/jpeg',
			'png'  => 'image/png',
			'gif'  => 'image/gif',
			'svg'  => 'image/svg+xml',
		];

		$extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
		return $mimeTypes[$extension] ?? 'application/octet-stream';
	}
}

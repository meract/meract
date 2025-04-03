<?php
namespace Meract\Core;

/**
 * Класс для управления маршрутизацией HTTP-запросов.
 *
 * Поддерживает:
 * - Регистрацию маршрутов для различных HTTP-методов
 * - Обработку динамических параметров в URL
 * - Обслуживание статических файлов
 * - Кастомные обработчики 404 ошибок
 * - middleware
 * - groups
 */
class Route
{
	private static $routes = [];
	private static $server = null;
	private static $requestLogger = null;
	private static $staticPath = null;
	private static $notFoundCallback = null;
	private static $globalMiddlewares = [];
	private static $groupStack = [];

	/**
	 * Устанавливает сервер и логгер запросов.
	 */
	public static function setServer(Server $server, RequestLogger $requestLogger): void
	{
		self::$server = $server;
		self::$requestLogger = $requestLogger;
	}

	/**
	 * Устанавливает путь к статическим файлам.
	 */
	public static function staticFolder(string $path): void
	{
		self::$staticPath = rtrim($path, '/');
	}

	/**
	 * Устанавливает обработчик 404 ошибки.
	 */
	public static function notFound(callable $callback): void
	{
		self::$notFoundCallback = $callback;
	}

	/**
	 * Регистрация GET-маршрута.
	 */
	public static function get(string $path, callable $callback, array $middlewares = []): void
	{
		self::addRoute('GET', $path, $callback, $middlewares);
	}

	/**
	 * Регистрация POST-маршрута.
	 */
	public static function post(string $path, callable $callback, array $middlewares = []): void
	{
		self::addRoute('POST', $path, $callback, $middlewares);
	}

	/**
	 * Добавление глобального Middleware.
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
	 * Группировка маршрутов с префиксом и Middleware.
	 */
	public static function group(string $prefix, callable $callback, array $middlewares = []): void
	{
		self::$groupStack[] = [
			'prefix' => $prefix,
			'middlewares' => $middlewares
		];

		$callback();

		array_pop(self::$groupStack);
	}

	/**
	 * Внутренний метод для добавления маршрута.
	 */
	private static function addRoute(string $method, string $path, callable $callback, array $middlewares = []): void
	{
		$fullPath = self::applyGroupPrefix($path);
		$combinedMiddlewares = array_merge(
			self::getGroupMiddlewares(),
			$middlewares
		);
		$wrappedCallback = self::wrapWithMiddlewares($callback, $combinedMiddlewares);
		self::$routes[$method][$fullPath] = $wrappedCallback;
	}

	/**
	 * Применяет префикс группы к пути.
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
	 * Возвращает Middleware текущей группы.
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
	 * Оборачивает callback в цепочку Middleware.
	 */
	private static function wrapWithMiddlewares(callable $handler, array $middlewares): callable
	{
		foreach (array_reverse($middlewares) as $middleware) {
			$handler = function (Request $req, array $params = []) use ($middleware, $handler) {
				return $middleware->handle($req, $handler, $params);
			};
		}
		return $handler;
	}
	/**
	 * Запуск обработки маршрутов.
	 */
	public static function startHandling(callable $onStartCallback)
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
	 * Определяет MIME-тип файла.
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

	/**
	 * Обрабатывает передаваемый запрос
	 * 
	 * @param \Meract\Core\Request $request
	 * @return \Meract\Core\Response
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
}

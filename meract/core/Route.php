<?php
namespace Meract\Core;
/**
 * Класс для управления маршрутизацией HTTP-запросов
 */
class Route
{
    /** @var array Массив зарегистрированных маршрутов */
    private static $routes = [];
    
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
     * @return void
     */
    public static function get(string $path, callable $callback, array $middlewares = []): void
    {
        self::addRoute('GET', $path, $callback, $middlewares);
    }

    /**
     * Создаёт маршрут предоставляющий ендпоинты для работы Morph
     * @param ?string $componentsPath
     * @return void
     */
    public static function autoRegisterMorphComponents(string $componentsPath = __DIR__ . '/../../app/views/components/'): void {
        if (!is_dir($componentsPath)) return;
    
        foreach (glob($componentsPath . '*.morph.php') as $file) {
            $name = basename($file, '.morph.php');
            self::get("/morph-component/{name}", function(Request $rq, array $params) {
                $name = $params['name'];
                $view = new \Meract\Core\View("components/{$name}");
                return new Response($view, 200);
            });
        }
    }

    /**
     * Регистрирует POST-маршрут
     *
     * @param string $path Путь маршрута
     * @param callable $callback Обработчик маршрута
     * @param array $middlewares Массив middleware
     * @return void
     */
    public static function post(string $path, callable $callback, array $middlewares = []): void
    {
        self::addRoute('POST', $path, $callback, $middlewares);
    }

    /**
     * Регистрирует PUT-маршрут
     *
     * @param string $path Путь маршрута
     * @param callable $callback Обработчик маршрута
     * @param array $middlewares Массив middleware
     * @return void
     */
    public static function put(string $path, callable $callback, array $middlewares = []): void
    {
        self::addRoute('PUT', $path, $callback, $middlewares);
    }

    /**
     * Регистрирует DELETE-маршрут
     *
     * @param string $path Путь маршрута
     * @param callable $callback Обработчик маршрута
     * @param array $middlewares Массив middleware
     * @return void
     */
    public static function delete(string $path, callable $callback, array $middlewares = []): void
    {
        self::addRoute('DELETE', $path, $callback, $middlewares);
    }

    /**
     * Регистрирует PATCH-маршрут
     *
     * @param string $path Путь маршрута
     * @param callable $callback Обработчик маршрута
     * @param array $middlewares Массив middleware
     * @return void
     */
    public static function patch(string $path, callable $callback, array $middlewares = []): void
    {
        self::addRoute('PATCH', $path, $callback, $middlewares);
    }

    /**
     * Регистрирует OPTIONS-маршрут
     *
     * @param string $path Путь маршрута
     * @param callable $callback Обработчик маршрута
     * @param array $middlewares Массив middleware
     * @return void
     */
    public static function options(string $path, callable $callback, array $middlewares = []): void
    {
        self::addRoute('OPTIONS', $path, $callback, $middlewares);
    }

    /**
     * Регистрирует HEAD-маршрут
     *
     * @param string $path Путь маршрута
     * @param callable $callback Обработчик маршрута
     * @param array $middlewares Массив middleware
     * @return void
     */
    public static function head(string $path, callable $callback, array $middlewares = []): void
    {
        self::addRoute('HEAD', $path, $callback, $middlewares);
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
     * @return void
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
     * Добавляет маршрут в коллекцию
     *
     * @param string $method HTTP-метод
     * @param string $path Путь маршрута
     * @param callable $callback Обработчик маршрута
     * @param array $middlewares Массив middleware
     * @return void
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
                return $middleware->handle($req, $handler, $params);
            };
        }
        return $handler;
    }

    /**
     * Запускает обработку маршрутов
     *
     * @param callable $onStartCallback Функция, вызываемая при старте сервера
     * @return void
     * @throws \Exception Если сервер не установлен
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
}
<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/meract/core/RecursiveLoad.php';

use Meract\Core\{
    Database,
    Route,
    Server,
    Request,
    Response,
    RequestLogger
};

// Загрузка конфигурации
$config = require __DIR__ . '/config.php';

// Инициализация базы данных

try {
    $database = Database::getInstance($config['database']);
    $pdo = $database->getPdo();
} catch (Exception $e) {
    throw new Exception("Database initialization failed. Please check config.php: " . $e->getMessage());
}




if (!isset($_SERVER['REQUEST_URI'])) { // Проверяем обращаются ли через php server, если нет, то инициализируем сервер.


    // Инициализация сервера
    try {
        $requestLogger = $config['server']['requestLogger'] ?? new RequestLogger();
        Route::setServer(
            new Server($config['server']['host'], $config['server']['port']),
            $requestLogger
        );
    } catch (Exception $e) {
        echo "Server startup error. Possible configuration issue or server is already running.\n";
        echo $e->getMessage() . "\n";
        exit(1);
    }


}

requireFilesRecursively(__DIR__ . '/meract/core');
requireFilesRecursively(__DIR__ . '/app/core');
requireFilesRecursively(__DIR__ . '/app/models');
requireFilesRecursively(__DIR__ . '/app/middleware');
requireFilesRecursively(__DIR__ . '/app/controllers');
requireFilesRecursively(__DIR__ . '/app/routes');

// Настройка обработчика воркеров
if (
    isset($config['worker']['enabled'])
    && $config['worker']['enabled']
    && isset($config['worker']['endpoint'])
    && isset($config['worker']['server-callback'])
) {
    Route::get(
        "/worker-" . $config['worker']['endpoint'],
        function (Request $rq) use ($config) {
            $data = $rq->parameters['data'] ?? null;
            return new Response(
                $config['worker']['server-callback']($data),
                200
            );
        }
    );
}

// Запуск в соответствующем режиме
$initFunction = $config['server']['initFunction'] ?? function () {
    echo "Server started!\n";
};

if ($config['server']['customServer'] ?? false) {
    // Режим для Apache/Nginx
    $response = Route::handleRequest(Request::fromGlobals());
    $response->send();
} else {
    // Оригинальный режим сокет-сервера
    Route::startHandling($initFunction);
}
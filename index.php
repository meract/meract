<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/meract/core/RecursiveLoad.php';

use Meract\Core\{SDR, Database, Route, Server, Request, Response, Injector};

// Инициализация контейнера
if (!SDR::isInitialized()) {
    $injector = new Injector();
    SDR::setInjector($injector);

    // Загрузка конфигурации
    $config = require __DIR__ . '/config.php';
    $injector->set('config', $config);
    $database = Database::getInstance($config['database']);

    // 2. Регистрируем экземпляр в контейнере
    $injector->set(Database::class, $database);

    // 3. Привязываем PDO к существующему подключению
    $injector->set(PDO::class, $database->getPdo());
    $pdo = SDR::make(PDO::class);
    $pdo->query("SELECT 1")->execute();
    // Проверяем подключение
    // try {
    //     echo "test start";
    //     $pdo = SDR::make(PDO::class);
    //     $pdo->query("SELECT 1")->execute();
    //     echo "test end";
    // } catch (PDOException $e) {
    //     die("Database connection failed: " . $e->getMessage());
    // }
}

// Загрузка модулей приложения
requireFilesRecursively(__DIR__ . '/app/core');
requireFilesRecursively(__DIR__ . '/app/models');
requireFilesRecursively(__DIR__ . '/app/middleware');
requireFilesRecursively(__DIR__ . '/app/controllers');
requireFilesRecursively(__DIR__ . '/app/routes');

// Проверка режима запуска
if (php_sapi_name() === 'cli' || !isset($_SERVER['REQUEST_URI'])) {
    // Режим сервера (запуск через mrst или CLI)
    $serverConfig = SDR::make('config')['server'] ?? [];
    $server = new Server(
        $serverConfig['host'] ?? '0.0.0.0',
        $serverConfig['port'] ?? 8000
    );

    Route::setServer(
        $server,
        SDR::make('config')['server']['requestLogger'] ?? new Meract\Core\RequestLogger()
    );

    $initFunction = $serverConfig['initFunction'] ?? function () {
        echo "Server started!\n";
    };
    Route::startHandling($initFunction);
} else {
    // Режим HTTP (Apache/Nginx)
    $response = Route::handleRequest(
        Request::fromGlobals()
    );
    $response->send();
}
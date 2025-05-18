<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor/meract/core/src/RecursiveLoad.php';
use Meract\Core\{Auth, View, SDR, Database, Route, Server, Request, Response, Injector, Storage, Morph, ScriptBuilder};



// Инициализация контейнера
if (!SDR::isInitialized()) {
	define("PROJECT_DIR", __DIR__);
    $injector = new Injector();
    SDR::setInjector($injector);

    // Загрузка конфигурации
    $config = require __DIR__ . '/config.php';



    $injector->set('config', $config);
    // $database = Database::getInstance($config['database']);

    $db = Database::getInstance($config['database']);
    SDR::set("pdo.connection", $db->getPdo());
}

View::addCompiler(new \Meract\Core\Compilers\BaseViewCompiler());
if (isset($config['viewCompilers'])) {
    foreach ($config['viewCompilers'] as $compiler) {
        View::addCompiler($compiler);
    }
}
Morph::setMorphLiveEncription($config['morph']['live'] ?? "superSecretKey");
Storage::init($config['storage']['driver']);
Storage::setTime($config['storage']['time']);
Auth::configure($config['auth']);

requireFilesRecursively(__DIR__. '/vendor/meract/core/src/Helpers');
ScriptBuilder::config($config['morph']['scripts'] ?? [
	"source" => base_path('app/scripts'),
	"output" => base_path('storage/scripts')
]);

// Загрузка модулей приложения
requireFilesRecursively(__DIR__ . '/app/core');
requireFilesRecursively(__DIR__ . '/app/models');
requireFilesRecursively(__DIR__ . '/app/middleware');
requireFilesRecursively(__DIR__ . '/app/controllers');
requireFilesRecursively(__DIR__ . '/app/routes');

Route::staticFolder("app/static");
Route::autoRegisterMorphComponents();
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

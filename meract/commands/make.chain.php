<?php
// meract/commands/make.chain.php
use Meract\Core\SDR;

return new class {
    private $componentMap = [
        'r' => ['type' => 'route', 'method' => 'createRoute'],
        'v' => ['type' => 'view', 'method' => 'createView'],
        'c' => ['type' => 'controller', 'method' => 'createController'],
        'm' => ['type' => 'model_migration', 'method' => 'createModelAndMigration']
    ];

    private $validFieldTypes = [
        'string', 'char', 'text', 'mediumText', 'longText',
        'binary', 'blob', 'mediumBlob', 'longBlob', 'enum', 'set',
        'integer', 'tinyInteger', 'smallInteger', 'mediumInteger', 'bigInteger',
        'unsignedInteger', 'float', 'double', 'decimal', 'boolean',
        'date', 'time', 'dateTime', 'timestamp', 'year',
        'json',
        'geometry', 'point', 'lineString', 'polygon', 'multiPoint',
        'multiLineString', 'multiPolygon', 'geometryCollection'
    ];

    public function run()
    {
        $argv = SDR::make('command.args');
		$argc = count($argv);
        $config = SDR::make('config');
        if ($argc < 2) {
            $this->showHelp();
            return 1;
        }

        $chain = $argv[0];
        $name = ucfirst($argv[1]);
        $fields = [];
        $tableName = strtolower($name);
        $isRest = false;

        // Парсинг аргументов в строгом порядке
        $args = array_slice($argv, 2);
        foreach ($args as $arg) {
            if ($arg === '-rest') {
                $isRest = true;
            } elseif (strpos($arg, '--table=') === 0) {
                $tableName = strtolower(substr($arg, 8));
            } elseif (strpos($arg, '{') === 0) {
                $fields = $this->parseFields($arg);
            }
        }

        if (!preg_match('/^[rvcm]+$/', $chain)) {
            echo "Ошибка: Некорректный формат цепочки. Допустимые символы: r, v, c, m\n";
            $this->showHelp();
            return 1;
        }

        foreach (str_split($chain) as $char) {
            if (isset($this->componentMap[$char])) {
                $method = $this->componentMap[$char]['method'];
                $this->$method($name, $fields, $tableName, $isRest);
            }
        }

        echo "Компоненты успешно созданы!\n";
        return 0;
    }

    private function parseFields($jsonInput)
    {
        $fields = json_decode($jsonInput, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "Ошибка: Некорректный JSON в параметрах полей\n";
            return [];
        }

        foreach ($fields as $field => $type) {
            if (!in_array($type, $this->validFieldTypes)) {
                echo "Ошибка: Неподдерживаемый тип поля '{$type}' для поля '{$field}'\n";
                $this->showValidFieldTypes();
                return [];
            }
        }

        return $fields;
    }

    private function createRoute($name, $fields, $tableName, $isRest)
    {
        $routeContent = "\n\n";
        $lowerName = strtolower($name);
        
        if ($isRest) {
            $routeContent .= <<<ROUTES
// REST API routes for {$name}
Route::get('/{$lowerName}', [{$name}Controller::class, 'index']);
Route::get('/{$lowerName}/{id}', [{$name}Controller::class, 'show']);
Route::post('/{$lowerName}', [{$name}Controller::class, 'store']);
Route::put('/{$lowerName}/{id}', [{$name}Controller::class, 'update']);
Route::delete('/{$lowerName}/{id}', [{$name}Controller::class, 'destroy']);

ROUTES;
        } else {
            $routeContent .= "Route::get('/{$lowerName}', [{$name}Controller::class, 'index']);\n";
        }

        $routePath = PROJECT_DIR.'/app/routes/web.php';
        file_put_contents($routePath, $routeContent, FILE_APPEND);
        echo "Route(s) добавлен(ы) в {$routePath}\n";
    }

    private function createView($name, $fields, $tableName, $isRest)
    {
        $viewPath = PROJECT_DIR.'/app/views/'.strtolower($name).'.php';
        file_put_contents($viewPath, "<!-- View for {$name} -->\n");
        echo "View создан: {$viewPath}\n";
    }

    private function createController($name, $fields, $tableName, $isRest)
    {
        $controllerContent = <<<CONTROLLER
<?php
namespace App\Controllers;

use Meract\Core\Controller;
use App\Models\\{$name}Model;

class {$name}Controller extends Controller
{
CONTROLLER;

        if ($isRest) {
            $controllerContent .= <<<REST_METHODS

    public static function index(\$request)
    {

    }

    public static function show(\$request, \$data)
    {

    }

    public static function store(\$request)
    {

    }

    public static function update(\$request, \$data)
    {

    }

    public static function destroy(\$request, \$data)
    {

    }
REST_METHODS;
        } else {
            $controllerContent .= <<<BASIC_METHOD

    public static function index(\$request)
    {

    }
BASIC_METHOD;
        }

        $controllerContent .= "\n}\n";

        $controllerPath = PROJECT_DIR.'/app/controllers/'.$name.'Controller.php';
        file_put_contents($controllerPath, $controllerContent);
        echo "Controller создан: {$controllerPath}\n";
    }

    private function createModelAndMigration($name, $fields, $tableName, $isRest)
    {
        // Создаем модель
        $fillable = $fields ? array_keys($fields) : "";
        $fillableString = "[\n\t\t'id',\n";
        foreach ($fillable as $field) {
            $fillableString .= "\t\t'{$field}',\n";
        }
        $fillableString .= "    ]";

        $modelContent = <<<MODEL
<?php
namespace App\Models;

use Meract\Core\Model;

class {$name}Model extends Model 
{
    protected static \$table = '{$tableName}';
    protected \$fillable = {$fillableString};
}
MODEL;

        $modelPath = PROJECT_DIR.'/app/models/'.$name.'Model.php';
        file_put_contents($modelPath, $modelContent);
        echo "Model создана: {$modelPath}\n";

        // Создаем миграцию
        $migrationFields = '';
        foreach ($fields as $field => $type) {
            $migrationFields .= "            \$table->{$type}('{$field}');\n";
        }

        $migrationContent = <<<MIGRATION
<?php

use Meract\Core\Migration;

return new class extends Migration {
    public function up(): void
    {
        \$this->schema->create('{$tableName}', function (\$table) {
            \$table->id();
{$migrationFields}
        });
    }

    public function down(): void
    {
        \$this->schema->drop('{$tableName}');
    }
};
MIGRATION;

        $migrationPath = PROJECT_DIR.'/app/migrations/'.$tableName.'.php';
        file_put_contents($migrationPath, $migrationContent);
        echo "Migration создана: {$migrationPath}\n";
    }

    private function showValidFieldTypes()
    {
        echo "Допустимые типы полей:\n";
        foreach (array_chunk($this->validFieldTypes, 5) as $chunk) {
            echo "  - " . implode(", ", $chunk) . "\n";
        }
    }

    private function showHelp()
    {
        echo "Использование: php mrst make.chain <цепочка> <имя> [--table=table_name] [-rest] ['{поля}']\n";
        echo "Порядок аргументов после <имя>:\n";
        echo "  1. --table=table_name (опционально)\n";
        echo "  2. -rest (опционально)\n";
        echo "  3. JSON с полями (опционально)\n\n";
        echo "Доступные компоненты в цепочке:\n";
        echo "  r - Route (маршрут)\n";
        echo "  v - View (шаблон)\n";
        echo "  c - Controller (контроллер)\n";
        echo "  m - Model и Migration (модель и миграция)\n\n";
        echo "Примеры:\n";
        echo "  php mrst make.chain rvcm Admin --table=admins -rest '{\"name\":\"string\"}'\n";
        echo "  php mrst make.chain rvcm Product '{\"title\":\"string\",\"price\":\"decimal\"}'\n";
    }
};
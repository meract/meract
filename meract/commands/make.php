<?php

return new class {
    private $templates = [
        'model' => [
            'path' => '/app/models/%sModel.php',
            'content' => <<<'MODEL'
<?php
namespace App\Models;

use Meract\Core\Model;

class %sModel extends Model 
{
    protected static $table = '%s';
    protected $fillable = ['id'];
}
MODEL
        ],
        'controller' => [
            'path' => '/app/controllers/%sController.php',
            'content' => <<<'CONTROLLER'
<?php
namespace App\Controllers;

use Meract\Core\Controller;

class %sController extends Controller
{

}
CONTROLLER
        ],
		'view' => [
			'path' => '/app/views/%sView.php',
			'content' => ""
		],
		'migration' => [
			'path' => '/app/migrations/%sMigration.php',
			'content' => <<<MIGRATION
<?php

use Meract\Core\Migration;

return new class extends Migration {

	public function up(): void
	{

	}

	public function down(): void
	{

	}

};
MIGRATION
	],
		'worker' => [
			'path' => '/app/workers/%sWorker.php',
			'content' => <<<WORKER
<?php
use Meract\Core\Worker;

return new class extends Worker {

    public function run(string \$message) {

    }

};
WORKER 
		],
		'command' => [
			'path' => '/meract/commands/%s.php',
			'content' => "<?php\nreturn new class\n{\n\n\tpublic function run(\$argv, \$argc)\n\t{\n\n\t}\n\n};"
		]
    ];
	

    public function run($argv, $argc)
    {
        if ($argc < 2) {
            $this->showHelp();
            return 1;
        }

        $type = $argv[0];
        $name = $argv[1];

        if (!isset($this->templates[$type])) {
            echo "Ошибка: Неизвестный тип '$type'\n";
            $this->showAvailableTypes();
            return 1;
        }

        $template = $this->templates[$type];
        $filename = sprintf(PROJECT_DIR . $template['path'], $name);
        $content = sprintf($template['content'], $name, strtolower($name));

        $this->ensureDirectoryExists(dirname($filename));
        file_put_contents($filename, $content);

        echo "Успешно создан {$type}: {$filename}\n";
        return 0;
    }

    private function showHelp()
    {
        echo "Использование: php console.php make <тип> <имя>\n";
        $this->showAvailableTypes();
    }

    private function showAvailableTypes()
    {
        echo "Доступные типы:\n";
        foreach (array_keys($this->templates) as $type) {
            echo "  - {$type}\n";
        }
    }

    private function ensureDirectoryExists($dir)
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
};

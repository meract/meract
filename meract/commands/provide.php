<?php
use Meract\Core\SDR;
return new class {
    public function run() {
        $args = SDR::make('command.args');
        
        if (count($args) < 1) {
            echo "Usage: php mrst provide <file|url>\n";
            echo "Example:\n";
            echo "  php mrst provide script.php\n";
            echo "  php mrst provide https://example.com/script.php\n";
            return 1;
        }

        $source = $args[0];
        $code = '';

        // Определяем, является ли источник URL или файлом
        if (filter_var($source, FILTER_VALIDATE_URL)) {
            // Загружаем код по URL
            $code = file_get_contents($source);
            if ($code === false) {
                echo "Failed to fetch code from URL: $source\n";
                return 1;
            }
        } else {
            // Читаем локальный файл
            if (!file_exists($source)) {
                echo "File not found: $source\n";
                return 1;
            }
            $code = file_get_contents($source);
        }

        try {
            // Выполняем код в контексте Meract
            $result = eval($code);
            if ($result !== null) {
                var_dump($result);
            }
        } catch (Throwable $e) {
            echo "Error executing code:\n";
            echo $e->getMessage() . "\n";
            echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
            return 1;
        }

        return 0;
    }
};

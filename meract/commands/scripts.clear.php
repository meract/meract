<?php
return new class {
    public function run() {
        $scriptsDir = __DIR__ . '/../../storage/scripts';
        
        if (!is_dir($scriptsDir)) {
            echo "Директория скриптов не существует.\n";
            return;
        }

        $files = glob($scriptsDir . '/*.{js,json}', GLOB_BRACE);
        $count = 0;

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                $count++;
            }
        }

        echo "Удалено файлов: $count\n";
        echo "Кэш скриптов очищен.\n";
    }
};

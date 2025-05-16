<?php
namespace Meract\Core;

class ScriptBuilder {
    private static $manifest = [];
    private const SOURCE_DIR = __DIR__ . '/../../app/scripts';
    private const OUTPUT_DIR = __DIR__ . '/../../storage/scripts';

    public static function build(): void {
        self::ensureOutputDirExists();
        self::$manifest = [];

        // Рекурсивный поиск JS-файлов
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(self::SOURCE_DIR)
        );

        foreach ($files as $file) {
            if ($file->isDir() || $file->getExtension() !== 'js') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            $conditions = self::parseConditions($content);
            $minified = self::minify($content);
            $relativePath = self::getRelativePath($file->getPathname());

            // Хеш учитывает относительный путь, чтобы избежать коллизий
            $hash = md5($relativePath . $minified);
            $outputFile = self::OUTPUT_DIR . '/' . $hash . '.js';

            file_put_contents($outputFile, $minified);

            foreach ($conditions as $path) {
                self::$manifest[$path][] = $hash;
            }
        }

		

        file_put_contents(self::OUTPUT_DIR . '/manifest.json', json_encode(self::$manifest));
    }

    public static function getScriptsForPath(string $path): string {
        if (!file_exists(self::OUTPUT_DIR . '/manifest.json')) {
            return '';
        }

        $manifest = json_decode(file_get_contents(self::OUTPUT_DIR . '/manifest.json'), true);
        $result = '';

        foreach ($manifest as $pattern => $hashes) {
            if (self::matchPath($path, $pattern)) {
                foreach ($hashes as $hash) {
                    $result .= file_get_contents(self::OUTPUT_DIR . '/' . $hash . '.js') . "\n";
                }
            }
        }

        return $result;
    }

    private static function getRelativePath(string $absolutePath): string {
        return str_replace(self::SOURCE_DIR . '/', '', $absolutePath);
    }

    private static function parseConditions(string $content): array {
        preg_match_all('/\/\/:\s*(\S+)/', $content, $matches);
        return $matches[1] ?? [];
    }

    private static function matchPath(string $path, string $pattern): bool {
        $regex = str_replace('\*', '.*', preg_quote($pattern, '#'));
        return preg_match("#^{$regex}$#", $path);
    }

    private static function minify(string $code): string {
		return \JShrink\Minifier::minify($code);
	}

    private static function ensureOutputDirExists(): void {
        if (!is_dir(self::OUTPUT_DIR)) {
            mkdir(self::OUTPUT_DIR, 0755, true);
        }
    }
}

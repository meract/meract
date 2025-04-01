<?php

/**
 * Рекурсивно подключает все PHP-файлы из указанной директории.
 *
 * @param string $directory Путь к директории.
 */
function requireFilesRecursively($directory) {
	$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
	foreach ($iterator as $file) {
		if ($file->isFile() && $file->getExtension() === 'php') {
			require_once $file->getPathname();
		}
	}
}

<?php

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor/meract/core/src/RecursiveLoad.php';

use Meract\Core\WorkerInstance;
use Meract\Core\Database;

$config = require "config.php";
try {
	$database = Database::getInstance($config['database']);
	$pdo = $database->getPdo();
} catch (Exception $e) {
	throw new Exception("Проблема с базой данных, проверь config.php");
}

if (isset($config['worker'], $config['worker']['enabled'], $config['worker']['endpoint'], $config['worker']['server-callback']) && $config['worker']['enabled']) {
	echo "Воркер настроен. Запуск.\n";	
	// Устанавливаем имя таблицы

	while (true) {
		$work = WorkerInstance::first();

		if ($work) {
			$file = $work->name;
			$message = $work->message;
			(require "app/workers/$file.php")->run($message);
			$work->delete();
		}
		sleep(1);
	}
} else {
	echo "Воркер не настроен. Завершение.\n";
	exit();
}

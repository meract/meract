<?php
use Meract\Core\Storage;
return [
	"server" => [
		"customServer" => false,
		"host" => "0.0.0.0",
		"port" => 80,
		"initFunction" => function () {
			Storage::setTime(600);
			echo "server started!\n";
		}
	],
	"database" => [
		"driver" => "sqlite",
		"sqlite_path" => __DIR__ . "/db.sqlite"
	],
	"storage" => [
		"driver" => null,
		"time" => 20
	]
];

<?php
return [
	"server" => [
		"customServer" => false,
		"host" => "0.0.0.0",
		"port" => 8000,
		"initFunction" => function () {
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

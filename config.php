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
		"driver" =>	null, 
		"time" => 600
	],
	"auth" => [
		'table' => 'meract_users',
		'login_fields' => ['email', 'password'],
		'registration_fields' => ['email', 'password'],
		'jwt_secret' => 'your-strong-secret',
		'tokens_table' => 'meract_tokens',
		'cookie_name' => "AUTHTOKEN"
	], 
	"morph" => ["live" => "SuperSecretKey"]
];

<?php
return [
	"server" => [
		"customServer" => true,
		"host" => "0.0.0.0",
		"port" => 8000
	],
	"database" => [
		"driver" => "sqlite",
		"sqlite_path" => __DIR__ . "/db.sqlite"
	],
	"storage" => [
		"driver" => null,
		"time" => 20
	],
	'auth' => [
		'table' => 'meract_users',               // Таблица пользователей
		'login_fields' => ['email', 'password'], // Поля для входа
		'registration_fields' => ['email', 'password'], // Поля для регистрации
		'jwt_secret' => 'your-strong-secret',    // Секретный ключ для JWT
		'tokens_table' => 'meract_tokens',       // Таблица недействительных токенов
		'cookie_name' => "AUTHTOKEN"            // Название cookie
	],
	"morph" => [
		"live" => "super secret key"
	]
];

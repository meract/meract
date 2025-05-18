<?php
return [
	'server' => [
		'customServer' => true,
		'host' => '0.0.0.0',
		'port' => 8000,
		'initFunction' => 'return function (] { echo "server started!\\n"}',
	],
	'database' => [
		'driver' => 'sqlite',
		'sqlite_path' => 'db.sqlite',
	],
	'storage' => [
		'driver' => null,
		'time' => 600,
	],
	'auth' => [
		'table' => 'meract_users',
		'login_fields' => [
			0 => 'email',
			1 => 'password',
		],
		'registration_fields' => [
			0 => 'hui',
			1 => 'penis',
		],
		'jwt_secret' => '6e50077d690921b2daedc233327b20ff8d9ccc0df10d8c93ee8ca75d540aca2a',
		'tokens_table' => 'meract_tokens',
		'cookie_name' => 'ebat',
	],
	'morph' => [
		'live' => 'e630814aedb50c95cc3538714ceafe034eacf582255e9f52168f24205a17fdd4',
	],
];

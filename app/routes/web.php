<?php

use Meract\Core\Route;
use Meract\Core\Response;

Route::get('/', function ($rq) {
	return new Response('hello world!', 200);
});

<?php
use Meract\Core\View;
use Meract\Core\Route;
use Meract\Core\Response;

Route::get('/', function ($rq) {
	return new Response(new View('main'), 200);
});

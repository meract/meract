<?php

use Meract\Core\Route;
use Meract\Core\Response;
use Meract\Core\View;

Route::get('/', function ($rq) {
	$view = new View("test", ["array" => [[1,2], [3,4], [5,6], [7,8], [9,10]]]);
	$resp = new Response($view, 200);
	$resp->header("Content-Type", "text/html");
	return $resp;
});
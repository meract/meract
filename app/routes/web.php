<?php

use Meract\Core\Route;
use Meract\Core\Response;
use App\Controllers\AdminController;

Route::get('/', function ($rq) {
	global $config;
	var_dump($config);
	return new Response('hello world!', 200);
});

Route::group("/admin", function () {
	Route::get("/add", [AdminController::class, "getAdd"]);
	Route::post("/add", [AdminController::class, "postAdd"]);
	Route::get("/show", [AdminController::class, "show"]);
});

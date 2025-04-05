<?php
namespace App\Controllers;

use Meract\Core\OUTVAR;
use Meract\Core\Controller;
use Meract\Core\View;
use App\Models\AdminModel;
use Meract\Core\Response;

class AdminController extends Controller
{
    public static function getAdd($request)
    {
		$body = new View("adminAdd");
		$template = new View("admin", ["body" => $body]);
		return self::html($template);
    }

	public static function postAdd($request)
	{
		extract($request->parameters);
		$age = (int) $age;
		$model = new AdminModel(["name" => $name, "age" => $age, "mail" => $mail]);
		$model->save();
		return self::json(["sucess" => "done", "model" => OUTVAR::dump($model)]);
	}

	public static function show($request) {
		return self::html("<pre>".OUTVAR::dump(AdminModel::all())."</pre");
	}
}

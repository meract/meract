# [BACK](helloWorld.md)
Данная статья посвящена демонстрации работы с бд. С использованием ORM

Вероятнее всего база данных у вас уже настроена. Ведь фреймворк не может работать без базы. Так что создадим миграцию и модель, а заодно и контроллер вместе с view.

Выполним команду:
```
php mrst make.chain cmv Admin --table=admins '{"name" : "string", "age" : "integer", "mail" : "string"}'
```
Или:
```
php mrst make.chain cmv Admin --table=admins "{\"name\" : \"string\", \"age\" : \"integer\", \"mail\" : \"string\"}"
```
Если ваш шелл, плохо обрабатывает апострофы. 

Мигрируем миграции.
```
php mrst migrate
```
Теперь у нас есть таблица "admins". Посмотрим что у нас в созданной модели.
```
<?php
namespace App\Models;

use Meract\Core\Model;

class AdminModel extends Model 
{
    protected static $table = 'admins';
    protected $fillable = [
		'id',
		'name',
		'age',
		'mail',
    ];
}
```
Как видим всё сформировалось правильно!

Сверстаем View.
Лично я для удобства добавлю ещё две view "adminAdd" и "adminShow"
```
php mrst make view adminAdd
php mrst make view adminShow
```


admin:
```
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta http-equiv="X-UA-Compatible" content="ie=edge">
		<title>admin</title>
	</head>
	<body>
		<?= $body ?>
	</body>
</html>
```
adminAdd:
```
<form action='/admin/add' method='post'>
	<input name='name' placeholder='Name:'><br>
	<input name='age' type='number' placeholder='Age:'><br>
	<input name='mail' placeholder='Mail'><br>
</form>
```


С `adminShow` пока повременим и перейдём к контроллеру `AdminController`:
```
<?php
namespace App\Controllers;

use Meract\Core\Controller;
use Meract\Core\View;
use App\Models\AdminModel;

class AdminController extends Controller
{
    public static function getAdd($request)
    {
		$body = new View("adminAdd");
		$template = new View("admin", ["body" => $body]);
		return self::html($template);
    }
}
```
Здесь мы создаём метод `getAdd` создаём объект View из adminAdd а потом передаём его в качестве `$body` в admin

А после с помощью self::html Выводим полученный html

Привяжем этот метод к роуту. Так же сразу на будущее создадим маршрут для обработки формы.
```
Route::group("/admin", function () {
	route::get("/add", [AdminController::class, "getAdd"]);
	route::post("/add", [AdminController::class, "postAdd"]);
});
```
Маршруты сгрупированы. Так что для обоих из них адрес будет "/admin/add"

Напишем метод для добавления данных в базу:
```
public static function postAdd($request)
{
    extract($request->parameters);
    $age = (int) $age;
    $model = new AdminModel(["name" => $name, "age" => $age, "mail" => $mail]);
    $model->save();
    return self::json(["sucess" => "done"]);
}
```

Теперь сделаем путь для показа.
route:
```
Route::group("/admin", function () {
	Route::get("/add", [AdminController::class, "getAdd"]);
	Route::post("/add", [AdminController::class, "postAdd"]);
	Route::get("/show", [AdminController::class, "show"]);
});
```

# [BACK](install.md)

Здесь я опишу процесс создания первого хелло ворлд приложения. Приведу структуру из `README`
```
.
├───app                 - Код вашего приложения
│   ├───controllers     - Контроллеры
│   ├───core            - Скрипты выполняемые перед запуском сервера. Общие утилиты, дополнительные настройки и т.д.
│   ├───middleware      - middleware Роутов
│   ├───migrations      - Миграции базы данных
│   ├───models          - Ваши модели
│   ├───routes          - Ваши роуты
│   ├───static          - Сатичные файлы(html, css, js)
│   ├───views           - Ваши шаблоны view
│   └───workers         - Ваши воркеры
├───meract              - Код фреймворка
│   ├───commands        - Команды для mrst
│   ├───core            - Основные классы и код фреймворка
│   └───drivers         - Драйвера для различных компонентов фреймворка например StorageDriver's
│
│
├───config.php          - Конфиг фреймворка, а так же вашего приложения.
├───index.php           - index файл запускаешь сервер и всё ваше приложение
├───worker.php          - Файл запускающий воркер, который будет выполнять задачи из очереди
├───mrst                - Утилита командной строки фреймворка
│
│
├───vendor              - composer vendor
├───composer.json       - composer.json
├───composer.lock       - composer.lock
└───tests               - Директория тестов phpUNIT
```

В директории `app/routes` уже будет находиться файл `web.php` и в нём уже будет находиться hello world. Так что разберёмся что тут происходит.
```
<?php

use Meract\Core\Route; //Подключаем Route
use Meract\Core\Response; //Подключаем Response

Route::get('/', function ($rq) { //Устанавливаем путь для гет запроса и функцию которая будет выполнена когда пользователь обратится на данную страницу
	return new Response('hello world!', 200); //Возвращаем объект Response с телом 'hello world!' и http код-статусом 200(успешно)
});
```

В целом ваше хелло ворлд приложение уже готово, но понятно дело вы не будете писать код в роутах. Поэтому создадим контроллер,
командой:
```
php mrst make controller HelloWorld
```
Получим следующий код в файле `app/controllers/HelloWorldController.php`:
```
<?php
namespace App\Controllers;

use Meract\Core\Controller;

class HelloWorldController extends Controller
{

}
```

Создадим метод index:
```
//...
public static function index($request) //index метод
{
    return self::html(new View('index', [ //self::html подготавливает html к выводу. View создаёт html из view шаблона.
        "text" => "hello world" //В данном случае index, так же устанавливаем параметр text в "hello world"
    ]));
}
```
И view в `app/views/index.php`
```
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>hello world!</title>
</head>
<body>
    <?= $text ?> <!-- Здесь выводим параметр текст -->
</body>
</html>
```

И обновим наши роуты.
```
/...
use App\Controllers\HelloWorldController; // Подключаем наш контроллер
/...
Route::get('/', [HelloWorldController::class, "index"]);// и Вешаем на него обработку адреса /
```

Теперь открыв сайт вы увидите надпись "hello world!"

## Полный код файлов:
```
<?php // app/routes/web.php

use App\Controllers\HelloWorldController;
use Meract\Core\Route;

Route::get('/', [HelloWorldController::class, "index"]);
?>



<?php // app/controllers/HelloWorldController.php
namespace App\Controllers;

use Meract\Core\Controller;
use Meract\Core\View;

class HelloWorldController extends Controller
{
    public static function index($request)
    {
        return self::html(new View('index', [
            "text" => "hello world"
        ]));
    }
}
?>


<!-- app/views/index.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>hello world!</title>
</head>
<body>
    <?= $text ?>
</body>
</html>
```


# [NEXT](morph1.md)

## Описание
Meract - MVC фреймворк для PHP.

У него есть много особенностей, выделяющих его на фоне других фреймворков, от своего сервера до плотной интеграции с фронтендом.

### Основной namespace
Основным неймспэйсом для большинства классов которые вы будете использовать является, `Meract\Core`

## Документация
Конкретная техническая документация по методам [доступна тут](https://lumetas.github.io/meract/)

[Step By Step Гайд](docs/stepByStep/install.md)


## Стрктура
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
│   ├───views           - Директория с view
│   │   ├───colorschemes- Цветовые схемы morph компонентов
│   │   ├───components  - Morph компоненты
│   │   ├───layouts     - layouts view
│   │   ├───modules     - morph модули
│   │   └───themes      - темы для компонентов morph
│   │
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


## Конфигурация
Конфигурация хранится в файле `config.php`, по умолчанию он выглядит примерно так:
```
<?php
return [
	"server" => [
		"customServer" => false,
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
	]
];
```
Здесь задаётся host и port сервера. Вы можете указать свою функцию при поднятии сервера, а так же свой логгер запросов:
```
<?php
return [
	"server" => [
		"host" => "0.0.0.0",
		"port" => 8000,
		"requestLogger" => new class extends RequestLogger {
			public function handle($rq) {
				echo "test\n";
			}
		},

		"initFunction" => function () { echo "test start\n"; }
	]
];
```


## Установка
```
composer create-project lumetas/meract project-name
cd project-name;
php mrst init;
php mrst migrate;
```

## Запуск
В зависимости от выбранного сервера, вы можете использовать либо `php index.php` либо `php -S interface:port index.php` или универсальный вариант `php mrst serve`

В случае использования встроенного сервера может быть выполнена функция инициализация сервера. В случае использования стандартного сервера или apache/nginx. Он выполнена не будет. Так что различные настройки которые необходимо настаивать из кода. Необходимо делать где-либо ещё. Так же в случае запуска тестового сервера используя стандартный сервер php. Вы не сможете настроить свой обработчик запросов. По крайней мере на данный момент.

Сервер начнёт слушать и принимать запросы выводя информацию о запросе в консоль, формат логов вы так же можете поменять как и было указано выше



## Роутеры и контроллеры
Очень во многом я вдохновляюсь laravel. Так что многое покажется для вас знакомым.

И так, вот все примеры синтаксиса роутеров:
```
Route::get('/', function(Request $rq) {
	$content = View::render("main", [
		"title" => "example lumframework project",
		"value" => IterateController::get()
	]);
	return (new Response($content))->header('Content-Type', "text/html")->cookie('test', 'cookie');
});

Route::get('/add/{num}', [IterateController::class, "add"]);

Route::get('/rem/{num}', [IterateController::class, "rem"]);

Route::notFound(function(Request $rq) {
	return new Response('is a 404 error', 404);
});

Route::group('/admin', function () {
    Route::get('/', function ($rq){return new Response('hello admin!', 200);});
    Route::get('/test1', function ($rq){return new Response('hello admin test1!', 200);});
    Route::get('/test2', function ($rq){return new Response('hello admin test2!', 200);});
});

Route::get('/', function ($rq) {
	return new Response('hello world!', 200);
}, [new FiftyFiftyMiddleware()]);

Route::group('/admin', function () {
    Route::get('/', function ($rq){return new Response('hello admin!', 200);});
    Route::get('/test1', function ($rq){return new Response('hello admin test1!', 200);});
    Route::get('/test2', function ($rq){return new Response('hello admin test2!', 200);});
}, [new FiftyFiftyMiddleware()]);

Route::middleware(new FiftyFiftyMiddleware); //Глобальный middleware

Route::get('/', function (){}, [], "route.name"); // Имя маршрута

route("route.name"); // Вернёт урл маршрута: /
```

Методы http запросов:
- get()
- post()
- put()
- delete()
- patch()
- options()
- head()

И контроллер используемый тут:
```
use Meract\Core\Controller;
class IterateController extends Controller{
	private static $i = 0;
	public static function add($rq, $arr) {
		self::$i += $arr["num"];
		return self::html("value added");
	}
	public static function get(): int{
		return self::$i;
	}
	public static function rem($rq, $arr) {
		self::$i -= $arr["num"];
		return self::html("value removed");
	}
}

```
А так же middleware:
```
use Meract\Core\Request;
use Meract\Core\Response;

class FiftyFiftyMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (mt_rand(0, 1) === 1) {
            // Пропускаем запрос (50% шанс)
            return $next($request);
        }

        // Блокируем запрос (50% шанс)
        return new Response("Sorry, you lost the 50/50 chance", 403);
    }
}
```
Мы можем передать в роутер путь, и коллбэк функцию, так же как и метода контроллера. Так же мы можем установить маршрут для ошибки 404.

Статический метод html который предоставляет класс Controller принимает html и возвращает, объект класса Response с установленным заголовком `Content-Type : text/html`, просто сокращает ненужный код в контроллерах.

Работает это следующим образом, когда приходит запрос, сервер сначала ищет по прописанным напрямую маршрутам, если не находит, тогда ищет соответствующий файл в папке static. Если она такого файла нет, выполняется маршрут 404. Если он не установлен тогда пользователь просто увидит "not found"

## view / morph
Шаблоны позволяют упрощать вывод. Синтаксис такой.
```
$view = new View("test", ["title" => "test", "year" => 2025, "posts" => [[1,2],[2,1],[3,5],[4,8],[58,85],[123,321]]]);
```
views/test.morph.php:
```
@extends('layouts/main')


@section('loop')

@loop($posts, "post")

{{post[0]}} {{post[1]}}<br> 

@endloop

@endsection


@section('year')
{{year}}
@endsection

@EOF
```
views/layots/main.morph.php:
```
<!DOCTYPE html>
<html>
<head>
    <title>{{title}}</title>
</head>
<body>
    @yeld('loop')

    @yeld('year')
</body>
</html>
```

В конфиге вы можете добавить свои собственные дополнительные прерпроцессоры, например:
```
"viewCompilers" => [
		new \Meract\Core\Compilers\MinifyHtmlViewCompiler
]
```
В данном примере вы можете увидеть как вставлять какие-то параметры, а так же использование нескольких шаблонов друг в друге.
## Модели
Для работы прийдётся настроить базу данных. В вашем файле config.php
Примеры:
```
"database" => [
    "driver" => "mysql",
    "host" => "localhost",
    "port" => 3306,
    "dbname" => "test",
    "username" => "root",
    "password" => "",
    "charset" => "utf8mb4"
]
```
```
"database" => [
    "driver" => "pgsql",
    "host" => "localhost",
    "port" => 5432,
    "dbname" => "test",
    "username" => "postgres",
    "password" => "password"
]
```
```
"database" => [
    "driver" => "sqlite",
    "sqlite_path" => __DIR__ . "/database.sqlite"
]
```
У вас должны быть установлены и включены модули pdo и pdo для вашей субд.
```
use Meract\Core\Model;
class TestModel extends Model{
	protected static $table = 'your_table'; // Имя таблицы
	protected $fillable = ['id', 'name'];

}
```
Вот так вы можете создать модель привязанную к таблице. Далее примеры использования данной модели. В рамках данного примера выполнение происходит внутри роута. Вы же должны делать это внутри контроллера.
```
Route::get('/', function (Request $rq) {
	$m = new TestModel(["name" => (string) random_int(0, 10000)]); // Создаём модель с случайным именем.
	$m->save(); //Сохраняем.
	$r = new Response("Запись создана", 200); //Создаём ответ. С текстом и статусом 200.
	$r->header("Content-Type", "text/html");// Устанавливаем тип html
	return $r;// возвращаем ответ.
});

Route::get('/show', function (Request $rq) {
	$m = new TestModel();//Создаём модель 
	$pices = OUTVAR::dump($m->all()); //$m->all() - Возвращает все записи. OUTVAR::dump делает var_dump в переменную

	$r = new Response("<pre>$pices</pre>", 200);// Выводим всё пользователю обрамляе в pre
	$r->header("Content-Type", "text/html");
	return $r;
});

Route::get('/up/{id}/{data}', function (Request $rq, array $data) {
	$test = TestModel::find((int) $data["id"]); //Создаём модель из записи с id полученным из запроса.
	$test->name = $data['data']; // Устанавливаем значение data из запроса в name.
	$test->save(); // сохраняем

	$pices = "Запись $data[id] обновлена";
	//Сообщаем пользователю.
	$r = new Response("<pre>$pices</pre>", 200);
	$r->header("Content-Type", "text/html");
	return $r;
});

Route::get('/del/{id}', function (Request $rq, array $data) {
	$test = TestModel::find((int) $data["id"]);// создаём модель из записи по id 
	$test->delete();// Удаляем запись.
	
	$pices = "Запись $data[id] Удалена";//Информируем пользователя.

	$r = new Response("<pre>$pices</pre>", 200);
	$r->header("Content-Type", "text/html");
	return $r;
});
```
Данные примеры кода охватывают стандартные CRUD операции выполненные через модели.


## Storage
Синтаксис:
```
Storage::setTime(int seconds); // Устанавливает время жизни записей.
Storage::set("property", "value" ?prefix); // Создаёт запись, при указании префикса запись на определённом префиксе
Storage::get("property", ?prefix); // Получает значение записи
Storage::update("property", ?prefix); // Обновляет время жизни записи.
Storage::remove("property", ?prefix); // Удаляет запись
Storage::handleDeletion(); // Удаляет все истёкшие записи.
```

### Настройка->config.php:
```
"storage" => [
	"driver" => null,
	"time" => 20
]
```

- driver - Объект класса StorageDriver или null(Стандартный драйвер)
- time - Время жизни записей в секундах или 0 вечно

Есть драйвер для работы в базе sql включить вот так:
```
"storage" => [
	"driver" => new \Meract\Core\Drivers\SQLStorageDriver,
	"time" => 600
]
```
Не забудьте смигрировать таблицу

Установка произвольного драйвера нужна поскольку при использовании стандартного сервера(fpm) невозможно сохранять данные между запросами в оперативной памяти. Таким образом вы можете использовать например redis или даже sql базу данных.

## Workers
Воркеры представляют собой систему очередей.

Начнём с конфигурации:
```
"worker" => [
	"enabled" => true,
	"endpoint" => "endpoint",
	"server-callback" => function (string $data): string {
		echo $data."\n";
		return "Понял";
	}
]
```
Далее создадим небольшой воркер `sleep`.

В файле `app/workers/sleep.php`:
```
<?php
use Meract\Core\Worker;

return new class extends Worker {
    public function run(string $message) {
        sleep((int) $message);
        $result = self::sendToServer("Я подождал $message секунд");
        if ($result == "Понял") {
            echo "Меня услышали!\n";
        }
    }
};
```
И в любом месте кода нашего мастер процесса можем использовать:
```
Worker::register("sleep", "3");
```
Это создаст запись в таблице. После worker process когда дойдёт до выполнения этой записи возьмёт имя "sleep" и запустит метод run передав туда message.

Метод sendToServer отправит данные на endpoint. И в мастер процессе отработает колбэк функция воркера. Возвращаемое ей значение выйдет из метода sendToServer.

По факту это система очередей. Но благодоря сохранению состояния. Вы можете создать воркер для обработки большого количества информации. Результат отправить в мастер и сохранить в storage для быстрого ответа пользователю.

Для запуска воркера нужно запустить `worker.php`.

## QRYLI
qryli это QueryBuilder.

Небольшие примеры использования:
```
Qryli::insert("users", ["name" => "aaaaa"])->run();
$users = Qryli::select('*')->from('users')->where('age > ?', [18])->orderBy('name')->limit(10)->run();
Qryli::update('users', ['age' => 26])->where('id = ?', [1])->run();
Qryli::delete('users')->where('id = ?', [1])->run();
```
## Session
В общем случае использование сессий выглядит примерно так:
```
Route::get('/', function ($rq) {
	$session = Session::start($rq);
	if (isset($session->a)) { $session->a += 1; } else {$session->a = 0;}
	
	return $session->end(new Response($session->a, 200));
});
```
Так вы можете устанавливать любые параметры любого типа. Сохранятся они будут с помощью `Storage` Так что не забывайте чистить истёкшие записи перед участками работы с сессиями. И настроить произвольный драйвер в случае использования fpm

## SDR
Простой способ управлять зависимостями в Meract.
Регистрация сервисов:
```
// Синглтон (один экземпляр)  
SDR::singleton(Database::class);  

// Привязка интерфейса к реализации  
SDR::bind(LoggerInterface::class, FileLogger::class);  

// Произвольное значение  
SDR::set('db.host', 'localhost');  
```

Получение сервисов:
```
// Автоматическое создание объекта  
$db = SDR::make(Database::class);  

// Получение значения  
$host = SDR::make('db.host');  
```

Автоматической внедрение:
```
class UserController {  
    public function __construct(  
        private Database $db,  // Автоматически создастся  
        private LoggerInterface $logger  
    ) {}  
}  

// Создаем контроллер – зависимости подставятся сами  
$controller = SDR::make(UserController::class);  
```



## Миграции
Фреймворк обладает базовым функционалом миграций.

Для создания миграции вам нужно создать файл, например `app/migrations/first_migration.php`:
```
<?php

use Meract\Core\Migration;

return new class extends Migration {
    public function up()
    {
        $this->schema->create('fist_migration', function ($table) {
            $table->id();               // Автоинкрементный первичный ключ
            $table->string('name');     // Строковое поле name
            $table->string('message');  // Строковое поле message
        });
    }

    public function down()
	{
        $this->schema->drop('first_migration');
    }
};
```
Дальше вы можете воспользоваться `mrst` для применения миграции:
```
php mrst migrate # Все миграции
php mrst migrate fist_migration # Миграция "first_migration"
```
Так же чтобы откатить миграции вы можете сделать:
```
php mrst migrate.rollback # Все миграции
php mrst migrate.rollback fist_migration # Миграция "first_migration"
```

## mrst
`mrst` или `meract support tool` средство помощи.
Для создания команды вам нужно создать файл в 'meract/commands/file.php'
С примерно таким синтаксисом:
```
<?php
return new class {
	public function run() {
        $args = SDR::make('command.args');
		var_dump($args);
	}
};
```
После вы сможете вызвать команду так:
```
php mrst file arg0 arg1 arg2 arg3
```
Вы увидите примерно следующее:
```
array(4) {
  [0]=>
  string(4) "arg0"
  [1]=>
  string(4) "arg1"
  [2]=>
  string(4) "arg2"
  [3]=>
  string(4) "arg3"
}
```

### make
```
php mrst make <type> <name>
```
Так это создасть файл указанного типа с указанным названием, например:
```
php mrst make model Test
```
Чтобы увидеть больше справки выполните `php mrst make`

### tests
```
php mrst tests
```
Проводит unit тесты из папки `tests` с помощью `phpUnit`

### make.chain
Создаёт цепочку жизненного цикла запроса. В моём понимании цепочка жизненного цикла запроса это: route->controller<-model->view
Т.е. сначала роут реагирует на запрос, после отдаёт его в контроллер, он обрабатывает этот запрос берёт или устанавливает какие-либо данные через модель. А после рендерит всё это через view. И вот команда `make.chain` одним вызовом способна создать такую цепочку жизненного цикла.
Пример:
```
php mrst make.chain rcmv product --table=products -rest '{"title" : "string", "price" : "float", "count" : "integer"}';
php mrst migrate;
```
Итог:
`app/migration/products.php`:
```
<?php

use Meract\Core\Migration;

return new class extends Migration {
    public function up(): void
    {
        $this->schema->create('products', function ($table) {
            $table->id();
            $table->string('title');
            $table->float('price');
            $table->integer('count');

        });
    }

    public function down(): void
    {
        $this->schema->drop('products');
    }
};
```
`app/models/ProductModel.php`:
```
<?php
namespace App\Models;

use Meract\Core\Model;

class ProductModel extends Model 
{
    protected static $table = 'products';
    protected $fillable = [
		'id',
		'title',
		'price',
		'count',
    ];
}
```
`app/controllers/ProductController.php`:
```
<?php
namespace App\Controllers;

use Meract\Core\Controller;
use App\Models\ProductModel;

class ProductController extends Controller
{
    public static function index($request)
    {

    }

    public static function show($request, $data)
    {

    }

    public static function store($request)
    {

    }

    public static function update($request, $data)
    {

    }

    public static function destroy($request, $data)
    {

    }
}
```
`app/views/product.php`:
```
<!-- View for Product -->
```
`app/routes/web.php`:
```
//your routes here...

// REST API routes for Product
Route::get('/product', [ProductController::class, 'index']);
Route::get('/product/{id}', [ProductController::class, 'show']);
Route::post('/product', [ProductController::class, 'store']);
Route::put('/product/{id}', [ProductController::class, 'update']);
Route::delete('/product/{id}', [ProductController::class, 'destroy']);
```

Вы получили, модель, миграцию, контроллер и роуты для круд операций. Со структорой указанной в json
Буквы `rcmv`:
- r - route
- c - controller
- m - model and migration
- v - view

Указывайте только те компоненты которые вам нужны. Параметр `--table=products` необязательный, указывает имя таблицы если оно отличается от основного имени. `-rest` Необязательный параметр, создающий не один роут, а несколько для круд операций в формате RESTApi Так же соответствующие методы в контроллере. Ну и структура таблицы в формате json необязательный параметр, который определяет структуру таблицы.(powershell игнорирует)


# Morph
morph - это клиентский фреймворк интегрированный в экосистему meract.

Для его использования внутри ваших view вам необходимо его подключить, например в теге head, делается это вот так: `@includeMorph`. После в body создайте один или несколько морф компонентов. Пример:

```
    <morph name="main">
      <button id="open" onclick="Morph.goTo('test')">go</button>
    </morph>

    <morph name="test">
      <button id="open" onclick="Morph.goTo('main')">back</button>
    </morph>
```

morph компонент занимает собой всю страницу. Так что данная разметка создаст две страницы, переключаться между которыми можно по нажатию соответствующей кнопки. 

morph уже имеет встроенные стили, если вы хотите создать свою тему, создайте файл, например: `app/views/themes/main.css` с содержимым на подобии:
```
morph[theme="main"] * {
    background:red;
}
```
После используйте тему внутри морфа:
```
    <morph theme="main" name="test">
      <button id="open" onclick="Morph.goTo('main')">back</button>
    </morph>
```

Morph сам позаботится о подгрузке соответствующего файла, и проследит чтобы он был загружен в одном экземпляре.

цветовые схемы работают аналогично, `app/views/colorschemes/main.css`:
```
morph[colorscheme="main"] * {
    --main-fg-color: white;
}
```

После используйте данную переменную в вашей теме.

## backloads
backload'ы - это система которая позволяет загружать дополнительные страницы, асинхронно после загрузки основного html для того чтобы сделать это вам необходимо правильно оформить ваш морф:
```
<morph backload='test' backloadType="once" name='test' theme='main'></morph>
```
После чего создайте файл `app/views/components/test.morph.php`, например с таким содержимым:
```
<form action="form" type="morph">
<input name="login">
<input name="password">
</form>
```
Дальше morph вставит содержимое данного файла внутрь. 
### типы backload'ов
| Тип | Поведение |
| ------------- | ------------- |
| once | Загружается один раз после загрузки DOM |
| goto | Загружается один раз при переходе к компоненту с помощью Morph.goTo |
| every | Загружается каждый раз при переходе к компоненту с помощью Morph.goTo |
| wait | Необходимо загружать руками через Morph.render("name", data?), При goTo Не обновляется |

## Параметры загрузки компонентов
При использовании `Morph.goTo` вы можете указать параметры для компонента:
```
Model.goTo('test', {a: 1, b: "2"});
```

Тогда внутри компонента вы сможете получить эти параметры:
```
a: {{a}}<br>
b: {{b}}
```

## customBackload
Если вам мало стандартной логики backload или вы хотите добавить использование моделей, для получения инфы из субд, вы можете использовать кастомные бэклоады. Для этого вам необходимо заменить аттрибут `backload='<componentName>'` на `customBackload=<url>`

Создайте route, привяжите метод контроллера, используйте модель, отрисуйте произвольный view. Всё в ваших руках!

Учитывайте что Morph.goTo без параметров отправляет get, а с параметрами - post запрос. Получить и использовать которые вы кстати можете так:
```
<morph customBackload="{{{route('component.test'}}}" name='test' backloadType='every'></morph>
```
```
Route::post('/test', function($rq) {
  $resp = (new Response(new View('components/test', ["a" => $rq->parameters['a']), 200));
  $resp->header('Content-Type', 'text/html');
  return $resp;
}, [], "component.test");
```
```
Morph.goTo({a : "Произвольное значение"});
```
(Данный пример реализует логику внутри route вам рекомендуется реализовывать логику внутри контроллеров)

## Примеры методов
```
Morph.goTo(name, ?data) // Открывает морф.
Morph.reload(?data) // Перезагружает морф, может так же принимать параметры с которыми морф будет снова загружен, работает только с backloadType "every" и "wait"

Morph.morphs.main // dom елемент морфа с именем "main"
morph('main') // dom морфа аналогично

Morph.morphs.main.virtual() // Возвращает виртуальное дeрево морфа
Morph.morphs.main.renderVirtual(virtual) // Рендерит виртуальное дерево (Изменения самого элемента morph не применятся)

Morph.ajaxForm(formElement) // Делает форму типа morph
```

### Формы
Вы можете либо дать форме аттрибут type="morph" либо использовать Morph.ajaxForm(formElement).

Тогда при отправке откроется морф с названием указанным в action с параметрами заполненными пользователем

## http
```
Morph.http.sync.get('url') // {body : str, status : number, headers: array, error: null, success: true}

Morph.http.async.get('url', (object) => console.log(object)); // object : {body : str, status : number, headers: array, error: null, success: true}

Morph.http.sync.post('url', {param: "value"}) // {body : str, status : number, headers: array, error: null, success: true}

Morph.http.async.post('url', {param: "value"}, (object) => console.log(object)); // object : {body : str, status : number, headers: array, error: null, success: true}
```

## morph live 
Morph live позволяет вам использовать методы контроллера и middleware для customBackload не регестрируя маршруты. Выглядит это вот так:
```
<morph customBackload='{{{morphLive([\App\Controllers\test::class, "index"])}}}' backloadType="every">without middleware</morph>

<morph customBackload='{{{morphLive([\App\Controllers\test::class, "index"], (new \App\Middlewares\User::class)->handle())}}}' backloadType="every">without middleware</morph>
```

Пожалуйста укажите ключ шифрования в конфиге:
```
"morph" => [
    "live" => "super secret key"
]
```

## Модули
Находятся в `app/views/modules/module.js` подключаем в конфиге:
```
"morph" => [
    "modules" => [ "module" ]
]
```
Код модуля будет подключен при использовании "@includeMorph"


## triggers
Позполяют асинхронно работать с сервером. Пример:
main.morph.php:
```
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    @includeMorph
</head>
<body>
    <morph name="main">
        <morph-trigger action="test">
            <button onclick="@morph-triggerSubmit">Выполнить</button>
            <input name='data'>
            <p>@MTrigger("data", "default")</p>
        </morph-trigger>
    </morph>
</body>
</html>
```
Аттрибут action в элементе <morph-trigger> указывает имя триггера для выполнения. Соответственно:
app/morph-triggers/*test*.php:
```
<?php
return function($data) {
    return $data;
};
```
Сообственно в данном примере при срабатывании события `click` на кнопке выполнится триггер `test` и она получит ассоциативный массив со всеми параметрами(data => input.value) в данном случае. 





# Auth
## Конфигурация
В файле конфигурации config.php укажите параметры аутентификации:
```
'auth' => [
    'table' => 'meract_users',               // Таблица пользователей
    'login_fields' => ['email', 'password'], // Поля для входа
    'registration_fields' => ['email', 'password'], // Поля для регистрации
    'jwt_secret' => 'your-strong-secret',    // Секретный ключ для JWT
    'tokens_table' => 'meract_tokens',       // Таблица недействительных токенов
    'cookie_name' => "AUTHTOKEN"            // Название cookie
]
```
## Базовое использование на сервере

### Инициализация
```
use Meract\Core\Auth;
use Meract\Core\Request;

// В middleware или обработчике маршрута
$auth = Auth::start($request);
Регистрация пользователя
php
try {
    $user = Auth::register([
        'email' => 'user@example.com',
        'password' => 'securepassword',
        'name' => 'John Doe' // дополнительные поля
    ], $request);
    
    $response = $user->set(new Response());
} catch (Exception $e) {
    // Обработка ошибки
}
```
### Авторизация пользователя
```
try {
    $user = Auth::login([
        'email' => 'user@example.com',
        'password' => 'securepassword'
    ], $request);
    
    $response = $user->set(new Response());
} catch (Exception $e) {
    // Обработка ошибки
}
```
### Выход из системы
```
$user = Auth::start($request);
$response = $user->logout(new Response());
Получение данных пользователя
php
$user = Auth::start($request);
if ($user->id) {
    // Пользователь авторизован
    $name = $user->name;
    $email = $user->email;
} else {
    // Пользователь не авторизован
}
```
## Использование на клиенте
### Авторизация
```
Morph.http.async.post('/auth', {
    type: 'log',
    login: 'user@example.com',
    password: 'securepassword'
}, (response) => {
    if (response.success) {
        // Успешная авторизация
        // Cookie установится автоматически
        window.location.href = '/show';
    } else {
        // Ошибка авторизации
        console.error(response.error);
    }
});
```
### Обновление токенов (если access истек)
```
// При получении 401 ошибки
function refreshTokens() {
    const refreshToken = localStorage.getItem('refresh_token');
    
    Morph.http.async.post('/auth/refresh', {
        refresh_token: refreshToken
    }, (response) => {
        if (response.success) {
            const data = JSON.parse(response.body);
            localStorage.setItem('refresh_token', data.refresh);
            // Повторяем оригинальный запрос с новым access токеном
        } else {
            // Перенаправляем на страницу входа
            window.location.href = '/login';
        }
    });
}
```
### Защищенные запросы
```
// Для API запросов передаем токен в заголовке
Morph.http.async.get('/api/data', (response) => {
    // Обработка ответа
}, {
    'Authorization': `Bearer ${localStorage.getItem('access_token')}`
});
Примеры маршрутов
Простой роут с проверкой авторизации
php
Route::get('/profile', function ($request) {
    $user = Auth::start($request);
    
    if (!$user->id) {
        return new Response('Unauthorized', 401);
    }
    
    return new View('profile', ['user' => $user]);
});
```
## API endpoint с токеном
```
Route::get('/api/user', function ($request) {
    $user = Auth::apiLogin($request->header('Authorization'));
    
    if (!$user) {
        return new Response(json_encode(['error' => 'Unauthorized']), 401);
    }
    
    return new Response(json_encode([
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email
    ]), 200, ['Content-Type' => 'application/json']);
});
```
## Особенности работы
1. Cookie-based аутентификация:
    - После успешного login/register устанавливается HTTP-only cookie
    - При каждом запросе токен автоматически проверяется

2. API аутентификация:
    - Используйте Authorization: Bearer <token> заголовок
    - Для проверки используйте Auth::apiLogin()

3. Обновление токенов:
    - Refresh токены должны храниться на клиенте (localStorage)
    - При истечении access токена клиент должен запросить новый

4. Безопасность:
    - Все токены подписываются с использованием HMAC-SHA256
    - Refresh токены можно отзывать
    - HTTP-only cookie защищает от XSS

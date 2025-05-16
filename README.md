| Язык | language |
|-----------------|---------------|
| [Русский / Russian](README-rus.md) | [English / Английский](README.md) |


## Description
Meract is an MVC framework for PHP.

It has many features that set it apart from other frameworks, from its server to its tight integration with the front-end.

### Main namespace
The main namespace for most of the classes that you will use is, `Meract\Core`

## Documentation
Specific technical documentation on the methods [available here](https://lumetas.github.io/meract/)

[Step By Step Guide](docs/stepByStep/install.md)


## Structure

```
.
├───app                 - Your application code
│   ├───controllers     - Controllers
│   ├───core            - Scripts executed before server startup (shared utilities, additional settings, etc.)
│   ├───middleware      - Route middleware
│   ├───migrations      - Database migrations
│   ├───models          - Your models
│   ├───routes          - Your routes
│   ├───static          - Static files (HTML, CSS, JS)
│   ├───views           - Views directory
│   │   ├───colorschemes- Morph component color schemes
│   │   ├───components  - Morph components
│   │   ├───layouts     - View layouts
│   │   ├───modules     - Morph modules
│   │   └───themes      - Morph component themes
│   │
│   └───workers         - Your workers (background tasks/queue processors)
│
├───meract              - Framework code
│   ├───commands        - Commands for `mrst` CLI tool
│   ├───core            - Core framework classes and code
│   └───drivers         - Drivers for framework components (e.g., StorageDrivers)
│
├───config.php          - Framework and application configuration
├───index.php           - Entry point (launches server and your application)
├───worker.php          - Worker entry point (processes queue tasks)
├───mrst                - Framework CLI utility
│
├───vendor              - Composer dependencies
├───composer.json       - Composer configuration
├───composer.lock       - Composer lock file
└───tests               - PHPUnit tests directory
```

## Configuration
The configuration is stored in a file `config.php`, by default it looks something like this:
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
The server's host and port are set here. You can specify your function when raising the server, as well as your query logger:
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


## Installation
```
composer create-project meract/meract project-name
```

## Launch
Depending on the server you choose, you can use either `php index.php` or `php -S interface:port index.php` or the universal command: `php mrst serve`

If the embedded server is used, the server initialization function can be performed. In the case of using a standard server or apache/nginx. It will not be executed. So there are various settings that need to be infused from the code. It must be done somewhere else. Also, in the case of running a test server using a standard php server. You will not be able to configure your request handler. At least for now.

The server will start listening and accepting requests by outputting information about the request to the console. You can also change the format of the logs as described above



## Routes and controllers
I am very much inspired by laravel. So a lot of things will seem familiar to you.

And so, here are all the examples of route syntax:
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

Route::middleware(new FiftyFiftyMiddleware); //Global middleware

Route::get('/', function (){}, [], "route.name "); // Route name

route("route.name "); // Returns the route URL: /
```

HTTP request methods:
- get()
- post()
- put()
- delete()
- patch()
- options()
- head()

And the controller used here:
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
As well as middleware:
```
use Meract\Core\Request;
use Meract\Core\Response;

class FiftyFiftyMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (mt_rand(0, 1) === 1) {
            // Skipping the request (50% chance)
return $next($request);
        }

        // Blocking the request (50% chance)
return new Response("Sorry, you lost the 50/50 chance", 403);
    }
}
```
We can pass the path and the callback function to the route, as well as the controller method. We can also set a route for the 404 error.

The static html method that provides the Controller class accepts html and returns an object of the Response class with the set header `Content-Type : text/html`, simply reduces unnecessary code in controllers.

It works as follows: when a request arrives, the server first searches for the routes specified directly, if it does not find it, then it searches for the corresponding file in the static folder. If there is no such file, route 404 is executed. If it is not installed, then the user will just see "not found"

## view / morph
Templates allow you to simplify the output. The syntax is like this.
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

In the config, you can add your own additional preprocessors, for example:
```
"viewCompilers" => [
		new \Meract\Core\Compilers\MinifyHtmlViewCompiler
]
```
In this example, you can see how to insert some parameters, as well as the use of several templates in each other.
## Models
To work, you will need to set up a database. In your file config.php
Examples:
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
You must have the pdo and pdo modules installed and enabled for your DBMS.
```
use Meract\Core\Model;
class TestModel extends Model{
	protected static $table = 'your_table'; // Table name
	protected $fillable = ['id', 'name'];

}
```
This is how you can create a model linked to a table. The following are examples of using this model. In this example, execution takes place inside the router. You have to do this inside the controller.
```
Route::get('/', function (Request $rq) {
	$m = new TestModel(["name" => (string) random_int(0, 10000)]); // Creating a model with a random name.
	$m->save(); //Save.
	$r = new Response("Record created", 200); //Creating a response. With text and status 200.
    $r->header("Content-Type", "text/html");// Setting the html type
to return $r;// returning the response.
});

Route::get('/show', function (Request $rq) {
	$m = new TestModel();//Creating a model 
	$pices = OUTVAR::dump($m->all()); //$m->all() - Returns all entries. OUTVAR::dump makes var_dump a variable

	$r = new Response("<pre>$pices</pre>", 200);// We display everything framed to the user in pre
	$r->header("Content-Type", "text/html");
	return $r;
});

Route::get('/up/{id}/{data}', function (Request $rq, array $data) {
	$test = TestModel::find((int) $data["id"]); //Creating a model from a record with the id obtained from the request.
	$test->name = $data['data']; // Setting the data value from the query to name.
	$test->save(); // save

	$pices = "Record $data[id] updated";
	//We inform the user.
	$r = new Response("<pre>$pices</pre>", 200);
	$r->header("Content-Type", "text/html");
	return $r;
});

Route::get('/del/{id}', function (Request $rq, array $data) {
	$test = TestModel::find((int) $data["id"]);// creating a model from an entry by id 
	$test->delete();// Deleting the record.
	
	$pices = "Record $data[id] Deleted";//We inform the user.

	$r = new Response("<pre>$pices</pre>", 200);
	$r->header("Content-Type", "text/html");
	return $r;
});
```
These code examples cover standard CRUD operations performed through models.


## Storage
Syntax:
```
Storage::setTime(int seconds); // Sets the lifetime of the records.
Storage::set("property", "value" ?prefix); // Creates an entry, when specifying a prefix, the entry is based on a specific prefix
Storage::get("property", ?prefix); // Gets the value of the record
Storage::update("property", ?prefix); // Updates the lifetime of the record.
Storage::remove("property", ?prefix); // Deletes an entry
Storage::handleDeletion(); // Deletes all expired records.
```

### Setup->config.php :
```
"storage" => [
	"driver" => null,
	"time" => 20
]
```

- driver - An object of the StorageDriver or null(Standard driver)
- time - Lifetime of records in seconds or 0 forever

There is a driver for working in the sql database to enable it like this:
```
"storage" => [
	"driver" => new \Meract\Core\Drivers\SQLStorageDriver,
	"time" => 600
]
```
Don't forget to migrate the table

Installing an arbitrary driver is necessary because when using a standard server (fpm), it is impossible to save data between requests in RAM. This way you can use, a redis or sql database drivers.

## Workers
Workers are a queue system.

Let's start with the configuration:
```
"worker" => [
    "enabled" => true,
    "endpoint" => "endpoint",
	"server-callback" => function (string $data): string {
		echo $data."\n";
		return "Understood";
    }
]
```
Next, let's create a small `sleep` worker.

In the file `app/workers/sleep.php `:
```
<?php
use Meract\Core\Worker;

return new class extends Worker {
    public function run(string $message) {
        sleep((int) $message);
        $result =self::sendToServer("I waited for $message seconds");
        if ($result == "Understood") {
            echo "I was heard!\n";
        }
    }
};
```
And anywhere in the code of our master process we can use:
```
Worker::register("sleep", "3");
```
This will create an entry in the table. After the worker process, when it comes to executing this record, it will take the name "sleep" and run the run method by passing a message there.

The sendToServer method will send the data to the endpoint. And in the master process, the worker's callback function will work out. The value returned to it will exit the sendToServer method.

In fact, this is a queue system. But thanks to the preservation of the state. You can create a worker to process a large amount of information. Send the result to the wizard and save it to storage for a quick response to the user.

To start the worker, you need to run `worker.php `.

## QRYLI
qryli is a QueryBuilder.

Small usage examples:
```
Qryli::insert("users", ["name" => "aaaaa"])->run();
$users = Qryli::select('*')->from('users')->where('age > ?', [18])->orderBy('name')->limit(10)->run();
Qryli::update('users', ['age' => 26])->where('id = ?', [1])->run();
Qryli::delete('users')->where('id = ?', [1])->run();
```
## Session
In general, the use of sessions looks something like this:
```
Route::get('/', function ($rq) {
	$session = Session::start($rq);
	if (isset($session->a)) { $session->a += 1; } else {$session->a = 0;}
	
	return $session->end(new Response($session->a, 200));
});
```
This way you can set any type of parameters. They will be saved using `Storage` So don't forget to clean expired records before doing any session work. And configure an arbitrary driver in case of using fpm

## SDR
An easy way to manage dependencies in Meract.
Service registration:
```
// Singleton (single instance)
SDR::singleton(Database::class);  

// Binding the interface to the implementation  
SDR::bind(LoggerInterface::class, FileLogger::class);  

// Any value  
SDR::set('db.host', 'localhost');  
```

Getting services:
```
// Automatic object creation  
$db = SDR::make(Database::class);  

// Getting the value  
$host = SDR::make('db.host');  
```

Automatic implementation:
```
class UserController {  
    public function __construct(
private Database $db, // Will be created automatically  
        private LoggerInterface $logger  
    ) {}  
}  

// Creating a controller – the dependencies will be substituted by themselves  
$controller = SDR::make(UserController::class);  
```



## Migrations
The framework has basic migration functionality.

To create a migration, you need to create a file, for example `app/migrations/first_migration.php `:
```
<?php

use Meract\Core\Migration;

return new class extends Migration {
    public function up()
    {
        $this->schema->create('fist_migration', function ($table) {
            $table->id(); // Auto-incremented primary key
            $table->string('name'); // String field name
            $table->string('message'); // String field message
        });
    }

    public function down()
	{
        $this->schema->drop('first_migration');
    }
};
```
Next, you can use `mrst` to apply migration:
```
php mrst migrate # All migrations
php mrst migrate fist_migration # Migration "first_migration"
```
Also, to roll back migrations, you can do:
```
php mrst migrate.rollback # All migrations
php mrst migrate.rollback fist_migration # Migration "first_migration"
```

## mrst
The `mrst` or meract support tool is an aid tool.
To create a command, you need to create a file in 'meract/commands/file.php'
With something like this syntax:
```
<?php
return new class {
public function run() {
$args = SDR::make('command.args');
		var_dump($args);
	}
};
```
Then you can call the command like this:
```
php mrst file arg0 arg1 arg2 arg3
```
You will see something like this:
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
So this is to create a file of the specified type with the specified name, for example:
```
php mrst make model Test
```
To see more help, run `php mrst make`

### tests
```
php mrst tests
```
Performs unit tests from the `tests` folder using `PHPUnit`

### make.chain
Creates a request lifecycle chain. In my understanding, the request lifecycle chain is: route->controller<-model->view
That is, first the router responds to the request, then it sends it to the controller, it processes this request, takes or sets any data through the model. And then renders it all through the view. And so the `make.chain` command is able to create such a lifecycle chain with one call.
Example:
```
php mrst make.chain rcmv product --table=products -rest'{"title" : "string", "price" : "float", "count" : "integer"}';
php mrst migrate;
```
Result:
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

You have received a model, migration, controller and routes for multiple operations. With the structure specified in json
Letters `rcmv`:
- r - route
- c - controller
- m - model and migration
- v - view

Specify only the components that you need. The `--table=products` parameter is optional. It specifies the table name if it differs from the main name. `-rest` An optional parameter that creates not one route, but several for multiple operations in the RestAPI format, as well as the corresponding methods in the controller. Well, the structure of the table in json format is an optional parameter that defines the structure of the table.(powershell ignores)

# Morph
morph is a client-side framework integrated into the meract ecosystem.

To use it inside your views, you need to connect it, for example, in the head tag, it's done like this: `@includeMorph`. Then create one or more morph components in the body. Example:

```
    <morph name="main">
      <button id="open" onclick="Morph.goTo('test')">go</button>
    </morph>

    <morph name="test">
      <button id="open" onclick="Morph.goTo('main')">back</button>
    </morph>
```

The morph component takes up the entire page. So this markup will create two pages that you can switch between by clicking the appropriate button. 

morph already has built-in styles, if you want to create your own theme, create a file, for example: `app/views/themes/main.css` with content similar to:
```
morph[theme="main"] * {
    background:red;
}
```
Then use the theme inside the morph:
```
<morph theme="main" name="test">
<button id="open" onclick="Morph.goTo('main')">back</button>
    </morph>
```

Morph will take care of uploading the corresponding file itself, and make sure that it is uploaded in a single instance.

color schemes work similarly, `app/views/colorschemes/main.css`:
```
morph[colorscheme="main"] * {
    --main-fg-color: white;
}
```

Then use this variable in your theme.

## backloads
backloads are a system that allows you to load additional pages asynchronously after loading the main html. In order to do this, you need to properly format your morph:
```
<morph backload='test' backloadType="once" name='test' theme='main'></morph>
```
Then create the file `app/views/components/test.morph.php `, for example, with the following content:
```
<form action="form" type="morph">
<input name="login">
<input name="password">
</form>
```
Morph will then paste the contents of this file inside. 
### types of backloads
| Type | Behavior |
| ------------- | ------------- |
| once | Loaded once after loading the DOM |
| goto | Loaded once when navigating to a component using Morph.goTo |
| every | Is loaded every time you navigate to a component using Morph.goTo |
| wait | It must be uploaded manually via Morph.render("name", data?), It is not updated with goTo |

## Component Loading options
When using `Morph.goTo`, you can specify the parameters for the component:
```
Model.goTo('test', {a: 1, b: "2"});
```

Then you can get these parameters inside the component.:
```
a: {{a}}<br>
b: {{b}}
```

## customBackload
If the standard backlog logic is not enough for you or you want to add the use of models to get information from the database, you can use custom backloads. To do this, you need to replace the attribute `backload='<ComponentName>'` to `customBackload=<url>`

Create a route, bind a controller method, use a model, and draw a custom view. Everything is in your hands!

Keep in mind that Morph.goTo sends a get without parameters, and a post request with parameters. Which, by the way, you can get and use like this:
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
Morph.goTo({a : "Arbitrary value"});
```
(This example implements logic inside a route. You are recommended to implement logic inside controllers)

## Examples of methods
```
Morph.goTo(name, ?data) // Opens the morph.
Morph.reload(?data) // Reloads the morph, can also accept parameters with which the morph will be loaded again, works only with the backloadType "every" and "wait"

Morph.morphs.main // dom element of a morph named "main"
morph('main') // dom morph similarly

Morph.morphs.main.virtual() // Returns a virtual morph tree
Morph.morphs.main.renderVirtual(virtual) // Renders the virtual tree (Changes to the morph element itself will not apply)

Morph.ajaxForm(FormElement) // Makes a morph type form
```

### Forms
You can either give the type="morph" attribute to the form, or use Morph.ajaxForm(FormElement).

Then, when sending, a morph will open with the name specified in the action with the parameters filled in by the user.

## http
```
Morph.http.sync.get('url') // {body : str, status : number, headers: array, error: null, success: true}

Morph.http.async.get('url', (object) => console.log(object)); // object : {body : str, status : number, headers: array, error: null, success: true}

Morph.http.sync.post('url', {param: "value"}) // {body : str, status : number, headers: array, error: null, success: true}

Morph.http.async.post('url', {param: "value"}, (object) => console.log(object)); // object : {body : str, status : number, headers: array, error: null, success: true}
```

## morph live 
Morph live allows you to use controller and middleware methods for customackload without having to register routes. It looks like this:
```
<morph customBackload='{{{morphLive([\App\Controllers\test::class, "index"])}}}' backloadType="every">without middleware</morph>

<morph customBackload='{{{morphLive([\App\Controllers\test::class, "index"], (new \App\Middlewares\User::class)->handle())}}}' backloadType="every">without middleware</morph>
```

Please specify the encryption key in the config:
```
"morph" => [
"live" => "super secret key"
]
```

## Modules
They are located in `app/views/modules/module.js` is connected in the config:
```
"morph" => [
"modules" => [ "module" ]
]
```
The module code will be enabled when using "@includeMorph"


## triggers
They allow you to work asynchronously with the server. Example:
main.morph.php :
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
            <button onclick="@morph-triggerSubmit">Execute</button>
            <input name='data'>
            <p>@MTrigger("data", "default")</p>
        </morph-trigger>
    </morph>
</body>
</html>
```
The action attribute in the <morph-trigger> element specifies the name of the trigger to execute. Accordingly:
app/morph-triggers/**test**.php:
```
<?php
return function($data) {
    return $data;
};
```
Accordingly, in this example, when the `click` event is triggered, the `test` trigger is executed on the button and it receives an associative array with all parameters(data => input.value) in this case. 


Another example:

```
<morph name="main">
        <morph-trigger action="test">
            <input name='login'>
            <input name='password'>
            <button onclick="@morph-triggerSubmit">Execute</button>
            <p>@MTrigger("data", "")</p>
        </morph-trigger>
    </morph>
```

trigger:
```
<?php
return function($data) {
    if ($data['login'] == "admin" && $data['password'] == "123") {
return ["data" => "You have successfully logged in!"];
    } else {
        return ["data" => "Invalid data :("];
    }
    
};
```


## hooks
At the moment, there is only one initialization hook.
You can use it like this:
```
Morph.registerInitHook(function () {
    document.querySelectorAll('morph-trigger').forEach(el => {
        console.log(el);
        Morph._registerTrigger_(el);
    })
})
```

## Scripts

Scripts will be loaded automatically. To use this feature, place your scripts in the app/scripts folder. Example script:

`app/scripts/main.js:`
```
//: /
alert('You are on /!');
```

This script will be automatically loaded when the user accesses the root path (/) and Morph is included on the page. The target path is specified in the first line of the script. You can also use wildcards like //: /users/* to match path patterns.

To analyze and process route paths, run the following command:
```
php mrst scripts.build
```
To clear the script cache:
```
php mrst scripts.clear
```
For development convenience, you can create a file like app/core/scripts.php with the following content to avoid manual rebuilding:
```
<?php
\Meract\Core\ScriptBuilder::build();
```
This will automatically rebuild scripts when a request is received.


# Auth
## Configuration
In the configuration file config.php specify the authentication parameters:
```
'auth' => [
    'table' => 'meract_users', // User table
    'login_fields' => ['email', 'password'], // Login fields
    'registration_fields' => ['email', 'password'], // Registration fields
    'jwt_secret' => 'your-strong-secret', // Secret key for JWT
    'tokens_table' => 'meract_tokens', // Table of invalid tokens
    'cookie_name' => "AUTHTOKEN" // Cookie name
]
```
## Basic usage on the server

### Initialization
```
use Meract\Core\Auth;
use Meract\Core\Request;

// In middleware or the route handler
$auth = Auth::start($request);

php
try user registration {
    $user = Auth::register([
        'email' => 'user@example.com',
        'password' => 'securepassword',
        'name' => 'John Doe' // additional fields
    ], $request);
    
    $response = $user->set(new Response());
} catch (Exception $e) {
    // Error handling
}
```
### User authorization
```
try {
    $user = Auth::login([
        'email' => 'user@example.com',
        'password' => 'securepassword'
    ], $request);
    
    $response = $user->set(new Response());
} catch (Exception $e) {
    // Error handling
}
```
### Logout
```
$user = Auth::start($request);
$response = $user->logout(new Response());
```

Getting user data
```
$user = Auth::start($request);
if ($user->id) {
    // The user is logged in
    $name = $user->name;
    $email = $user->email;
} else {
    // The user is not authorized
}
```
## Use on the client
### Authorization
```
Morph.http.async.post('/auth', {
    type: 'log',
    login: 'user@example.com',
    password: 'securepassword'
}, (response) => {
    if (response.success) {
        // Successful authorization
        // The cookie will be set automatically
        window.location.href = '/show';
    } else {
        // Authorization error
        console.error(response.error);
    }
});
```
### Updating tokens (if access has expired)
```
// When receiving a 401 error
function refreshTokens() {
    const refreshToken = localStorage.getItem('refresh_token');
    
    Morph.http.async.post('/auth/refresh', {
        refresh_token: refreshToken
    }, (response) => {
        if (response.success) {
            const data = JSON.parse(response.body);
            localStorage.setItem('refresh_token', data.refresh);
            // Repeating the original request with a new access token
        } else {
            // Redirecting to the login page
            window.location.href = '/login';
        }
    });
}
```
### Protected requests
```
// For API requests, we pass the token in the header
Morph.http.async.get('/api/data', (response) => {
// Response processing
}, {
    'Authorization': `Bearer ${localStorage.getItem('access_token')}`
});
Examples of routes
A simple router with
php authorization verification
Route::get('/profile', function ($request) {
    $user = Auth::start($request);
    
    if (!$user->id) {
        return new Response('Unauthorized', 401);
    }
    
    return new View('profile', ['user' => $user]);
});
```
## API endpoint with the token
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
## Work features
1. Cookie-based authentication:
    - After a successful login/register, an HTTP-only cookie is set.
    - The token is automatically verified with each request.

2. API authentication:
    - Use the Authorization:Bearer <token> header
    - Use Auth::apiLogin() for verification

3. Token renewal:
    - Refresh tokens must be stored on the client (localStorage)
    - When the access token expires, the client must request a new one

4. Security:
    - All tokens are signed using HMAC-SHA256   
    - Refresh tokens can be revoked
    - HTTP-only cookie protects against XSS

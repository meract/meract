# [BACK](helloWorld.md)
В данной статье мы разберёмся как сделать простейший интерфейс с помощью morph. Тут будут продемонстрированы не лучшие практики, а так же минимум возможностей. Данная статья на целена на людей которые абсолютно не понимает зачем им morph и как его использовать.

Для начала оформим маршрут, для отрисовки страницы app/routes/web.php:
```
<?php
use Meract\Core\View;
use Meract\Core\Route;
use Meract\Core\Response;

Route::get('/', function ($rq) {
	return new Response(new View('main'), 200);
});

Route::post('/form', function ($rq) {
	return new Response(new View('components/formHandler', $rq->parameters), 200);
}, [], "form.handler"); 
// Вы должны оформить эту логику внутри контролера и сохранить данные с помощью модели.
```

Теперь создадим view шаблон "main", app/views/main.morph.php:
```
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta http-equiv="X-UA-Compatible" content="ie=edge">
		<title>morph example</title>
		@includeMorph <!-- Подключаем morph -->
	</head>
	<body>
		<morph name="sidebar" theme="sidebar"> <!-- Создаём sidebar ввиде morph компонента -->
			<!-- Добавляем две кнопки для переключения контента -->
			<button onclick="Morph.goTo('content1');">content1</button>
			<button onclick="Morph.goTo('content2');">content2</button>
		</morph> 

		<!-- Создаём два morph'а с фоновой загрузкой, чтобы страница грузилась быстрее, один из которых делаем активным по умолчанию-->
		<morph name="content1" theme="content" backload="content1" backloadType="once" active></morph>
		<morph name="content2" theme="content" backload="content2" backloadType="once"></morph>

		<!-- Морф для обработки формы -->
		<morph name="form" theme="content" customBackload="{{{route("form.handler")}}}" backloadType="every"</morph>

	</body>
</html>

```
Теперь зададим содержимое morph-компонентам. morph-компоненты находятся в `app/views/components`. Вы можете разместить там абсолютно любую вёрстку.

content1.morph.php:
```
<p>content1</p>
```
content2.morph.php:
```
<form action="form" type="morph"><!-- Указываем в action morph для обработки, а так же указываем что форма должна обрабатываться морфом -->
    <!-- Создаём два инпута с полями -->
	<input name="mail" placeholder="email:">
	<input name="name" placeholder="name:">
	<input type="submit">
</form>
```

formHandler.morph.php:
```
<!-- Сообщаем пользователю информацию-->
<p>Спасибо, {{name}}, мы напишем вам на почту: {{mail}}</p> 
<!-- Кнопка для возврата на предыдущий морф -->
<button onclick="history.back()">Назад</button> 
```

Теперь создадим две темы в `app/views/themes`

content.css:
```
morph[theme="content"] {
	--morph-width:70vw;
	--morph-height:100vh;
	--morph-top:0;
	--morph-left:30vw;
	--morph-position:absolute;
}
```

sidebar.css:
```
morph[theme="sidebar"] {
	--morph-width:30vw;
	--morph-height:100vh;
	--morph-top:0;
	--morph-left:0;
	--morph-position:absolute;
	--morph-no-active-opacity: 1;
	--morph-no-active-enable: all;
}
```

В данных темах с помощью переменных мы задём ширину, высоту, положение морфа, а в случае с sidebar `--morph-no-active-opacity: 1;` Делает морф всегда видимым(Даже когда он не активен), а `--morph-no-active-enable: all;` Всегда доступным для взаимодействия.

В итоге мы имеем простое SPA приложение которое загружается быстро. Работает без загрузки доп страниц. Позволяет нам переключать две вкладки контента через sidebar и обрабатывать форму.

<?php
namespace Meract\Core;

/**
 * Класс для работы с представлениями (шаблонами).
 *
 * Обеспечивает:
 * - Рендеринг PHP-шаблонов
 * - Передачу данных в шаблоны
 * - Буферизацию вывода
 */
class View
{
	/**
	 * @var string Путь к директории с шаблонами
	 */
	private static $viewPath = 'app/views';

	/**
	 * @var string Имя шаблона
	 */
	private $template;

	/**
	 * @var array Данные для передачи в шаблон
	 */
	private $data = [];

	/**
	 * Конструктор класса View
	 *
	 * @param string $template Имя шаблона (без расширения .php)
	 * @param array $data Ассоциативный массив данных для шаблона
	 */
	public function __construct(string $template, array $data = [])
	{
		$this->template = $template;
		$this->data = $data;
	}

	/**
	 * Магический метод для автоматического рендеринга при использовании объекта как строки
	 *
	 * @return string Содержимое отрендеренного шаблона
	 * @throws Exception Если файл шаблона не найден
	 */
	public function __toString(): string
	{
		return $this->render();
	}

	/**
	 * Рендерит шаблон с переданными данными
	 *
	 * @return string Содержимое отрендеренного шаблона
	 * @throws Exception Если файл шаблона не найден
	 */
	public function render(): string
	{
		// Формируем полный путь к файлу шаблона
		$templatePath = self::$viewPath . '/' . $this->template . '.php';

		// Проверяем существование файла шаблона
		if (!file_exists($templatePath)) {
			throw new Exception("Template file not found: $templatePath");
		}

		// Извлекаем переменные из массива данных
		extract($this->data);

		// Включаем буферизацию вывода
		ob_start();
		include $templatePath;
		return ob_get_clean();
	}

	/**
	 * Устанавливает новый путь к директории с шаблонами.
	 *
	 * @param string $path Абсолютный или относительный путь
	 * @return void
	 */
	public static function setViewPath(string $path): void
	{
		self::$viewPath = rtrim($path, '/');
	}
}
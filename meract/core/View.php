<?php
namespace Meract\Core;

/**
 * Класс для работы с шаблонами представлений.
 * 
 * Реализует систему шаблонов с поддержкой наследования, секций и компиляторов.
 * Поддерживает интерфейс Stringable для автоматического преобразования в строку.
 */
class View implements \Stringable
{
    /**
     * @var string Путь к директории с шаблонами
     */
    private static string $viewsPath = 'app/views';
    
    /**
     * @var array Массив зарегистрированных компиляторов шаблонов
     */
    private static array $compilers = [];
    
    /**
     * @var string Имя текущего шаблона
     */
    private string $template;
    
    /**
     * @var array Данные для передачи в шаблон
     */
    private array $data;
    
    /**
     * @var array Секции контента
     */
    private array $sections = [];
    
    /**
     * @var string|null Родительский шаблон для наследования
     */
    private ?string $extendedTemplate = null;
    
    /**
     * @var string Текущая активная секция
     */
    private string $currentSection = '';

    /**
     * Конструктор представления.
     *
     * @param string $template Имя шаблона (без расширения)
     * @param array $data Данные для передачи в шаблон
     */
    public function __construct(string $template, array $data = [])
    {
        $this->template = $template;
        $this->data = $data;
    }

    /**
     * Магический метод для получения данных шаблона.
     *
     * @param string $name Имя переменной
     * @return mixed Значение переменной или null если не существует
     */
    public function __get(string $name)
    {
        return $this->data[$name] ?? null;
    }

    /**
     * Магический метод для установки данных шаблона.
     *
     * @param string $name Имя переменной
     * @param mixed $value Значение переменной
     */
    public function __set(string $name, $value): void
    {
        $this->data[$name] = $value;
    }

    /**
     * Преобразует представление в строку (реализация Stringable).
     *
     * @return string Результат рендеринга шаблона
     */
    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * Рендерит шаблон и возвращает результат.
     *
     * @return string Результат рендеринга
     * @throws \Exception Если шаблон не найден
     */
    public function render(): string
    {
        $templatePath = self::$viewsPath . '/' . $this->template . '.morph.php';
        
        if (!file_exists($templatePath)) {
            throw new \Exception("View [{$this->template}] not found at: {$templatePath}");
        }

        $content = file_get_contents($templatePath);
        
        foreach (self::$compilers as $compiler) {
            $content = $compiler->run($content);
        }

        extract($this->data);
        ob_start();
        eval('?>' . $content);
        $content = ob_get_clean();

        if ($this->extendedTemplate) {
            return $this->renderExtendedTemplate();
        }

        return $content;
    }

    /**
     * Рендерит родительский шаблон с подставленными секциями.
     *
     * @return string Результат рендеринга
     * @throws \Exception Если родительский шаблон не найден
     */
    private function renderExtendedTemplate(): string
    {
        $layoutPath = self::$viewsPath . '/' . $this->extendedTemplate . '.morph.php';
        if (!file_exists($layoutPath)) {
            throw new \Exception("Layout [{$this->extendedTemplate}] not found");
        }

        $layoutContent = file_get_contents($layoutPath);
        foreach (self::$compilers as $compiler) {
            $layoutContent = $compiler->run($layoutContent);
        }

        extract($this->data);
        ob_start();
        eval('?>' . $layoutContent);
        return ob_get_clean();
    }

    /**
     * Устанавливает родительский шаблон для наследования.
     *
     * @param string $template Имя родительского шаблона
     */
    public function extends(string $template): void
    {
        $this->extendedTemplate = $template;
    }

    /**
     * Начинает новую секцию контента.
     *
     * @param string $name Имя секции
     */
    public function section(string $name): void
    {
        $this->currentSection = $name;
        ob_start();
    }

    /**
     * Завершает текущую секцию контента.
     */
    public function endSection(): void
    {
        if ($this->currentSection) {
            $this->sections[$this->currentSection] = ob_get_clean();
            $this->currentSection = '';
        }
    }

    /**
     * Выводит содержимое секции.
     *
     * @param string $name Имя секции
     * @return string Содержимое секции или пустая строка
     */
    public function yeld(string $name): string
    {
        return $this->sections[$name] ?? '';
    }

    /**
     * Устанавливает путь к директории с шаблонами.
     *
     * @param string $path Путь к директории
     */
    public static function setViewsPath(string $path): void
    {
        self::$viewsPath = rtrim($path, '/');
    }

    /**
     * Добавляет компилятор шаблонов.
     *
     * @param ViewCompilerInterface $compiler Компилятор шаблонов
     */
    public static function addCompiler(ViewCompilerInterface $compiler): void
    {
        self::$compilers[] = $compiler;
    }
}

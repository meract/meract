<?php
namespace Meract\Core;

class View implements \Stringable
{
    private static string $viewsPath = 'app/views';
    private static array $compilers = [];
    
    private string $template;
    private array $data;
    private array $sections = [];
    private ?string $extendedTemplate = null;
    private string $currentSection = '';

    public function __construct(string $template, array $data = [])
    {
        $this->template = $template;
        $this->data = $data;
    }

    public function __get(string $name)
    {
        return $this->data[$name] ?? null;
    }

    public function __set(string $name, $value): void
    {
        $this->data[$name] = $value;
    }

    public function __toString(): string
    {
        return $this->render();
    }

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

    public function extends(string $template): void
    {
        $this->extendedTemplate = $template;
    }

    public function section(string $name): void
    {
        $this->currentSection = $name;
        ob_start();
    }

    public function endSection(): void
    {
        if ($this->currentSection) {
            $this->sections[$this->currentSection] = ob_get_clean();
            $this->currentSection = '';
        }
    }

    public function yeld(string $name): string
    {
        return $this->sections[$name] ?? '';
    }

    public static function setViewsPath(string $path): void
    {
        self::$viewsPath = rtrim($path, '/');
    }

    public static function addCompiler(ViewCompilerInterface $compiler): void
    {
        self::$compilers[] = $compiler;
    }
}
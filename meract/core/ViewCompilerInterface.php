<?php
namespace Meract\Core;

/**
 * Метод используемый для обработки шаблона
 */
interface ViewCompilerInterface
{
    public function run(string $template): string;
}

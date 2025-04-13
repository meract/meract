<?php
namespace Meract\Core;

interface ViewCompilerInterface
{
    public function run(string $template): string;
}
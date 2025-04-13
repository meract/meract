<?php
namespace Meract\Core\Compilers;
use Meract\Core\ViewCompilerInterface;
class BaseViewCompiler implements ViewCompilerInterface
{
    public function run(string $template): string
    {
        // Обработка extends
        $template = preg_replace('/@extends\(\'(.+?)\'\)/', '<?php $this->extends(\'$1\'); ?>', $template);

        // Обработка section
        $template = preg_replace('/@section\(\'(.+?)\'\)/', '<?php $this->section(\'$1\'); ?>', $template);
        $template = str_replace('@endsection', '<?php $this->endSection(); ?>', $template);

        // Обработка EEF (конец всего)
        $template = str_replace('@EOF', '', $template);

        // Обработка yeld
        $template = preg_replace('/@yeld\(\'(.+?)\'\)/', '<?= $this->yeld(\'$1\') ?>', $template);

        // Обработка переменных
        $template = preg_replace('/\{\{(.+?)\}\}/', '<?= htmlspecialchars($$1 ?? null, ENT_QUOTES) ?>', $template);

        // Обработка циклов
        $template = preg_replace_callback('/@loop\((.+?), ?\"(.+?)\"\)/', function($matches) {
            return "<?php foreach({$matches[1]} as \${$matches[2]}): ?>";
        }, $template);
        $template = str_replace('@endloop', '<?php endforeach; ?>', $template);

        // Обработка свойств объектов и массивов
        $template = preg_replace('/\{\{(.+?)->(.+?)\}\}/', '<?= htmlspecialchars($$1->$2 ?? null, ENT_QUOTES) ?>', $template);
        $template = preg_replace('/\{\{(.+?)\[(.+?)\]\}\}/', '<?= htmlspecialchars($$1[$2] ?? null, ENT_QUOTES) ?>', $template);

        return $template;
    }
}
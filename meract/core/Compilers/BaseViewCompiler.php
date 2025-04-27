<?php
namespace Meract\Core\Compilers;
use Meract\Core\ViewCompilerInterface;
use Meract\Core\SDR;

class BaseViewCompiler implements ViewCompilerInterface
{
    public function run(string $template): string
    {
        // Обработка extends - теперь принимает как одинарные, так и двойные кавычки
        $template = preg_replace('/@extends\(\s*([\'"])(.+?)\1\s*\)/', '<?php $this->extends($1$2$1); ?>', $template);
		
		// Обработка функций
		$template = preg_replace('/\{\{\{\s*(.+?)\s*\}\}\}/', '<?= htmlspecialchars($1); ?>', $template);

        // Обработка section - аналогично extends
        $template = preg_replace('/@section\(\s*([\'"])(.+?)\1\s*\)/', '<?php $this->section($1$2$1); ?>', $template);
        $template = str_replace('@endsection', '<?php $this->endSection(); ?>', $template);

        // Обработка EEF (конец всего)
        $template = str_replace('@EOF', '', $template);

        // Обработка yield (исправлено опечатку yeld -> yield)
        $template = preg_replace('/@yield\(\s*([\'"])(.+?)\1\s*\)/', '<?= $this->yield($1$2$1) ?>', $template);

        // Обработка переменных - добавлена поддержка пробелов внутри {{ }}
        $template = preg_replace('/\{\{\s*(.+?)\s*\}\}/', '<?= htmlspecialchars($$1 ?? null, ENT_QUOTES) ?>', $template);

        // Обработка циклов - теперь учитывает разные кавычки и пробелы
        $template = preg_replace_callback('/@loop\(\s*(.+?)\s*,\s*([\'"])(.+?)\2\s*\)/', function($matches) {
            return "<?php foreach({$matches[1]} as \${$matches[3]}): ?>";
        }, $template);
        $template = str_replace('@endloop', '<?php endforeach; ?>', $template);
	
		$modules = SDR::make('config')['morph']['modules'] ?? [];
		
		$morph = "
            <script>".file_get_contents(__DIR__.'/../../client/morph.js')."</script>
            <script>".file_get_contents(__DIR__.'/../../client/morph-http.js')."</script>
			<style>".file_get_contents(__DIR__.'/../../client/morph.css')."</style>
			<meta name='viewport' content='width=device-width, initial-scale=1' />
";

		for($i = 0; $i < count($modules); $i ++){
			$morph .= "<script>".file_get_contents(__DIR__.'/../../../app/views/modules/'.$modules[$i].'.js')."</script>";
		}

        $template = str_replace('@includeMorph', $morph, $template);

		$template = str_replace('@includeNamedRoutes', "<script> Morph.routes = JSON.parse(`".json_encode(\Meract\Core\Route::getNamedRoutes())."`);</script>", $template);


        // Обработка свойств объектов и массивов - добавлена поддержка пробелов
        $template = preg_replace('/\{\{\s*(.+?)->(.+?)\s*\}\}/', '<?= htmlspecialchars($$1->$2 ?? null, ENT_QUOTES) ?>', $template);
        $template = preg_replace('/\{\{\s*(.+?)\[(.+?)\]\s*\}\}/', '<?= htmlspecialchars($$1[$2] ?? null, ENT_QUOTES) ?>', $template);

        return $template;
    }
}

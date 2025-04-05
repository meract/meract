<?php
use Meract\Core\SDR;
return new class {
    public function run() {
		$argv = SDR::make('command.args');
		$argc = count($argv);
        include PROJECT_DIR.'/vendor/bin/phpunit';
    }
};
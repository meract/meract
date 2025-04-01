<?php
return new class {

	public function run($argv, $argc) {
		include PROJECT_DIR.'/vendor/bin/phpunit';
	}

};

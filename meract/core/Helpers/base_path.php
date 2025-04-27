<?php
function base_path(null|string $path = null): string {
	return $path ? PROJECT_DIR.DIRECTORY_SEPARATOR.str_replace("/",DIRECTORY_SEPARATOR, $path) : PROJECT_DIR;
}

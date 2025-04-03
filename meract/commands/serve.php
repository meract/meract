<?php
return new class
{

	public function run($argv, $argc)
	{
        global $config;
        if ($config['server']['customServer'] ?? false) {
            $host = $config['server']['host'];
            $port = $config['server']['port'];
            shell_exec("php -S $host:$port index.php");
        } else {
            include "index.php";
        }
	}

};
<?php
use Meract\Core\SDR;

return new class {
    public function run() {
        $config = SDR::make('config');
        
        if ($config['server']['customServer'] ?? false) {
            $host = $config['server']['host'];
            $port = $config['server']['port'];
            shell_exec("php -S $host:$port index.php");
        } else {
            include "index.php";
        }
    }
};
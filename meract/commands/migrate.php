<?php
namespace Meract\Commands;

use Meract\Core\{Migrator, SDR};

return new class {
    public function run()
    {
        $args = SDR::make('command.args');
        $migrationsPath = SDR::make('config')['migrations_path'] 
            ?? SDR::make('project_dir') . '/app/migrations';
        
        $migrator = new Migrator(
            $migrationsPath,
            SDR::make('pdo.connection')
        );

        $migrator->migrate($args[0] ?? null);
    }
};
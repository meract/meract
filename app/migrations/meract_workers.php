<?php

use Meract\Core\Migration;

return new class extends Migration {
    public function up(): void
    {
        $this->schema->create('meract_workers', function ($table) {
            $table->id();               // Автоинкрементный первичный ключ
            $table->string('name');     // Строковое поле name
            $table->string('message');  // Строковое поле message
        });
    }

    public function down(): void
	{
        $this->schema->drop('meract_workers');
    }
};

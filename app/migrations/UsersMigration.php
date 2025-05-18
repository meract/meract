<?php

use Meract\Core\Migration;

return new class extends Migration {

	public function up(): void
	{
        $this->schema->create('users', function ($table) {
            $table->id();               // Автоинкрементный первичный ключ
            $table->string('name');     // Строковое поле name
            $table->string('message');  // Строковое поле message
        });
	}

	public function down(): void
	{
        $this->schema->drop('users');
	}

};

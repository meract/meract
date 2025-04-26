<?php

use Meract\Core\Migration;

return new class extends Migration {
    public function up(): void
    {
        $this->schema->create('meract_tokens', function ($table) {
            $table->id();               // Автоинкрементный первичный ключ
			$table->string("token");
			$table->string("created_at");
        });
    }

    public function down(): void
	{
        $this->schema->drop('meract_tokens');
    }
};

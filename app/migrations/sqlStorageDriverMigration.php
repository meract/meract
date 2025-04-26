<?php

use Meract\Core\Migration;

return new class extends Migration {
    public function up(): void
    {
        $this->schema->create('meract_storage', function ($table) {
            $table->string('key')->primary();  // Строковый первичный ключ
            $table->text('value');             // Текстовое поле для хранения данных
            $table->integer('expires');        // Unix timestamp истечения срока
        });
    }

    public function down(): void
    {
        $this->schema->drop('meract_storage');
    }
};

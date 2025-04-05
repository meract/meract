<?php

use Meract\Core\Migration;

return new class extends Migration {
    public function up(): void
    {
        $this->schema->create('admins', function ($table) {
            $table->id();
            $table->string('name');
            $table->integer('age');
            $table->string('mail');

        });
    }

    public function down(): void
    {
        $this->schema->drop('admins');
    }
};

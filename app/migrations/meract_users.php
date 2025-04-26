<?php

use Meract\Core\Migration;
use Meract\Core\Qryli;

return new class extends Migration {
	public function up(): void
	{
		$this->schema->create('meract_users', function ($table) {
			// Основные поля
			$table->id();  // Автоинкрементный ID
			$table->string('email')->unique();    // Уникальный email
			$table->string('password');          // Хеш пароля
			$table->string('name')->nullable();  // Имя пользователя
		});

		// Опционально: добавляем дефолтного администратора
		$this->seedDefaultAdmin();
	}

	public function down(): void
	{
		$this->schema->drop('meract_users');
	}

	private function seedDefaultAdmin(): void
	{
		// Можно добавить начального пользователя (опционально)
		Qryli::insert('meract_users', [
			'email' => 'admin@example.com',
			'password' => password_hash('admin123', PASSWORD_BCRYPT),
			'name' => 'Administrator',
		])->run();
	}
};

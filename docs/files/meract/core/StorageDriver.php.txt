<?php
namespace Meract\Core;
/**
 * Абстрактный класс драйвера хранилища.
 * Определяет интерфейс, который должны реализовывать все конкретные драйверы.
 */
abstract class StorageDriver
{
    /**
     * Устанавливает значение для свойства.
     *
     * @param string $property Название свойства
     * @param mixed $value Значение свойства
     * @param int $ttl Время жизни в секундах
     * @param string|null $prefix Префикс для подхранилища
     * @return void
     */
    abstract public function set(string $property, $value, int $ttl, ?string $prefix = null): void;

    /**
     * Получает значение свойства.
     *
     * @param string $property Название свойства
     * @param string|null $prefix Префикс для подхранилища
     * @return mixed|null Возвращает значение или null, если не найдено или истекло
     */
    abstract public function get(string $property, ?string $prefix = null);

    /**
     * Удаляет запись из хранилища.
     *
     * @param string $property Название свойства
     * @param string|null $prefix Префикс для подхранилища
     * @return void
     */
    abstract public function remove(string $property, ?string $prefix = null): void;

    /**
     * Обновляет время жизни записи.
     *
     * @param string $property Название свойства
     * @param int $ttl Новое время жизни в секундах
     * @param string|null $prefix Префикс для подхранилища
     * @return void
     */
    abstract public function updateTtl(string $property, int $ttl, ?string $prefix = null): void;

    /**
     * Удаляет все истёкшие записи.
     *
     * @return void
     */
    abstract public function handleDeletion(): void;
}
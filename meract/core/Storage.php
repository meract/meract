<?php
namespace Meract\Core;

/**
 * Класс для временного хранения данных. В стандартном драйвере(который работает только с встроенным сервером) хранит данные в оперативной памяти.
 */
class Storage
{
    private static StorageDriver $driver;
    private static int $defaultTtl = 3600;

    /**
     * Инициализирует хранилище с указанным драйвером.
     *
     * @param StorageDriver|null $driver Драйвер хранилища (по умолчанию MemoryStorageDriver)
     */
    public static function init(?StorageDriver $driver = null): void
    {
        self::$driver = $driver ?? new \Meract\Core\Drivers\MemoryStorageDriver();
    }

    /**
     * Устанавливает время жизни записей в хранилище.
     *
     * @param int $seconds Время жизни в секундах
     */
    public static function setTime(int $seconds): void
    {
        self::$defaultTtl = $seconds;
    }

    /**
     * Устанавливает значение для свойства.
     *
     * @param string $property Название свойства
     * @param mixed $value Значение свойства
     * @param string|null $prefix Префикс для подхранилища
     */
    public static function set(string $property, $value, ?string $prefix = null): void
    {
        self::ensureDriverInitialized();
        self::$driver->set($property, $value, self::$defaultTtl, $prefix);
    }

    /**
     * Получает значение свойства.
     *
     * @param string $property Название свойства
     * @param string|null $prefix Префикс для подхранилища
     * @return mixed|null
     */
    public static function get(string $property, ?string $prefix = null)
    {
        self::ensureDriverInitialized();
        return self::$driver->get($property, $prefix);
    }

    /**
     * Удаляет запись из хранилища.
     *
     * @param string $property Название свойства
     * @param string|null $prefix Префикс для подхранилища
     */
    public static function remove(string $property, ?string $prefix = null): void
    {
        self::ensureDriverInitialized();
        self::$driver->remove($property, $prefix);
    }

    /**
     * Обновляет время жизни записи.
     *
     * @param string $property Название свойства
     * @param string|null $prefix Префикс для подхранилища
     */
    public static function update(string $property, ?string $prefix = null): void
    {
        self::ensureDriverInitialized();
        self::$driver->updateTtl($property, self::$defaultTtl, $prefix);
    }

    /**
     * Удаляет истёкшие записи из хранилища.
     */
    public static function handleDeletion(): void
    {
        self::ensureDriverInitialized();
        self::$driver->handleDeletion();
    }

    /**
     * Убеждается, что драйвер инициализирован.
     *
     * @throws \RuntimeException Если драйвер не инициализирован
     */
    private static function ensureDriverInitialized(): void
    {
        if (!isset(self::$driver)) {
            self::init();
        }
    }
}
<?php
namespace Meract\Core;

/**
 * Статический фасад для работы с контейнером внедрения зависимостей
 * 
 * @method static void bind(string $abstract, string|callable $concrete) Привязывает абстракцию к реализации
 * @method static void singleton(string $abstract, string|callable|null $concrete = null) Регистрирует синглтон
 * @method static mixed make(string $abstract) Создает или возвращает экземпляр
 */
class SDR
{
    /**
     * @var Injector Контейнер зависимостей
     */
    private static Injector $injector;
    private static bool $InjectorInitialyzed = false;
    /**
     * Устанавливает контейнер для фасада
     *
     * @param Injector $injector
     * @return void
     */
    public static function setInjector(Injector $injector): void
    {
        self::$injector = $injector;
        self::$InjectorInitialyzed = true;
    }
    /**
     * Проверка на то инициализирован ли SDR
     * @return bool
     */
    public static function isInitialized(): bool {
        return self::$InjectorInitialyzed;
    }


    /**
     * Устанавливает произвольное значение в контейнер
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function setValue(string $key, mixed $value): void
    {
        if (!isset(self::$injector)) {
            throw new \RuntimeException("Injector not initialized. Call SDR::setInjector() first.");
        }
        self::$injector->set($key, $value);
    }

    /**
     * Магический вызов методов контейнера
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public static function __callStatic(string $method, array $args): mixed
    {
        if (!isset(self::$injector)) {
            throw new \RuntimeException("Injector not initialized. Call SDR::setInjector() first.");
        }
        return self::$injector->$method(...$args);
    }
}
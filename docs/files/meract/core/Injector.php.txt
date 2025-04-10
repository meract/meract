<?php
namespace Meract\Core;

use ReflectionClass;
use ReflectionException;

/**
 * Контейнер внедрения зависимостей
 * 
 * Автоматически разрешает зависимости классов, управляет синглтонами
 * и хранит произвольные значения.
 */
class Injector
{
    /**
     * @var array<string, object> Кеш экземпляров-синглтонов
     */
    protected array $singletons = [];

    /**
     * @var array<string, string|callable> Привязки интерфейсов к реализациям
     */
    protected array $bindings = [];

    /**
     * @var array<string, mixed> Хранилище произвольных значений
     */
    protected array $values = [];

    /**
     * Привязывает абстракцию к реализации
     *
     * @param string $abstract Абстрактный класс или интерфейс
     * @param string|callable $concrete Конкретный класс или фабричная функция
     * @return void
     *
     * @example
     * $injector->bind(LoggerInterface::class, FileLogger::class);
     */
    public function bind(string $abstract, string|callable $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    /**
     * Регистрирует синглтон
     *
     * @param string $abstract Абстрактный класс или интерфейс
     * @param string|callable|null $concrete Конкретный класс или фабрика
     * @return void
     *
     * @example
     * $injector->singleton(Database::class);
     */
    public function singleton(string $abstract, string|callable|null $concrete = null): void
    {
        $this->bind($abstract, $concrete ?? $abstract);
        $this->singletons[$abstract] = null;
    }

    /**
     * Устанавливает произвольное значение
     *
     * @param string $key Уникальный ключ
     * @param mixed $value Значение
     * @return void
     *
     * @example
     * $injector->set('config.path', '/app/config');
     */
    public function set(string $key, mixed $value): void
    {
        $this->values[$key] = $value;
    }

    /**
     * Создает или возвращает экземпляр класса
     *
     * @template T
     * @param class-string<T> $abstract Имя класса/интерфейса
     * @return T
     * @throws \RuntimeException Если разрешение зависимости невозможно
     */
    public function make(string $abstract): mixed
    {
        // Возвращаем значение, если оно было установлено
        if (array_key_exists($abstract, $this->values)) {
            return $this->values[$abstract];
        }

        // Возвращаем синглтон, если он существует
        if (isset($this->singletons[$abstract]) && $this->singletons[$abstract] !== null) {
            return $this->singletons[$abstract];
        }

        // Разрешаем привязку
        $concrete = $this->bindings[$abstract] ?? $abstract;

        // Создаем экземпляр
        $instance = is_callable($concrete) 
            ? $concrete($this) 
            : $this->build($concrete);

        // Сохраняем как синглтон, если требуется
        if (array_key_exists($abstract, $this->singletons)) {
            $this->singletons[$abstract] = $instance;
        }

        return $instance;
    }

    /**
     * Автоматически строит объект с зависимостями
     *
     * @param class-string $class Имя класса
     * @return object
     * @throws \RuntimeException Если класс не может быть создан
     */
    protected function build(string $class): object
    {
        try {
            $reflector = new ReflectionClass($class);

            if (!$reflector->isInstantiable()) {
                throw new \RuntimeException("Class {$class} is not instantiable");
            }

            $constructor = $reflector->getConstructor();
            if ($constructor === null) {
                return new $class();
            }

            $dependencies = array_map(
                fn($param) => $this->resolveParameter($param),
                $constructor->getParameters()
            );

            return $reflector->newInstanceArgs($dependencies);

        } catch (ReflectionException $e) {
            throw new \RuntimeException("DI build failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Разрешает параметр конструктора
     *
     * @param \ReflectionParameter $parameter
     * @return mixed
     * @throws \RuntimeException Для непримитивных типов без значения по умолчанию
     */
    protected function resolveParameter(\ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if (!$type || $type->isBuiltin()) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }
            throw new \RuntimeException(
                "Cannot resolve parameter \${$parameter->getName()} in ".
                $parameter->getDeclaringClass()?->getName()
            );
        }

        return $this->make($type->getName());
    }
}
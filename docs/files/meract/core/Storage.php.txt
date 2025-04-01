<?php
namespace Meract\Core;
class Storage
{
    private static $storage = [];
    private static $defaultTtl = 3600; // Время жизни по умолчанию (в секундах)

    /**
     * Устанавливает время жизни записей в хранилище.
     *
     * @param int $seconds
     */
    public static function setTime(int $seconds): void
    {
        self::$defaultTtl = $seconds;
    }

    /**
     * Устанавливает значение для свойства в основном хранилище или в подхранилище, если указан префикс.
     *
     * @param string $property Название свойства
     * @param mixed $value Значение свойства
     * @param string|null $prefix Префикс
     * @return void Ничего не возвращает
     */
    public static function set(string $property, $value, string|null $prefix = null): void
    {
        if ($prefix !== null) {
            self::$storage[$prefix][$property] = [
                'value' => $value,
                'expires' => time() + self::$defaultTtl
            ];
        } else {
            self::$storage[$property] = [
                'value' => $value,
                'expires' => time() + self::$defaultTtl
            ];
        }
    }

    /**
     * Получает значение свойства из основного хранилища или из подхранилища, если указан префикс.
     *
     * @param string $property
     * @param string|null $prefix
     * @return mixed|null
     */
    public static function get(string $property, string|null $prefix = null)
    {
        if ($prefix !== null) {
            if (isset(self::$storage[$prefix][$property])) {
                $data = self::$storage[$prefix][$property];
                if ($data['expires'] > time()) {
                    return $data['value'];
                }
                self::remove($property, $prefix); // Удаляем истёкшую запись
            }
        } else {
            if (isset(self::$storage[$property]) && self::$storage[$property]['expires'] > time()) {
                return self::$storage[$property]['value'];
            }
            self::remove($property); // Удаляем истёкшую запись
        }
        return null;
    }

    /**
     * Удаляет запись из основного хранилища или из подхранилища, если указан префикс.
     *
     * @param string $property
     * @param string|null $prefix
     */
    public static function remove(string $property, string|null $prefix = null): void
    {
        if ($prefix !== null) {
            unset(self::$storage[$prefix][$property]);
            if (empty(self::$storage[$prefix])) {
                unset(self::$storage[$prefix]);
            }
        } else {
            unset(self::$storage[$property]);
        }
    }

    /**
     * Обновляет время жизни записи в основном хранилище или в подхранилище, если указан префикс.
     *
     * @param string $property
     * @param string|null $prefix
     */
    public static function update(string $property, string|null $prefix = null): void
    {
        if ($prefix !== null) {
            if (isset(self::$storage[$prefix][$property])) {
                self::$storage[$prefix][$property]['expires'] = time() + self::$defaultTtl;
            }
        } else {
            if (isset(self::$storage[$property])) {
                self::$storage[$property]['expires'] = time() + self::$defaultTtl;
            }
        }
    }

    /**
     * Удаляет истёкшие записи из хранилища.
     */
    public static function handleDeletion(): void
    {
        foreach (self::$storage as $key => $data) {
            if (is_array($data)) {
                foreach ($data as $subKey => $subData) {
                    if (isset($subData['expires']) && $subData['expires'] < time()) {
                        unset(self::$storage[$key][$subKey]);
                    }
                }
                if (empty(self::$storage[$key])) {
                    unset(self::$storage[$key]);
                }
            } elseif (isset($data['expires']) && $data['expires'] < time()) {
                unset(self::$storage[$key]);
            }
        }
    }
}

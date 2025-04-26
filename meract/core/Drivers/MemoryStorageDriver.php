<?php
namespace Meract\Core\Drivers;
use Meract\Core\StorageDriver;

/**
 * Драйвер хранилища, работающий в памяти.
 */
class MemoryStorageDriver extends StorageDriver
{
    private array $storage = [];
    
    /**
     * {@inheritdoc}
     */
    public function set(string $property, $value, int $ttl, ?string $prefix = null): void
    {
        $data = ['value' => $value];
        
        // Устанавливаем expires только если TTL не равен 0
        if ($ttl !== 0) {
            $data['expires'] = time() + $ttl;
        }
        
        if ($prefix !== null) {
            $this->storage[$prefix][$property] = $data;
        } else {
            $this->storage[$property] = $data;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $property, ?string $prefix = null)
    {
        if ($prefix !== null) {
            if (isset($this->storage[$prefix][$property])) {
                $data = $this->storage[$prefix][$property];
                // Проверяем срок действия только если он установлен
                if (!isset($data['expires']) || $data['expires'] > time()) {
                    return $data['value'];
                }
                $this->remove($property, $prefix);
            }
        } else {
            if (isset($this->storage[$property])) {
                $data = $this->storage[$property];
                // Проверяем срок действия только если он установлен
                if (!isset($data['expires']) || $data['expires'] > time()) {
                    return $data['value'];
                }
                $this->remove($property);
            }
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $property, ?string $prefix = null): void
    {
        if ($prefix !== null) {
            unset($this->storage[$prefix][$property]);
            if (empty($this->storage[$prefix])) {
                unset($this->storage[$prefix]);
            }
        } else {
            unset($this->storage[$property]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateTtl(string $property, int $ttl, ?string $prefix = null): void
    {
        if ($prefix !== null) {
            if (isset($this->storage[$prefix][$property])) {
                if ($ttl === 0) {
                    unset($this->storage[$prefix][$property]['expires']);
                } else {
                    $this->storage[$prefix][$property]['expires'] = time() + $ttl;
                }
            }
        } else {
            if (isset($this->storage[$property])) {
                if ($ttl === 0) {
                    unset($this->storage[$property]['expires']);
                } else {
                    $this->storage[$property]['expires'] = time() + $ttl;
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function handleDeletion(): void
    {
        foreach ($this->storage as $key => $data) {
            if (is_array($data)) {
                foreach ($data as $subKey => $subData) {
                    // Удаляем только записи с истекшим сроком (те, у которых expires установлен)
                    if (isset($subData['expires']) && $subData['expires'] < time()) {
                        unset($this->storage[$key][$subKey]);
                    }
                }
                if (empty($this->storage[$key])) {
                    unset($this->storage[$key]);
                }
            } elseif (isset($data['expires']) && $data['expires'] < time()) {
                unset($this->storage[$key]);
            }
        }
    }
}

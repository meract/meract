<?php
namespace Meract\Drivers;
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
        $expires = time() + $ttl;
        
        if ($prefix !== null) {
            $this->storage[$prefix][$property] = [
                'value' => $value,
                'expires' => $expires
            ];
        } else {
            $this->storage[$property] = [
                'value' => $value,
                'expires' => $expires
            ];
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
                if ($data['expires'] > time()) {
                    return $data['value'];
                }
                $this->remove($property, $prefix);
            }
        } else {
            if (isset($this->storage[$property])) {
                $data = $this->storage[$property];
                if ($data['expires'] > time()) {
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
        $expires = time() + $ttl;
        
        if ($prefix !== null) {
            if (isset($this->storage[$prefix][$property])) {
                $this->storage[$prefix][$property]['expires'] = $expires;
            }
        } else {
            if (isset($this->storage[$property])) {
                $this->storage[$property]['expires'] = $expires;
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
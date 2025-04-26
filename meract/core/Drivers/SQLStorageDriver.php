<?php
namespace Meract\Core\Drivers;

use Meract\Core\StorageDriver;
use Meract\Core\Qryli;
use Exception;

/**
 * Драйвер хранилища, работающий с SQL базой данных
 */
class SqlStorageDriver extends StorageDriver
{
    private string $table = 'meract_storage';
    
    /**
     * {@inheritdoc}
     */
    public function set(string $property, $value, int $ttl, ?string $prefix = null): void
    {
        $key = $this->buildKey($property, $prefix);
        $serialized = serialize($value);
        
        // Устанавливаем expires только если TTL не равен 0
        $data = [
            'key' => $key,
            'value' => $serialized
        ];
        
        if ($ttl !== 0) {
            $data['expires'] = time() + $ttl;
        }
        
        try {
            // Проверяем существование записи
            $existing = Qryli::select('*')
                ->from($this->table)
                ->where('key = ?', [$key])
                ->limit(1)
                ->run();
            
            if (!empty($existing)) {
                // Обновляем существующую запись
                Qryli::update($this->table, $data)
                    ->where('key = ?', [$key])
                    ->run();
            } else {
                // Вставляем новую запись
                Qryli::insert($this->table, $data)->run();
            }
        } catch (Exception $e) {
            throw new Exception("Storage set failed: " . $e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $property, ?string $prefix = null)
    {
        $key = $this->buildKey($property, $prefix);
        
        try {
            $result = Qryli::select('value, expires')
                ->from($this->table)
                ->where('key = ?', [$key])
                ->limit(1)
                ->run();
            
            if (!empty($result)) {
                $data = $result[0];
                // Если expires не установлен (null) или срок не истек
                if (!isset($data['expires']) || $data['expires'] > time()) {
                    return unserialize($data['value']);
                }
                $this->remove($property, $prefix);
            }
            return null;
        } catch (Exception $e) {
            throw new Exception("Storage get failed: " . $e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $property, ?string $prefix = null): void
    {
        $key = $this->buildKey($property, $prefix);
        
        try {
            Qryli::delete($this->table)
                ->where('key = ?', [$key])
                ->run();
        } catch (Exception $e) {
            throw new Exception("Storage remove failed: " . $e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateTtl(string $property, int $ttl, ?string $prefix = null): void
    {
        $key = $this->buildKey($property, $prefix);
        $data = [];
        
        if ($ttl === 0) {
            $data['expires'] = null; // Устанавливаем NULL для вечного хранения
        } else {
            $data['expires'] = time() + $ttl;
        }
        
        try {
            Qryli::update($this->table, $data)
                ->where('key = ?', [$key])
                ->run();
        } catch (Exception $e) {
            throw new Exception("Storage updateTtl failed: " . $e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function handleDeletion(): void
    {
        try {
            // Удаляем только записи с установленным и истекшим сроком
            Qryli::delete($this->table)
                ->where('expires IS NOT NULL AND expires < ?', [time()])
                ->run();
        } catch (Exception $e) {
            throw new Exception("Storage cleanup failed: " . $e->getMessage());
        }
    }
    
    /**
     * Строит ключ для хранения с учетом префикса
     */
    private function buildKey(string $property, ?string $prefix): string
    {
        return $prefix !== null ? "{$prefix}.{$property}" : $property;
    }
    
    /**
     * Устанавливает имя таблицы для хранения
     */
    public function setTable(string $table): void
    {
        $this->table = $table;
    }
}

<?php
namespace fl\cache;

/**
 * sae平台缓存支持且只支持memcached
 */
final class sae extends cache implements Icache
{

    /**
     * 使用原生memcache时资源
     *
     * @var resource
     */
    private $memcache = null;

    /**
     * Flag: indicates data is compressed
     */
    const MEMCACHE_COMPRESSED = 2;

    const MEMCACHE_SERIALIZED = 1;

    public function __construct($cfg)
    {
        if (function_exists('memcache_init')) {
            $this->memcache = call_user_func_array('memcache_init', array());
            if (! is_resource($this->memcache)) {
                throw new \Exception("Memcached can't connect.");
            }
        } else {
            throw new \Exception("not support Memcached.");
        }
    }

    public function __destruct()
    {
        if (is_resource($this->memcache)) {
            memcache_close($this->memcache);
        }
    }

    public function set($name, $data, $ttl = 86400)
    {
        if (! is_resource($this->memcache)) {
            throw new \Exception("Memcached can't connect.");
        }
        return memcache_set($this->memcache, $name, $data, 0, $ttl);
    }

    public function get($name)
    {
        if (! is_resource($this->memcache)) {
            throw new \Exception("Memcached can't connect.");
        }
        $data = memcache_get($this->memcache, $name);
        if ($data) {
            return $data;
        } else {
            throw new \Exception("cache not exists.");
        }
    }

    public function delete($name)
    {
        if (! is_resource($this->memcache)) {
            throw new \Exception("Memcached can't connect.");
        }
        return memcache_delete($this->memcache, $name);
    }

    public function flush_all()
    {
        if (! is_resource($this->memcache)) {
            throw new \Exception("Memcached can't connect.");
        }
        return memcache_flush($this->memcache);
    }
}
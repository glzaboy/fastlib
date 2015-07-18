<?php
namespace fl\cache;

/**
 *
 * @author guliuzhong
 *        
 */
class apc extends cache implements icache
{

    public function set($name, $data, $ttl = 86400)
    {
        return apc_store($name, $data, $ttl);
    }

    public function get($name)
    {
        $data = apc_fetch($name, $success);
        if (! $success) {
            throw new \Exception("cache not exists.");
        }
        return $data;
    }

    public function delete($name)
    {
        return apc_delete($name);
    }

    public function flush_all()
    {
        if (extension_loaded('apcu')) {
            return apc_clear_cache();
        } else {
            return apc_clear_cache('user');
        }
    }
}
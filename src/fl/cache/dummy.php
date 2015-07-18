<?php
namespace fl\cache;

/**
 *
 * @author guliuzhong
 *        
 */
class dummy extends cache implements icache
{

    public function __construct($cfg)
    {}

    public function __destruct()
    {}

    public function set($name, $data, $ttl = 86400)
    {
        return true;
    }

    public function get($name)
    {
        throw new \Exception('cache not exists.');
    }

    public function delete($name)
    {
        return true;
    }

    public function flush_all()
    {
        return true;
    }
}
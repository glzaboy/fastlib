<?php
namespace fl\cache;

/**
 * memcache 缓存类
 *
 * @author guliuzhong
 *        
 */
class memcache extends cache implements Icache
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

    function __construct($cfg)
    {
        $this->memcache = @fsockopen($cfg['server'], $cfg['port'], $errno, $errstr, 5);
        if (! is_resource($this->memcache)) {
            throw new \Exception("Memcached can't connect.");
        }
    }

    function __destruct()
    {
        if (is_resource($this->memcache)) {
            fwrite($this->memcache, "quit");
            fclose($this->memcache);
        }
    }

    public function set($name, $data, $ttl = 86400)
    {
        if (! is_resource($this->memcache)) {
            throw new \Exception("Memcached can't connect.");
        }
        $flag = 0;
        if (! is_scalar($data)) {
            $data = serialize($data);
            $flag |= self::MEMCACHE_SERIALIZED;
        }
        if (strlen($data) >= 500) {
            $data = gzcompress($data, 9);
            $flag |= self::MEMCACHE_COMPRESSED;
        }
        fwrite($this->memcache, "set " . $name . ' ' . $flag . ' ' . $ttl . ' ' . strlen($data) . "\r\n" . $data . "\r\n");
        $line = trim(fgets($this->memcache));
        if ($line == "STORED") {
            return true;
        } else {
            return false;
        }
    }

    public function get($name)
    {
        if (! is_resource($this->memcache)) {
            throw new \Exception("Memcached can't connect.");
        }
        fwrite($this->memcache, "get " . $name . "\r\n");
        $info = fgets($this->memcache);
        if (! preg_match('/^VALUE (\S+) (\d+) (\d+)\r\n$/', $info, $match)) {
            throw new \Exception("cache not exists.");
        } else {
            list ($rkey, $flags, $len) = array(
                $match[1],
                $match[2],
                $match[3]
            );
            $len += 2;
            $offset = 0;
            $data = '';
            for ($offset = 0; $offset < $len;) {
                $data = fread($this->memcache, $len - $offset);
                $offset += strlen($data);
            }
            $line = fgets($this->memcache);
            if ($line === "END\r\n") {
                if ($flags & self::MEMCACHE_COMPRESSED) {
                    $data = gzuncompress($data);
                }
                if ($flags & self::MEMCACHE_SERIALIZED) {
                    $data = unserialize($data);
                }
                return $data;
            } else {
                throw new \Exception("cache server data error.");
            }
        }
    }

    public function delete($name)
    {
        if (! is_resource($this->memcache)) {
            throw new \Exception("Memcached can't connect.");
        }
        fwrite($this->memcache, "delete " . $name . ' 0 ' . "\r\n");
        $line = trim(fgets($this->memcache));
        if ($line == "DELETED") {
            return true;
        } else {
            return false;
        }
    }

    public function flush_all()
    {
        if (! is_resource($this->memcache)) {
            throw new \Exception("Memcached can't connect.");
        }
        fwrite($this->memcache, "flush_all\r\n");
        $line = trim(fgets($this->memcache));
        if ($line == "OK") {
            return true;
        } else {
            return false;
        }
    }
}
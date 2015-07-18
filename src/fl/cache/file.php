<?php
namespace fl\cache;

class file extends cache implements icache
{

    /**
     * 文件缓存的根目录
     *
     * @var string
     */
    private $cacheroot = '';

    public function __construct($cfg)
    {
        $this->cacheroot = FL_TMP . 'cache/';
        if (! is_dir($this->cacheroot)) {
            mkdir($this->cacheroot);
        }
    }

    public function __destruct()
    {}

    public function set($name, $data, $ttl = 86400)
    {
        $cachedata = array(
            'data' => $data,
            'ttl' => $ttl,
            'mtime' => time()
        );
        $name = str_replace(DIRECTORY_SEPARATOR, '_', $name);
        $file = $this->cacheroot . $name . '.php';
        $http403 = '<?php' . PHP_EOL . ' die("HTTP 403");?>';
        return file_put_contents($file, $http403 . serialize($cachedata));
    }

    public function get($name)
    {
        clearstatcache();
        $name = str_replace(DIRECTORY_SEPARATOR, '_', $name);
        $file = $this->cacheroot . $name . '.php';
        if (file_exists($file)) {
            $http403 = '<?php' . PHP_EOL . ' die("HTTP 403");?>';
            $cachedata = unserialize(substr(file_get_contents($file), strlen($http403)));
            if ($cachedata['ttl'] == 0) {
                return $cachedata['data'];
            } elseif ($cachedata['mtime'] + $cachedata['ttl'] >= time()) {
                return $cachedata['data'];
            } else {
                unlink($file);
                throw new \Exception('cache not exists.');
            }
        } else {
            throw new \Exception('cache not exists.');
        }
    }

    public function delete($name)
    {
        $file = $this->cacheroot . $name . '.php';
        if (file_exists($file)) {
            return unlink($file);
        } else {
            return true;
        }
    }

    public function flush_all()
    {
        foreach (glob($this->cacheroot . '/*') as $file) {
            unlink($file);
        }
        rmdir($this->cacheroot);
        return true;
    }
}
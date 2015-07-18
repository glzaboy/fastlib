<?php
namespace fl\cache;

use fl\base\object;

/**
 * 缓存接口
 *
 * @author guliuzhong
 *        
 */
abstract class cache extends object implements Icache
{

    /**
     *
     * @param string $cacheType
     *            缓存类型
     * @throws cacheException
     * @return Ambigous <>|Ambigous <\fl\cache\icache>
     */
    public static function instance($cacheType = null)
    {
        static $cacheMap = array();
        if (\fl\base\core::isSae()) {
            $cacheType = 'sae';
        } else {
            $s_cfg = \fl\cfg\cfg::instance('cache', 'ini');
            $defaultType = $s_cfg->get('main', 'default');
            $cacheType = $cacheType ? $cacheType : $defaultType;
        }
        if (isset($cacheMap[$cacheType]) && is_object($cacheMap[$cacheType])) {
            return $cacheMap[$cacheType];
        }
        switch (strtolower($cacheType)) {
            case 'memcache':
            case 'memcached':
                $cacheMap[$cacheType] = new \fl\cache\memcache($s_cfg->get('memcache'));
                break;
            case 'apc':
                $cacheMap[$cacheType] = new \fl\cache\apc(array());
                break;
            case 'dummy':
                $cacheMap[$cacheType] = new \fl\cache\dummy(array());
                break;
            case 'file':
                $cacheMap[$cacheType] = new \fl\cache\file(array());
                break;
            case 'sae':
                $cacheMap[$cacheType] = new \fl\cache\sae(array());
                break;
            default:
                throw new \Exception("not support cache Type.", - 100);
        }
        return $cacheMap[$cacheType];
    }
}
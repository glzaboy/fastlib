<?php
namespace fl\base;

/**
 * 基础核心类
 *
 * @author guliuzhong
 *        
 */
class core extends object
{

    /**
     * 取得临时目录
     *
     * @return string
     */
    static public function gettmpdir()
    {
        if (defined('FL_TMP')) {
            return FL_TMP . 'tmp/';
        } else {
            return null;
        }
    }

    /**
     * 是否是sae平台
     *
     * @return boolean
     */
    static public function isSae()
    {
        return defined('SAE_ACCESSKEY');
    }
}

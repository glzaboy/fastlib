<?php
namespace fl\base;

/**
 * 时间停表
 *
 * @author glzaboy<glzaboy@163.com>
 * @package fastlib
 * @subpackage classes
 * @copyright 2005-2012 (c) glzaboy
 */
class timer extends object
{

    /**
     * 计时开始时间
     *
     * @var float
     */
    private $startTime = 0.0;

    /**
     * 开始一个计时
     */
    function start()
    {
        $this->startTime = microtime(true);
    }

    /**
     * 返回上一次start到现在的时间间隔
     *
     * @param int $id
     *            ID
     * @return float 时间间隔
     */
    public function spent($id = 0)
    {
        if ($id) {
            $time = microtime(true) - $this->startTime;
            return $id . '|' . $time . '<br />';
        } else {
            return microtime(true) - $this->startTime;
        }
    }
}
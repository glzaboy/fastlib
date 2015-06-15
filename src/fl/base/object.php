<?php
namespace fl\base;

/**
 * 基础类结构
 *
 * @author guliuzhong
 *        
 */
class object
{

    /**
     * 错误信息
     *
     * @var unknown
     */
    private $_error = '';

    public function __call($name, $params)
    {
        $showparams = array();
        foreach ($params as $param) {
            $showparams[] = is_scalar($param) ? $param : var_export($param, true);
        }
        throw new \Exception('Calling unknown method: ' . get_class($this) . "::$name(" . implode(',', $showparams) . ")");
    }

    static public function __callstatic($name, $params)
    {
        $showparams = array();
        foreach ($params as $param) {
            $showparams[] = is_scalar($param) ? $param : var_export($param, true);
        }
        throw new \Exception('Calling unknown static method: ' . get_called_class() . "::$name(" . implode(',', $showparams) . " )");
    }

    public function hasMethod($name)
    {
        return method_exists($this, $name);
    }

    /**
     * 返回调用者class 名称
     *
     * @return string
     */
    public function getclassname()
    {
        return get_called_class();
    }

    /**
     * 设置错误信息
     *
     * @param string $error            
     * @return unknown|NULL
     */
    public function setError($error)
    {
        if (isset($this) && is_string($error)) {
            return $this->_error = $error;
        } else {
            return null;
        }
    }

    /**
     * 返回错误信息
     *
     * @return string|NULL
     */
    public function getError()
    {
        if (isset($this)) {
            return $this->_error;
        } else {
            return null;
        }
    }
}

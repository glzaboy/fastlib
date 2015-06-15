<?php
namespace fl\helpers;

use fl\base\object;

class hook extends object
{

    /**
     * HOOK
     *
     * @var array
     */
    static $_HOOK = array();

    /**
     * 是否允许hook运行
     *
     * @var bool
     */
    static $enableHook = true;

    /**
     * 是否已经运行HOOK
     *
     * @var bool
     */
    static $_inHook = false;

    static function runhook($tag, $params = array())
    {
        if (! self::$enableHook) {
            return false;
        }
        if (! isset(self::$_HOOK[$tag]) || ! is_array(self::$_HOOK[$tag])) {
            return false;
        }
        if (self::$_inHook) {
            return false;
        }
        self::$_inHook = true;
        foreach (self::$_HOOK[$tag] as $hook) {
            if (is_callable($hook)) {
                if (! is_array($params)) {
                    call_user_func_array($hook, array(
                        $params
                    ));
                } else {
                    call_user_func_array($hook, $params);
                }
            }
        }
        self::$_inHook = false;
    }

    static function register($tag, $name, $callback)
    {
        if (self::$_inHook) {
            return false;
        }
        if (! isset(self::$_HOOK[$tag])) {
            self::$_HOOK[$tag] = array();
        }
        if (is_callable($callback)) {
            self::$_HOOK[$tag][$name] = $callback;
        } else {
            return false;
        }
    }

    static function unregister($tag, $name)
    {
        if (! isset(self::$_HOOK[$tag][$name])) {
            return false;
        }
        unset(self::$_HOOK[$tag][$name]);
    }

    static function delhook($tag)
    {
        if (! isset(self::$_HOOK[$tag])) {
            return false;
        }
        unset(self::$_HOOK[$tag]);
    }

    /**
     * 开启hook运行
     */
    static function enableHook()
    {
        self::$enableHook = true;
    }

    /**
     * 禁止hook运行
     */
    static function disableHook()
    {
        self::$enableHook = false;
    }
}
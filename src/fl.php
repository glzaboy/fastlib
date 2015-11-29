<?php
date_default_timezone_set('UTC');
if (version_compare(PHP_VERSION, '5.3.0', '<')) {
    die('fl require php5.3.0 or later. current version is ' . PHP_VERSION);
}
/**
 * Fastlib目录路径
 */
define("FL_PATH", dirname(__FILE__));
if (! defined('FL_RUNDIR')) {
    /**
     * FL运行目录
     *
     * @var string
     */
    define('FL_RUNDIR', FL_PATH);
}
/**
 * 临时目录
 *
 * @var string
 */
define('FL_TMP', FL_RUNDIR . DIRECTORY_SEPARATOR . 'tmp');
/**
 * Fastlib 版本信息
 */
define("FL_VERSION", '0.0.1');
if (! fl\base\core::iscli()) {
    header('x-powered-by: fastlib ver ' . FL_VERSION);
    header('software: fastlib ver ' . FL_VERSION);
}
$cfg = \fl\cfg\cfg::getcfgobj('site', 'ini');
define('FL_DEBUG', $cfg->get('main', 'debug'));
if (FL_DEBUG) {
    error_reporting((E_ALL | E_STRICT) & ~ E_NOTICE);
} else {
    error_reporting(0);
}
if ($cfg->get('main', 'timezone')) {
    date_default_timezone_set($cfg->get('main', 'timezone'));
}
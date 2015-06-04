<?php
namespace fl\base;

/**
 * 简易MVC控制类
 *
 * 本模块会自动生成几个常量
 * FL_PAGE_DIR
 * FL_PAGE_MOD　使用的模块名
 * FL_PAGE_ACT　要操作的动作
 *
 * 实现一个简单的MVC控制结构
 *
 * @author guliuzhong
 */
class route extends object
{

    /**
     * 默认模块
     *
     * @var string
     */
    private $defmodule = null;

    /**
     * 默认动作
     *
     * @var string
     */
    private $defaction = null;

    /**
     * PATH 传入的参数
     *
     * @var string
     */
    private $pathcmd = "";

    /**
     * mvc 初始设置
     *
     * @param $defmodule string
     *            默认模块
     * @param $defaction string
     *            默认动作
     */
    function __construct($defmodule = "default", $defaction = 'home')
    {
        $this->defmodule = is_string($defmodule) ? $defmodule : 'default';
        $this->defaction = is_string($defaction) ? $defaction : 'home';
    }

    private function getrouteclass($params = null)
    {
        if (! FL_CLI) {
            if (isset($_SERVER["PATH_INFO"])) {
                $pathinfo = ! empty($_SERVER["PATH_INFO"]) ? $_SERVER["PATH_INFO"] : "";
            } elseif (isset($_SERVER['REDIRECT_URL'])) {
                $pathinfo = ! empty($_SERVER["REDIRECT_URL"]) ? $_SERVER["REDIRECT_URL"] : "";
            }
        } else {
            $requesturl = ! empty($_SERVER["argv"][1]) ? $_SERVER["argv"][1] : "";
            $path = (parse_url($requesturl));
            $pathinfo = $path['path'];
            if (isset($path['query'])) {
                parse_str($path['query'], $_GET);
                $_REQUEST = $_GET;
            }
        }
        $dir = ".";
        $m = $this->defmodule;
        $ac = $this->defaction;
        if (! empty($pathinfo)) {
            $splitFlag = preg_quote("-/_", "/");
            $urls = preg_split("/[$splitFlag]/", $pathinfo, - 1);
            if (is_dir($urls[1])) {
                $dir = $urls[1];
                $m = ! empty($urls[2]) ? $urls[2] : $this->defmodule;
                $ac = ! empty($urls[3]) ? $urls[3] : $this->defaction;
            } else {
                $dir = '.';
                $m = ! empty($urls[1]) ? $urls[1] : $this->defmodule;
                $ac = ! empty($urls[2]) ? $urls[2] : $this->defaction;
            }
            $m = preg_replace('/([a-zA-Z0-9]+)[^a-zA-Z0-9].+/', "$1", $m);
            $this->pathcmd = preg_replace('/^[a-zA-Z0-9]{0,}/', "", $ac);
            $ac = preg_replace('/^([a-zA-Z0-9]{0,})[^a-zA-Z0-9].+/', "$1", $ac);
            $ac = $ac ? $ac : $this->defaction;
        }
        if (! is_dir(FL_RUNDIR . DIRECTORY_SEPARATOR . $dir)) {
            throw new \Exception("Page not found. status 404", 404);
        }
        if ($dir != '.') {
//             array_unshift(\fl\load::$path, FL_RUNDIR . DIRECTORY_SEPARATOR . $dir);
        }
        $loader=include FL_RUNDIR.'/vendor/autoload.php';
        $routeload=realpath(FL_RUNDIR . DIRECTORY_SEPARATOR . $dir);
        $loader->setPsr4("page\\",$routeload.'/page');
        $class = "page\\{$m}";
        if (! class_exists($class)) {
            throw new \Exception("Page not found. status 404.1", 404.1);
        }
        /**
         * 使用的模块名
         */
        define('FL_PAGE_DIR', $dir);
        /**
         * 使用的模块名
         */
        define('FL_PAGE_MOD', $m);
        /**
         * 操作的动作
         */
        define('FL_PAGE_ACT', $ac);
        if ($params) {
            return new $class($params);
        } else {
            return new $class();
        }
    }

    public function runasthrift()
    {
        if (! class_exists('\Thrift\Transport\TPhpStream')) {
            return 'Thrift lib not install.';
        }
        try {
            $this->stream = new \Thrift\Transport\TPhpStream(\Thrift\Transport\TPhpStream::MODE_R | \Thrift\Transport\TPhpStream::MODE_W);
            $this->transport = new \Thrift\Transport\TBufferedTransport($this->stream);
            // $input = new \Thrift\Protocol\TJSONProtocol($this->transport);
            $input = new \Thrift\Protocol\TBinaryProtocol($this->transport);
            $class = $this->getrouteclass($input);
            $this->stream->open();
            $type = null;
            $name = "null";
            $seqid = 0;
            $input->readMessageBegin($name, $type, $seqid);
        } catch (\Exception $e) {}
        
        $classifs = class_implements($class);
        foreach ($classifs as $classif) {
            if (preg_match('/If$/', $classif)) {
                $inputclass = preg_replace('/If$/', '_' . $name . "_args", $classif);
                
                $args = new $inputclass();
                try {
                    $args->read($input);
                    $input->readMessageEnd();
                } catch (\Exception $e) {}
                $params = array();
                if ($args::$_TSPEC) {
                    foreach ($args::$_TSPEC as $spec) {
                        $params[] = $args->$spec['var'];
                    }
                }
                $result = call_user_func_array(array(
                    $class,
                    $name
                ), $params);
                $this->stream->open();
                $outputclass = preg_replace('/If$/', '_' . $name . "_result", $classif);
                $outputclass = new $outputclass();
                $outputclass->success = $result;
                $input->writeMessageBegin($name, \Thrift\Type\TMessageType::REPLY, $seqid);
                $outputclass->write($input);
                $input->writeMessageEnd();
                $this->transport->flush();
            }
        }
    }

    /**
     * mvc运行
     * 引导文件名一般会去除扩展名
     * http://www.baidu.com/s?wd=%E4%BD%A0%E5%A5%BD
     *
     * @return string
     */
    public function run()
    {
        try {
            $class = $this->getrouteclass();
            $ac = 'do' . FL_PAGE_ACT;
            if (method_exists($class, 'do' . FL_PAGE_ACT)) {
                return $class->$ac($this->pathcmd);
            } else {
                throw new \Exception("Page not found. status 404.2", 404.2);
            }
        } catch (\Exception $e) {
            header("HTTP/1.0 " . $e->getCode());
            return $e->getMessage();
        }
    }
}
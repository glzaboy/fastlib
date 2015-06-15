<?php
namespace fl\helpers;

use fl\base\object;

/**
 * Http客户端模拟组件
 *
 * 本类同时支持post,get,postfile,cookie的http
 * 协议发送和数据返回接收。
 * 本类使用http1.1进行发送。
 *
 * @author guliuzhong
 *        
 */
final class httpclient extends object
{

    /**
     * POST数据
     *
     * @var array
     */
    private $post = array();

    /**
     * curl对象
     *
     * @var curl
     */
    private $curl = null;

    /**
     * 是否已经中止
     *
     * @var bool
     */
    private $halt = false;

    /**
     * 使用COOKIE文件
     *
     * @var string
     */
    private $cookiefile = null;

    /**
     * 使用COOKIE文件
     *
     * @var string
     */
    private $maxredirect = 5;

    /**
     * 请求完删除cookie
     *
     * @var string
     */
    private $delcookie = false;

    /**
     * 超时时间
     *
     * @var int
     */
    private $timeout = 5;

    /**
     * 构造函数
     *
     * @param $cookiefile string
     *            cookie文件
     * @param $maxredirect int
     *            重定向次数
     * @param $timeout int
     *            超时时间
     */
    function __construct($cookiefile = null, $maxredirect = null, $timeout = 5)
    {
        if ($cookiefile !== null) {
            $this->cookiefile = FL_TMP . "{$cookiefile}.cookie";
            if (substr($this->cookiefile, - 11) == '.tmp.cookie') {
                $this->delcookie = true;
            }
        }
        if ($maxredirect) {
            $this->maxredirect = intval($maxredirect);
        }
        $this->timeout = $timeout;
        $this->initcurl();
    }

    function __destruct()
    {
        if (is_resource($this->curl)) {
            curl_close($this->curl);
        }
        if ($this->cookiefile && $this->delcookie && is_file($this->cookiefile)) {
            unlink($this->cookiefile);
        }
    }

    /**
     * 初始化curl
     */
    private function initcurl()
    {
        if (! is_resource($this->curl)) {
            if (extension_loaded('curl')) {
                $this->curl = curl_init();
                curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($this->curl, CURLOPT_MAXREDIRS, $this->maxredirect);
                curl_setopt($this->curl, CURLOPT_AUTOREFERER, true);
                curl_setopt($this->curl, CURLOPT_TIMEOUT, $this->timeout);
                curl_setopt($this->curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] . ' fl ' . FL_VERSION);
                if ($this->cookiefile) {
                    curl_setopt($this->curl, CURLOPT_COOKIEJAR, $this->cookiefile);
                    curl_setopt($this->curl, CURLOPT_COOKIEFILE, $this->cookiefile);
                }
                curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($this->curl, CURLOPT_FAILONERROR, false);
            } else {
                $this->halt = true;
            }
        }
    }

    /**
     * POST 数据
     * 由于curl限制目前不支持多维post数据
     *
     * @param $data array
     *            post数据
     */
    public function postData($data)
    {
        foreach ($data as $k => $v) {
            $this->post[$k] = $v;
        }
    }

    /**
     * 将普通上传数据转换成带mimetype的上传数据
     *
     * @param $files array
     *            上传的文件
     */
    private function addfiles($files)
    {
        if (is_string($files)) {
            if ($files{0} == '@') {
                $file = substr($files, 1);
                if (! is_file($file)) {
                    return false;
                }
            }
            return '@' . realpath($file) . ';type=' . \fl\helpers\mime::getMIMEType($file);
        } elseif (is_array($files)) {
            $return = array();
            foreach ($files as $key => $v) {
                $return[$key] = $this->addfiles($v);
            }
            return $return;
        } else {
            return false;
        }
    }

    /**
     * POST 数据
     * 由于curl限制目前不支持多维post文件
     *
     * @param $files array
     *            上传的文件
     */
    public function postFiles($files)
    {
        foreach ($files as $k => $v) {
            $this->post[$k] = $this->addfiles($v);
        }
    }

    /**
     * 返回HTTP数据
     *
     * @param $url string
     *            URI地址
     * @param $cookies array
     *            临时cookie不会保存
     * @param $getdata array
     *            get数据建议远直接写在url中因为这里不支持多维
     * @return string HTML代码
     */
    public function getData($url, $cookies = array(), $getdata = array())
    {
        $this->initcurl();
        if ($this->halt) {
            $this->setError('curl init error.');
            return false;
        }
        if (count($this->post)) {
            curl_setopt($this->curl, CURLOPT_POST, true);
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->post);
        }
        if (is_array($cookies) && count($cookies) > 0) {
            curl_setopt($this->curl, CURLOPT_COOKIE, http_build_query($cookies, null, ';'));
        }
        if (is_array($getdata) && count($getdata)) {
            $getBodyString = "";
            foreach ($getdata as $k => $v) {
                $getBodyString .= "$k=" . urlencode($v) . "&";
            }
            unset($k, $v);
            if (strpos($url, '?') === false) {
                $url .= '?' . substr($getBodyString, 0, - 1);
            } else {
                $url .= '&' . substr($getBodyString, 0, - 1);
            }
        }
        curl_setopt($this->curl, CURLOPT_URL, $url);
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            if (file_exists(FL_PATH . 'etc/cert/cacert.pem')) {
                curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($this->curl, CURLOPT_CAINFO, realpath(FL_PATH . 'etc/cert/cacert.pem'));
                curl_setopt($this->curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_DEFAULT);
            } else {
                curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false);
            }
        }
        $content = curl_exec($this->curl);
        $httpcode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        if (curl_errno($this->curl)) {
            $this->setError(curl_error($this->curl));
            return false;
        }
        if ($httpcode == 200) {
            return $content;
        }
        $this->setError($content, $httpcode);
        return false;
    }

    /**
     * 设置代理
     *
     *
     * @param $host string
     *            代理主机地址
     * @param $port int
     *            代理主机端口
     * @param $type int
     *            代理类型 CURLPROXY_SOCKS5,CURLPROXY_SOCKS4,CURLPROXY_HTTP
     * @param $authtype int
     *            认证类型 CURLAUTH_BASIC，CURLAUTH_NTLM
     * @param $user string
     *            代理主机用户名
     * @param $pwd string
     *            代理主机密码
     */
    public function setproxy($host, $port, $type = CURLPROXY_SOCKS5, $authtype = CURLAUTH_BASIC, $user = null, $pwd = null)
    {
        curl_setopt($this->curl, CURLOPT_PROXYTYPE, $type);
        curl_setopt($this->curl, CURLOPT_PROXY, $host);
        curl_setopt($this->curl, CURLOPT_PROXYPORT, $port);
        if ($user) {
            $userpwd = $user;
            if ($pwd) {
                $userpwd .= ':' . $pwd;
            }
        }
        curl_setopt($this->curl, CURLOPT_PROXYUSERPWD, $userpwd);
        if ($type == CURLPROXY_HTTP) {
            curl_setopt($this->curl, CURLOPT_PROXYAUTH, $authtype);
        }
    }

    /**
     * 清除post数据
     */
    public function cleanpost()
    {
        $this->post = array();
    }
}
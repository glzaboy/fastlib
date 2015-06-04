<?php
namespace fl\ip;

use fl\base\object;

/**
 *
 * @author Administrator
 *        
 */
class ip extends object implements Iip
{

    /**
     * ip查询服务器
     *
     * @var string
     */
    private $ipserver = "http://ip.taobao.com/service/getIpInfo.php";

    function __construct()
    {}

    /**
     * 判断是否是IP格式
     *
     * @param $ipstring String
     *            查询IP字 串
     * @return bool
     */
    static public function isIp($ipstring)
    {
        if (filter_var($ipstring, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return true;
        }
        return false;
    }

    public function ipinfo($ip)
    {
        $ipinfo = new ipinfo();
        try {
            if (preg_match('/^[\w\.]{1,}.[\w]{0,4}$/i', $ip)) {
                $ip = gethostbyname($ip);
            }
            if (! $this->isIp($ip)) {
                throw new \Exception("{$ip} is not an ip", - 1);
            }
            $httpclient = new \fl\helpers\httpclient();
            $jsondata = $httpclient->getData($this->ipserver, null, array(
                'ip' => $ip
            ));
            $ipinfo->ip = $ip;
            if (! $jsondata) {
                throw new \Exception("network error" . $httpclient->getError(), - 1);
            }
            $data = json_decode($jsondata, true);
            if ($data['code'] == 0) {
                $ipinfo->isp = $data['data']['isp'];
                $ipinfo->country = $data['data']['country_id'];
                $ipinfo->area = $data['data']['country'] . ' ' . $data['data']['region'] . ' ' . $data['data']['city'] . ' ' . $data['data']['county'];
            } else {
                throw new \Exception("network error, taobao ipsearch error.", - 1);
            }
        } catch (\Exception $e) {
            $ipinfo->country = "UNKNOWN";
            $ipinfo->area = "UNKNOWN";
            $ipinfo->ip = "UNKNOWN";
            $ipinfo->isp = "UNKNOWN";
        }
        return $ipinfo;
    }

    /**
     */
    function __destruct()
    {}
}
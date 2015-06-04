<?php
namespace fl\ip;

/**
 * IP查询
 *
 * @author glzaboy
 *        
 */
interface Iip
{

    /**
     * 根据IP地址取得地理位置
     *
     * @param $ip String
     *            查询条件
     * @param $returnpart string
     *            返回部分，如果为空则返回全部数组
     * @return ipinfo
     */
    public function ipinfo($ip);
}

/**
 * ip查询结果
 *
 * @author guliuzhong
 */
class ipinfo
{

    public $isp;

    public $country;

    public $area;

    public $ip;
}
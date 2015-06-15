<?php
namespace fl\cfg;

/**
 * 站点配置读写
 *
 * @author guliuzhong
 */
interface Icfg
{

    /**
     * 修改
     *
     * @param string $nameSpace            
     * @param string $key            
     * @param mixed $value            
     * @return boolean
     */
    public function change($nameSpace, $key, $value);

    /**
     * 读取配置
     *
     * @param string $nameSpace            
     * @param string $key            
     * @return string array boolean
     */
    public function get($nameSpace, $key = null);

    /**
     * 解析内容
     */
    public function parse();

    /**
     * 保存内容
     */
    public function save();

    /**
     * 导出数据
     *
     * @return string
     */
    public function exportdata();
}

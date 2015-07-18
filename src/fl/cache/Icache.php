<?php
namespace fl\cache;

/**
 * 缓存接口
 *
 * @author guliuzhong
 *        
 */
interface icache
{

    /**
     * 写入缓存
     *
     * 本缓存使用的是文件型缓存
     *
     * @param $name string
     *            名称
     * @param $data mixed
     *            可以进行序列化的数据
     * @param $ttl int
     *            有效时间
     */
    public function set($name, $data, $ttl = 86400);

    /**
     * 返回缓存内容
     *
     * 本缓存使用的是文件型缓存
     *
     * @param $name string
     *            名称
     * @throws Exception 文件读取异常
     */
    public function get($name);

    /**
     * 清除点定的缓存
     *
     * @param $name string
     *            缓存名称
     */
    public function delete($name);

    /**
     * 清除系统缓存
     *
     * 系统缓存是自动刷新的，增加本功能的意义在于可以
     * 立刻将缓存全部清除后重新生成
     * 界面定义主要用于显示,定义如下
     *
     * @param $maxdelete int
     *            最大删除文件数
     * @param $face array
     *            清除缓存界面定义
     * @return bool 无
     */
    public function flush_all();
}
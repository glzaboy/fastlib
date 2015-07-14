<?php
namespace fl\db;

interface Iconnect
{

    public function __construct($dbcfg);

    public function __destruct();

    /**
     * 关闭数据库连接
     */
    public function disconnect();

    /**
     * 开启事务
     */
    public function beginTransaction();

    /**
     * 回滚事务
     */
    public function rollback();

    /**
     * 提交事务
     */
    public function commint();

    /**
     * 连接数据库
     *
     * @param string $ismaster            
     * @throws \Exception
     * @return boolean|\PDO
     */
    public function getmasterpdo();

    /**
     * 连接数据库
     *
     * @param string $ismaster            
     * @throws \Exception
     * @return boolean|\PDO
     */
    public function getslavepdo();
}
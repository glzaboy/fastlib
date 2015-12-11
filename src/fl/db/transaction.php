<?php
namespace fl\db;

use fl\base\object;

class transaction extends object
{

    /**
     * 数据库事务对象
     *
     * @var array
     */
    static $_Transactions = array();

    /**
     * 是否进入transaction模式
     *
     * @var boolean
     */
    static $_inTransaction = false;

    static public function beginTransaction()
    {
        self::$_Transactions = array();
        // foreach (\fl\db\connect::$connections as $dbhash => $connect) {
        // if (in_array($dbhash, self::$_Transactions)) {
        // $connect = new \fl\db\connect($dbhash);
        // $connect->beginTransaction();
        // }
        // }
        \fl\helpers\hook::register('DB:AfterConnect', "Transaction", array(
            '\fl\db\transaction',
            'checkTransaction'
        ));
        self::$_inTransaction = true;
    }

    /**
     *
     * @param array $dbcfgs            
     */
    static public function addtoTransaction($dbcfgs)
    {
        if (! self::$_inTransaction) {
            throw new \PDOException("Not in Transaction", - 100);
        }
        foreach ($dbcfgs as $dbcfg) {
            if (in_array($dbcfg, self::$_Transactions)) {
                continue;
            }
            array_push(self::$_Transactions, $dbcfg);
            foreach (\fl\db\connect::$connections as $dbhash => $connect) {
                if ($dbcfg == $dbhash) {
                    $QueryBuilder = \fl\db\connect::getQueryerBuilder($dbhash);
                    $QueryBuilder->getconnect()->beginTransaction();
                }
            }
        }
    }

    static function rollback()
    {
        if (! self::$_inTransaction) {
            throw new \Exception("Not in Transaction", - 100);
        }
        foreach (\fl\db\connect::$connections as $dbhash => $connect) {
            if (in_array($dbhash, self::$_Transactions)) {
                $QueryBuilder = \fl\db\connect::getQueryerBuilder($dbhash);
                $QueryBuilder->getconnect()->rollback();
            }
        }
        \fl\helpers\hook::unregister('DB:AfterConnect', "Transaction");
    }

    static function commint()
    {
        if (! self::$_inTransaction) {
            throw new \PDOException("Not in Transaction", - 100);
        }
        foreach (\fl\db\connect::$connections as $dbhash => $connect) {
            if (in_array($dbhash, self::$_Transactions)) {
                $QueryBuilder = \fl\db\connect::getQueryerBuilder($dbhash);
                $QueryBuilder->getconnect()->commint();
            }
        }
        \fl\helpers\hook::unregister('DB:AfterConnect', "Transaction");
    }

    static function Transation($dbArray = array(), callable $callback)
    {
        try {
            self::beginTransaction($dbArray);
            call_user_func_array($callback, array());
            self::commint();
            return true;
        } catch (\Exception $e) {
            self::rollback();
            return false;
        }
    }

    static function checkTransaction($dbhash, $isMaster)
    {
        if (! $isMaster) {
            return;
        }
        $QueryBuilder = \fl\db\connect::getQueryerBuilder($dbhash);
        $QueryBuilder->getconnect()->beginTransaction();
    }

    /**
     * 数据连接是否在Transation中
     *
     * @param string $dbhash
     *            数据库链接名称
     * @return boolean
     */
    static function inTransation($dbhash = '')
    {
        if (in_array($dbhash, self::$_Transactions)) {
            return true;
        } else {
            return false;
        }
    }
}
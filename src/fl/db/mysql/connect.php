<?php
namespace fl\db\mysql;

/**
 *
 * @author guliuzhong
 *        
 */
class connect extends \fl\db\connect
{

    function __construct($dbcfg)
    {
        parent::__construct($dbcfg);
    }

    public function getslavepdo()
    {
        if (isset(connect::$connections[$this->_dbhash]['slave']) && connect::$connections[$this->_dbhash]['slave']->getAttribute(\PDO::ATTR_CONNECTION_STATUS)) {
            return connect::$connections[$this->_dbhash]['slave'];
        }
        if (! $this->dbexist()) {
            throw new \Exception('db config error.', - 1);
        }
        // 主从库连接策略
        if ($this->disableSlave === 1) {
            throw new \Exception('db config error.', - 1);
        }
        if ($this->disableSlave === 0) {
            $dbmain = $this->cfg->get('main');
            if (trim($dbmain['slave']) === '') {
                $this->disableSlave = 1;
            } else {
                $this->disableSlave = - 1;
            }
        }
        if ($this->disableSlave === 1) {
            throw new \Exception('Slave db not enable.', - 2);
        }
        if (! in_array(strtolower($this->cfg->get('main', 'type')), \PDO::getAvailableDrivers())) {
            throw new \Exception('not support db.', - 4);
        }
        try {
            $slaves = $this->cfg->get('main', 'slave');
            if (! $slaves) {
                throw new \Exception('slave db not enable.', - 2);
            }
            $slaves = explode(',', $slaves);
            shuffle($slaves);
            $dbcfg = $this->cfg->get($slaves[0]);
            $pdo = new \PDO($this->cfg->get('main', 'type') . ':' . $dbcfg['dsn'], $dbcfg['user'], $dbcfg['passwd'], array(
                \PDO::ATTR_TIMEOUT => 1
            ));
            if (! $pdo->getAttribute(\PDO::ATTR_CONNECTION_STATUS)) {
                throw new \Exception('db connect error.', - 2);
            }
            $dbinit = $this->cfg->get($this->cfg->get('main', 'init'));
            foreach ($dbinit as $cmd) {
                $pdo->exec($cmd);
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }
        connect::$connections[$this->_dbhash]['slave'] = $pdo;
        \fl\helpers\hook::runhook('DB:AfterConnect', array(
            $this->_dbhash,
            false
        ));
        return connect::$connections[$this->_dbhash]['slave'];
    }

    public function getmasterpdo()
    {
        if (isset(connect::$connections[$this->_dbhash]['master']) && connect::$connections[$this->_dbhash]['master']->getAttribute(\PDO::ATTR_CONNECTION_STATUS)) {
            return connect::$connections[$this->_dbhash]['master'];
        }
        if (! $this->dbexist()) {
            throw new \Exception('db config error.', - 1);
        }
        // 主从库连接策略
        if ($this->disableMaster === 1) {
            throw new \Exception('db config error.', - 1);
        }
        if ($this->disableMaster === 0) {
            $dbmain = $this->cfg->get('main');
            if (trim($dbmain['master']) === '') {
                $this->disableMaster = 1;
            } else {
                $this->disableMaster = - 1;
            }
        }
        if ($this->disableMaster === 1) {
            throw new \Exception('master db not enable.', - 2);
        }
        if (! in_array(strtolower($this->cfg->get('main', 'type')), \PDO::getAvailableDrivers())) {
            throw new \Exception('not support db.', - 4);
        }
        try {
            $dbcfg = $this->cfg->get($this->cfg->get('main', 'master'));
            if (! $dbcfg) {
                throw new \Exception('master db not enable.', - 2);
            }
            $pdo = new \PDO($this->cfg->get('main', 'type') . ':' . $dbcfg['dsn'], $dbcfg['user'], $dbcfg['passwd'], array(
                \PDO::ATTR_TIMEOUT => 1
            ));
            if (! $pdo->getAttribute(\PDO::ATTR_CONNECTION_STATUS)) {
                throw new \Exception('db connect error.', - 2);
            }
            $dbinit = $this->cfg->get($this->cfg->get('main', 'init'));
            foreach ($dbinit as $cmd) {
                $pdo->exec($cmd);
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }
        connect::$connections[$this->_dbhash]['master'] = $pdo;
        \fl\helpers\hook::runhook('DB:AfterConnect', array(
            $this->_dbhash,
            true
        ));
        return connect::$connections[$this->_dbhash]['master'];
    }
}
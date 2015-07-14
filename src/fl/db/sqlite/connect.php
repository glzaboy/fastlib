<?php
namespace fl\db\sqlite;

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
        return $this->getmasterpdo();
    }

    public function getmasterpdo()
    {
        if (isset(connect::$connections[$this->_dbhash]['master'])) {
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
            $pdo = new \PDO($this->cfg->get('main', 'type') . ':' . FL_RUNDIR . '/data/' . $dbcfg['dsn'], $dbcfg['user'], $dbcfg['passwd'], array(
                \PDO::ATTR_TIMEOUT => 1
            ));
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
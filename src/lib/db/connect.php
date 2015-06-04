<?php
namespace fl\db;

use fl\base\object;

class connect extends object implements Iconnect
{

    /**
     * 数据库链接
     *
     * @var \PDO
     */
    static $connections = array();

    /**
     * 数据库组KEY
     *
     * @var string
     */
    protected $_dbhash = '';

    /**
     * 数据库前缀
     *
     * @var string
     */
    public $prefix = '';

    /**
     * 数据库类型
     *
     * @var string
     */
    public $type = null;

    /**
     * 数据库编码
     *
     * @var string
     */
    protected $_charset = 'utf8';

    /**
     * 是否禁用主库
     * 0未初始化，1已禁用，-1未禁用
     *
     * @var int
     */
    protected $disableMaster = 0;

    /**
     * 是否禁用从库
     * 0未初始化，1已禁用，-1未禁用
     *
     * @var int
     */
    protected $disableSlave = 0;

    /**
     * PDO从连接
     *
     * @var \fl\cfg\cfg
     */
    protected $cfg;

    public function __construct($dbcfg)
    {
        if (! is_string($dbcfg)) {
            return false;
        }
        $this->cfg = \fl\cfg\cfg::instance('db/' . $dbcfg, 'ini');
        $this->_dbhash = $dbcfg;
        $this->type = $this->cfg->get('main', 'type');
        $this->prefix = $this->cfg->get('main', 'prefix');
        if ($this->cfg->get('main', 'charset')) {
            $this->_charset = $this->cfg->get('main', 'charset');
        }
    }

    public function __destruct()
    {
        unset($this->cfg);
    }

    /**
     * 数据库是否存在
     *
     * @return boolean
     */
    public function dbexist()
    {
        if (count($this->cfg->get('main')) == 0) {
            return false;
        } else {
            return true;
        }
    }
    /**
     * 取得DBhash
     *
     * @return boolean
     */
    public function intransaction()
    {
        return transaction::inTransation($this->_dbhash);
    }
    /**
     * 连接数据库
     *
     * @return \PDO
     */
    /**
     *
     * @param string $ismaster            
     * @throws \Exception
     * @return boolean|\PDO
     */
    public function getconnect($ismaster = null)
    {
        if (! $this->dbexist()) {
            throw new \Exception('db config error.', - 1);
        }
        // 主从库连接策略
        if ($this->disableMaster === 1 && $this->disableSlave === 1) {
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
        if ($this->disableMaster === 1 && $ismaster) {
            throw new \Exception('master db not enable.', - 2);
        }
        if ($this->disableSlave === 0) {
            $dbmain = $this->cfg->get('main');
            if (trim($dbmain['slave']) === '') {
                $this->disableSlave = 1;
            } else {
                $this->disableSlave = - 1;
            }
        }
        if ($this->disableSlave === 1 && ! $ismaster) {
            throw new \Exception('Slave db not enable.', - 2);
        }
        if ($this->disableMaster === 1 && $this->disableSlave === 1) {
            throw new \Exception('db config error.', - 3);
        }
        if (! in_array($this->cfg->get('main', 'type'), \PDO::getAvailableDrivers())) {
            throw new \Exception('not support db.', - 4);
        }
        if ($ismaster && $this->disableMaster === - 1) {
            if (isset(connect::$connections[$this->_dbhash]['master']) && connect::$connections[$this->_dbhash]['master']->getAttribute(\PDO::ATTR_CONNECTION_STATUS)) {
                return connect::$connections[$this->_dbhash]['master'];
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
                $ismaster
            ));
            return connect::$connections[$this->_dbhash]['master'];
        } elseif (! $ismaster && $this->disableSlave == - 1) {
            if (isset(connect::$connections[$this->_dbhash]['slave']) && connect::$connections[$this->_dbhash]['slave']->getAttribute(\PDO::ATTR_CONNECTION_STATUS)) {
                return connect::$connections[$this->_dbhash]['slave'];
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
                $ismaster
            ));
            return connect::$connections[$this->_dbhash]['slave'];
        } else {
            $this->setError("db config error.");
            return false;
        }
    }

    public function disconnect()
    {
        unset(self::$connections[$this->_dbhash]);
    }

    public function beginTransaction()
    {
        $pdo = $this->getconnect(true);
        $pdo->beginTransaction();
    }

    public function rollback()
    {
        $pdo = $this->getconnect(true);
        $pdo->rollBack();
    }

    public function commint()
    {
        $pdo = $this->getconnect(true);
        $pdo->commit();
    }

    public function query($sql, $bindparams = array(), $ismaster = false)
    {
        $sth = $this->runsql($sql, $bindparams, $ismaster);
        if (! $sth) {
            return false;
        }
        $sth->setFetchMode(\PDO::FETCH_ASSOC);
        return $sth;
    }

    public function exec($sql, $bindparams = array(), $ismaster = false)
    {
        $sth = $this->runsql($sql, $bindparams, $ismaster);
        if (! $sth) {
            return false;
        }
        return $sth->rowCount() ? $sth->rowCount() : true;
    }

    /**
     * 执行一个SQL
     *
     * @param unknown $sql
     *            SQL
     * @param array $bindparams
     *            绑定参数
     * @param string $ismaster
     *            是否主库执行
     * @return boolean|\PDOStatement
     */
    private function runsql($sql, $bindparams = array(), $ismaster = false)
    {
        if (FL_DEBUG) {
            $this->lastsql = $sql . ' BIND Value :' . var_export($bindparams, true);
        }
        $pdo = $this->getconnect($ismaster);
        if (! $pdo) {
            return false;
        }
        $sth = $pdo->prepare($sql);
        if (is_array($bindparams)) {
            foreach ($bindparams as $k => $v) {
                $sth->bindValue($k + 1, $v);
            }
        }
        if (! $sth->execute()) {
            $errInfo = $sth->errorInfo();
            throw new \PDOException("SQL ERROR." . $sql . ' BIND params :' . var_export($bindparams, true) . $errInfo[2], - 1);
        }
        return $sth;
    }
}
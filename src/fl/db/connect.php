<?php
namespace fl\db;

use fl\base\object;

abstract class connect extends object implements Iconnect
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

    public function disconnect()
    {
        unset(self::$connections[$this->_dbhash]);
    }

    public function beginTransaction()
    {
        $pdo = $this->getmasterpdo();
        $pdo->beginTransaction();
    }

    public function rollback()
    {
        $pdo = $this->getmasterpdo();
        $pdo->rollBack();
    }

    public function commint()
    {
        $pdo = $this->getmasterpdo();
        $pdo->commit();
    }

    public function query($sql, $bindparams = array(), $ismaster = false)
    {
        $sth = $this->runsql($sql, $bindparams, $ismaster);
        if (! $sth) {
            return false;
        }
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
        if ($ismaster) {
            $pdo = $this->getmasterpdo();
        } else {
            $pdo = $this->getslavepdo();
        }
        if (! $pdo) {
            return false;
        }
        $sth = $pdo->prepare($sql);
        if (is_array($bindparams)) {
            foreach ($bindparams as $k => $v) {
                $sth->bindValue($k + 1, $v);
            }
        }
        $sth->setFetchMode(\PDO::FETCH_ASSOC);
        if (! $sth->execute()) {
            $errInfo = $sth->errorInfo();
            throw new \PDOException("SQL ERROR." . $sql . ' BIND params :' . var_export($bindparams, true) . $errInfo[2], - 1);
        }
        return $sth;
    }

    final public static function adaptor($dbcfg)
    {
        static $dbconnetc = array();
        if (isset($dbconnetc[$dbcfg])) {
            return $dbconnetc[$dbcfg];
        }
        $s_cfg = \fl\cfg\cfg::instance('db/' . $dbcfg, 'ini');
        switch (strtolower($s_cfg->get('main', 'type'))) {
            case 'mysql':
                $dbconnetc[$dbcfg] = new mysql\connect($dbcfg);
                break;
            case 'sqlite':
                $dbconnetc[$dbcfg] = new sqlite\connect($dbcfg);
                break;
            default:
        }
        return $dbconnetc[$dbcfg];
    }

    /**
     *
     * @param \fl\db\connect $connnect
     *            数据库链接
     * @return \fl\db\mysql\QueryBuilder|sqlite\QueryBuilder
     */
    public function getQueryerBuilder()
    {
        switch ($this->type) {
            case 'mysql':
                return new mysql\QueryBuilder($this);
                break;
            case 'sqlite':
                return new sqlite\QueryBuilder($this);
                break;
            default:
        }
    }
}
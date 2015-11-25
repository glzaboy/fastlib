<?php
namespace fl\db;

abstract class QueryBuilder extends \fl\base\object implements IQueryBuilder
{

    /**
     * 锁定只允许读取
     *
     * @var int
     */
    const LOCK_FOR_SHARE = 1;

    /**
     * 锁定不允许读取
     *
     * @var int
     */
    const LOCK_FOR_WRITE = 2;

    /**
     * 字段左分隔符
     *
     * @var string
     */
    protected $left_delimiter = '[';

    /**
     * 字段右分隔符
     *
     * @var string
     */
    protected $right_delimiter = ']';

    /**
     * 数据表前缀
     *
     * @var string
     */
    protected $_prefix = '';

    /**
     * 字段右分隔符
     *
     * @var 数据表前缀
     */
    protected $_enableprefix = false;

    /**
     * 数据库链接
     *
     * @var \fl\db\connect
     */
    protected $_connect = null;

    /**
     * 统计条数 sql
     *
     * @var string
     */
    protected $_counsql = '';

    /**
     * 统计条数 bind
     *
     * @var array
     */
    protected $_counbindvalue = array();

    /**
     * SQL
     *
     * @var string
     */
    protected $_sql = '';

    /**
     * SQL bind
     *
     * @var array
     */
    protected $_bindvalue = array();

    /**
     * page
     *
     * @var int
     */
    protected $_ipage = 0;

    /**
     * limit
     *
     * @var int
     */
    protected $_ilimit = 0;

    public function __construct(\fl\db\connect $connnect = null)
    {
        $this->_connect = $connnect;
        $this->setprefix($this->_connect->prefix);
    }

    private function setprefix($prefix = '')
    {
        $this->_prefix = $prefix;
        if ($this->_prefix) {
            $this->enableprefix();
        } else {
            $this->disableprefix();
        }
    }

    public function enableprefix()
    {
        return $this->_enableprefix = true;
    }

    public function disableprefix()
    {
        return $this->_enableprefix = false;
    }

    public function getprefix()
    {
        if ($this->_enableprefix) {
            return $this->_prefix;
        } else {
            return '';
        }
    }

    public function quotetable($inputtable)
    {
        $return = '';
        if (strpos($inputtable, ' ')) {
            list ($inputtable, $as) = explode(' ', $inputtable);
            $return = ' AS ' . $this->left_delimiter . $as . $this->right_delimiter;
        }
        if (strpos($inputtable, '.')) { // table.field as
            list ($inputtable, $field) = explode('.', $inputtable);
            $return = $this->left_delimiter . $this->getprefix() . $inputtable . $this->right_delimiter . '.' . $this->left_delimiter . $field . $this->right_delimiter . $return;
        } else {
            $return = $this->left_delimiter . $this->getprefix() . $inputtable . $this->right_delimiter . $return;
        }
        return $return;
    }

    public function quotefield($inputtable)
    {
        $return = '';
        if (strpos($inputtable, ' ')) { // as
            list ($inputtable, $as) = explode(' ', $inputtable);
            $return = ' AS ' . $this->left_delimiter . $as . $this->right_delimiter;
        }
        if (strpos($inputtable, '.')) { // table.field
            list ($inputtable, $field) = explode('.', $inputtable);
            $return = $this->left_delimiter . $this->getprefix() . $inputtable . $this->right_delimiter . '.' . $this->left_delimiter . $field . $this->right_delimiter . $return;
        } else {
            if (preg_match('/[\(|\)]/', $inputtable)) {
                $return = $inputtable . ' ' . $return;
            } else {
                $return = $this->left_delimiter . $inputtable . $this->right_delimiter . $return;
            }
        }
        return $return;
    }

    /**
     * 处理where SQL条件
     *
     *
     * @param $condition string|Array
     *            表名
     * @return array 处理后的条件
     */
    protected function processcondition($condition, $valueasfiled = false)
    {
        $bindwherestring = "";
        $bindwherearray = array();
        $return = array();
        if (is_array($condition)) {
            foreach ($condition as $k => $v) {
                if (! is_string($k) && $k) {
                    $bindwherestring .= '(' . $v . ')' . ' AND ';
                    continue;
                }
                if (is_string($k) && $valueasfiled) {
                    $bindwherestring .= $this->quotefield($k) . '=' . $this->quotefield($v) . ' AND ';
                } elseif (is_string($k)) {
                    if (is_scalar($v)) {
                        $bindwherestring .= $this->quotefield($k) . '=? AND ';
                        array_push($bindwherearray, $v);
                    } elseif (is_array($v)) {
                        /* 创建一个填充了和params相同数量占位符的字符串 */
                        $place_holders = implode(',', array_fill(0, count($v), '?'));
                        $bindwherestring .= $this->quotefield($k) . ' IN (' . $place_holders . ') AND ';
                        foreach ($v as $tmpparams) {
                            array_push($bindwherearray, $tmpparams);
                        }
                    }
                }
            }
        } elseif (is_string($condition)) {
            $bindwherestring = $condition;
        }
        if ($bindwherestring) {
            $bindwherestring = preg_replace('/\sAND\s$/i', '', $bindwherestring);
        }
        $return['bindvalue'] = $bindwherearray;
        $return['condition'] = $bindwherestring;
        return $return;
    }

    /**
     * 设置数据分页
     *
     * @param number $ilimit
     *            限制返回条数
     * @param number $ipage
     *            数据页码
     */
    public function setpager($ilimit = 10, $ipage = 1)
    {
        $this->_ipage = intval($ipage);
        $this->_ilimit = intval($ilimit);
    }

    public function select()
    {
        return $this->_connect->query($this->_sql, $this->_bindvalue, $this->_connect->intransaction());
    }

    public function selectdata()
    {
        $PDOStatement = $this->_connect->query($this->_sql, $this->_bindvalue, $this->_connect->intransaction());
        $return = array();
        foreach ($PDOStatement as $val) {
            array_push($return, $val);
        }
        return $return;
    }

    public function selectcount()
    {
        $data = $this->_connect->query($this->_counsql, $this->_counbindvalue, $this->_connect->intransaction())
            ->fetch();
        return $data['count'];
    }
}
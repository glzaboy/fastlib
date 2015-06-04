<?php
namespace fl\db;

class QueryBuilder extends \fl\base\object implements IQueryBuilder
{

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
     * 数据库链接
     *
     * @var boolean
     */
    protected $_bcount = 0;

    /**
     * 数据库链接
     *
     * @var int
     */
    protected $_ipage = 0;

    /**
     * 数据库链接
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
        }
    }

    /**
     * 启用数据库前缀
     *
     * @return boolean
     */
    public function enableprefix()
    {
        return $this->_enableprefix = true;
    }

    /**
     * 禁用数据表前缀
     *
     * @return boolean
     */
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

    /**
     *
     * @param \fl\db\connect $connnect
     *            数据库链接
     * @return \fl\db\QueryBuilder\mysql\QueryBuilder
     */
    static function getQueryerBuilder(\fl\db\connect $connnect)
    {
        switch ($connnect->type) {      case 'mysql':
                return new \fl\db\QueryBuilder\mysql\QueryBuilder($connnect);
                break;
            default:
        }
    }

    function gettables()
    {}

    function getFileds($table)
    {}

    public function delete($table, $condition = null)
    {}

    public function update($table, $data = array(), $condition = null)
    {}

    public function insert($table, $data = array())
    {}

    public function select($table, $condition = null, $field = "*", $orderby, $groupby, $join = array())
    {}

    /**
     * 处理where SQL条件
     *
     *
     * @param $condition string|Array
     *            表名
     * @return array 处理后的条件
     */
    protected function processcondition($condition)
    {
        $bindwherestring = "";
        $bindwherearray = array();
        $return = array();
        if (is_array($condition)) {
            foreach ($condition as $k => $v) {
                if (is_string($k)) {
                    $bindwherestring .= strtr($this->left_delimiter . $k . $this->right_delimiter . '=?', array(
                        '.' => $this->right_delimiter . '.' . $this->left_delimiter
                    )) . ' AND ';
                    array_push($bindwherearray, $v);
                } else {
                    $bindwherestring .= '(' . $v . ')' . ' AND ';
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

    public function setCount($bCount = false)
    {
        $this->_bcount = $bCount;
    }

    public function setlimit($ilimit = 0)
    {
        $this->_ilimit = intval($ilimit);
    }

    public function setpage($ipage = 0)
    {
        $this->_ipage = intval($ipage);
    }
}
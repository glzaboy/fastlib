<?php
namespace fl\db;

abstract class QueryBuilder extends \fl\base\object implements IQueryBuilder
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
     * @var string
     */
    protected $_counsql = '';

    /**
     * 数据库链接
     *
     * @var array
     */
    protected $_counbindvalue = array();

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

    public function gettable($table)
    {
        if (strpos($table, '.')) {
            $field = true;
            list ($table, $field) = explode('.', $table);
            if ($table && $field) {
                return $this->left_delimiter . $this->getprefix() . $table . $this->right_delimiter . '.' . $this->left_delimiter . $field . $this->right_delimiter;
            }
        } else {
            return $this->left_delimiter . $this->getprefix() . $table . $this->right_delimiter;
        }
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
                    $bindwherestring .= $this->gettable($k) . '=' . $this->gettable($v) . ' AND ';
                } elseif (is_string($k)) {
                    if (is_string($v)) {
                        $bindwherestring .= $this->gettable($k) . '=? AND ';
                        array_push($bindwherearray, $v);
                    } elseif (is_array($v)) {
                        /* 创建一个填充了和params相同数量占位符的字符串 */
                        $place_holders = implode(',', array_fill(0, count($v), '?'));
                        $bindwherestring .= $this->gettable($k) . ' IN (' . $place_holders . ') AND ';
                        foreach ($v as $tmpparams){
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
}
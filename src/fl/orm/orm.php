<?php
namespace fl\orm;

use fl\base\object;

/**
 *
 * @author guliuzhong
 *        
 */
abstract class orm extends object
{

    /**
     * 数据库配置
     *
     * @var string
     */
    protected $dbcfg = '';

    /**
     * QueryBuilder对象
     *
     * @var \fl\db\IQueryBuilder
     */
    protected $QueryBuilder = null;

    /**
     * 数据表名
     *
     * @var string
     */
    protected $table = '';

    /**
     * pk id
     *
     * @var int
     */
    protected $pk_id = '';

    /**
     * pkname
     *
     * @var unknown
     */
    protected $pk = '';

    /**
     * 数据关系
     *
     * @var string
     */
    protected $releation = 'MASTER';

    /**
     * 关联字段
     *
     * @var string $releation_key
     */
    protected $releation_key = '';

    /**
     * 关联字段 值
     *
     * @var int|string
     */
    protected $releation_value = 0;

    /**
     * model data
     *
     * @var array
     */
    private $_data = array();

    /**
     * data new state
     *
     * @var array
     */
    private $_newdata = array();

    private $is_merge = true;

    protected $releations_class = array();

    protected $_map = array();

    const RELEATION_ONE = "ONE";

    const RELEATION_MANY = "MANY";

    const RELEATION_MASTER = "MASTER";

    function __construct($pk_id = 0, $releation = self::RELEATION_MASTER, $releation_key = null, $releation_value = 0)
    {
        $dbinfo = explode('\\', $this->getclassname());
        if (count($dbinfo) < 2) {
            throw new \Exception('this class is not orm class.');
        }
        $this->table = array_pop($dbinfo);
        $this->dbcfg = array_pop($dbinfo);
        $tabinfo = explode('_', $this->table);
        $this->pk_id = $pk_id;
        $this->pk = end($tabinfo) . '_id';
        if ($releation) {
            $this->releation = $releation;
        }
        if ($releation_key) {
            $this->releation_key = $releation_key;
        }
        if ($releation_value) {
            $this->releation_value = $releation_value;
        }
        $this->getQueryBuilder();
    }

    protected function getQueryBuilder()
    {
        if ($this->QueryBuilder) {
            return $this->QueryBuilder;
        }
        $db = \fl\db\connect::adaptor($this->dbcfg);
        $this->QueryBuilder = $db->getQueryerBuilder();
        return $this->QueryBuilder;
    }

    /**
     * 获取数据表名
     *
     * @return string|mixed
     */
    public function gettable()
    {
        return $this->table;
    }

    /**
     * 获取数据表名
     *
     * @return string|mixed
     */
    public function setpager($limit = 10, $page = 1)
    {
        return $this->getQueryBuilder()->setpager($limit, $page);
    }

    /**
     * 选择数据库记录
     *
     * @param string|array $condition
     *            条件
     * @param string|array $item
     *            字段
     * @param string|array $orderby
     *            排序
     * @param string|array $groupby
     *            分组
     * @param array $join
     *            联表
     * @param array $otherinfo            
     * @return \PDOStatement
     */
    public function select($condition = null, $item = "*", $orderby = array(), $groupby = array(), $join = array(), $otherinfo = array())
    {
        return $this->getQueryBuilder()
            ->prepareselect($this->gettable(), $condition, $item, $orderby, $groupby, $join, $otherinfo)
            ->select();
    }

    /**
     * 选择数据库记录
     *
     * @param string|array $condition
     *            条件
     * @param string|array $item
     *            字段
     * @param string|array $orderby
     *            排序
     * @param string|array $groupby
     *            分组
     * @param array $join
     *            联表
     * @param array $otherinfo            
     * @return array
     */
    public function selectdata($condition = null, $item = "*", $orderby = array(), $groupby = array(), $join = array(), $otherinfo = array())
    {
        return $this->getQueryBuilder()
            ->prepareselect($this->gettable(), $condition, $item, $orderby, $groupby, $join, $otherinfo)
            ->selectdata();
    }

    private function getdata()
    {
        if ($this->_data) {
            if (in_array($this->releation, array(
                self::RELEATION_MASTER,
                self::RELEATION_ONE
            )) && $this->is_merge == false && $this->_newdata) {
                foreach ($this->_newdata as $key => $val) {
                    $this->_data[0][$key] = $val;
                }
            }
            return;
        }
        if ($this->gecondition()) {
            $this->_data = $this->selectdata($this->gecondition());
        }
    }

    private function gecondition()
    {
        switch ($this->releation) {
            case self::RELEATION_ONE:
                return array(
                    $this->releation_key => $this->releation_value
                );
                break;
            case self::RELEATION_MANY:
                return array(
                    $this->releation_key => $this->releation_value
                );
                break;
            case self::RELEATION_MASTER:
                return array(
                    $this->pk => $this->pk_id
                );
                break;
            default:
                break;
        }
    }

    public function getrelationclas($relation)
    {
        $relationinfo = $this->releation($relation);
        if (isset($this->releations_class[$relation]) && is_object($this->releations_class[$relation])) {
            return $this->releations_class[$relation];
        }
        if (count($relationinfo) >= 2) {
            $releation_class = new $relationinfo[0](0, $relationinfo[1], $relationinfo[2], $this->get($relationinfo[2]));
            $this->releations_class[$relation] = $releation_class;
        }
        return $this->releations_class[$relation];
    }

    /**
     *
     * @param string $key
     *            'releation.field'
     * @param number $limit            
     * @param number $page            
     * @throws \Exception
     */
    public function get($key = false, $limit = 10, $page = 1)
    {
        $this->getdata();
        if ($this->releation == self::RELEATION_MASTER) {
            if (! $key) {
                $relation = $this->releation;
                $field = false;
            }
            $keys = explode('.', $key);
            if (count($keys) == 1) {
                if ($keys[0] && $this->releation($keys[0])) {
                    $relation = $keys[0];
                    $field = false;
                } else {
                    $relation = self::RELEATION_MASTER;
                    $field = $keys[0];
                }
            }
            if (count($keys) == 2) {
                if ($keys[0] && $this->releation($keys[0])) {
                    $relation = $keys[0];
                    $field = $keys[1];
                } else {
                    $relation = self::RELEATION_MASTER;
                    $field = $keys[0];
                }
            }
            if (count($keys) >= 3) {
                $this->setError('key not exists');
                return false;
            }
            if ($relation == self::RELEATION_MASTER) {
                if ($field) {
                    if (isset($this->_data[0][$field])) {
                        return $this->_data[0][$field];
                    } else {
                        return '';
                    }
                } else {
                    if (isset($this->_data[0])) {
                        return $this->_data[0];
                    } else {
                        return array();
                    }
                }
            }
            if ($relation) {
                $class = $this->getrelationclas($relation);
                return $class->get($field, $limit, $page);
            }
        } else {
            switch ($this->releation) {
                case self::RELEATION_ONE:
                    if ($key) {
                        if (isset($this->_data[0][$key])) {
                            return $this->_data[0][$key];
                        } else {
                            return '';
                        }
                    } else {
                        if (isset($this->_data[0])) {
                            return $this->_data[0];
                        } else {
                            return array();
                        }
                    }
                    break;
                case self::RELEATION_MANY:
                    if ($key) {
                        $return = array();
                        foreach ($this->_data as $val) {
                            array_push($return, $val[$key]);
                        }
                        return array_slice($return, ($page - 1) * $limit, $limit);
                    } else {
                        return array_slice($this->_data, ($page - 1) * $limit, $limit);
                    }
                    break;
            }
        }
    }

    public function setdata($data)
    {
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function set($key, $value)
    {
        if (! in_array($this->releation, array(
            self::RELEATION_MASTER,
            self::RELEATION_ONE
        ))) {
            throw new \Exception("set not supprt MANY releation");
        }
        $this->is_merge = false;
        $this->_newdata[$key] = $value;
    }

    public function save()
    {}

    public function releation($relation = false)
    {
        if ($relation == false) {
            return $this->_map;
        }
        if (isset($this->_map[$relation])) {
            return $this->_map[$relation];
        } else {
            return array();
        }
    }
}
?>
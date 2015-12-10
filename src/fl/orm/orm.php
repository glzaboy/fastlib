<?php
namespace fl\orm;

use fl\base\object;
use fl\thrift\thrift;

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
     * @var int|string
     */
    protected $pk_id = '';

    /**
     * pkname
     *
     * @var string
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

    public function getQueryBuilder()
    {
        if ($this->QueryBuilder) {
            return $this->QueryBuilder;
        }
        $this->QueryBuilder = \fl\db\connect::getQueryerBuilder($this->dbcfg);
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
        return $this->QueryBuilder->setpager($limit, $page);
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
        return $this->QueryBuilder->prepareselect($this->gettable(), $condition, $item, $orderby, $groupby, $join, $otherinfo)
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
        return $this->QueryBuilder->prepareselect($this->gettable(), $condition, $item, $orderby, $groupby, $join, $otherinfo)
            ->selectdata();
    }

    private function mergedata($cleartmp = false)
    {
        if ($this->is_merge === false) {
            foreach ($this->_newdata as $key => $val) {
                $this->_data[0][$key] = $val;
            }
            $this->is_merge = true;
        }
        if ($cleartmp) {
            $this->_newdata = array();
        }
    }

    private function getdata()
    {
        if (! $this->_data) {
            $this->_data = $this->selectdata($this->getcondition());
        }
        if (in_array($this->releation, array(
            self::RELEATION_MASTER,
            self::RELEATION_ONE
        )) && $this->is_merge == false) {
            $this->mergedata(false);
        }
    }

    private function getcondition()
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

    /**
     *
     * @param string $relation            
     * @return orm
     */
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
     * key2releation
     * 返回key到rel关系
     *
     * @param array $key            
     */
    private function key2rel($key)
    {
        if (! $key) {
            $relation = $this->releation;
            $field = null;
        }
        $keys = explode('.', $key);
        if (count($keys) == 1) {
            if ($keys[0] && $this->releation($keys[0])) {
                $relation = $keys[0];
                $field = null;
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
        return array(
            $relation,
            $field
        );
    }

    /**
     * 获取数据
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
            list ($relation, $field) = $this->key2rel($key);
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

    /**
     * 设置数据
     *
     * @param unknown $data            
     * @throws \Exception
     */
    public function setdata($data)
    {
        if ($this->releation == self::RELEATION_MASTER) {
            foreach ($data as $key => $value) {
                $this->set($key, $value);
            }
        } else {
            throw new \Exception('setdata only support master releation');
        }
    }

    public function set($key, $value)
    {
        if ($this->releation == self::RELEATION_MASTER) {
            list ($relation, $field) = $this->key2rel($key);
            if ($relation == self::RELEATION_MASTER) {
                if ($field) {
                    $this->is_merge = false;
                    $this->_newdata[$key] = $value;
                    return true;
                }
            }
            if ($relation) {
                $class = $this->getrelationclas($relation);
                if ($class) {
                    return $class->set($field, $value);
                }
            }
        } else {
            switch ($this->releation) {
                case self::RELEATION_ONE:
                    $this->is_merge = false;
                    $this->_newdata[$key] = $value;
                    return true;
                    break;
                case self::RELEATION_MANY:
                    throw new \Exception("set not supprt MANY releation");
                    return false;
                    break;
            }
        }
    }

    public function getdbcfg()
    {
        if ($this->releation == self::RELEATION_MASTER) {
            $return = array();
            array_push($return, $this->dbcfg);
            foreach ($this->releation() as $releation) {
                $dbinfo = explode('\\', $releation[0]);
                array_pop($dbinfo);
                array_push($return, array_pop($dbinfo));
            }
            return array_unique($return);
        } else {
            return $this->dbcfg;
        }
    }

    public function save()
    {
        if ($this->releation == self::RELEATION_MASTER) {
            if ($this->_newdata) {
                $pk_id = $this->get($this->pk);
                if (! $pk_id) {
                    if ($this->pk_id) {
                        $this->_newdata[$this->pk] = $this->pk_id;
                    }
                    $this->pk_id = $this->QueryBuilder->insert($this->table, $this->_newdata);
                    $this->_data[0][$this->pk] = $this->pk_id; // set pkid;
                } else {
                    $this->QueryBuilder->update($this->table, $this->_newdata, array(
                        $this->pk => $pk_id
                    ));
                }
                $this->mergedata(true);
            }
            $pk_id = $this->get($this->pk);
            if ($pk_id) {
                foreach ($this->releation() as $key => $val) {
                    $class = $this->getrelationclas($key);
                    if ($class->_newdata && $class->releation == self::RELEATION_ONE) {
                        $condition = $class->getcondition();
                        foreach ($condition as $key => $val) {
                            $class->set($key, $this->get($key));
                        }
                        $class->save();
                    }
                }
            }
        } elseif ($this->releation == self::RELEATION_ONE) {
            if ($this->_newdata) {
                $pk_id = $this->get($this->pk);
                if (! $pk_id) {
                    $this->pk_id = $this->QueryBuilder->insert($this->table, $this->_newdata);
                } else {
                    $this->QueryBuilder->update($this->table, $this->_newdata, array(
                        $this->pk => $pk_id
                    ));
                }
                $this->mergedata(true);
            }
        }
    }

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
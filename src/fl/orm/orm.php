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

    protected $dbcfg = '';

    /**
     *
     * @var \fl\db\IQueryBuilder
     */
    protected $QueryBuilder = null;

    protected $table = '';

    protected $pk_id = '';

    protected $pk = '';

    protected $releation = 'MASTER';
    // master，ONE MANY
    protected $releation_key = '';
    // master，ONE MANY
    protected $releation_value = 0;
    //
    private $_data = array();

    protected $releations_class = array();

    protected $_map = array();

    const RELEATION_ONE = "ONE";

    const RELEATION_MANY = "MANY";

    const RELEATION_MASTER = "MASTER";

    function __construct($pk_id, $releation = self::RELEATION_MASTER, $releation_key = null, $releation_value = 0)
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
        return $this->QueryBuilder->select($this->gettable(), $condition, $item, $orderby, $groupby, $join, $otherinfo);
    }

    private function getdata()
    {
        if ($this->_data) {
            return;
        }
        if ($this->gecondition()) {
            $data = $this->select($this->gecondition());
            foreach ($data as $d) {
                $this->_data[] = $d;
            }
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
    public function get($key = false, $limit = 0, $page = 1)
    {
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
            if (! $this->_data) {
                $this->getdata();
            }
            if ($field) {
                switch ($this->releation) {
                    case self::RELEATION_ONE:
                    case self::RELEATION_MASTER:
                        if (isset($this->_data[0][$field])) {
                            return $this->_data[0][$field];
                        } else {
                            return '';
                        }
                        break;
                }
            } else {
                switch ($this->releation) {
                    case self::RELEATION_ONE:
                    case self::RELEATION_MASTER:
                        if (isset($this->_data[0])) {
                            return $this->_data[0];
                        } else {
                            return array();
                        }
                        break;
                    case self::RELEATION_MANY:
                        return $this->_data;
                        break;
                }
            }
        }
        $class = $this->getrelationclas($relation);
        return $class->get($field);
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
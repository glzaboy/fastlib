<?php
namespace fl\db\mysql;

/**
 * Mysql
 * 
 * @author Administrator
 *        
 */
class QueryBuilder extends \fl\db\QueryBuilder
{

    /**
     */
    function __construct($connnect)
    {
        $this->left_delimiter = '`';
        $this->right_delimiter = '`';
        return parent::__construct($connnect);
    }

    /**
     */
    function __destruct()
    {}

    public function gettables()
    {
        $tables = array();
        foreach ($this->_connect->query("show tables", array(), false) as $v) {
            array_push($tables, array_pop($v));
        }
        return $tables;
    }

    public function insert($table, $data = array())
    {
        $field_list = "";
        $value_list = implode(',', array_fill(0, count($data), '?'));
        $bindvalue = array();
        foreach ($data as $k => $v) {
            $field_list .= $this->quotefield($k) . ',';
            array_push($bindvalue, $v);
        }
        $field_list = substr($field_list, 0, - 1);
        $sql = 'INSERT INTO ' . $this->quotetable($table) . ' (' . $field_list . ') VALUES (' . $value_list . ');';
        $affected = $this->_connect->exec($sql, $bindvalue, true);
        if ($affected === false) {
            return false;
        }
        $lastInsertId = $this->_connect->getmasterpdo()->lastInsertId();
        return $lastInsertId ? $lastInsertId : $affected;
    }

    public function update($table, $data = array(), $condition = null)
    {
        $field_set = "";
        $bindvalue = array();
        foreach ($data as $k => $v) {
            $field_set .= $this->quotefield($k) . '=?,';
            array_push($bindvalue, $v);
        }
        $field_set = substr($field_set, 0, - 1);
        if ($condition) {
            $condition = $this->processcondition($condition);
            if (count($condition['bindvalue'])) {
                foreach ($condition['bindvalue'] as $v) {
                    array_push($bindvalue, $v);
                }
            }
            $sql = 'UPDATE ' . $this->quotetable($table) . ' SET ' . $field_set . ' WHERE ' . $condition['condition'];
        } else {
            $sql = 'UPDATE ' . $this->quotetable($table) . ' SET ' . $field_set;
        }
        $affected = $this->_connect->exec($sql, $bindvalue, true);
        if ($affected === false) {
            throw new \Exception();
            return false;
        }
        return $affected;
    }

    public function delete($table, $condition = null)
    {
        $bindvalue = array();
        if ($condition) {
            $condition = $this->processcondition($condition);
            if (count($condition['bindvalue'])) {
                foreach ($condition['bindvalue'] as $v) {
                    array_push($bindvalue, $v);
                }
            }
            $sql = 'DELETE FROM ' . $this->quotetable($table) . ' WHERE ' . $condition['condition'];
        } else {
            $sql = 'DELETE FROM ' . $this->quotetable($table);
        }
        $affected = $this->_connect->exec($sql, $bindvalue, true);
        if ($affected === false) {
            return false;
        }
        return $affected;
    }

    public function getFileds($table)
    {
        $sql = "SHOW COLUMNS FROM " . $this->left_delimiter . $this->getprefix() . $table . $this->right_delimiter;
        $fileds = array();
        foreach ($this->_connect->query($sql, array(), false) as $v) {
            array_push($fileds, $v['Field']);
        }
        return $fileds;
    }

    public function prepareselect($table, $condition = null, $item = "*", $orderby = array(), $groupby = array(), $join = array(), $otherinfo = array())
    {
        foreach ($otherinfo as $k => $v) {
            $otherinfo[strtoupper($k)] = $v;
        }
        $this->_bindvalue = array();
        if (empty($item)) {
            $item = '*';
        }
        if (is_array($item)) {
            foreach ($item as $k => $v) {
                $tmp = $this->quotefield($v);
                $item[$k] = $tmp;
            }
            $item = @implode(" , ", $item);
        }
        $joinstr = "";
        if (is_array($join)) {
            foreach ($join as $key => $value) {
                $jointtype = 'LEFT';
                if (is_array($value) && count($value) >= 2) {
                    if (isset($value[0]) && in_array(strtolower($value['0']), array(
                        'left',
                        'right',
                        'inner',
                        'outer'
                    ))) {
                        $jointtype = strtoupper(trim($value[0]));
                        unset($value[0]);
                    } else {
                        $jointtype = 'LEFT';
                    }
                }
                if (is_string($value)) {
                    $joinstr .= $jointtype . ' JOIN ' . $this->quotetable($key) . ' ON (' . $value . ') ';
                } elseif (is_array($value) && count($value) >= 1) {
                    $tmpcondition = $this->processcondition($value, true);
                    $joinstr .= $jointtype . ' JOIN ' . $this->quotetable($key) . " ON (" . $tmpcondition['condition'] . ')';
                    foreach ($tmpcondition['bindvalue'] as $v) {
                        array_push($this->_bindvalue, $v);
                    }
                    unset($tmpcondition);
                }
            }
        }
        if ($this->_ilimit != 0 && $this->_ipage >= 1) {
            $limit = ($this->_ipage - 1) * $this->_ilimit;
            $limit = " LIMIT $limit,$this->_ilimit";
        } else {
            $limit = '';
        }
        if (! $orderby) {
            $orderby = "";
        }
        if (is_array($orderby)) {
            $tmporderby = 'ORDER BY ';
            foreach ($orderby as $val) {
                if (substr($val, 0, 1) == '!') {
                    $tmporderby .= $this->quotefield(substr($val, 1)) . ' DESC,';
                } else {
                    $tmporderby .= $this->quotefield($val) . ' ASC,';
                }
            }
            $orderby = substr($tmporderby, 0, - 1);
            unset($tmporderby);
        }
        if (! $groupby) {
            $groupby = "";
        }
        if (is_array($groupby)) {
            $tmporderby = 'GROUP BY ';
            foreach ($groupby as $val) {
                $tmporderby .= $this->quotefield($val) . ',';
            }
            $groupby = substr($tmporderby, 0, - 1);
        }
        if ($condition) {
            $condition = $this->processcondition($condition);
            if (count($condition['bindvalue'])) {
                foreach ($condition['bindvalue'] as $v) {
                    array_push($this->_bindvalue, $v);
                }
            }
            $this->_counsql = "SELECT {$item},count(1) as `count` FROM " . $this->quotetable($table) . $joinstr . ' WHERE ' . $condition['condition'] . ' ' . $groupby;
            $this->_sql = "SELECT {$item} FROM " . $this->quotetable($table) . $joinstr . ' WHERE ' . $condition['condition'] . ' ' . $groupby . ' ' . $orderby . $limit;
        } else {
            $this->_sql = "SELECT {$item} FROM " . $this->quotetable($table) . $joinstr . ' ' . $groupby . ' ' . $orderby . $limit;
            $this->_counsql = "SELECT {$item},count(1) as `count` FROM " . $this->quotetable($table) . $joinstr . ' ' . $groupby;
        }
        $this->_counbindvalue = $this->_bindvalue;
        if (isset($otherinfo['LOCK'])) {
            if ($otherinfo['LOCK'] == self::LOCK_FOR_WRITE) {
                $this->_sql .= ' FOR UPDATE';
            } elseif ($otherinfo['LOCK'] == self::LOCK_FOR_SHARE) {
                $this->_sql .= ' lock in share mode';
            } else {
                $this->_sql .= ' FOR UPDATE';
            }
        }
        return $this;
    }
}
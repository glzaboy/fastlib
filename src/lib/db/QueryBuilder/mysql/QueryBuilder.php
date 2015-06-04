<?php
namespace fl\db\QueryBuilder\mysql;

use fl\db\transaction;

class QueryBuilder extends \fl\db\QueryBuilder
{

    public function __construct($connnect)
    {
        $this->left_delimiter = '`';
        $this->right_delimiter = '`';
        return parent::__construct($connnect);
    }

    public function gettables()
    {
        $tables = array();
        foreach ($this->_connect->query("show tables", array(), false) as $v) {
            array_push($tables, array_pop($v));
        }
        parent::gettables($tables);
        return $tables;
    }

    public function getFileds($table)
    {
        $sql = "SHOW COLUMNS FROM " . $this->left_delimiter . $table . $this->right_delimiter;
        $fileds = array();
        foreach ($this->_connect->query($sql, array(), false) as $v) {
            array_push($fileds, $v['Field']);
        }
        return $fileds;
    }

    public function insert($table, $data = array())
    {
        $field_list = "";
        $value_list = "";
        $bindvalue = array();
        foreach ($data as $k => $v) {
            $field_list .= $this->left_delimiter . $k . $this->right_delimiter . ',';
            $value_list .= '?,';
            array_push($bindvalue, $v);
        }
        $field_list = substr($field_list, 0, - 1);
        $value_list = substr($value_list, 0, - 1);
        $sql = 'INSERT INTO ' . $this->left_delimiter . $this->getprefix() . $table . $this->right_delimiter . ' (' . $field_list . ') VALUES (' . $value_list . ');';
        $affected = $this->_connect->exec($sql, $bindvalue, true);
        if ($affected === false) {
            return false;
        }
        $lastInsertId = $this->_connect->getconnect(true)->lastInsertId();
        return $lastInsertId ? $lastInsertId : $affected;
    }

    public function update($table, $data = array(), $condition = null)
    {
        $field_set = "";
        $bindvalue = array();
        foreach ($data as $k => $v) {
            $field_set .= $this->left_delimiter . $k . $this->right_delimiter . '=?,';
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
            $sql = 'UPDATE ' . $this->left_delimiter . $this->getprefix() . $table . $this->right_delimiter . ' SET ' . $field_set . ' WHERE ' . $condition['condition'];
        } else {
            $sql = 'UPDATE ' . $this->left_delimiter . $this->getprefix() . $table . $this->right_delimiter . ' SET ' . $field_set;
        }
        $affected = $this->_connect->exec($sql, $bindvalue, true);
        if ($affected === false) {
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
            $sql = 'DELETE FROM ' . $this->left_delimiter . $this->getprefix() . $table . $this->right_delimiter . ' WHERE ' . $condition['condition'];
        } else {
            $sql = 'DELETE FROM ' . $this->left_delimiter . $this->getprefix() . $table . $this->right_delimiter;
        }
        $affected = $this->_connect->exec($sql, $bindvalue, true);
        if ($affected === false) {
            return false;
        }
        return $affected;
    }

    public function select($table, $condition = null, $item = "*", $orderby, $groupby, $join = array())
    {
        $bindvalue = array();
        if ($item == "") {
            $item = "*";
        }
        if (is_array($item)) {
            $item = @implode(" , ", $item);
        }
        $joinstr = "";
        if (is_array($join)) {
            foreach ($join as $key => $value) {
                list ($jointable, $as) = explode(" ", $key);
                if ($jointable && $as) {
                    $tablesql = $this->left_delimiter . $this->getprefix() . $jointable . $this->right_delimiter . ' AS ' . $this->left_delimiter . $as . $this->right_delimiter;
                } else {
                    $tablesql = $this->left_delimiter . $this->getprefix() . $jointable . $this->right_delimiter;
                }
                if (is_string($value)) {
                    $joinstr .= " LEFT JOIN {$tablesql} ON (" . $value . ') ';
                } else {
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
                    $tmpcondition = $this->processcondition($value);
                    $joinstr .= "{$jointtype} JOIN {$tablesql} ON (" . $tmpcondition['condition'] . ')';
                    foreach ($tmpcondition['bindvalue'] as $v) {
                        array_push($bindvalue, $v);
                    }
                    unset($tmpcondition);
                }
            }
        }
        if ($this->_ilimit != 0) {
            $limit = ($this->_ipage - 1) * $this->_ilimit;
            $limit = " LIMIT $limit,$this->_ilimit";
        } else {
            $limit = '';
        }
        @list ($seltable, $as) = explode(" ", $table);
        if ($seltable && $as) {
            $tablesql = $this->left_delimiter . $this->getprefix() . $seltable . $this->right_delimiter . ' AS ' . $this->left_delimiter . $as . $this->right_delimiter;
        } else {
            $tablesql = $this->left_delimiter . $this->getprefix() . $seltable . $this->right_delimiter;
        }
        if ($condition) {
            $condition = $this->processcondition($condition);
            if (count($condition['bindvalue'])) {
                foreach ($condition['bindvalue'] as $v) {
                    array_push($bindvalue, $v);
                }
            }
            $sql = "SELECT {$item} FROM " . $tablesql . $joinstr . ' WHERE ' . $condition['condition'] . ' ' . $groupby . ' ' . $orderby . $limit;
        } else {
            $sql = "SELECT {$item} FROM " . $tablesql . $joinstr . ' ' . $groupby . ' ' . $orderby . $limit;
        }
        return $this->_connect->query($sql, $bindvalue, $this->_connect->intransaction());
    }

    function count()
    {}
}
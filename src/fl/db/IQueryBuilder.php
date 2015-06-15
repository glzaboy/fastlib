<?php
namespace fl\db;

Interface IQueryBuilder
{

    /**
     * 获取数据库表前缀
     *
     * @return string
     */
    public function getprefix();

    /**
     * 启用数据库前缀
     *
     * @return boolean
     */
    public function enableprefix();

    /**
     * 禁用数据表前缀
     *
     * @return boolean
     */
    public function disableprefix();

    /**
     * 取数据库表
     *
     * @return array;
     */
    public function gettables();

    /**
     * 取数据表字段
     *
     * @return array;
     */
    public function getFileds($table);

    /**
     * 取数据表字段
     *
     * @return array;
     */
    public function insert($table, $data = array());

    /**
     * 取数据表字段
     *
     * @return array;
     */
    public function update($table, $data = array(), $condition = null);

    /**
     * 删除数据库表记录
     *
     * @param string $table
     *            数据库表名
     * @param string $condition            
     */
    public function delete($table, $condition = null);

    /**
     * 选择数据库记录
     * 
     * @param $table 表名            
     * @param $condition 条件            
     * @param $item 字段            
     * @param $orderby 排序            
     * @param $groupby 分组            
     * @param $join 联表            
     * @return \PDOStatement
     */
    public function select($table, $condition = null, $item = "*", $orderby, $groupby, $join = array());
}
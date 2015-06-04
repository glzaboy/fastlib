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
     * 取数据表字段
     *
     * @return array;
     */
    /**
     * 删除数据库表记录
     *
     * @param string $table
     *            数据库表名
     * @param string $condition            
     */
    public function delete($table, $condition = null);
}
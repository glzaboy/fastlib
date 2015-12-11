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
     * 设置数据分页
     *
     * @param number $ilimit
     *            限制返回条数
     * @param number $ipage
     *            数据页码
     */
    public function setpager($ilimit = 10, $ipage = 1);

    /**
     * 选择数据库记录
     *
     * @param string $table
     *            表名
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
     * @return QueryBuilder
     */
    public function prepareselect($table, $condition = null, $item = "*", $orderby = array(), $groupby = array(), $join = array(), $otherinfo = array());

    /**
     * 查询数据
     *
     * @return \PDOStatement|false
     */
    public function select();

    /**
     * 获取返回数据
     *
     * @return array
     */
    public function selectdata();

    /**
     * 返回select 不还limit条件条数
     *
     * @return int
     */
    public function selectcount();

    /**
     * 返回链接
     * 
     * @return \fl\db\Iconnect
     */
    public function getconnect();
}
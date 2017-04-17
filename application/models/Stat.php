<?php
/**
 * 
 * @package Model/CronJob
 * @author zhouchang 
 * @since 2014-12-01
 * 
 */
class StatModel extends ControlMysql {
	const CLASS_NAME = __CLASS__;
    const TABLE_NAME = "crontab_stat";
    const DB_CONF = "db_crontab_conf";
    /**
     * 构造函数
     *
     * @param boolean $debug=false
     */
    public function __construct($debug = false) {
        $db_conf = Mif_Registry::get(self::DB_CONF);
        parent::__construct ( $db_conf, self::TABLE_NAME, $debug );
    }
	/**
	 * 向当前表增加数据条目
	 * 其中$data数组的key为field名，value为相应字段插入的值
	 * 支持ON DUMPLICATE 子句，该参数与$data数组结构相同，默认值为null
	 * 返回结果为插入条目last_id
	 *
	 * @param array $data        	
	 * @param array $duplicate=null        	
	 * @return string
	 */
	public function add($data, $duplicate = null) {
		return parent::add ( $data, $duplicate );
	}
	/**
	 * 向当前表批量增加条目
	 * $data_arr为二维数组
	 * 其中每维数组的key为field名，value为相应字段插入的值
	 * $duplicate 参数结构与add()方法中的相同
	 *
	 * @param array[] $data        	
	 * @param array $duplicate=null        	
	 * @return mix
	 */
	public function multiAdd($data_arr, $duplicate = null) {
		return parent::multiAdd ( $data_arr, $duplicate );
	}
	/**
	 * 按条件获取当前操作表的指定条数
	 *
	 * @param int $count=10        	
	 * @param int $page=1        	
	 * @param mix $fields='*'        	
	 * @param array $where_condition=null;        	
	 * @param array $order_by=null;        	
	 * @param array $group_by=null;        	
	 * @return array
	 */
	public function getList($count = 10, $page = 0, $fields = '*', $where_condition = null, $order_by = null, $group_by = null) {
		$result = parent::getList ( $count, $page, $fields, $where_condition, $order_by, $group_by );
		return $result;
	}
	/**
	 * 通过主键id获取一条数据
	 * 如果主键是联合主键需要使用key=>value的数组传参，单独一个主键则只传key_id的值即可
	 *
	 * @param array|string $data        	
	 * @param array|string $fields='*'        	
	 * @return mix
	 */
	public function getByKey($key_id, $fields = '*') {
		$result = parent::getByKey ( $key_id, $fields );
		return $result;
	}
	/**
	 * 按条件移除当前操作表的指定条目
	 *
	 * @param array $where_condition        	
	 * @param int $count=-1        	
	 * @param array $order_by=null        	
	 * @return int
	 */
	public function remove($where_condition, $count = -1, $order_by = null) {
		return parent::remove ( $where_condition, $count, $order_by );
	}
	/**
	 * 按主键删除一条信息
	 * 单独一个主键则只传key_id的值即可
	 * 如果主键是联合主键需要使用key=>value的数组传参
	 *
	 * @param mix $key_id        	
	 * @return int
	 */
	public function removeByKey($key_id) {
		return parent::removeByKey ( $key_id );
	}
	/**
	 * 按条件更新当前操作表的指定条目
	 *
	 * @param array $set_arr        	
	 * @param array $where_condition=null        	
	 * @param int $count=-1        	
	 * @param array $order_by=null        	
	 * @return int
	 */
	public function update($set_arr, $where_condition = null, $count = -1, $order_by = null) {
		return parent::update ( $set_arr, $where_condition, $count, $order_by );
	}
	/**
	 * 根据条件统计条目数
	 *
	 * @param string $where_condition=null        	
	 * @return int
	 */
	public function count($where_condition = null) {
		$result = parent::count ( $where_condition );
		return $result;
	}

}

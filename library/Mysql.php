me: Mysql.php Description:	mysql数据库操作公共底层 Created by: liukang1 Project :
 * 微音乐资料库 Create Date: 2013-01-25 @author kunjuan <kunjuan@staff.sina.com.cn>
 */
class Mysql {
	private $db;
	private $sql_str; // sql语句
	private $last_insert_id; // 最后的插库自增id
	private $affected_rows; // 影响条数
	private $db_conf; // 操作的是哪个数据库
	private $master_flag = false; // 如果为true,则所有操作都走主库
	static $instance = array ();
	static $default_conf = array (
			'master' => array (
					'host' => 'localhost',
					'port' => 3306,
					'username' => 'root',
					'password' => 'root',
					'dbname' => 'musiclib',
					'charset' => "UTF8" 
			),
			'slave' => array (
					'host' => 'localhost',
					'port' => 3306,
					'username' => 'root',
					'password' => 'root',
					'dbname' => 'musiclib',
					'charset' => "UTF8" 
			) 
	);
	
	/**
	 * 构造函数
	 */
	function __construct($db_conf, $master_flag = false) {
		if (empty ( $db_conf )) {
			$this->db_conf = self::$default_conf;
		} else {
			$this->db_conf = $db_conf;
		}
		
		$this->master_flag = $master_flag;
	}
	
	/**
	 * 数据库连接
	 */
	private function mysqlConnect($db_conf, $master_flag = false) {
		$mysql_conf = $db_conf;
		$db_conf = $db_conf ['master'] ['dbname'];
		if (empty ( $mysql_conf )) {
			return false;
		}
		if ($master_flag) {
			$tdb = $mysql_conf ['master'];
			$master_or_slave = 'master';
		} else {
			$tdb = $mysql_conf ['slave'];
			$master_or_slave = 'slave';
		}
		if (empty ( self::$instance [$db_conf] [$master_or_slave] ) || ! @mysql_ping ( self::$instance [$db_conf] [$master_flag] )) {
			$dsn = "mysql:host=" . $tdb ['host'] . ";port=" . $tdb ['port'] . ";dbname=" . $tdb ['dbname'];
			self::$instance [$db_conf] [$master_or_slave] = new PDO ( $dsn, $tdb ['username'], $tdb ['password'] );
		}
		
		$this->db = self::$instance [$db_conf] [$master_or_slave];
		if (isset ( $mysql_conf [$master_or_slave] ['charset'] )) {
			$sql = "set names " . $mysql_conf [$master_or_slave] ['charset'];
			$this->db->query ( $sql );
		}
		return self::$instance [$db_conf] [$master_or_slave];
	}
	
	/**
	 * 执行SQL，并返回结果
	 */
	private function query() {
		$args = func_get_args ();
		$sql = array_shift ( $args );
		
		// 查询数据库
		if ($args) {
			$query = $this->db->prepare ( $sql );
			$query->execute ( $args );
		} else {
			$query = $this->db->query ( $sql );
		}
		
		if (! $query) {
			return false;
		}
		return $query->fetchAll ( PDO::FETCH_ASSOC );
	}
	
	/**
	 * 执行SQL
	 */
	private function execute($pSql) {
		$this->affected_rows = $this->db->exec ( $pSql );
		return $this->affected_rows;
	}
	
	/**
	 * performQuery($sql_str)对所有的操作进行分析并执行这个操作
	 * 包括delete/insert/update/select操作
	 */
	public function performQuery($sql_str) {
		$this->sql_str = trim ( $sql_str );
		$temp = preg_split ( "/\\s+/", trim ( $this->sql_str ) );
		$sub_sql = count ( $temp ) > 0 ? strtolower ( $temp [0] ) : "";
		switch (strtolower ( $sub_sql )) {
			case "select" :
			case "show" :
				$connection = $this->mysqlConnect ( $this->db_conf, $this->master_flag );
				
				return $this->query ( $this->sql_str );
				break;
			case "delete" :
			case "update" :
			case "truncate" :
			case "replace" :
			case "alter" :
			case "drop" :
			case "create" :
				$connection = $this->mysqlConnect ( $this->db_conf, true );
				$retval = $this->execute ( $this->sql_str );
				return $retval;
				break;
			case "insert" :
				$connection = $this->mysqlConnect ( $this->db_conf, true );
				$retval = $this->execute ( $this->sql_str );
				$this->last_insert_id = $this->db->lastInsertId ();
				return $retval;
				break;
			default :
				return false;
		}
	}
	/**
	 * 取最后插入的一个条目的id
	 *
	 * @return int
	 */
	public function getLastInsertId() {
		return $this->last_insert_id;
	}
	/**
	 * 取sql影响的条目
	 *
	 * @return int
	 */
	public function getAffectedRows() {
		return $this->affected_rows;
	}
	/**
	 * 开始一个事务
	 */
	public function beginTransaction() {
		$connection = $this->mysqlConnect ( $this->db_conf, $this->master_flag );
		$this->query ( "begin" );
	}
	/**
	 * 提交事务
	 */
	public function commit() {
		$this->query ( 'commit' );
		$this->query ( 'end' );
	}
	/**
	 * 事务回滚
	 */
	public function rollback() {
		$this->query ( 'rollback' );
		$this->query ( 'end' );
	}
	/**
	 * 返回pdo的错误信息
	 *
	 * @return array
	 */
	public function getError() {
		return $this->db->errorInfo ();
	}
}


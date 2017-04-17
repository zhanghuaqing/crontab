<?php
/**
 * 调试类
 *
 * setDebug()方法可以设置是否开启debug模式，getDebug()获取debug模式信息
 * 浏览器合适增加get类型的参数debug也可控制debug模式
 *
 * TO DO: 按ERROR || DEBUG 级别记录日志
 */
class Debug
{
    const CLASS_NAME = __CLASS__;
    const FILE_NAME_LOG = "debug_error";
    private static $_debug = false;
    private static $_force_debug = false;
    private static $_error_message = null;
    const ERROR_SUCCESS = 0;
    const ERROR_ERROR_PARAMS = 2;
    const ERROR_MYSQL_ERROR = 200;
    const ERROR_MYSQL_CONNECT_FAULT = 201;
    const ERROR_REDIS_ERROR = 300;
    const ERROR_CURL_ERROR = 400;
    public static $_ERROR_SET = array(
        self::ERROR_SUCCESS => array(
            'error_code' => self::ERROR_SUCCESS,
            'error_message' => 'success'
        ) ,
        'dont use 1 index' => array() ,
        self::ERROR_ERROR_PARAMS => array(
            'error_code' => self::ERROR_ERROR_PARAMS,
            'error_message' => '参数错误'
        )
    );
    /**
     * 调试输出
     *
     * @param string $data
     * @param bool $is_force=false,是否无视生产机打印调试输出
     * @return null
     */
    public static function debugDump($data) {
        if (!self::getDebug()) {
            return false;
        }
        $debug_info = debug_backtrace();
        $line = '';
        $call = '';
        foreach ($debug_info as $one) {
            if ($one['class'] != self::CLASS_NAME) {
                $call = $one;
                break;
            }
            $line = $one['line'];
        }
        $type = 'DEBUG';
        
        $res = date("Y-m-d H:i:s ") . '[' . $type . '] ' . $call['class'] . $call['type'] . $call['function'] . ' line [' . $line . "] :\n";
        echo $res;
        var_dump($data);
        return $res;
    }
    /**
     * 获取当前DEBUG模式
     *
     * @param bool $is_force=false,是否无视生产机打印调试输出
     */
    public static function getDebug() {
        if (isset($_GET['debug'])){
            return self::$_debug || $_GET['debug'] == 'true';
        }
        return self::$_debug;
    }
    /**
     * 设置当前DEBUG模式
     */
    public static function setDebug($debug) {
        if (!is_bool($debug)) {
            return false;
        }
        self::$_debug = $debug;
        return true;
    }
    /**
     * 无视生产环境强制调试
     *
     * @param boolean $force
     * @return boolean
     */
    public static function setForce($force) {
        if (!is_bool($force)) {
            return false;
        }
        self::$_force_debug = $force;
        return true;
    }
    /**
     * 查看是否强制调试
     *
     * @return boolean
     */
    public static function getForce() {
        return self::$_force_debug;
    }
    /**
     * * 设置错误信息,debug开启模式下会输出错误
     */
    public static function setErrorMessage($err_message_or_code = null) {
        if (!empty(self::$_ERROR_SET[$err_message_or_code])) {
            self::$_error_message = self::$_ERROR_SET[$err_message_or_code];
        } 
        else {
            self::$_error_message = array(
                'error_code' => 1,
                'error_message' => $err_message_or_code
            );
        }
        self::debugDump(self::$_error_message);
        return true;
    }
    /**
     * 获取当前错误信息
     */
    public static function getErrorMessage() {
        return self::$_error_message;
    }
    /**
     * 输出错误信息并退出
     */
    public static function echoErrorMessage($error_message, $is_exit = true) {
        var_dump($error_message);
        $is_exit && exit();
    }
    /**
     * 获取执行命令机器的ip和mac
     */
    private static function getNetworkInterface() {
        $ips = array();
        $info = `/sbin/ifconfig   -a`;
        $infos = explode("\n\n", $info);
        foreach ($infos as $info) {
            $info = trim($info);
            if (substr($info, 0, 3) == 'eth') {
                $lines = explode("\n", $info);
                $interface = substr($lines[0], 0, strpos($lines[0], '   '));
                $mac = substr($lines[0], strlen($lines[0]) - 19);
                preg_match('/addr:([0-9\.]+)/i', $lines[1], $matches);
                if (!$matches)continue;
                $ip = $matches[1];
                $ips[$interface] = array(
                    'ip' => trim($ip) ,
                    'mac' => trim($mac)
                );
            }
            // end if
            
            
        }
        // end foreach
        return $ips;
    }
	/**
	 * 获取调用方信息
	 */
	private static function getPreMethod() {
		$debug_info = debug_backtrace ();
		$line = '';
		$call = '';
		foreach ( $debug_info as $one ) {
			if ($one ['class'] != self::CLASS_NAME) {
				$call = $one;
				break;
			}
			$line = $one ['line'];
		}
		return $call;
	}
}

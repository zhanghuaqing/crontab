<?php
/**
 * 资源管理日志类
 * @author zhouchang@staff.weibo.com
 */
class LogModel {

    private $_resource_log = null;
    private $_resource_path = '';
    private $_current_date = '';
    private $_current_hour = '';
    private $_mac = '';

    public function __construct($path = '') {
        $log_path = Mif_Registry::get('log_path');
        if (empty($log_path)){
            throw new Exception('没有配置日志路径,请在' . APP_PATH . 'microframe/config.php 文件里配置');
        }
        $this->_resource_path = $path ? $path : $log_path;
    }

    //资源日志记录开始

    /**
     * writeResourceLog 
     * 记录一条资源日志
     * @param $pid_info     ps -o 得到的资源信息 
     * @param $job_id       任务ID
     * @access public
     * @return void
     */
    public function writeResourceLog($pid_info, $job_id) {
        if (is_null($this->_resource_log)) {
            $this->initResourceLog();
        }    
        $this->_isNewLog();
        $mac = $this->_mac ? $this->_mac : $this->_getMac();
        $job_id = $job_id >= 0 ? $job_id : "NONE";
        $line = implode("\t", array(date('Y-m-d H:i:s'), $job_id, $pid_info['PID'], $mac, json_encode($pid_info))) . "\n";
        @fwrite($this->_resource_log, $line);
    }

    /**
     * initResourceLog 
     * 初始化资源日志
     * @access public
     * @return void
     */
    public function initResourceLog() {
        $path = $this->_resource_path;
        $this->_current_date = date('Ymd');
        $this->_current_hour = date('H');
        $path = $path.$this->_current_date."/";
        if (!is_dir($path)) {
            mkdir($path, 0755);
        }
        $mac = $this->_mac ? $this->_mac : $this->_getMac();
        $log_path = $path.$this->_current_hour."_".$mac."_log";
        $this->_resource_log = fopen($log_path, "a");
    }
    /**
     * 获取服务器mac地址
     * @return Ambigous <string, multitype:Ambigous <string, unknown> >
     */
    private function _getMac() {
        $info = PublicModel::getServerEthInfo();
        $mac = 'UNKNOWN';
        if ($info){
            foreach ($info as $eth => $one){
                $mac = $one ['mac'];
                break;
            }
        }
        return $mac;
    }

    /**
     * closeResourceLog 
     * 关闭资源日志
     * @access public
     * @return void
     */
    public function closeResourceLog() {
        if ($this->_resource_log) {
            return fclose($this->_resource_log);
        }
    }

    /**
     * _isNewLog 
     * 新的日志文件
     * @access private
     * @return void
     */
    private function _isNewLog() {
        if ($this->_current_date !== date('Ymd') || $this->_current_hour !== date('H')) {
            $this->closeResourceLog();
            $this->initResourceLog();
            return true;
        }
        return false;
    }

    //资源日志记录结束

}
?>

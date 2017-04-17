<?php
/**
 * 任务管理
 * @author zhiyuan12@staff.weibo.com
 */
class  WorkManageModel extends ProcessManage {
    private $_file_joblist = 'joblist_%s.txt';//每分钟更新一次
	private static $jobinfo_list = array();
    public function __construct() {
        parent::__construct();
    }
    /**
     * 开启所有cron任务,一分钟启动一次
     */
    public function cronStartCronJob(){
        $now_time = strtotime(date('Y-m-d H:i:00'));//$_REQUEST ['REQUEST_TIME'];
        $cur_serverlist =  PublicModel::getServerEthInfo();
        if (empty($cur_serverlist)){
            echo "获取服务器网卡信息失败!\n";
            return false;
        }
        $cur_mac = '';
        foreach ($cur_serverlist as $info){
            $cur_mac = $info ['mac'];
            break;
        }
        
        //读文件获取需要执行的所有cron任务列表
        $cronjob_obj = new  JobManageModel();
        $job_list = array();
        $file = $this->_getJobListFile($cur_mac);
        if (!file_exists($file)){//如果不存在,直接生成文件
            $this->cronWriteJobListFromDb();
            //sleep(1);
        }
        //检查文件固定时间内是否更新 
        $mtime = filemtime($file);
        $joblist_file_utime_gap = Mif_Registry::get('joblist_file_utime_gap');
        if (empty($joblist_file_utime_gap) || !is_numeric($joblist_file_utime_gap)){
            $joblist_file_utime_gap = 600;//默认600s
        }
        if (time () - $mtime >=  $joblist_file_utime_gap){
            $mail_content = '当前服务器:' . $cur_mac . " 的cronjob任务列表文件上次更新时间: <br>";
            $mail_content .= date('Y-m-d H:i:s', $mtime);
            PublicModel::sendWarnMail($mail_content, 'crontab任务列表文件更新时间报警');
            echo $mail_content . "\n";
            //更新一遍文件
            $this->cronWriteJobListFromDb();
            return false;
        }
        
        $fp = fopen($file, 'r');
        while (!feof($fp)){
            $line = fgets($fp);
            //echo $line . "\n";
            $job_info = json_decode($line, true);
            $job_id = $job_info ['id'];
            if (empty($job_id))continue;
            if ($job_info ['status'] != JobManageModel::ONLINE)continue;
            //判断当前服务器ip是否可执行该任务
            if(!$this->_checkMacCanExec($cur_mac, $job_info ['select_serverlist'], $job_info ['exec_server']))continue;
            
            $cron_time = $job_info ['cron_time'];
            $is_exec = self::parseCronTime($cron_time, $now_time);
            if ( !$is_exec ) continue;
            if (in_array($job_id, $job_list))continue;
            $job_list [] = $job_id;
            self::$jobinfo_list [$job_id] = $job_info;           
        }
        fclose($fp);
        
        //开启多进程
        if ($job_list) {
            echo "开启的任务ID列表:\n";var_dump($job_list);
        	$this->addJobIdList($job_list);
        	$this->run();
        	var_dump($this->getJobExecInfo());
        }else{
        	echo "当前没有可启动的cron任务\n";
        }
        
    }
    /**
     * 从数据库读取crontab，写入文件
     * @return boolean
     */
    public function cronWriteJobListFromDb(){
        $file = $this->_getJobListFile();
        if ($file === false){
            return false;
        }
        
        //获取需要执行的所有cron任务列表
        $cronjob_obj = new  JobManageModel();
        
        $status =  JobManageModel::ONLINE;
        $field = '*';
        $job_list_tmp = $cronjob_obj->getJobList(-1, -1, $status, $field);
        $line = '';
        foreach ($job_list_tmp as $job_info){
            $line .= json_encode($job_info) . "\n";
        }
        if ($job_list_tmp !== false){
            $line = trim($line, "\n");
            file_put_contents($file, $line);
        }
    }
    /**
     * 杀掉进程
     */
    public function cronKillProcess(){
        //获取需要执行的所有cron任务列表
        $cronjob_obj = new  JobModel();
        $cronjob_manage_obj = new  JobManageModel();
        $field = '*';
        $where = array(
            array('field' => 'is_kill', 'condition' => 1),
        );
        while (true){
            $job_list_tmp = $cronjob_obj->getList(-1, -1, $field, $where);
            if (empty($job_list_tmp))break;
            foreach ($job_list_tmp as $job_info){
                $exec_cmd = $job_info ['cron_cmd'];
                if (strstr($exec_cmd, ">")){
                    $pattern = '/(.*?)\s+[\d|&]*>/';
                    preg_match($pattern, $exec_cmd, $match);
                    $exec_cmd_sh = $match [1];
                }else{
                    $exec_cmd_sh = $exec_cmd;
                }
                if (empty($exec_cmd_sh))continue;
                $cmd_str = "pa aux|grep '$exec_cmd_sh' |grep -v grep | grep -v 'sh -c' | awk -F\" \" '{print $2}' ";
                $process_ids = shell_exec($cmd_str);
                if (empty($process_ids))continue;
                $children_pidlist_str = str_replace("\n", ' ', $process_ids);
                shell_exec("kill -9 $process_ids");
                //更新is_kill值
                $data = array('is_kill' => 0);
                $flag = $cronjob_manage_obj->updateJobInfo($job_info ['id'], $data);
                $flag = $flag === false ? '失败' :'成功'; 
                echo date('Y-m-d H:i:s') . "\t" . $job_info ['id'] ."\t" .$children_pidlist_str ."\t" . $flag . "\n";
            }
            sleep(2);
        }
        return true;
    }
    private function _getJobListFile($cur_mac = ''){
        if (empty($cur_mac)){
            $cur_serverlist =  PublicModel::getServerEthInfo();
            if (empty($cur_serverlist)){
                echo "获取服务器网卡信息失败!\n";
                return false;
            }
            $cur_mac = '';
            foreach ($cur_serverlist as $info ){
                $cur_mac = $info ['mac'];
                break;
            }
        }
        $log_path = Mif_Registry::get('log_path');
        if (empty($log_path)){
            throw new Exception('没有配置日志路径,请在' . APP_PATH . 'microframe/config.php 文件里配置');
        }
        $file =  $log_path . $this->_file_joblist;
        $file = sprintf($file, $cur_mac);
        return $file;
    }
    /**
     * 判断该mac地址对应的服务器是否可以执行
     * @param unknown $mac
     * @param unknown $select_serverlist
     * @param unknown $exec_server
     * @return boolean
     */
    private function _checkMacCanExec($mac, $select_serverlist, $exec_server){
        $jobserver_obj = new JobServerManageModel();
        $server_info = $jobserver_obj->getServerByMac($mac);
        $mac_server_id = $server_info ? $server_info ['id'] : '';
        
        if ($select_serverlist){//部分服务器
            $select_serverlist_arr = explode(",", $select_serverlist);
            if (!in_array($mac_server_id, $select_serverlist_arr)){
                Debug::setErrorMessage("mac地址 $mac 对应的服务器不在可选执行服务器列表里！");
                return false;
            }
            if ($exec_server){//单台服务器执行
                if ($mac_server_id != $exec_server){
                    Debug::setErrorMessage("mac地址 $mac 对应的服务器不是当前执行服务器！");
                    return false;
                }
            }
        }else{
            if ($exec_server){//单台服务器执行
                if ($mac_server_id != $exec_server){
                    Debug::setErrorMessage("mac地址 $mac 对应的服务器不是当前执行服务器！");
                    return false;
                }
            }
        }
        return true;
    }
    /**
     * 实现父类任务进程超时判断
     * @see ProcessManage::timeOutCheck()
     */
    public function timeOutCheck($job_id, $begin_time) {
        $job_info = self::$jobinfo_list [$job_id];
        if ($job_info){
            $max_exectime = $job_info ['max_exectime'];
            $now_time = time();
            if ($max_exectime > 0 && $now_time - $begin_time >= $max_exectime){
                return true;
            }
        }
        return false;
    }
    public function childExec($job_id) {
        $job_info = self::$jobinfo_list [$job_id];
        if (empty($job_info)){
            return 0;
        }
        $cmd = $job_info['cron_cmd'];
        $exec_ret = shell_exec($cmd); 
        return 1;
    }
    /**
     * 更新最近一次进程开始和结束、中断时的信息
     * 
     * @param int $job_id
     * @param array $exec_info 
     *    array(
     *      'begin_time' => xxxx,
     *      'pid' => xxx,
     *      'end_time' => xxx
     *    )
     */
    public function updateLastProExecInfo($job_id, $exec_info){
        $update_data = array();
        if (isset($exec_info ['begin_time'])){
        	$update_data ['lastpro_st'] = $exec_info ['begin_time'];
        	$update_data ['lastpro_et'] = '0000-00-00 00:00:00';
        }
        if (isset($exec_info ['pid'])){
        	$update_data ['lastpro_pid'] = $exec_info ['pid'];
        }
        if (isset($exec_info ['exec_server'])){
        	$update_data ['lastpro_server'] = is_numeric($exec_info ['exec_server'])? $exec_info ['exec_server'] :0;
        }
        if (isset($exec_info ['end_time']) && !empty($exec_info ['end_time'])){
           $update_data ['lastpro_et'] = $exec_info ['end_time'];
        }
        $jm_obj = SingletonManager::$SINGLETON_POOL->getInstance('JobManageModel');//new  JobManageModel();
        return $jm_obj->updateJobInfo($job_id, $update_data);
    }
    /**
     * Finds next execution time(stamp) parsin crontab syntax,
     * after given starting timestamp (or current time if ommited)
     *
     * @param string $_cron_string:
     *
     * 0 1 2 3 4
     * * * * * *
     * - - - - -
     * | | | | |
     * | | | | +----- day of week (0 - 6) (Sunday=0)
     * | | | +------- month (1 - 12)
     * | | +--------- day of month (1 - 31)
     * | +----------- hour (0 - 23)
     * +------------- min (0 - 59)
     * @param int $_after_timestamp timestamp [default=current timestamp]
     * @return int unix timestamp - next execution time will be greater
     * than given timestamp (defaults to the current timestamp)
     * @throws InvalidArgumentException
     */
    public static function parseCronTime($_cron_string,$_timestamp=null)
    {
        if(! PublicModel::checkCronTime($_cron_string)){
            //throw new InvalidArgumentException("Invalid cron string: ".$_cron_string);
            Debug::setDebug('crontime格式有误');
            return false;
        }
        if($_timestamp && !is_numeric($_timestamp)){
        	Debug::setDebug('当前时间戳不是有效数字');
        	return false;
            //throw new InvalidArgumentException("\$_after_timestamp must be a valid unix timestamp ($_after_timestamp given)");
        }
        $cron = preg_split("/[\s]+/i",trim($_cron_string));
        $start = empty($_timestamp)?time():$_timestamp;
    
        $date = array( 'minutes' =>self::_parseCronNumbers($cron[0],0,59),
            'hours' =>self::_parseCronNumbers($cron[1],0,23),
            'dom' =>self::_parseCronNumbers($cron[2],1,31),
            'month' =>self::_parseCronNumbers($cron[3],1,12),
            'dow' =>self::_parseCronNumbers($cron[4],0,6),
        );
        // limited to time()+366 - no need to check more than 1year ahead
        $i = 0;
        if( in_array(intval(date('j',$start+$i)),$date['dom']) &&
        	in_array(intval(date('n',$start+$i)),$date['month']) &&
        	in_array(intval(date('w',$start+$i)),$date['dow']) &&
        	in_array(intval(date('G',$start+$i)),$date['hours']) &&
        	in_array(intval(date('i',$start+$i)),$date['minutes'])
        ){
        	return true;
        }
        return false;
    }
    
    /**
     * get a single cron style notation and parse it into numeric value
     *
     * @param string $s cron string element
     * @param int $min minimum possible value
     * @param int $max maximum possible value
     * @return int parsed number
     */
    public static function _parseCronNumbers($s,$min,$max)
    {
        $result = array();
    
        $v = explode(',',$s);
        foreach($v as $vv){
            $vvv = explode('/',$vv);
            $step = empty($vvv[1])?1:$vvv[1];
            $vvvv = explode('-',$vvv[0]);
            $_min = count($vvvv)==2?$vvvv[0]:($vvv[0]=='*'?$min:$vvv[0]);
            $_max = count($vvvv)==2?$vvvv[1]:($vvv[0]=='*'?$max:$vvv[0]);
    
            for($i=$_min;$i<=$_max;$i+=$step){
                $result[$i]=intval($i);
            }
        }
        ksort($result);
        return $result;
    }
}

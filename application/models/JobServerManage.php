<?php 
/**
 * job服务器列表操作
 * 每台机器通过mac地址唯一标识,如果多个网卡，则默认以第一个网卡作为标识
 * @author huaqing1
 *
 */
class JobServerManageModel{
    //服务器状态任务id
    const TASK_ID = 0; //默认为0， 用于复用cronjob_stat表 
    //在线状态值
    const TO_ONLINE = 0;//未上线
    const ONLINE = 1;//在线
    const OFFLINE = 2;//下线
    public static $STATUS_CONF = array(
        self::TO_ONLINE => array(
            'code' => self::TO_ONLINE,
            'name' => '未上线'
        ),
        self::ONLINE => array(
            'code' => self::ONLINE,
            'name' => '在线'
        ),
        self::OFFLINE => array(
            'code' => self::OFFLINE,
            'name' => '下线'
        ),
    );
    
    //服务器状态值配置
    const WORK_TIME = 'work_time'; //系统运行时间
    const LOAD_AVERAGE = 'load_average'; //平均负载
    const TASK_TOTAL = 'task_total';//当前总进程数
    const TASK_RUNNING = 'task_running';//当前正运行进程数 
    const TASK_SLEEPING = 'task_sleeping';//当前睡眠进程数    
    const TASK_STOPPED = 'task_stopped';//当前停止进程数    
    const TASK_ZOMBIE = 'task_zombie';//当前僵死进程数     
    const CPU_US = 'cpu_us';//用户空间占用CPU百分比      
    const CPU_SY = 'cpu_sy';//内核空间占用CPU百分比    
    const CPU_NI = 'cpu_ni';//用户进程空间内改变过优先级的进程占用CPU百分比     
    const CPU_ID = 'cpu_id';//空闲CPU百分比
    const MEM_TOTAL = 'mem_total';// 物理内存总量
    const MEM_USED = 'mem_used';// 使用的物理内存总量
    const MEM_FREE = 'mem_free';// 空闲内存总量
    const MEM_BUFFERS = 'mem_buffers';// 用作内核缓存的内存量
    const SWAP_TOTAL = 'swap_total';// 交换区总量
    const SWAP_USED = 'swap_used';// 使用的交换区总量
    const SWAP_FREE = 'swap_free';// 空闲交换区总量
    const SWAP_CACHED = 'swap_cached';// 缓冲的交换区总量
    const IFACE_RXOK = 'iface_rxok';// 网卡正确接收数据包的数量
    const IFACE_RXERR = 'iface_rxerr';// 网卡接收数据包发生错误的数量
    const IFACE_RXDRP = 'iface_rxdrp';// 网卡接收数据包流失的数量
    const IFACE_TXOK = 'iface_txok';// 网卡正确发送数据包的数量
    const IFACE_TXERR = 'iface_txerr';// 网卡发送数据包发生错误的数量
    const IFACE_TXDRP = 'iface_txdrp';// 网卡发送数据包流失的数量
    const DISK_INFO = 'disk_info';// 磁盘占用情况
    
    public static $server_fieldconf = array(
        self::WORK_TIME => array(
            'name' => '系统运行时间',
        ),
        self::LOAD_AVERAGE => array(
            'name' => '平均负载',
        ),
        self::TASK_TOTAL => array(
            'name' => '当前总进程数',
        ),
        self::TASK_RUNNING => array(
            'name' => '当前正运行进程数',
        ),
        self::TASK_STOPPED => array(
            'name' => '当前停止进程数',
        ),
        self::TASK_ZOMBIE => array(
            'name' => '当前僵死进程数',
        ),
        self::CPU_US => array(
            'name' => '用户空间占用CPU百分比',
        ),
        self::CPU_SY => array(
            'name' => '内核空间占用CPU百分比',
        ),
        self::CPU_NI => array(
            'name' => '用户进程空间内改变过优先级的进程占用CPU百分比',
        ),
        self::CPU_ID => array(
            'name' => '空闲CPU百分比',
        ),
        self::MEM_TOTAL => array(
            'name' => '物理内存总量',
        ),
        self::MEM_USED => array(
            'name' => '使用的物理内存总量',
        ),
        self::MEM_FREE => array(
            'name' => '空闲内存总量',
        ),
        self::MEM_BUFFERS => array(
            'name' => '用作内核缓存的内存量',
        ),
        self::SWAP_TOTAL => array(
            'name' => '交换区总量',
        ),
        self::SWAP_USED => array(
            'name' => '使用的交换区总量',
        ),
        self::SWAP_FREE => array(
            'name' => '空闲交换区总量',
        ),
        self::SWAP_CACHED => array(
            'name' => '缓冲的交换区总量',
        ),
        self::IFACE_RXOK => array(
            'name' => '网卡正确接收数据包的数量',
        ),
        self::IFACE_RXERR => array(
            'name' => '网卡接收数据包发生错误的数量',
        ),
        self::IFACE_RXDRP => array(
            'name' => '网卡接收数据包流失的数量',
        ),
        self::IFACE_TXOK => array(
            'name' => '网卡正确发送数据包的数量',
        ),
        self::IFACE_TXERR => array(
            'name' => '网卡发送数据包发生错误的数量',
        ),
        self::IFACE_TXDRP => array(
            'name' => '网卡发送数据包流失的数量',
        ),
        self::DISK_INFO => array(
            'name' => '磁盘占用情况'
        ),
    );
    public static $todatabase_field_list = array(
        self::TASK_RUNNING,
        self::CPU_US,
        self::CPU_ID,
        self::MEM_USED,
        self::MEM_FREE,
        self::MEM_BUFFERS,
        self::SWAP_CACHED,
        self::IFACE_RXOK,
        self::IFACE_RXERR,
        self::IFACE_TXOK,
        self::IFACE_TXERR
    );
    
    /**
     * 定时获取服务器负载状态信息
     */
    public function cronGetServerStatinfo(){
        $cur_serveriplist = PublicModel::getServerEthInfo();
        if (empty($cur_serveriplist)){
            echo '获取服务器网卡信息失败!' . "\n";
            return false;
        }
        //判断当前服务器是不是在服务器列表里,自动增加,有的话更新IP
        foreach ($cur_serveriplist as $eth => $info){
            $ip = $info ['ip'];
            $mac = $info ['mac'];
            $data = array(
                'ip' => $ip,
                'mac' => $mac,
                'desc' => $eth . "\t" . $ip,
                'status' => self::ONLINE
            );
            $insert_id = ($this->addServer($data));
            break;//默认只写第一个ip
        }

        //当前服务器状态信息获取,并写日志,每隔一秒获取一次，返回数组
        $server_statinfo_list = self::getServerStatInfo();
        if ($server_statinfo_list)$server_statinfo = $server_statinfo_list [count($server_statinfo_list) - 1];
        //添加磁盘使用情况信息
        $disk_info = self::getDiskInfo();
        $server_statinfo [self::DISK_INFO] = $disk_info;
        return $this->updateLastStatInfo($mac, json_encode($server_statinfo));
    }
    /**
     * 监控服务器状态,目前只监控load_average,cpu_id
     * 每5分钟监控一次
     */
    public function cronMonitorServerStatus(){
    	//监控指标
    	$monitor_field_list = array(
    	    self::LOAD_AVERAGE => array(
    	        'max' => '40','min' => '0'
    	    ),
    	    self::CPU_ID => array(
    	        'max' => '100','min' => '10'
    	    ),
    	);
    	$max_gap_time = 5;//分钟
    	$status_count = 5; //记录数
    	
    	$now_time = time();
    	$all_server = $this->getAllServer();
    	//获取所有服务器最后五分钟的状态，如果没有状态，则报警
    	$cronjob_stat_obj  = new StatModel();
    	$mail_content = '';
    	$subject = 'crontab服务器状态报警邮件';
    	$is_need_warn = false;
    	$list = array();
    	$order = array('create_time' =>'desc');
    	foreach ($all_server as $server_id){
    	    $where = array(
    	    				array('field' => 'job_id', 'condition' => self::TASK_ID),
    	    				array('field' => 'pid', 'condition' => 1),
    	    				array('field' => 'server', 'condition' => $server_id),
    	    );
    	    $list_tmp = $cronjob_stat_obj->getList($status_count, 0, '*', $where, $order);
    	    if (empty($list_tmp)) {
    	        $mail_content .= "服务器 $server_id:读表获取状态失败<br>";
    	        continue;
    	    }
    	    $list = array_merge($list, $list_tmp);
    	    //判断最近5分钟内有没有更新
    	    if ($list_tmp [0]['create_time'] < date('Y-m-d H:i:s', time() - $max_gap_time * 60)) {
    	        $mail_content .= "服务器$server_id 最近一次状态更新时间 :" . $list_tmp [0]['create_time'] . "<br>";
    	    }
    	    $monitor_server_excessivenum = array();
    	    $count = count($list_tmp);
    	    foreach ($list_tmp as $one){
    	        $info = json_decode($one ['info'], true);
    	        if ($info){
    	            foreach ($info as $field => $value){
    	                if (isset($monitor_field_list [$field])){
    	                    if ($value < $monitor_field_list [$field]['min']  || $value > $monitor_field_list[$field]['max'] ){
    	                        $monitor_server_excessivenum[$field]  ++;
    	                    }
    	                }
    	            }
    	        }
    	    }
    	    foreach ($monitor_server_excessivenum as $field => $num){
    	        if ($num + 1 >= $count){
    	            $mail_content .= "服务器 $server_id:$field 超标<br>";
    	            $is_need_warn = true;
    	        }
    	    }
    	}
    	 
    	//整理输出报警内容
    	if ($is_need_warn || $mail_content){
    	    $mail_content .= '<table border="1"><tr><td>服务器ip<td>服务器mac</td><td>记录时间</td>';
    	    foreach ($monitor_field_list as $key => $one){
    	        $mail_content .= '<td>' . $key . '</td>';
    	    }
    	    $mail_content .= '</tr>';
    	    foreach ( $list as $one ) {
    	        $server_id = $one ['server'];
    	        $info = json_decode($one ['info'], true);
    	        $server_info = $this->getServer($server_id);
    	        $mail_content .= '<tr><td>' . long2ip($server_info ['ip']) . '</td><td>' . ($server_info ['mac']) . '</td><td>' . $one ['create_time'] . '</td>';
    	        foreach ($info as $field => $value){
    	            if (isset($monitor_field_list [$field])){
    	                //输出
    	                if ($value < $monitor_field_list [$field]['min'] || $value > $monitor_field_list[$field]['max']){
    	                    $value_format = '<span style="color:red;font-size:20;">' . $value . '</span>';
    	                    $mail_content .= '<td>' . $value_format . '</td>';
    	                }else{
    	                    $mail_content .= '<td>' . $value . '</td>';
    	                }
    	            }
    	        }
    	        $mail_content .= '</tr>';
    	    }
    	    $mail_content .= '</table>';
    	    PublicModel::sendWarnMail($mail_content, $subject);
    	}
    	//单独监控磁盘占用情况
    	$server_info_arr = $this->getServerList(-1, -1, self::ONLINE, 'mac,ip,last_statusinfo');
    	if ($server_info_arr){
    	    $mail_content = '';
    	    $subject = 'crontab服务器磁盘占用情况报警邮件';
    	    foreach ($server_info_arr as $server_info){
    	        $ip = long2ip($server_info ['ip']);
    	        $mac = $server_info ['mac'];
    	        $last_statusinfo = $server_info ['last_statusinfo'];
    	        $last_statusinfo_arr = json_decode($last_statusinfo, true);
    	        $disk_info_arr = $last_statusinfo_arr [self::DISK_INFO];
    	        foreach ($disk_info_arr as $disk_one){
    	            foreach ($disk_one as $disk_field => $field_value){
    	                if (stristr($disk_field, 'use') && stristr($disk_field, '%')){
    	                    if (intval(trim($field_value, '%')) > 90){
    	                        $mail_content .= $mac. "\t" .$ip . " 磁盘占用情况:" . implode("\t", $disk_one) . "<br>";
    	                    }
    	                }
    	            }
    	        }
    	
    	    }
    	    if ($mail_content){
    	        PublicModel::sendWarnMail($mail_content, $subject);
    	    }
    	}
    	 
    	return true;
    }
    /**
     * 增加一条server
     * @param unknown $data
     */
    public function addServer($data){
        $insert_data = $this->_checkInsertData($data);
        if ($insert_data === false){
            return false;
        }
        $jobserver_obj = SingletonManager::$SINGLETON_POOL->getInstance('JobServerModel');
        $duplicate = array('ip' => $insert_data ['ip']);
        return $jobserver_obj->add($insert_data, $duplicate);
    }
    public function updateLastStatInfo($mac, $statinfo = array()){
        if (empty($mac) || empty($statinfo)){
            Debug::setErrorMessage("参数错误");
            return false;
        }
        $jobserver_obj = SingletonManager::$SINGLETON_POOL->getInstance('JobServerModel');
        $set = array('last_statusinfo' => $statinfo);
        $where = array(
            array('field' => 'mac', 'condition' => $mac)
        );
        return $jobserver_obj->update($set, $where);
    }
    /**
     * 修改server信息，这里不能修改状态
     * @param unknown $id
     * @param unknown $update
     */
    public function updateServerInfo($id, $update){
        if (!is_numeric($id)){
            Debug::setErrorMessage("参数错误");
            return false;
        }
        if (isset($data['status'])) {
            unset($data['status']);
        }
        $data = $update;
        $data ['id'] = $id;
        return $this->_updateJobInfo($data);
    }
    /**
     * 彻底移除server，
     *
     * @param int $id
     * @return boolean
     */
    public function removeServer($id){
        if (!is_numeric($id)){
            Debug::setErrorMessage("参数错误");
            return false;
        }
        $where[] = array('field' => 'id', 'condition' => $id);
        $jobserver_obj = SingletonManager::$SINGLETON_POOL->getInstance('JobServerModel');
        $ret = $jobserver_obj->remove($where);
        return $ret;
    }
    /**
     * 获取一个Server信息
     * @param int $id
     * @return array
     */
    public function getServer($id){
        if (! is_numeric($id)) {
            Debug::setErrorMessage(Debug::ERROR_ERROR_PARAMS);
            return false;
        }
        $jobserver_obj = SingletonManager::$SINGLETON_POOL->getInstance('JobServerModel');
        $info = $jobserver_obj->getByKey($id);
        return $info;
    }
    /**
     * 按mac获取信息
     */
    public function getServerByMac($mac){
        if (empty($mac))return false;
        $jobserver_obj = SingletonManager::$SINGLETON_POOL->getInstance('JobServerModel');
        $where = array(
            array('field' => 'mac','condition' => $mac)
        );
    
        $list = $jobserver_obj->getList(-1, -1, '*', $where);
        return $list [0];
    }
    /**
     * 按ip获取信息
     */
    public function getServerByIp($ip){
        if (empty($ip))return false;
        $jobserver_obj = SingletonManager::$SINGLETON_POOL->getInstance('JobServerModel');
        $where = array(
                array('field' => 'ip','condition' => $ip)
        );
        
        $list = $jobserver_obj->getList(-1, -1, '*', $where);
        return $list [0];
    }
    /**
     * 获取所有服务器编号
     */
    public function getAllServer($status = self::ONLINE){
        $jobserver_obj = SingletonManager::$SINGLETON_POOL->getInstance('JobServerModel');
        if (is_numeric($status)){
            $where = array(
                array('field' => 'status','condition' => $status)
            );
        }
        $list = $jobserver_obj->getList(-1, -1, 'ip,id', $where);
        $ret = array();
        foreach ($list as $one){
            $ret [] = $one ['id'];
        }
        return $ret;
    }
    /**
     * 按状态获取Server列表
     * @param unknown $count
     * @param unknown $page
     * @param unknown $status
     */
    public function getServerList($count = 20, $page = 0, $status = self::ONLINE, $field = '*', $order_by = null){
        if (!is_numeric($count)){
            $count = 20;
        }
        if (!is_numeric($page)){
            $page = 0;
        }
        $jobserver_obj = SingletonManager::$SINGLETON_POOL->getInstance('JobServerModel');
        if (is_numeric($status)){
            $where = array(
                array('field' => 'status','condition' => $status)
            );
        }
    
        return $jobserver_obj->getList($count, $page, $field, $where, $order_by);
    }
    
    /**
     * 上线job
     *
     * @param int $id
     * @return boolean
     */
    public function onlineServer($id)
    {
        if (! is_numeric($id)) {
            Debug::setErrorMessage(Debug::ERROR_ERROR_PARAMS);
            return false;
        }
        $data = array(
            'id' => $id,
            'status' => self::ONLINE
        );
        return $this->_updateJobInfo($data);
    }
    
    /**
     * 下线job
     *
     * @param int $id
     * @return boolean
     */
    public function offlineServer($id)
    {
        if (! is_numeric($id)) {
            Debug::setErrorMessage(Debug::ERROR_ERROR_PARAMS);
            return false;
        }
        $data = array(
            'id' => $id,
            'status' => self::OFFLINE
        );
        return $this->_updateJobInfo($data);
    }
    /**
     * 置状态为未上线
     *
     * @param int $id
     * @return boolean
     */
    public function toonlineServer($id)
    {
        if (! is_numeric($id)) {
            Debug::setErrorMessage(Debug::ERROR_ERROR_PARAMS);
            return false;
        }
        $data = array(
            'id' => $id,
            'status' => self::TO_ONLINE
        );
        return $this->_updateJobInfo($data);
    }
    private function _updateJobInfo($data)
    {
        if (empty($data) || ! isset($data['id'])) {
            Debug::setErrorMessage(Debug::ERROR_ERROR_PARAMS);
            return false;
        }
        $where = array(
            array(
                'field' => 'id','condition' => $data['id']
            )
        );
        //修改用户
        $update_userid = '';
        if($update_userid>0 || empty($update_userid))$data ['alter_user'] = $update_userid;
        unset($data ['id']);
        $jobserver_obj = SingletonManager::$SINGLETON_POOL->getInstance('JobServerModel');
        return $jobserver_obj->update($data, $where);
    }
    /**
     * 判断插入一条的数据有效性,返回处理后的数据
     * @param unknown $data
     */
    private function _checkInsertData($data){
        $data_new = array();
        if (!PublicModel::checkMacValid($data ['mac'])){
            Debug::setErrorMessage('mac格式不对！');
            return false;
        }
        $data_new ['mac'] = $data ['mac'];
        $data_new ['ip'] = $data ['ip'] ? ip2long($data ['ip']) : '';
        if (isset($data ['desc']) && empty($data ['desc'])){
            Debug::setErrorMessage('服务器描述为空！');
            return false;
        }
        $data_new ['desc'] = $data ['desc'];       
    
        if (isset($data ['status']) && is_numeric($data ['status'])){
            $data_new ['status'] = $data ['status'];
        }else{
            $data_new ['status'] = self::TO_ONLINE;
        }
        //添加创建用户,待完善
        $update_userid = '';
        if($update_userid)$data_new ['alter_user'] = $update_userid;
        $data_new ['create_time'] = date('Y-m-d H:i:s');
        return $data_new;
    }
    /**
     * 获取磁盘占用信息
     * @return multitype:|multitype:multitype:
     */
    public static function getDiskInfo(){
        $cmd_disk_str = 'df -hl';
        $disk_info_str = shell_exec($cmd_disk_str);
        if (empty($disk_info_str)){
            return array();
        }
        $ret_arr = array();
        $disk_info_arr = explode("\n", $disk_info_str);
        $index = 0;
        $field_arr = array();
        foreach ($disk_info_arr as $filesystem_info_str){
            $index ++;
            if (empty($filesystem_info_str))continue;
            if ($index == 1){
                $filesystem_info_str = preg_replace('/\s\s+/', ' ', $filesystem_info_str);
                $field_arr = explode(' ', $filesystem_info_str);
                if (strtolower($field_arr [count($field_arr) - 1]) == 'on')unset($field_arr [count($field_arr) - 1]);
                continue;
            }
            $filesystem_info_str = preg_replace('/\s\s+/', ' ', $filesystem_info_str);
            $filesystem_info_arr = explode(' ', $filesystem_info_str);
            $ret_arr [] = array_combine($field_arr, $filesystem_info_arr);
        }
        return $ret_arr;
    }
    /**
     * 获取当前服务器负载状态信息,先每隔1s取一次结果,取60次,然后在分析结果输出
     */
    public static function getServerStatInfo(){
        $gap = 30;
        $ret = array();
        $topinfo_list = array();
        $ifaceinfo_list = array();

        $log_obj = SingletonManager::$SINGLETON_POOL->getInstance('LogModel');
        $cmd_iface = 'netstat -i |sed -n \'3,$p\'';
        $iface_info_1 = shell_exec($cmd_iface);
        $iface_info_1 = self::_parseIfaceInfo($iface_info_1);
        sleep(1);
        for($i = 0;$i<$gap;$i++){
            $cmd = "top -b -n 1 | sed -n '1,5p'";
            $top_info = shell_exec($cmd);
            $top_parse_info = self::_parseTopInfo($top_info);
            
            $cmd_iface = 'netstat -i |sed -n \'3,$p\'';
            $iface_info_2 = shell_exec($cmd_iface);
            $iface_info_2 = self::_parseIfaceInfo($iface_info_2);
            $iface_info_diff = self::diffIfaceInfo($iface_info_1, $iface_info_2);
            $server_statinfo = array_merge($top_parse_info, $iface_info_diff);
            $ret [] = $server_statinfo;
            //写入日志
            $server_statinfo ['PID'] = 1;
            $log_obj->writeResourceLog($server_statinfo,  JobServerManageModel::TASK_ID);
            
            $iface_info_1 = $iface_info_2;
            sleep(1);//每隔一秒获取一次
        }
        
        return $ret;
    }
    /**
     * 服务器负载信息
     * @param unknown $top_info
     * @return multitype:string number
     */
    private static function _parseTopInfo($top_info){
        $arr = (explode("\n",trim($top_info)));
        
        $ret = array();
        //依次解析
        //第一列，服务器运行时间和负载情况
        $line = $arr [0];
        $pattern_time = '/up(.*)user/';
        preg_match($pattern_time, $line, $match);
        $ret [self::WORK_TIME] = '';
        if (trim($match [1])){
            $tmp = explode(',  ', trim($match [1]));
            $ret [self::WORK_TIME]  = $tmp [0] . ",". $tmp [1];
        }
        $tmp = explode('load average:', $line);
        $ret [self::LOAD_AVERAGE]  = $tmp [1] ? trim($tmp [1]) : '';
        //第二列，进程信息
        $line = $arr [1];
        $line_arr = explode(",", $line);
        $pattern_task = '/Tasks:(.*)total/';
        preg_match($pattern_task, trim($line_arr [0]), $match);
        $ret [self::TASK_TOTAL] = 0;
        if (trim($match [1])){
            $ret [self::TASK_TOTAL] = trim(($match [1]));
        }
        $pattern_task = '/(.*)running/';
        preg_match($pattern_task, $line_arr [1], $match);
        $ret [self::TASK_RUNNING] = 0;
        if (trim($match [1])){
            $ret [self::TASK_RUNNING] = trim(trim($match [1]));
        }
        /*
        $pattern_task = '/(.*)sleeping/';
        preg_match($pattern_task, $line_arr [2], $match);
        $ret [self::TASK_SLEEPING] = '';
        if (trim($match [1])){
            $ret [self::TASK_SLEEPING] = trim($match [1]);
        }
        $pattern_task = '/(.*)stopped/';
        preg_match($pattern_task, $line_arr [3], $match);
        $ret [self::TASK_STOPPED] = '';
        if (trim($match [1])){
            $ret [self::TASK_STOPPED] = trim($match [1]);
        }
        $pattern_task = '/(.*)zombie/';
        preg_match($pattern_task, $line_arr [4], $match);
        $ret [self::TASK_ZOMBIE] = '';
        if (trim($match [1])){
            $ret [self::TASK_ZOMBIE] = trim($match [1]);
        }
        */
        //第三列，cpu信息
        $line = $arr [2];
        $line_arr = explode(",", $line);
        $pattern_task = '/:(.*)%us/';
        preg_match($pattern_task, trim($line_arr [0]), $match);
        $ret [self::CPU_US] = 0;
        if (trim($match [1])){
            $ret [self::CPU_US] = trim($match [1]);
        }
        $pattern_task = '/(.*)%sy/';
        preg_match($pattern_task, $line_arr [1], $match);
        $ret [self::CPU_SY] = 0;
        if (trim($match [1])){
            $ret [self::CPU_SY] = trim($match [1]);
        }
        $pattern_task = '/(.*)%ni/';
        preg_match($pattern_task, $line_arr [2], $match);
        $ret [self::CPU_NI] = 0;
        if (trim($match [1])){
            $ret [self::CPU_NI] = trim($match [1]);
        }
        $pattern_task = '/(.*)%id/';
        preg_match($pattern_task, $line_arr [3], $match);
        $ret [self::CPU_ID] = 0;
        if (trim($match [1])){
            $ret [self::CPU_ID] = trim($match [1]);
        }
        //第四列，mem信息
        $line = $arr [3];
        $line_arr = explode(",", $line);
        $pattern_task = '/Mem:(.*)k total/';
        preg_match($pattern_task, trim($line_arr [0]), $match);
        $ret [self::MEM_TOTAL] = 0;
        if (trim($match [1])){
            $ret [self::MEM_TOTAL] = trim($match [1]);
        }
        $pattern_task = '/(.*)k used/';
        preg_match($pattern_task, $line_arr [1], $match);
        $ret [self::MEM_USED] = 0;
        if (trim($match [1])){
            $ret [self::MEM_USED] = trim($match [1]);
        }
        $pattern_task = '/(.*)k free/';
        preg_match($pattern_task, $line_arr [2], $match);
        $ret [self::MEM_FREE] = 0;
        if (trim($match [1])){
            $ret [self::MEM_FREE] = trim($match [1]);
        }
        $pattern_task = '/(.*)k buffers/';
        preg_match($pattern_task, $line_arr [3], $match);
        $ret [self::MEM_BUFFERS] = 0;
        if (trim($match [1])){
            $ret [self::MEM_BUFFERS] = trim($match [1]);
        }
        //第五列，mem信息
        $line = $arr [4];
        $line_arr = explode(",", $line);
        $pattern_task = '/Swap:(.*)k total/';
        preg_match($pattern_task, trim($line_arr [0]), $match);
        $ret [self::SWAP_TOTAL] = 0;
        if (trim($match [1])){
            $ret [self::SWAP_TOTAL] = trim($match [1]);
        }
        $pattern_task = '/(.*)k used/';
        preg_match($pattern_task, $line_arr [1], $match);
        $ret [self::SWAP_USED] = 0;
        if (trim($match [1])){
            $ret [self::SWAP_USED] = trim($match [1]);
        }
        $pattern_task = '/(.*)k free/';
        preg_match($pattern_task, $line_arr [2], $match);
        $ret [self::SWAP_FREE] = 0;
        if (trim($match [1])){
            $ret [self::SWAP_FREE] = trim($match [1]);
        }
        $pattern_task = '/(.*)k cached/';
        preg_match($pattern_task, $line_arr [3], $match);
        $ret [self::SWAP_CACHED] = 0;
        if (trim($match [1])){
            $ret [self::SWAP_CACHED] = trim($match [1]);
        }
        
        return $ret;
    }
    /**
     * 服务器网络信息
     * @param unknown $iface_info
     * @return multitype:number unknown Ambigous <>
     */
    private static function _parseIfaceInfo($iface_info){
        $ret = array();
        $iface_info_arr = explode("\n", $iface_info);
        $ret [self::IFACE_RXOK] = 0;
        $ret [self::IFACE_RXERR] = 0;
        $ret [self::IFACE_RXDRP] = 0;
        $ret [self::IFACE_TXOK] = 0;
        $ret [self::IFACE_TXERR] = 0;
        $ret [self::IFACE_TXDRP] = 0;
        foreach ($iface_info_arr as $info){
            $info = preg_replace("/\s+/", "\n", $info);
            $arr_tmp = explode("\n", $info);
            if (!strstr($arr_tmp[0], 'eth'))continue;
            $ret [self::IFACE_RXOK] += $arr_tmp [3];
            $ret [self::IFACE_RXERR] += $arr_tmp [4];
            $ret [self::IFACE_RXDRP] += $arr_tmp [5];
            $ret [self::IFACE_TXOK] += $arr_tmp [7];
            $ret [self::IFACE_TXERR] += $arr_tmp [8];
            $ret [self::IFACE_TXDRP] += $arr_tmp [9];
        }
        
        return $ret;
    }
    private static function diffIfaceInfo($iface_info_now, $iface_info_next){
        $ret = array();
        foreach ($iface_info_next as $field => $value){
            $diff = $value - $iface_info_now [$field];
            $diff = is_numeric($diff) ? $diff : 0;
            $ret [$field] = $diff;
        }
        return $ret;
    }
}

?>
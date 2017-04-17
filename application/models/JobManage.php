<?php
/**
 * 任务管理类
 * 每台机器通过mac唯一标识
 * 任务表里的select_serverlist和exec_server关系说明
 * select_serverlist:表示执行任务可选择的机器列表编号,为空时表示所有
 * exec_server:表示真正执行任务时，执行该项任务的服务器编号,为空时表示需要在select_serverlist中的每台机器上执行
 * 在做负载均衡的时候，只能更新select_iplist大于1个并且exec_server不为空的任务
 * @author zhiyuan12@staff.weibo.com
 */
class JobManageModel {
    private $c_pid = '';
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
    //任务是否在每台机器上执行
    const EXEC_ALLSERVER = 0;
    const EXEC_ONESERVER = 1;
    const SERVER_NUM_FIELD = 'server_num';
    public static $EXEC_SERVER_NUM_CONF = array(
        self::EXEC_ALLSERVER => array(
            'code' => self::EXEC_ALLSERVER,
            'name' => '每台'
        ),
        self::EXEC_ONESERVER => array(
            'code' => self::EXEC_ONESERVER,
            'name' => '单台'
        ),
    );
    /**
     * 增加一条job
     * @param unknown $data
     */
    public function addJob($data){
        $insert_data = $this->_checkInsertData($data);
        if ($insert_data === false){
            return false;
        }
        $job_obj = SingletonManager::$SINGLETON_POOL->getInstance('JobModel');
        return $job_obj->add($insert_data);
    }
     /**
         * 第一次启动时，默认加载七个任务
         * 1、获取服务器状态信息任务
         * 2、将任务列表从数据库写入文件
         * 3、任务分配
         * 4、监控服务器状态
         * 5、将日志落地到数据库
         * 6、删除日志（文件日志和数据库里的数据）
         * 7、kill 任务启动的进程
    */
    public function initCrontab($document_dir = ''){
        if (empty($document_dir)){
            Debug::setErrorMessage('项目路径为空');
            return false;
        }
        $php_bin = '/usr/bin/php';
        if (Mif_Registry::get('php_bin')) {
        	$php_bin = Mif_Registry::get('php_bin');
        }
        
        $dir_base = $document_dir . 'application/cronscript/' ;
        
        $job_list = array(
            array(
                'name' => '获取服务器状态信息',
                'cron_time' => '* * * * *',
                'cron_cmd' => $php_bin . ' ' . $dir_base . 'crontab_getserverstatus.php &>/dev/null',
                'max_exectime' => 0,
                'select_serverlist' => '',
                'exec_server' => 0,
                'status' => JobManageModel::ONLINE,
            ),
            array(
                'name' => '将任务列表从数据库写入文件',
                'cron_time' => '*/30 * * * *',
                'cron_cmd' =>  $php_bin . ' ' . $dir_base . 'crontab_getjoblist.php &>/dev/null',
                'max_exectime' => 0,
                'select_serverlist' => '',
                'exec_server' => 0,
                'status' => JobManageModel::ONLINE,
            ),
            array(
                'name' => '任务分配',
                'cron_time' => '*/5 * * * *',
                'cron_cmd' =>  $php_bin . ' ' . $dir_base . 'crontab_distributeJob.php &>/dev/null',
                'max_exectime' => 0,
                'select_serverlist' => '',
                'exec_server' => 0,
                'status' => JobManageModel::ONLINE,
            ),
            array(
                'name' => '服务器状态报警',
                'cron_time' => '*/5 * * * *',
                'cron_cmd' =>  $php_bin . ' ' . $dir_base . 'crontab_monitorServer.php &>/dev/null',
                'max_exectime' => 0,
                'select_serverlist' => '',
                'exec_server' => 0,
                'status' => JobManageModel::ONLINE,
            ),
            array(
                'name' => '将日志落地到数据库',
                'cron_time' => '* * * * *',
                'cron_cmd' =>  $php_bin . ' ' . $dir_base . 'crontab_writeLogToDb.php &>/dev/null',
                'max_exectime' => 0,
                'select_serverlist' => '',
                'exec_server' => 0,
                'status' => JobManageModel::ONLINE,
            ),
            array(
                'name' => '删除日志',
                'cron_time' => '0 9 1 * *',
                'cron_cmd' =>  $php_bin . ' ' . $dir_base . 'crontab_deleteStatLog.php &>/dev/null',
                'max_exectime' => 0,
                'select_serverlist' => '',
                'exec_server' => 0,
                'status' => JobManageModel::ONLINE,
            ),
            array(
                'name' => '杀掉进程',
                'cron_time' => '* * * * *',
                'cron_cmd' =>  $php_bin . ' ' . $dir_base . 'crontab_killprocess.php &>/dev/null',
                'max_exectime' => 0,
                'select_serverlist' => '',
                'exec_server' => 0,
                'status' => JobManageModel::ONLINE,
            ),
        );
        
        $job_obj = new JobModel();
        $job_manage_obj = new JobManageModel();
        $job_obj->beginTransaction();
        foreach ($job_list as $job_info){
            try {
                $insert_id = $job_manage_obj->addJob($job_info);
                if ($insert_id === false){
                    $job_obj->rollback();
                    Debug::setErrorMessage( "默认的七个任务添加失败!");
                    return false;
                }
            } catch (Exception $e) {
                $job_obj->rollback();
                Debug::setErrorMessage( "默认的七个任务添加失败!");
                return false;
            }
        }
        $job_obj->commit();
        Debug::setErrorMessage( "默认的qi个任务添加成功!");
        return true;
    }
    /**
     * 判断job是否存在,根据任务名和select_serverlist是否唯一判断
     * @param unknown $data
     */
    public function checkJobExist($data){
        if (!isset($data ['name'])){
            return false;
        }
        if (empty($data ['select_serverlist'])){
            $data ['select_serverlist'] = '';
        }
        $job_obj = SingletonManager::$SINGLETON_POOL->getInstance('JobModel');
        $where = array(
            array('field' => 'name','condition' => $data ['name']),
            array('field' => 'select_serverlist','condition' => $data ['select_serverlist']),
        );
        $list = $job_obj->getList(-1, -1, 'id', $where);
        if ($list){
            return true;
        }
        return false;
    }
    /**
     * 修改job信息，这里不能修改状态
     * @param unknown $id
     * @param unknown $update
     */
    public function updateJobInfo($id, $update){
        if (!is_numeric($id)){
            Debug::setErrorMessage("参数错误");
            return false;
        }
        $data = $update;
        if (isset($data['status'])) {
            unset($data['status']);
        }
        $data ['id'] = $id;
        $select_serverlist = array();
        if (isset($data ['select_serverlist']) && !empty($data ['select_serverlist'])){
            $server_list = explode(',',$data ['select_serverlist']);
            $select_serverlist = $server_list;
        }
        if (isset($data ['select_serverlist']))$data ['exec_server'] = $this->getExecipBySelectiplist($select_serverlist, $data [self::SERVER_NUM_FIELD]);
        if (isset($data [self::SERVER_NUM_FIELD])) {
        	unset($data [self::SERVER_NUM_FIELD]);
        }
        return $this->_updateJobInfo($data);
    }
    /**
     * 判断mac是否能执行某个任务
     * @param unknown $mac
     * @param unknown $id
     * @return boolean
     */
    public function checkMacCanExec($mac, $id){
        if (! PublicModel::checkMacValid($mac)){
            Debug::setErrorMessage('mac格式有误！');
            return false;
        }
        if (!is_numeric($id)){
            Debug::setErrorMessage('任务id格式错误！');
            return false;
        }
        $jobserver_obj = new JobServerManageModel();
        $server_info = $jobserver_obj->getServerByMac($mac);
        $mac_server_id = $server_info ? $server_info ['id'] : '';
        
        $job_info = $this->getJob($id);
        if (empty($job_info)){
            Debug::setErrorMessage('任务不存在！');
            return false;
        }
        $select_serverlist = $job_info ['select_serverlist'];
        $exec_server = $job_info ['exec_server'];
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
     * 彻底移除活动基本信息，
     *
     * @param int $id
     * @return boolean
     */
    public function removeJob($id){
        if (!is_numeric($id)){
            Debug::setErrorMessage("参数错误");
            return false;
        }
        $where[] = array('field' => 'id', 'condition' => $id);  
        $job_obj = SingletonManager::$SINGLETON_POOL->getInstance('JobModel');
        $ret = $job_obj->remove($where);
        return $ret;
    }
    /**
     * 获取一个任务信息
     * @param int $id 任务ID
     * @return array
     */
    public function getJob($id){
        if (! is_numeric($id)) {
            Debug::setErrorMessage(Debug::ERROR_ERROR_PARAMS);
            return false;
        }
        $job_obj = SingletonManager::$SINGLETON_POOL->getInstance('JobModel');
        $info = $job_obj->getByKey($id);
        return $info;
    }
    /**
     * 按状态获取任务列表
     * @param unknown $count
     * @param unknown $page
     * @param unknown $status
     */
    public function getJobList($count = 20, $page = 0, $status = self::ONLINE, $field = '*', $order_by = null){
        if (!is_numeric($count)){
            $count = 20;
        }
        if (!is_numeric($page)){
            $page = 0;
        }
        $job_obj = SingletonManager::$SINGLETON_POOL->getInstance('JobModel');
        if (is_numeric($status)){
            $where = array(
                array('field' => 'status','condition' => $status)
            );
        }
            
        return $job_obj->getList($count, $page, $field, $where, $order_by);
    }
    /**
     * 获取需要进行负载均衡的任务
     */
    public function getDistributeJob(){
        $where = array(
            array('field' => 'status', 'condition' => self::ONLINE,),
            array('field' => 'exec_server', 'condition' => '', 'operator' => '<>'),
        );
        $job_obj = SingletonManager::$SINGLETON_POOL->getInstance('JobModel');
        $list = $job_obj->getList(-1, -1, 'select_serverlist,id', $where);
        if (empty($list)){
            return array();
        }
        $ret = array();
        foreach ($list as $one){
            $select_serverlist = $one ['select_serverlist'];
            if (empty($select_serverlist)){
                $ret [] = $one;
            }else{
                $select_serverlist_arr = explode(",", $select_serverlist);
                if (count($select_serverlist_arr) > 1){
                    $ret [] = $one;
                }
            }
        }
        return $ret;
    }
    /**
     * 上线job
     *
     * @param int $id
     * @return boolean
     */
    public function onlineJob($id)
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
    public function offlineJob($id)
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
    public function toonlineJob($id)
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
        if($update_userid>0 )$data ['alter_user'] = $update_userid;
        unset($data ['id']);
        $job_obj = SingletonManager::$SINGLETON_POOL->getInstance('JobModel');
        return $job_obj->update($data, $where);
    }
    /**
     * 判断插入一条的数据有效性,返回处理后的数据
     * @param unknown $data
     */
    private function _checkInsertData($data){
        $data_new = array();
        $is_exist = $this->checkJobExist($data);
        if ($is_exist){
            Debug::setErrorMessage('任务已经存在');
            return false;
        }
        if (empty($data ['name'])){
            Debug::setErrorMessage('任务名为空！');
            return false;
        }
        $data_new ['name'] = $data ['name'];
        if (! PublicModel::checkCronTime($data ['cron_time'])){
            Debug::setErrorMessage('cron执行时间设置格式有误！');
            return false;
        }
        $data_new ['cron_time'] = $data ['cron_time'];
        if (! PublicModel::checkCronCmd($data ['cron_cmd'])){
            Debug::setErrorMessage('cron执行命令格式有误！');
            return false;
        }
        $data_new ['cron_cmd'] = $data ['cron_cmd'];
        if (isset($data ['max_exectime']) && (!is_numeric($data ['max_exectime']) || $data ['max_exectime']<0)){
            Debug::setErrorMessage('cron脚本执行最长时间格式错误！');
            return false;
        }
        if (isset($data ['max_exectime']))$data_new ['max_exectime'] = $data ['max_exectime'];
        $select_serverlist = array();
        if (isset($data ['select_serverlist']) && !empty($data ['select_serverlist'])){
            $server_list = explode(',',$data ['select_serverlist']);
            $data_new ['select_serverlist'] = $data ['select_serverlist'];
            $select_serverlist = $server_list;
        }
        $data_new ['exec_server'] = $this->_getExecServerBySelectServerlist($select_serverlist, $data [self::SERVER_NUM_FIELD]);
        
        $data_new ['status'] = isset($data ['status']) ? $data ['status']: self::TO_ONLINE;
        //添加创建用户
        $update_userid = '';
        if($update_userid)$data_new ['alter_user'] = $update_userid;
        $data_new ['create_time'] = date('Y-m-d H:i:s');
        return $data_new;
    }
    /**
     * 根据可执行的服务器列表和执行任务的机器数量，来确定初始的exec_server
     * @param unknown $select_serverlist
     * @param unknown $server_num
     * @return string
     */
    private function _getExecServerBySelectServerlist($select_serverlist, $server_num){
        $exec_server = '';
        if ($select_serverlist){
            //处理exec_server,需要判断是不是在每台机器上执行
            if ($server_num == self::EXEC_ONESERVER || count($select_serverlist) ==1){
                $exec_server = $select_serverlist [0];
            }
        }else{
            //处理exec_server,随机取一个
            $jobserver_obj = new JobServerManageModel();
            $serverlist_all = $jobserver_obj->getAllServer();
            $select_index = rand(0, count($serverlist_all) - 1);
            if ($server_num == self::EXEC_ONESERVER){
                $exec_server = $serverlist_all [$select_index];
            }
        }
        
        return $exec_server;
    }
}

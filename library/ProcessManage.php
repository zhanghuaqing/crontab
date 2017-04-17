<?php
/**
 * 多进程管理
 * @author zhiyuan12@staff.weibo.com
 */
declare (ticks = 1);
abstract class ProcessManage {
    public $max_processes        = 9999;
    private $_current_jobs       = array();
    private $_signal_queue       = array();
    private $_jobs_exec_info     = array();
    private $_jobs_exec_resource = array();
    private $_job_ids            = array();

    public function __construct() {
        pcntl_signal(SIGCHLD, array($this, "_childSignalHandler"));
    }
    /**
     * 子进程执行函数
     * @param  int $job_id     任务id
     * @return int 退出码
     */
    abstract public function childExec($job_id);
    /**
     * 超时检测
     * @param  int       $job_id            任务id
     * @param  timestamp $begin_time        任务开始时间
     * @return boolean   超时返回true
     */
    abstract public function timeOutCheck($job_id, $begin_time);
    /**
     * 加载所需执行的任务号
     * @param array $job_id_list 任务id号数组
     */
    public function addJobIdList($job_id_list) {
        $this->_job_ids = array_merge($this->_job_ids, $job_id_list);
        return $this;
    }
    /**
     * 获取执行任务id列表
     * @return array
     */
    public function getJobIdList() {
        return $this->_job_ids;
    }
    /**
     * 设置最大子进程数
     * @param int $max_num 最大正在执行子任务数
     */
    public function setMaxProcess($max_num) {
        if (is_numeric($max_num)) {
            $this->_max_processes = $max_num;
        }
        return $this;
    }
    /**
     * 获取任务执行信息
     * @return array
     */
    public function getJobExecInfo() {
        return $this->_jobs_exec_info;
    }
    /**
     * 执行入口
     */
    public function run() {
        if (empty($this->_job_ids)) {
            return true;
        }
        foreach ($this->_job_ids as $job_id) {
            while (count($this->_current_jobs) >= $this->max_processes) {
                sleep(1);
            }
            $launched = $this->_forkJob($job_id);
        }
        //获取fork后子进程的所有子进程,fork后子进程及下面的所有子进程 作为一个任务（单元）来处理
        $children_pidlist = self::getAllChildrenPidList(array_keys($this->_current_jobs));
        
        $begin_time = time();
        $log_obj = SingletonManager::$SINGLETON_POOL->getInstance('LogModel');
        while (count($this->_current_jobs) > 0) {
            $pid_list  = array_keys($this->_current_jobs);
            $pids_info = self::getResourceInfo($pid_list, $children_pidlist);
            foreach ($pids_info as $pid_info) {
                $pid = $pid_info['PID'];
                $job_id = $this->_jobs_exec_info[$pid]['job_id'];
                //资源监控
                $log_obj->writeResourceLog($pid_info, $job_id);
                //超时kill
                if ($this->timeOutCheck($job_id, $begin_time) == true) {
                    $tmp = posix_kill($pid, SIGKILL);
                    if (true == $tmp) {
                        $this->_addJobExecInfo($pid, $job_id, SIGKILL, -1);
                        unset($this->_current_jobs[$pid]);
                        unset($this->_signal_queue[$pid]);
                        //kill掉pid所有子进程
                        $children_pids = $children_pidlist [$pid];
                        if ($children_pids){
                            $children_pidlist_str = implode(' ', $children_pids);
                            shell_exec("kill -9 $children_pidlist_str");
                        }
                    }
                }
            }
            sleep(1);
        }
        return true;
    }
    /**
     * 获取当前时间的毫秒数
     *
     * @return float
     */
    public function getMicrotime() {
        list($usec, $sec) = explode(' ', microtime());
        return ((float) $usec + (float) $sec);
    }

    /**
     * 获取制定pid列表的资源状态
     * @param array $pid_list
     */
    public static function getResourceInfo($pid_list = array(), $children_pidlist = array()) {
        $children_pidall = array();
        if($children_pidlist){
            $pid_list_tmp = $pid_list;
            foreach ($pid_list_tmp as $pid){
                $children_pids = $children_pidlist [$pid];
                if (empty($children_pids))continue;
                $pid_list = array_merge($pid_list, $children_pids);
                $children_pidall = array_merge($children_pidall, $children_pids);
            }
            $pid_list = array_unique($pid_list);
        }
        //cmd 一定要放在最后一位
        $tmp           = shell_exec("ps -o pid,ppid,user,%cpu,%mem,vsz,rss,time,sz,s,f,pri,ni,wchan,cmd -p" . implode(',', $pid_list));
        $tmp_arr       = explode("\n", trim($tmp));
        $resource_info = array(); 
        $children_resource_info = array();
        $first         = true;
        $key_list      = array();
        foreach ($tmp_arr as $value) {
            if ($first) {
                $first    = false;
                $key_tmp  = preg_replace('/\s\s+/', ' ', trim($value));
                $key_list = explode(' ', $key_tmp);
                continue;
            }
            $trim_str        = preg_replace('/\s\s+/', ' ', trim($value));
            $field_value     = explode(' ', $trim_str);
            $pid = $field_value [0];
            if (in_array($pid, $children_pidall)){
                $children_resource_info[$pid] = array_combine($key_list, array_merge(
                    array_slice($field_value, 0, count($key_list) - 1),
                    array(implode(' ', array_slice($field_value, count($key_list) - 1)))
                ));
            }else{
                $resource_info[$pid] = array_combine($key_list, array_merge(
                    array_slice($field_value, 0, count($key_list) - 1),
                    array(implode(' ', array_slice($field_value, count($key_list) - 1)))
                ));
            }
        }
        //合并信息
        $resource_info_tmp = $resource_info;
        foreach ($resource_info_tmp as $pid => $pid_info){
            $children_pids = $children_pidlist [$pid];
            if ($children_pids){
                foreach ($children_pids as $pid_tmp){
                    if (isset($children_resource_info [$pid_tmp])){
                        $pid_info ['%CPU'] += $children_resource_info [$pid_tmp] ['%CPU'];
                        $pid_info ['%MEM'] += $children_resource_info [$pid_tmp] ['%MEM'];
                        $pid_info ['VSZ'] += $children_resource_info [$pid_tmp] ['VSZ'];
                        $pid_info ['RSS'] += $children_resource_info [$pid_tmp] ['RSS'];
                        $pid_info ['SZ'] += $children_resource_info [$pid_tmp] ['SZ'];
                        $pid_info ['CMD'] .= "$" . $children_resource_info [$pid_tmp] ['CMD'] ;
                    }
                }
            }
            $resource_info [$pid] = $pid_info;
        }
        
        return $resource_info;
    }
    /**
     * 获取所有的子pid
     * @param unknown $pid_list
     * @return boolean|multitype:unknown
     */
    public static function getAllChildrenPidList($pid_list = array()){
        $cmd = "ps -eo pid,ppid|grep -v PID";
        $all_pidlist = shell_exec($cmd);
        if (empty($all_pidlist)){
            return false;
        }
        $pidlist_arr_tmp = explode("\n", $all_pidlist);
        $ppid_pid_arr = array();
        foreach ($pidlist_arr_tmp as $one){
            $one = preg_replace('/\s\s+/', ' ', trim($one));
        	if (empty($one)) {
        		continue;
        	}
            $tmp = explode(" ", $one);
            $pid = $tmp [0];
            $ppid = $tmp [1];
            $ppid_pid_arr [$ppid] = $pid; 
        }
        //递归寻找子pid
        $children_pidlist = array();
        foreach ($pid_list as $ppid){
            $ppid_tmp = $ppid;
            $children_pidlist [$ppid_tmp] = array();
            while(true){
                if (isset($ppid_pid_arr [$ppid])){
                    $children_pidlist [$ppid_tmp][] = $ppid_pid_arr [$ppid];
                    $ppid = $ppid_pid_arr [$ppid];
                }else{
                    break;
                }
            }
        }
        
        return $children_pidlist;
    }
    private function _forkJob($job_id) {
        $pid = pcntl_fork();
        if (-1 == $pid) {
            return false;
        } else if (0 == $pid) {
            $exit_code = $this->childExec($job_id);
            exit($exit_code);
        }
        $this->_current_jobs[$pid] = $job_id;

        $this->_addJobExecInfo($pid, $job_id);

        if (isset($this->_signal_queue[$pid])) {
            $this->_childSignalHandler(SIGCHLD, $pid, $this->_signal_queue[$pid]);
            unset($this->_signal_queue[$pid]);
        }
        return true;
    }
    public function _childSignalHandler($signo, $pid = null, $status = null) {
        if ( ! $pid) {
            $pid = pcntl_waitpid(-1, $status, WNOHANG);
        }
        while ($pid > 0) {
            if (isset($this->_current_jobs[$pid])) {
                $exit_code = pcntl_wexitstatus($status);
                $this->_addJobExecInfo($pid, $this->_current_jobs[$pid], $status, $exit_code);
                unset($this->_current_jobs[$pid]);
            } else {
                $this->_signal_queue[$pid] = $status;
            }
            $pid = pcntl_waitpid(-1, $status, WNOHANG);
        }
        return true;
    }
    private function _addJobExecInfo($pid, $job_id, $status = null, $exit_code = null) {
        if (is_null($status)) {
            $this->_jobs_exec_info[$pid] = array(
                'pid'    => $pid,
                'job_id' => $job_id
            );
            $this->_jobs_exec_info[$pid]['begin_time'] = self::getMicrotime();
            $log_obj = SingletonManager::$SINGLETON_POOL->getInstance('LogModel');
            $pid_info = $this->_jobs_exec_info[$pid];
            $pid_info['PID'] = $pid;
            $pid_info['type'] = 'start';
            $log_obj->writeResourceLog($pid_info, $job_id);
        } else {
            $this->_jobs_exec_info[$pid]['status'] = $status;
        }
        if (!is_null($exit_code)) {
            $this->_jobs_exec_info[$pid]['exit_code'] = $exit_code;
            $this->_jobs_exec_info[$pid]['end_time']  = self::getMicrotime();
            $log_obj = SingletonManager::$SINGLETON_POOL->getInstance('LogModel');
            $pid_info = $this->_jobs_exec_info[$pid];
            $pid_info['PID'] = $pid;
            $pid_info['type'] = 'exit';
            $log_obj->writeResourceLog($pid_info, $job_id);
        }
        return $this;
    }

}

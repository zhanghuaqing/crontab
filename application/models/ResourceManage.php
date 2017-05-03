<?php
/**
 * 资源管理
 * @author zhouchang@staff.weibo.com
 */
class ResourceManageModel {

    private $_resource_path = '';

    public function __construct($path = '') {
        $log_path = Mif_Registry::get('log_path');
        if (empty($log_path)){
            throw new Exception('没有配置日志路径,请在' . APP_PATH . 'microframe/config.php 文件里配置');
        }
        $this->_resource_path = $path ? $path : $log_path;
    }

    //日志转数据库开始

    public function getOneMinute($time = '') {
        $time = $time ? strtotime($time) : strtotime("-1 minute");
        $date = date('Ymd', $time);
        $hour = date('H', $time);
        $minute = date('Y-m-d H:i', $time);
        $path = $this->_resource_path.$date."/";
        $file_path = $path.$hour."_*_log";
        $min = date('YmdHi');
        //将本小时的日志导出到一分钟的临时文件中
        $min_file_name = $path."/".$min.".log";
        $cmd = "grep '".$minute."' ".$file_path." > ".$min_file_name;
        shell_exec($cmd);var_dump($file_path,$cmd);
        //key: job_id,pid, value: cpu,mem,...
        $result = array();
        $jobserver_obj = new JobServerManageModel();
        $work_obj = SingletonManager::$SINGLETON_POOL->getInstance('WorkManageModel');
        $fp = fopen($min_file_name, "r");
        while (!feof($fp)) {
            $line = trim(fgets($fp));
            if (empty($line)) {
                break;
            }
            list($time_li, $job_id, $pid, $mac, $json) = explode("\t", $line);
            $server_info = $jobserver_obj->getServerByMac($mac);
            $server = $server_info ['id'] ? $server_info ['id'] : 0;
            $job_id = is_numeric($job_id) ? $job_id : 0;
            $json = json_decode($json, true);
            if ( isset($json['type']) && ($json['type'] == 'exit' || $json['type'] == 'start') && $job_id > 0) {
                if ($json['type'] == 'start') {
                    $exec_info = array(
                        'begin_time' => date('Y-m-d H:i:s', $json['begin_time']),
                        'pid' => $pid,
                        'exec_server' => ($server) ? ($server) : 0,
                    );
                } else {
                    $exec_info = array(
                        'end_time' => date('Y-m-d H:i:s', $json['end_time']),
                    );
                }
                ($work_obj->updateLastProExecInfo($job_id, $exec_info));
            } else {
                //为计算平均数准备数据
                $json['pid'] = $pid;
                $json['server'] = $server;
                $key = $job_id."_".$server."_".$pid;
                if (!isset($result[$key])) {
                    $result[$key] = array($json);
                } else {
                    $result[$key] []= $json;
                }
            }
        }
        $data = array();
        foreach ($result as $key => $arr) {
            list($job_id, $server, $pid) = explode("_", $key);
            if ($job_id == JobServerManageModel::TASK_ID && $pid == 1) {//job_id=1的单独处理
                $ret = array(
                    'job_id' => $job_id,
                    'server' => $server,
                    'pid' => $pid,
                );
                $arr_tmp = array();
                $need_field_list =  JobServerManageModel::$todatabase_field_list;
                foreach ($arr as $arr_one) {
                    //统计处理
                    foreach ($arr_one as $field => $value) {
                        if (in_array($field, $need_field_list)) {
                            if(!isset($arr_tmp [$field]))$arr_tmp [$field] = 0;
                            $arr_tmp [$field] += $value;
                        }elseif($field ==  JobServerManageModel::LOAD_AVERAGE){
                            if(!isset($arr_tmp [$field]))$arr_tmp [$field] = 0;
                            $value_arr = explode(',', $value);
                            $value_1 = trim($value_arr [0]);
                            $arr_tmp [$field] += $value_1;
                        }
                    }
                }
                $count = count($arr);
                foreach ($arr_tmp as $field => $value) {
                    $arr_tmp [$field] = round($value / $count, 2);
                }
                $ret['info'] = json_encode($arr_tmp);
                $ret['create_time'] = date('Y-m-d H:i:00', $time);
                $data []= $ret;
                continue;
            }
            $ret = array(
                'job_id' => $job_id,
                'server' => $server,
                'pid' => $arr[0]['pid'],
            );
            //计算CPU和内存占用的一分钟平均数
            $cpu = 0;
            $mem = 0;
            foreach ($arr as $item) {
                $cpu += $item['%CPU'];
                $mem += $item['RSS'];
            }
            $cpu /= count($arr);
            $mem /= count($arr);
            $ret['info'] = json_encode(array('cpu' => $cpu, 'mem' => $mem));
            $ret['create_time'] = date('Y-m-d H:i:00', $time);
            $data []= $ret;
        }
        fclose($fp);
        if (!empty($data)) {
            $stat_obj = SingletonManager::$SINGLETON_POOL->getInstance('StatModel');
            $stat_obj->multiAdd($data);
        }
        $cmd = "rm ".$min_file_name;
        exec($cmd);
        return $data;
    }

    //日志转数据库结束
    
    //资源数据展现开始

    public function getResource($job_id, $start_time, $end_time) {
        if (empty($job_id) || !is_numeric($job_id)) {
            return false;
        }
        $start_time = date('Y-m-d H:i:s', strtotime($start_time));
        $end_time = date('Y-m-d H:i:s', strtotime($end_time));
        $stat_obj = SingletonManager::$SINGLETON_POOL->getInstance('StatModel');
        $where = array(
            array('field' => 'job_id', 'condition' => $job_id),
            array('field' => 'create_time', 'operator' => '>=', 'condition' => $start_time),
            array('field' => 'create_time', 'operator' => '<=', 'condition' => $end_time),
        );
        $list = $stat_obj->getList(-1, 0, '*', $where);
        return $list;
    }
    /**
     * 删除半年前的数据
     */
    public function cronDeleteData(){
        $rm_log_time = Mif_Registry::get('rm_log_time');
        if (! is_numeric($rm_log_time)  || $rm_log_time <= 0 ){
            $rm_log_time = 180 * 24 * 3600;
        }
        $end_time = date('Y-m-d 00:00:00', time() - $rm_log_time);
        $jobserver_obj = SingletonManager::$SINGLETON_POOL->getInstance('JobServerManageModel');
        $stat_obj = SingletonManager::$SINGLETON_POOL->getInstance('StatModel');
        $inet_info = PublicModel::getServerEthInfo();
        foreach ($inet_info as $info){
            $mac = $info ['mac'];
            break;
        }
        $server_info = $jobserver_obj->getServerByMac($mac);
        if (empty($server_info)){
            echo "该服务器不在列表中\n";
            return false;
        }
        $where = array(
            array('field' => 'server', 'condition' => $server_info ['id']),
            array('field' => 'create_time', 'operator' => '<=', 'condition' => $end_time),
        );
        $count = -1;
        $del_count = $stat_obj->remove($where, $count);
        //同时删除日志文件
        $rm_date = date('Ymd', time() - $rm_log_time);
        $rm_dir = $this->_resource_path . $rm_date . '/';
        if (is_dir($rm_dir)){
            $cmd = 'rm -rf '. $rm_dir;
            shell_exec($cmd);
        }
        
        return $del_count;
    }
}

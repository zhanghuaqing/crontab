<?php 
/**
 * 任务分配底层
 * 分配方法：
 * 1、根据服务器的负载状态（load_average,cpu_id）,按权值计算分配比例,采用转轮盘方法确定选择那个任务
 * @author huaqing1
 *
 */
class JobDistributeModel{
    /**
     * 评价服务器性能指标的字段
     * @var unknown
     */
    public static $loadindex_field = array(
        JobServerManageModel::LOAD_AVERAGE => array(
            'weight' => 0.8,
            'value_type' => '-',
            'section_value' => array(
                '-1000_-30' => array(0,21),
                '-30_-4' => array(21,61),
                '-4_-3' => array(61,71),
                '-3_-2' => array(71,81),
                '-2_-1' => array(81,91),
                '-1_0' => array(91,100)
            ),
        ),
        JobServerManageModel::CPU_ID => array(
            'weight' => 0.2,
            'value_type' => '+',
            'section_value' => array(
                '0_20' => array(0,31),
                '20_30' => array(31,61),
                '30_40' => array(61,71),
                '40_50' => array(71,81),
                '50_60' => array(81,91),
                '60_100' => array(91,100)
            ),
        ),
    );
    /**
     * cron 分配任务到指定的服务器IP上
     * @return unknown
     */
    public function cronDistributeJob(){
        //获取需要进行分配的任务列表
        $jobmanege_obj = new JobManageModel();
        $job_list = $jobmanege_obj->getDistributeJob();
        if (empty($job_list)){
            echo '没有需要分配的任务'. "\n";
            return false;
        } 
        //获取所有的服务器IP列表
        $server_obj = new JobServerManageModel();
        $all_serverlist = $server_obj->getAllServer();
        //获取服务器和任务的对应关系
        $tmp = $this->_parseJobList($job_list, $all_serverlist);
        $server_joblist = $tmp ['server_joblist'];
        $job_serverlist = $tmp ['job_serverlist'];
        if (empty($ip_joblist) || empty($job_serverlist)){
            echo '任务与服务器对应关系存在错误' . "\n";
            return false;
        }
        $server_list = array_keys($server_joblist);
        $now_time = time();
        $loadindex_list = array();
        //根据服务器的负载情况确定服务器的负载指标值
        foreach ($server_list as $server_id){
            $server_info = $server_obj->getServer($server_id);
            if (empty($server_info)){
                echo "服务器 $server_id 查询失败\n";
                continue;
            }
            if($server_info ['update_time'] < date('Y-m-d H:i:s', $now_time - 10 * 60)){
                echo "服务器 $server_id 最近10分钟未更新\n";
                $mail_content =  "服务器 $server_id 负载状态最近10分钟未更新<br>";
                $subject = '服务器状态更新失败';
                PublicModel::sendWarnMail($mail_content, $subject);
                continue;
            }
            $server_status_info = json_decode($server_info ['last_statusinfo'], true);
            if (isset($server_status_info [ JobServerManageModel::LOAD_AVERAGE])){
                $load_average = $server_status_info [ JobServerManageModel::LOAD_AVERAGE];
                $load_average_arr = explode(",", $load_average);
                $server_status_info [ JobServerManageModel::LOAD_AVERAGE] = $load_average_arr [0];
            }
            $loadindex_list [$server_id] = $this->soluteServerLoadIndex($server_id, $server_status_info);
        }
                     
        //采用轮盘法确定选择那个ip执行
        $job_distribute_res = array();
        foreach ($job_serverlist as $job_id => $serverlist){
            $serverlist_index = array();
            foreach ($serverlist as $server){
                if (isset($loadindex_list [$server])){
                    $serverlist_index [$server] = $loadindex_list [$server];
                }
            }
            $serverlist_rate = $this->_soluteProbability($serverlist_index);
            if ($serverlist_rate){
                //生成一个随机数,计算概率值
                $rand = rand(1, 100);
                $rate_rand = $rand / 100;
                foreach ($serverlist_rate as $server => $info){
                    $start_rate = $info ['start_rate'];
                    $end_rate = $info ['end_rate'];
                    if ($rate_rand > $start_rate && $rate_rand <= $end_rate){
                        $job_distribute_res [$job_id] = $server;
                        break;
                    }
                }
                if (isset($job_distribute_res [$job_id]))continue;
                $job_distribute_res [$job_id] = $server;
            }
        }
        
        var_dump($loadindex_list,$job_distribute_res);
        //更新任务表
        foreach ($job_distribute_res as $job_id => $server){
            $update = array(
                'exec_server' => $server
            );
            $flag = $jobmanege_obj->updateJobInfo($job_id, $update);
            $flag = $flag !== false? 'success' : "failure";
            echo "任务 $job_id" . "\t" . $flag . "\n";
        }
        
        return true;
    }
    /**
     * 计算服务器负载指标值
     * @param string $server
     * @param array $server_status 
     * array('load_average' => 12,'cpu_id' => 14.2,...)
     * @return multitype:
     */
    public function soluteServerLoadIndex($server, $server_status){
        $ret = 0;
        $loadindex_fieldlist = self::$loadindex_field;
        foreach ($loadindex_fieldlist as $field => $feildinfo){
            if (!isset($server_status [$field])){
                Debug::setErrorMessage("字段 $field 不存在!");
                return false;
            }else{
                //判断在那个区间
                $server_status_value = $server_status [$field];
                switch ($feildinfo ['value_type']) {
                    case '-':
                        $server_status_value = -1 * $server_status_value;
                        break;
                    case '/':
                        $server_status_value = 1 / $server_status_value;
                        break;
                    default:
                        ;
                    break;
                }
                foreach ($feildinfo ['section_value'] as $section => $percent_arr){
                    $section_arr = explode("_", $section);
                    $min = $section_arr [0];
                    $max = $section_arr [1];
                    if ($server_status_value > $min && $server_status_value <= $max){
                        $ret += ($percent_arr [0] + ($percent_arr [1] - $percent_arr [0])*($server_status_value - $min) / ($max - $min))*$feildinfo ['weight'];
                        break;
                    }
                }
            }
        }
        
        return $ret;
    }
    private function _soluteProbability($serverlist_index){
        if (empty($serverlist_index) || !is_array($serverlist_index)){
            return false;
        }
        $serverlist_rate_percent = array();
        $portion_all = 0;
        foreach ($serverlist_index as $server => $portion){
            $portion_all += $portion;
        }
        $start_rate = 0;
        foreach ($serverlist_index as $server => $portion){
            $rate = round($portion / $portion_all, 5);
            $end_rate = $start_rate + $rate;
            $serverlist_rate_percent [$server] = array(
                'rate' => $rate,
                'start_rate' => $start_rate,
                'end_rate' => $end_rate
            );
            $start_rate = $end_rate;
        }
        return $serverlist_rate_percent;
    }
    private function _parseJobList($job_list, $all_serverlist){
        $server_joblist = array();
        $job_serverlist = array();
        foreach ($job_list as $one){
            $select_serverlist = $one ['select_serverlist'];
            $job_id = $one ['id'];
            if (empty($select_serverlist)){
                $job_serverlist [$job_id] = $all_serverlist;
                foreach ($all_serverlist as $ip){
                    $server_joblist [$ip] [] = $job_id;
                }
            }else{
                $select_iplist_arr = explode(",", $select_serverlist);
                //已经下线的服务器就不在分配任务
                $select_serverlist_arr_tmp = array();
                foreach ($select_serverlist_arr_tmp as $server_tmp){
                    if (in_array($server_tmp, $all_serverlist)){
                        $select_serverlist_arr_tmp [] = $server_tmp;
                    }
                }
                $job_serverlist [$job_id] = $select_serverlist_arr_tmp;
                foreach ($select_serverlist_arr_tmp as $server_tmp){
                    $server_joblist [$server_tmp] [] = $job_id;
                }
            }
        }
        return array(
            'server_joblist' => $server_joblist,
            'job_serverlist' => $job_serverlist,
        );
    }
    
}
?>
<?php 
class IndexController extends Mif_Controller{
    public function init(){
        //关闭视图渲染
        $this->disableView();
        //关闭警告
        error_reporting(E_ERROR);
    }
    public function index(){
        //echo phpinfo();
        session_start();
        if (isset($_SESSION['test_sess'])){
        
            $_SESSION['test_sess']++;
        
        }else{
            setcookie(session_name(),session_id(),time()+2);
            $_SESSION['test_sess'] = 0;
        
        }  var_dump(session_id());
        echo $_SESSION['test_sess'];
    }
    /**
     * 获取任务列表
     */
    public function getJobList(){
        //参数
        $job_id = isset( $_GET ['id'] )? ($_GET ['id']):"";
        $job_name = isset ( $_GET ['name'] ) ? trim($_GET ['name']) : "";
        $status = isset ( $_GET ['status'] ) && is_numeric($_GET ['status'])? intval($_GET ['status']) : '';
        
        $cronjob_obj = new JobModel();
        $status_list = JobManageModel::$STATUS_CONF;
        
        $whereCondtion = $this->_buildWhereCondition ($job_id, $job_name, $status);
        
        //获取action_list
        $order = array ('id' => 'desc' );
        $job_list_tmp = $cronjob_obj->getList(-1, - 1, "*", $whereCondtion, $order);
        $job_list = array();
        $server_list = array();
        if ($job_list_tmp){
            //获取所有服务器列表
            $server_obj = new JobServerModel();
            $fields= array('mac','id','ip','`desc`');
            $server_list_tmp = $server_obj->getList(-1, -1, $fields);
            foreach ($server_list_tmp as $server_info){
                $server_info ['ip'] = long2ip($server_info ['ip']);
                $server_list [$server_info ['id']] = $server_info;
            }
            foreach ($job_list_tmp as $index => $job_info){
                if ($index == 0){
                    //$job_info ['select_serverlist'] = array(1,2);
                }
                if ($job_info ['select_serverlist']){
                    $select_serverlist = array();
                    $job_info ['select_serverlist'] = explode(',', $job_info ['select_serverlist']);
                    foreach ($job_info ['select_serverlist'] as $server_id){
                        $select_serverlist [] = $server_list [$server_id];
                    }
                    $job_info ['select_serverlist'] = $select_serverlist;
                    $job_info ['select_serverlist_isall'] = 0;
                }else{
                    $job_info ['select_serverlist'] = array();
                    $job_info ['select_serverlist_isall'] = 1;
                }

                //机器执行数量
                $job_info [JobManageModel::SERVER_NUM_FIELD] = $job_info ['exec_server'] ? JobManageModel::EXEC_ONESERVER : JobManageModel::EXEC_ALLSERVER;
                $job_info ['exec_server'] = $job_info ['exec_server'] ? $server_list [$job_info ['exec_server']] : array();
                $job_info ['lastpro_server'] = $job_info ['lastpro_server'] ? $server_list [$job_info ['lastpro_server']] : array();
                
                $job_info ['lastpro_info']['开始时间'] = $job_info ['lastpro_st'] ;
                $job_info ['lastpro_info']['结束时间'] = $job_info ['lastpro_et'] ;
                $job_info ['lastpro_info']['执行IP'] = $job_info ['lastpro_server']['ip'];
                $job_info ['lastpro_info']['pid'] = $job_info ['lastpro_pid'];
                //判断执行状态
                if ($job_info ['lastpro_st'] == '0000-00-00 00:00:00'){
                    $job_info ['exec_status'] = '尚未启动';
                }elseif($job_info ['lastpro_et'] == '0000-00-00 00:00:00'){
                    $job_info ['exec_status'] = '执行中';
                }else{
                    $job_info ['exec_status'] = '执行结束';
                }
                $job_list [] = $job_info;
            }
        }
        
        // 总记录数
        $item_total = $cronjob_obj->count($whereCondtion);
        $item_total = $item_total ? $item_total:0;
        
        $out_data = array (
                "job_list" => $job_list,
                "status_list" => $status_list,
                "job_total" => $item_total,
                "server_list" => array_values($server_list)
            );
        //输出
        ApiModel::echoResult(ApiModel::SUCCESS_ERROR_CODE, $out_data);
    }
    /**
     * 添加或者修改任务
     * $params = array(
     *  'job_id' => xxx,
     *  'cron_time' => xxx,
     *  'cron_cmd' => xxx,
     *  'max_exectime' => xxx,
     *  'select_serverlist' => '1,2,3,xxx',
     * )
     */
    public function saveJob(){
        $params = $_POST;
        $job_id = $params ['id'];
        //echo json_encode($params);
        $jobManage_obj = new JobManageModel();
        
        if (!isset($params [JobManageModel::SERVER_NUM_FIELD]))$params [JobManageModel::SERVER_NUM_FIELD] = JobManageModel::EXEC_ONESERVER;
        $status = ApiModel::SUCCESS_ERROR_CODE;
        if ($job_id){//update
            $update = $params;
            unset($update ['id']);
            $update ['select_serverlist'] = $update ['select_serverlist'] ? $update ['select_serverlist']:'';
            $flag = $jobManage_obj->updateJobInfo($job_id, $update);
            if ($flag!==false){
                $status = ApiModel::SUCCESS_ERROR_CODE;
            }else{
                $status = ApiModel::UPDATE_FAILD_ERROR_CODE;
            }
        }else{
            $add = $params;
            unset($add ['id']);
            $add ['select_serverlist'] = $add ['select_serverlist'] ? $add ['select_serverlist']:'';
            $insert_id = $jobManage_obj->addJob($add);
            if ($insert_id!==false){
                $status = ApiModel::SUCCESS_ERROR_CODE;
            }else{
                $status = ApiModel::ADD_FAILD_ERROR_CODE;
            }
        }
        //输出
        $error_message = Debug::getErrorMessage();
        $params ['msg'] = $error_message ['error_message'];
        ApiModel::echoResult($status, $params);
    }
    /**
     * 修改任务状态
     * 
     * array(
     *  'ids' => '1,2,...',
     *  'operate_type' => 'del|offline|online', //操作类型
     * )
     */
    public function updateJobStatus(){
        $job_ids = (isset ( $_GET ['ids'] ) && ! empty ( $_GET ['ids'] )) ? $_GET ['ids'] : false;
        $opt = (isset ( $_GET ['operate_type'] ) && ! empty ( $_GET ['operate_type'] )) ? $_GET ['operate_type'] : "";
        if (empty($job_ids) || empty($opt)){
            $status = ApiModel::PARAM_ERROR_CODE;
            ApiModel::echoResult($status);
            exit;
        }
        
        $aifm_obj = SingletonManager::$SINGLETON_POOL->getInstance ( 'JobManageModel' );
        $job_ids = explode(",", $job_ids);
        if ( $opt == 'del' ) {
            foreach ( $job_ids as $one ) {
                $flag = $aifm_obj->removeJob ( $one );
                $flag = $flag ===false ? "删除失败":"删除成功";
                $res [$one] = $flag;
            }
        }
        if ( $opt == 'offline' ) {
            foreach ( $job_ids as $one ) {
                $flag = $aifm_obj->offlineJob ( $one );
                $flag = $flag ===false ? "下线失败":"下线成功";
                $res [$one] =  $flag;
            }
        }
        if ( $opt == 'online' ) {
            foreach ( $job_ids as $one ) {
                $flag = $aifm_obj->onlineJob ( $one );
                $flag = $flag ===false ? "上线失败":"上线成功";
                $res [$one] = $flag;
            }
        }
        
        $status = ApiModel::SUCCESS_ERROR_CODE;
        ApiModel::echoResult($status, $res);
    }
    private function _buildWhereCondition( $job_id, $job_name, $status  ){
        $where = array ();
        if ( ! empty( $job_id )) {
            $where [] = array(
                'field' => 'id',
                'condition' => $job_id
            );
        }
        if ( ! empty( $job_name )) {
            $where [] = array(
                'field' => 'name',
                'operator' => 'like',
                'condition' => $job_name
            );
        }
        if ($status !== '' ) {
            $where [] = array(
                'field' => 'status',
                'condition' => $status
            );
        }
        return $where;
    }
    
}
?>
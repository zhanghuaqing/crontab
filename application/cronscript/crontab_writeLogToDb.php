<?php 
/**
 * 日志落数据库
 * 
 */
//当前目录
define('APP_PATH', realpath(dirname(__FILE__) . '/../../') . '/');
//设置加载文件路径
set_include_path(APP_PATH);

//配置文件路径,也可以不配置
$config_file = APP_PATH.'microframe/Config.php';

//加载mvc启动类
require_once APP_PATH.'microframe/Starup.php';
$start_obj = new Mif_Starup($config_file);
$start_obj->execCli('run',$argv);

function run($argv){
    echo "开始时间:" . date('Y-m-d H:i:s') . "\n";
    
    $obj = new ResourceManageModel();
    $obj->getOneMinute();

    echo "结束时间:" . date('Y-m-d H:i:s') . "\n";
}

?>
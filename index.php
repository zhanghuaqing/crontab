<?php 
/**
 * 入口文件
 * 
 */
//当前目录
define('APP_PATH', dirname(__FILE__) . '/');
//设置加载文件路径
set_include_path(APP_PATH);

//配置文件路径,也可以不配置
$config_file = APP_PATH.'microframe/Config.php';

//加载mvc启动类
require_once APP_PATH.'microframe/Starup.php';
$start_obj = new Mif_Starup($config_file);
$start_obj->run();

?>

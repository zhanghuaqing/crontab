<?php 
 return $conf = array(
     'route_info' => array(
         'url_mode' => 1, //url传参模式,0或者1
         'valid_modules' => 'Index,Test', //有效的module
        'default_module' => 'Index',
        'default_controller' => 'Index',
        'default_action' => 'index',
        'var_module' => 'm',
        'var_controller' => 'c',
        'var_action' => 'a',
     ),
     'db_crontab_conf' => array (
			'master' => array (
					'host' => '127.0.0.1',
					'port' => 3306,
					'username' => 'root',
					'password' => 'root',
					'dbname' => 'crontab',
					'charset' => "UTF8" 
			),
			'slave' => array (
					'host' => '127.0.0.1',
					'port' => 3306,
					'username' => 'root',
					'password' => 'root',
					'dbname' => 'crontab',
					'charset' => "UTF8" 
			) 
	),
 	'php_bin' => '/usr/local/php-5.6.27/bin/php',
    'log_path' => '/var/log/', //日志路径
    'rm_log_time' => 180 * 24 * 3600, //日志删除时间长度
    'joblist_file_utime_gap' => 2400, //任务文件更新时间最长间隔
    'mail_conf' => array(
 		'from_mailer' => 'zhq11121065@163.com', //仅限于163,QQ邮箱
 		'from_mailername' => 'huaqing',			//发送者昵称
 		'user_name' => 'zhq11121065',	//发送者账号名
 		'password' => 'zhq351990',		//发送者账号密码
 		'bodytype' => 'text/html',		//邮件内容格式
 		'to_mailers' => array(
    		array('343955993@qq.com','huaqing'),
    	),		//接收者账号列表
 	),
 );
 
?>
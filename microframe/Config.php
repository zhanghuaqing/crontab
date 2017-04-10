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
 );
 
?>
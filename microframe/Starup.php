<?php 
/**
 * 启动框架类
 * @author huaqing1
 *
 */
class Mif_Starup{
    public function __construct($config_file = ''){
        //注册自动加载函数
        spl_autoload_register(array($this, 'autoload'));  
        //加载配置文件
        if ($config_file){
            $this->_importConfig($config_file);
        }
    }
    // 运行程序
    public function run()
    {      
        //字符串预处理
        $this->removeMagicQuotes();
        $this->unregisterGlobals();
        //分发请求并输出结果
        $this->dispatch();
    }

    // 分发一个路由
    public function dispatch()
    {
        //获取路由配置内容
        $route_conf = Mif_Registry::get('route_info');
        $route_obj = Mif_Router::getInstance();
        $route_obj->init($route_conf);
        $route_obj->route();
        //获取当前路由信息
        $module = $route_obj->getModule();
        //判断是不是合法的module
        $valid_modules = $route_conf ['valid_modules'];
        if ($valid_modules && !strstr($valid_modules, $module)){
            throw new Exception('非法的url');
        }
        $controller = $route_obj->getController();
        $action = $route_obj->getAction();
        
        $controller_class = $controller . 'Controller';
        $controller_obj = new $controller_class();
        // 如果控制器存和动作存在，这调用并传入URL参数
         
        if ((int)method_exists($controller_obj, $action)) {
            call_user_func_array(array($controller_obj, $action), array());
        } else {
            throw new Exception('action:' . $action . " 不存在");
        }
    }

    // 删除敏感字符
    public function stripSlashesDeep($value)
    {
        $value = is_array($value) ? array_map(array($this, 'stripSlashesDeep'), $value) : stripslashes($value);
        return $value;
    }

    // 检测敏感字符并删除
    public function removeMagicQuotes()
    {
        if (get_magic_quotes_gpc()) {
            $_GET = isset($_GET) ? $this->stripSlashesDeep($_GET ) : '';
            $_POST = isset($_POST) ? $this->stripSlashesDeep($_POST ) : '';
            $_COOKIE = isset($_COOKIE) ? $this->stripSlashesDeep($_COOKIE) : '';
            $_SESSION = isset($_SESSION) ? $this->stripSlashesDeep($_SESSION) : '';
        }
    }

    // 检测自定义全局变量（register globals）并移除
    public function unregisterGlobals()
    {
        if (ini_get('register_globals')) {
            $array = array('_SESSION', '_POST', '_GET', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES');
           foreach ($array as $value) {
                foreach ($GLOBALS[$value] as $key => $var) {
                    if ($var === $GLOBALS[$key]) {
                        unset($GLOBALS[$key]);
                    }
                }
            }
        }
    }

    /**
     * 自动加载控制器和模型类,需要加载的类有以下几种，不同的类寻找的路径不一样
     * 1、以Mif开头的框架类,类名与文件名一致
     * 2、以Model结尾的model类,需要采用驼峰式命名类名,驼峰最后的名称与文件名一致
     * 3、以controller结尾的controller类,需要采用驼峰式命名类名,驼峰最后的名称与文件名一致
     * 4、其他的类library类,需要采用驼峰式命名类名,驼峰最后的名称与文件名一致
     */
    public static function autoload($class)
    {
        if (strtolower(substr($class, 0,3)) == 'mif'){
            $file_name = substr($class, 4);
            $file = APP_PATH . 'microframe/' . $file_name . '.php';
        }elseif(strtolower(substr($class, -5)) == 'model'){
            $file_name = self::parseHump($class); 
            $file_name = str_replace('Model', '', $file_name);
            $file = APP_PATH . 'application/models/' . $file_name . '.php';
        }elseif(strtolower(substr($class, -10)) == 'controller'){
            $file_name = self::parseHump($class);
            $module = Mif_Router::getInstance()->getModule();
            if ($module == 'Index'){
                $file_name = str_replace('Controller', '', $file_name);
                $file = APP_PATH . 'application/controllers/' . $file_name . '.php';
            }else{
                $file_name = str_replace('Controller', '', $file_name);
                $file = APP_PATH . "application/modules/${module}/controllers/" . $file_name . '.php';
            }
        }else{
            $file_name = self::parseHump($class);
            $file = APP_PATH . 'library/' . $file_name . '.php';
        }
        
        try {
            require_once $file;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    /**
     * 解析类名
     * @param unknown $hump_name
     * @return unknown|string
     */
    public static function parseHump($hump_name){
        if (!strstr($hump_name, '_')){
            return $hump_name ;
        }
        $arr = explode("_", $hump_name);
        $path = implode("/", $arr);
        return $path;
    }
    /**
     * 注册配置信息
     * @param unknown $config_file
     * @return boolean
     */
    private function _importConfig($config_file){
        if (!file_exists($config_file)){
            return false;
        }
        $conf = include $config_file;
        if (!is_array($conf)){
            return false;
        }
        foreach ($conf as $name => $value){
            Mif_Registry::set($name, $value);
        }
        return true;
    }
}
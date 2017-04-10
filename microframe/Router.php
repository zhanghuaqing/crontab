<?php 
/**
 * 路由处理
 * 单例模式
 * 
 */
final class Mif_Router{
    /**
     * url传参模式，支持两种，
     * 动态传参模式 例如：www.ruanpower.com/index.php?m=home&c=index&a=test&aid=5
     * pathinfo模式 例如:www.ruanpower.com/home/index/test?aid=5
     */
    private $_url_mode = 1;  
    private $_valid_modules = array();
    private $_module = 'Index';//大写首字母
    private $_controller = 'Index';//大写首字母
    private $_action = 'index';//小写首字母
    private $_var_module = 'm';
    private $_var_controller = 'c';
    private $_var_action = 'a';
    
    protected static $_instance = null;
    /**
     * private标记的构造方法
     */
    private function __construct(){
        return false;
    }
    /*
     * 创建__clone方法防止对象被复制克隆
     */
    public function __clone(){
        trigger_error('Clone is not allow!',E_USER_ERROR);
    }
    /**
     * 单例方法,用于访问实例的公共的静态方法
     */
    public static function getInstance(){
        if(!(self::$_instance instanceof self)){
            self::$_instance = new self;
        }
        return self::$_instance;
    }
    /**
     * 初始化
     * @param unknown $conf
     */
    public function init($conf){
        if (isset($conf ['url_mode']) && is_numeric($conf ['url_mode'])){
            $this->_url_mode = $conf ['url_mode'];
        }
        if (isset($conf ['valid_modules']) && is_array($conf ['valid_modules'])){
            $this->_valid_modules = $conf ['valid_modules'];
        }
        if (isset($conf ['default_module']) && !empty($conf ['default_module'])){
            $this->_module = $conf ['default_module'];
        }
        if (isset($conf ['default_controller']) && !empty($conf ['default_controller'])){
            $this->_controller = $conf ['default_controller'];
        }
        if (isset($conf ['default_action']) && !empty($conf ['default_action'])){
            $this->_action = $conf ['default_action'];
        }
        if (isset($conf ['var_module']) && !empty($conf ['var_module'])){
            $this->_var_module = $conf ['var_module'];
        }
        if (isset($conf ['var_controller']) && !empty($conf ['var_controller'])){
            $this->_var_controller = $conf ['var_controller'];
        }
        if (isset($conf ['var_action']) && !empty($conf ['var_action'])){
            $this->_var_action = $conf ['var_action'];
        }
    }
    public function getUrlMode(){
        return $this->_url_mode;
    }
    /**
     * 输出大写首字母,其他小写
     * @return Ambigous <number, unknown>
     */
    public function getModule(){
        $module = strtolower($this->_module);        
        return ucfirst($module);
    }
    /**
     * 输出大写首字母,其他小写
     * @return Ambigous <number, unknown>
     */
    public function getController(){
        $controller = strtolower($this->_controller);
        return ucfirst($controller);
    }
    /**
     * 设置默认module，用于以后plugins使用
     * @param unknown $module
     * @return boolean
     */
    public function setDefaultModule($module){
        if (empty($module)){
            return false;
        }   
        $this->_module = $module;
        return true;
    }

    /**
     * 设置默认Controller，用于以后plugins使用
     * @param unknown $controller
     * @return boolean
     */
    public function setDefaultController($controller){
        if (empty($controller)){
            return false;
        }
        $this->_controller = $controller;
        return true;
    }
    /**
     * 设置默认action，用于以后plugins使用
     * @param unknown $action
     * @return boolean
     */
    public function setDefaultAction($action){
        if (empty($action)){
            return false;
        }   
        $this->_action = $action;
        return true;
    }
    /**
     * 小写
     * @return Ambigous <number, unknown>
     */
    public function getAction(){
        return strtolower($this->_action);
    }
    /**
     * 解析一个路由，分解出module,controller,action
     * @return boolean
     */
    public function route(){
        switch ($this->_url_mode) {
            case 0:
                $this->parseDynamicUrl();
            ;
            break;
            case 1:
                $this->parsePathinfo();
            ;
                break;
            default:
                ;
            break;
        }
        return true;
    }
    /**
     * 解析request_uri
     * @param string $request_uri
     * @return boolean
     */
    private function parseDynamicUrl(){
        $request = $_REQUEST;
        if (isset($request [$this->_var_module]) && !empty($request [$this->_var_module])){
            $this->_module = $request [$this->_var_module];
        }
        if (isset($request [$this->_var_controller]) && !empty($request [$this->_var_controller])){
            $this->_controller = $request [$this->_var_controller];
        }
        if (isset($request [$this->_var_action]) && !empty($request [$this->_var_action])){
            $this->_action = $request [$this->_var_action];
        }
    }
    private function parsePathinfo(){
        $modules = $this->_valid_modules;
        if (trim($_SERVER['REQUEST_URI'], "/") == ''){
            return false;
        }
        $uri_arr_tmp = explode("?", trim($_SERVER['REQUEST_URI'], "/"));
        $uri_arr = explode("/", $uri_arr_tmp[0]);
        
        if ($uri_arr){
            $count = count($uri_arr);
            if ($count == 3){
                $this->_action = $uri_arr [2];
                $this->_controller = $uri_arr [1];
                $this->_module = $uri_arr [0];
            }elseif($count == 2){
                $this->_action = $uri_arr [1];
                $this->_controller = $uri_arr [0];
            }elseif ($count == 1){
                $this->_action = $uri_arr [0];
            }
        }
    }
}
?>
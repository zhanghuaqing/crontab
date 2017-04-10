<?php 
/**
 * 简单的视图引擎实现
 */
class Mif_View{
    protected $_vars = array();
    protected $_script_path = '';
    
    public function __construct($script_path = ''){
        if (is_dir($script_path)){
            $this->_script_path = $script_path;
        }else{
            $this->_getDefaultScriptPath();
        }
    }
    public function setScriptPath($script_path){
        if (!empty($script_path)){
            $this->_script_path = $script_path;
            return true;
        }
        return false;
    }
    public function getScriptPath(){
        return $this->_script_path;
    }
    public function assign($name, $value){
        if (empty($name)){
            return false;
        }
        $this->_vars [$name] = $value;
    }
    /**
     * 渲染视图模版
     * @param string $view_path
     * @param string $tpl_vars
     */
    public function render($view_path = '', $tpl_vars = ''){
        if (empty($view_path)){
            $route_obj = Mif_Router::getInstance();
            $action = $route_obj->getAction();
            $view_path = $this->_script_path . $action . '.phtml';
        }
        if (!file_exists($view_path)){
            throw new Exception('视图文件不存在');
        }
        if (!is_array($tpl_vars)){
            $tpl_vars = $this->_vars;
        }
        extract($tpl_vars);
        ob_start();
        require $view_path;
        $render_info = ob_get_clean();
        return $render_info;
    }
    /**
     * 渲染视图模版并输出
     * @param string $view_path
     * @param string $tpl_vars
     * @throws Exception
     */
    public function display($view_path = '', $tpl_vars = ''){
        if (empty($view_path)){
            $route_obj = Mif_Router::getInstance();
            $action = $route_obj->getAction();
            $view_path = $this->_script_path . $action . '.phtml';
        }
        if (!file_exists($view_path)){
            throw new Exception('视图文件不存在');
        }
        if (!is_array($tpl_vars)){
            $tpl_vars = $this->_vars;
        }
        extract($tpl_vars);
        require $view_path;
    }
    private function _getDefaultScriptPath(){
        $route_obj = Mif_Router::getInstance();
        $module = $route_obj->getModule();
        $controller = $route_obj->getController();
        
        $view_base = APP_PATH . 'application/';
        if ($module == 'Index'){
            $view_path = $view_base . 'views/' . strtolower(Mif_Starup::parseHump($controller)) .'/';
        }else{
            $view_path = $view_base . 'modules/' . $module . '/views/'.strtolower(Mif_Starup::parseHump($controller)) . '/';
        }
        if (!is_dir($view_path)){
            throw new Exception('视图目录' . $view_path . '不存在');
        }
        $this->_script_path = $view_path;
    }
}
?>
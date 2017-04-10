<?php 
/**
 * 控制器实现抽象类，用于继承
 * @author huaqing1
 *
 */
abstract class Mif_Controller{
    protected $_view;
    protected $_autorender = true;//自动渲染视图开关,默认动作结束后开启自动渲染视图
    
    public function __construct(){
        $script_path = '';//默认视图目录
        $this->_view = new Mif_View($script_path);
    }
    public function __destruct(){
        if ($this->_autorender){
            $this->display();
        }
    }
    
    protected function assign($name, $value){
        return $this->_view->assign($name, $value);
    }
    
    protected function render(){
        return $this->_view->render();
    }
    
    protected function display(){
        return $this->_view->display();
    }
    
    protected function enableView(){
        $this->_autorender = true;
    }
    protected function disableView(){
        $this->_autorender = false;
    }
    
}
?>
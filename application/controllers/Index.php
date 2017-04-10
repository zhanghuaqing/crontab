<?php 
class IndexController extends Mif_Controller{
    public function index(){
        $this->assign('test', 'good');
        $render = $this->render();
        var_dump($render);
    }
}
?>
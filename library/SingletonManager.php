<?php
/**
 * 类单例 管理底层类
 *
 * 使用的时候直接使用 SingletonManager::$SINGLETON_POOL->getInstance('SongModel'); 即可
 * 如果有特别需求，只想自己使用自己的类实例，则可以实例化new SingletonManager(),
 * 然后调用实例化后的getInstance()方法
 *
 * @author zhiyuan <zhiyuan12@staff.sina.com.cn>
 *
 */
class SingletonManager
{
    public static $SINGLETON_POOL;
    private $_singletons;
    /**
     * 获取一个classname类型的实例，如果实例存在，则直接获取，如果实例不存在则利用多余的参数创建一个
     */
    public function getInstance($classname) {
        $args = func_get_args();
        $key = $this->_buildKey($classname, $args);
        if (isset($this->_singletons[$key])) {
            return $this->_singletons[$key];
        }
        array_shift($args);
        $ret = $this->_createInstance($classname, $args);
        if (!$ret) {
            return false;
        }
        return $this->_singletons[$key] = $ret;
    }
    /**
     * 获取一个classname类型的实例,并在第一次获取时调用$init_function($instance)
     * 如果init_function失败，则该函数返回失败
     */
    public function getInstanceWithInit($classname, $init_function) {
        $args = func_get_args();
        array_shift($args);
        array_shift($args);
        $ret = $this->getInstance($classname, $args);
        if (!$ret) { 
            return false; 
        }
        if (!$init_function($ret)) {
            return false;
        }
        return $ret;
    }
    /**
     * 实例化一个对象
     *
     * @param String $className
     * @param Array $arguments
     * @return Mixed instance of $className
     */
    private function _createInstance($className, array $arguments = array()) {
        if (class_exists($className)) {
            return call_user_func_array(array(
                new ReflectionClass($className) ,
                'newInstance'
            ) , $arguments);
        }
        Debug::setErrorMessage('类不存在');
        return false;
    }
    /**
     * 获取一个classname的key
     */
    private function _buildKey($classname, $args = '') {
        return $classname . serialize($args);
    }
}
SingletonManager::$SINGLETON_POOL = new SingletonManager();

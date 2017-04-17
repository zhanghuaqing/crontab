<?php 
/**
 * 通过静态类来实现注册表的操作,默认会初始化配置文件里的静态变量
 * 
 */
class Mif_Registry{
    private static $data;
    /**
     * 往注册表添加一条数据
     * @param unknown $name
     * @param unknown $value
     */
    public static function set($name, $value){
        if (!empty($name)){
            self::$data [$name] = $value;
            if ($name == 'log_path' && $value){
                self::$data [$name] = rtrim($value, '/') . '/';
            }
            return true;
        }
        return false;
    }
    /**
     * 从注册表获取一条数据
     * @param unknown $name
     * @param unknown $value
     */
    public static function get($name){
        if (isset(self::$data [$name])){
            return self::$data [$name];
        }
        return false;
    }
}
?>
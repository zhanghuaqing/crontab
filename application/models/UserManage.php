<?php 
/**
 * 用户表操作
 * 1、增删改查
 * 2、后台登陆操作
 * 3、认证操作
 * @author huaqing1
 *
 */
class UserManageModel{
    //在线状态值
    const OFFLINE = 0;//未激活
    const ONLINE = 1;//激活
    public static $STATUS_CONF = array(
        self::OFFLINE => array(
            'code' => self::OFFLINE,
            'name' => '未激活'
        ),
        self::ONLINE => array(
            'code' => self::ONLINE,
            'name' => '激活'
        ),
    );
    //******************************用户数据增删改查操作*********************************//
    /**
     * 添加一个用户
     * @param unknown $data
    */
    public function addUser($data){
        $insert_data = $this->_checkInsertData($data);
        if ($insert_data === false){
            return false;
        }
        $user_obj = SingletonManager::$SINGLETON_POOL->getInstance('UserModel');
        return $user_obj->add($insert_data);
    }
    /**
     * 判断用户是否存在
     * @param unknown $data
     */
    public function checkUserExist($user_name){
        if (empty($user_name)){
            return false;
        }
        $user_obj = SingletonManager::$SINGLETON_POOL->getInstance('UserModel');
        $where = array(
            array('field' => 'name','condition' => $user_name),
        );
        $list = $user_obj->getList(-1, -1, 'id', $where);
        if ($list){
            return true;
        }
        return false;
    }
    /**
     * 修改用户信息，这里不能修改状态
     * @param unknown $id
     * @param unknown $update
     */
    public function updateUserInfo($id, $update){
        if (!is_numeric($id)){
            Debug::setErrorMessage("参数错误");
            return false;
        }
        $data = $this->_checkUpdateData($update);
        if ($data === false){
            return false;
        }
        $data ['id'] = $id;
        return $this->_updateUserInfo($data);
    }
    
    /**
     * 彻底移除用户，
     *
     * @param int $id
     * @return boolean
     */
    public function removeUser($id){
        if (!is_numeric($id)){
            Debug::setErrorMessage("参数错误");
            return false;
        }
        $where[] = array('field' => 'id', 'condition' => $id);
        $user_obj = SingletonManager::$SINGLETON_POOL->getInstance('UserModel');
        return $user_obj->remove($where);
    }
    /**
     * 获取一个用户信息
     * @param int $id 任务ID
     * @return array
     */
    public function getUser($id){
        if (! is_numeric($id)) {
            Debug::setErrorMessage(Debug::ERROR_ERROR_PARAMS);
            return false;
        }
        $user_obj = SingletonManager::$SINGLETON_POOL->getInstance('UserModel');
        return $user_obj->getByKey($id);
    }
    /**
     * 按状态获取用户列表
     * @param unknown $count
     * @param unknown $page
     * @param unknown $status
     */
    public function getUserList($count = 20, $page = 0, $status = self::ONLINE, $field = '*', $order_by = null){
        if (!is_numeric($count)){
            $count = 20;
        }
        if (!is_numeric($page)){
            $page = 0;
        }
        $user_obj = SingletonManager::$SINGLETON_POOL->getInstance('UserModel');
        if (is_numeric($status)){
            $where = array(
                array('field' => 'active','condition' => $status)
            );
        }
    
        return $user_obj->getList($count, $page, $field, $where, $order_by);
    }
    /**
     * 激活用户
     *
     * @param int $id
     * @return boolean
     */
    public function onlineUser($id)
    {
        if (! is_numeric($id)) {
            Debug::setErrorMessage(Debug::ERROR_ERROR_PARAMS);
            return false;
        }
        $data = array(
            'id' => $id,
            'active' => self::ONLINE
        );
        return $this->_updateUserInfo($data);
    }
    
    /**
     * 下线用户
     *
     * @param int $id
     * @return boolean
     */
    public function offlineUser($id)
    {
        if (! is_numeric($id)) {
            Debug::setErrorMessage(Debug::ERROR_ERROR_PARAMS);
            return false;
        }
        $data = array(
            'id' => $id,
            'active' => self::OFFLINE
        );
        return $this->_updateUserInfo($data);
    }
    private function _updateUserInfo($data)
    {
        if (empty($data) || ! isset($data['id'])) {
            Debug::setErrorMessage(Debug::ERROR_ERROR_PARAMS);
            return false;
        }
        $where = array(
            array(
                'field' => 'id','condition' => $data['id']
            )
        );
        //修改用户
        $update_userid = '';
        if($update_userid>0 )$data ['alter_user'] = $update_userid;
        unset($data ['id']);
        $user_obj = SingletonManager::$SINGLETON_POOL->getInstance('UserModel');
        return $user_obj->update($data, $where);
    }
    /**
     * 判断插入一条的数据有效性,返回处理后的数据
     * @param unknown $data
     */
    private function _checkInsertData($data){
        $data_new = array();
        $is_exist = $this->checkUserExist($data ['name']);
        if ($is_exist){
            Debug::setErrorMessage('任务已经存在');
            return false;
        }
        if (empty($data ['name'])){
            Debug::setErrorMessage('用户名为空！');
            return false;
        }
        if ($this->strLength($data ['name']) > 50){
            Debug::setErrorMessage('用户名超过50个字符');
            return false;
        }
        $data_new ['name'] = $data ['name'];
        if (empty($data ['pwd'])){
            Debug::setErrorMessage('密码为空！');
            return false;
        }
        if (preg_match("/[\x7f-\xff]/", $data ['pwd'])){
            Debug::setErrorMessage('密码含有中文字符');
            return false;
        }
        if (strlen($data ['pwd']) > 32){
            Debug::setErrorMessage('密码超过32个字符');
            return false;
        }
        $data_new ['pwd'] = $data ['pwd'];
        if (isset($data ['description']))$data_new ['description'] = $data ['description'];
        
        $data_new ['create_time'] = date('Y-m-d H:i:s');
        return $data_new;
    }
    /**
     * 判断更新的数据有效性(不判断状态),返回处理后的数据
     * @param unknown $data
     */
    private function _checkUpdateData($data){
        $data_new = array();
        if (isset($data ['name'])){
            if (empty($data ['name'])){
                Debug::setErrorMessage('用户名为空！');
                return false;
            }
            if ($this->strLength($data ['name']) > 50){
                Debug::setErrorMessage('用户名超过50个字符');
                return false;
            }
            $data_new ['name'] = $data ['name'];
        }
        if(isset($data ['pwd'])){
            if (empty($data ['pwd'])){
                Debug::setErrorMessage('密码为空！');
                return false;
            }
            if (preg_match("/[\x7f-\xff]/", $data ['pwd'])){
                Debug::setErrorMessage('密码含有中文字符');
                return false;
            }
            if (strlen($data ['pwd']) > 32){
                Debug::setErrorMessage('密码超过32个字符');
                return false;
            }
            $data_new ['pwd'] = $data ['pwd'];
        }
        if (isset($data ['description']))$data_new ['description'] = $data ['description'];
        
        return $data_new;
    }
    /**
     * PHP获取字符串中英文混合长度
     * @param $str string 字符串
     * @param $$charset string 编码
     * @return 返回长度，1中文=1位，2英文=1位
     */
    public function strLength($str,$charset='utf-8'){
        if($charset=='utf-8') $str = iconv('utf-8','gb2312',$str);
        $num = strlen($str);
        $cnNum = 0;
        for($i=0;$i<$num;$i++){
            if(ord(substr($str,$i+1,1))>127){
                $cnNum++;
                $i++;
            }
        }
        $enNum = $num-($cnNum*2);
        $number = ($enNum/2)+$cnNum;
        return ceil($number);
    }
    //**************************登陆逻辑******************************
    /**
     * 根据用户名去匹配密码是否正确
     * 正确的用户，种下cookie
     * @param unknown $name
     * @param unknown $pwd
     */
    public function checkUserValid($name, $pwd){
        
    }
    
}

?>
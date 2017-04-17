<?php 
/**
 * 以smtp方式发送邮件
 * 工厂模式类
 * 需要实现
 * @author huaqing1
 *
 */
class Mail_SmtpMail implements Mail_MailIterface{
    private $_phpmailer_obj = null;
    private $_host = '';
    private $_port = '';
    //*********邮件发送信息设置变量**********
    private $_from_mailer = '';
    private $_from_mailername = '';
    private $_user_name = '';
    private $_password = '';
    private $_to_mailers = null;
    private $_subject = '';
    private $_body = '';
    private $_bodytype = 'text/html';
    
    public static $bodytype_list = array(
        'text/html','text/plain'
    );
    public static $hostlist_conf = array(
        'smtp.163.com' => array('port' => array(465,987), 'mail_flag' => '163.com'),
    	'smtp.qq.com' => array('port' => array(465,994), 'mail_flag' => 'qq.com'),
    );
    
    /**
     * 初始化
     * 
     * @param unknown $mail_conf
     * $mail_conf = array(
     * 		'from_mailer' => 'xxx@qq.com',
     * 		'from_mailername' => 'xxx',
     * 		'user_name' => 'xxx',
     * 		'password' => '#@!',
     * 		'to_mailers' => array(array('yyy@qq.com','yyy'),...),
     * 		'subject' => 'xxx@qq.com',
     * 		'body' => 'hello',
     * 		'bodytype' => 'text/html',
     * )
     */
    public function __construct($mail_conf){
    	/***************初始化邮件发送类****************/
        $this->_phpmailer_obj = new Mail_PHPMailer();
        $this->_phpmailer_obj->SMTPDebug = 0;
        $this->_phpmailer_obj->isSMTP();
        $this->_phpmailer_obj->SMTPAuth=true;
        $this->_phpmailer_obj->SMTPSecure = 'ssl';//需要php_openssl扩展支持
        $this->_phpmailer_obj->Hostname = 'localhost';
        $this->_phpmailer_obj->CharSet = 'UTF-8';
        /**************初始化邮件发送配置信息****************/
        if(isset($mail_conf ['from_mailer']) && !empty($mail_conf ['from_mailer'])){
        	$this->setFrom($mail_conf ['from_mailer']);
        }
        if(isset($mail_conf ['from_mailername']) && !empty($mail_conf ['from_mailername'])){
        	$this->setFromName($mail_conf ['from_mailername']);
        }
        if(isset($mail_conf ['user_name']) && !empty($mail_conf ['user_name'])){
        	$this->setUserName($mail_conf ['user_name']);
        }
        if(isset($mail_conf ['password']) && !empty($mail_conf ['password'])){
        	$this->setPassword($mail_conf ['password']);
        }
        if(isset($mail_conf ['to_mailers']) && is_array($mail_conf ['to_mailers'])){
        	$this->setMailTo($mail_conf ['to_mailers']);
        }
        if(isset($mail_conf ['subject']) && !empty($mail_conf ['subject'])){
        	$this->setSubject($mail_conf ['subject']);
        }
        if(isset($mail_conf ['body']) && !empty($mail_conf ['body'])){
        	$this->setBody($mail_conf ['body']);
        }
        if(isset($mail_conf ['bodytype']) && !empty($mail_conf ['bodytype'])){
        	$this->setBodyType($mail_conf ['bodytype']);
        }
    }
    
    public function send(){
    	/****************检验邮件发送参数是否配置了*****************/
    	if (empty($this->_host) || !in_array($this->_host, array_keys(self::$hostlist_conf))) {
    		Debug::setErrorMessage('发送host配置错误');
    		return false;
    	}
        $this->_phpmailer_obj->Host = $this->_host;
    	if (empty($this->_port)) {
    		Debug::setErrorMessage('port配置为空');
    		return false;
    	}
    	$this->_phpmailer_obj->Port = $this->_port;
    	if (empty($this->_from_mailer)) {
    		Debug::setErrorMessage('from_mailer配置为空');
    		return false;
    	}
    	$this->_phpmailer_obj->From = $this->_from_mailer;
    	if (empty($this->_from_mailername)) {
    		Debug::setErrorMessage('from_mailername配置为空');
    		return false;
    	}
    	$this->_phpmailer_obj->FromName = $this->_from_mailername;
    	if (empty($this->_user_name)) {
    		Debug::setErrorMessage('user_name配置为空');
    		return false;
    	}
    	$this->_phpmailer_obj->Username = $this->_user_name;
    	if (empty($this->_password)) {
    		Debug::setErrorMessage('password配置为空');
    		return false;
    	}
    	$this->_phpmailer_obj->Password = $this->_password;
    	if ($this->_bodytype == 'text/html') {
    		$this->_phpmailer_obj->isHTML(true);
    	}
    	if (empty($this->_subject)) {
    		Debug::setErrorMessage('subject配置为空');
    		return false;
    	}
    	$this->_phpmailer_obj->Subject = $this->_subject;
    	if (empty($this->_body)) {
    		Debug::setErrorMessage('body配置为空');
    		return false;
    	}
    	$this->_phpmailer_obj->Body = $this->_body;
    	if (!is_array($this->_to_mailers)) {
    		Debug::setErrorMessage('to_mailers配置为空');
    		return false;
    	}
    	foreach ($this->_to_mailers as $to_mailer){
    		$this->_phpmailer_obj->addAddress($to_mailer [0], $to_mailer [1]);
    	}
    	//发送
    	return $this->_phpmailer_obj->send();
    }
    /**
     * 设置邮件发送账号，根据账号匹配是哪类邮箱（163、qq）,设置host
     * @param unknown $from_mailer
     */
    public function setFrom($from_mailer){
        if (empty($from_mailer)) {
        	return false;
        }
        foreach (self::$hostlist_conf as $host => $host_info){
        	if (strstr($from_mailer, $host_info ['mail_flag'])) {
        		$this->_host = $host;
        		$this->_port = $host_info ['port'][0];
        		$this->_from_mailer = $from_mailer;
        		return true;
        	}
        }
        return false;
    }
    
    public function setFromName($from_mailername){
        if (empty($from_mailername)) {
        	return false;
        }
        $this->_from_mailername = $from_mailername;
        return true;
    }
    
    public function setUserName($user_name){
    	if (empty($user_name)) {
    		return false;
    	}
    	$this->_user_name = $user_name;
    	return true;
    }
    
    public function setPassword($password){
    	if (empty($password)) {
    		return false;
    	}
    	$this->_password = $password;
    	return true;
    }
    
    public function setMailTo($to_mailers){
    	if (empty($to_mailers) || !is_array($to_mailers)) {
    		return false;
    	}
    	$this->_to_mailers = $to_mailers;
    	return true;
    }
    
    public function setSubject($subject){
    	if (empty($subject)) {
    		return false;
    	}
    	$this->_subject = $subject;
    	return true;
    }
    
    public function setBody($body){
    	if (empty($body)) {
    		return false;
    	}
    	$this->_body = $body;
    	return true;
    }
    
    public function setBodyType($body_type){
    	if (empty($body_type)) {
    		return false;
    	}
    	if (!in_array($body_type, self::$bodytype_list)) {
    		return false;
    	}
    	$this->_bodytype = $body_type;
    	return true;
    }
}
?>
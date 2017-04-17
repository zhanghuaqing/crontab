<?php 
/**
 * 邮件发送接口
 * 接口需要实现的方法
 * 1、send() 发送邮件方法
 * 2、setFrom() 设置发送邮件方
 * 3、setFromName() 设置发送方昵称
 * 4、setUserName() 设置发送方用户名
 * 5、setPassword() 设置发送方密码
 * 4、setMailTo() 设置接收方，可以发送多个，数组分隔
 * 5、setSubject() 设置邮件标题
 * 6、setBody() 设置邮件内容
 * 7、setBodyType() 设置邮件格式,html/text
 * 
 * @author huaqing1
 *
 */
interface Mail_MailIterface{
    
    public function send();
    
    public function setFrom($from_mailer);
    
    public function setFromName($from_mailername);
    
    public function setUserName($user_name);
    
    public function setPassword($password);
    
    public function setMailTo($to_mailers);
    
    public function setSubject($subject);
    
    public function setBody($body);

    public function setBodyType($body_type);
}
?>
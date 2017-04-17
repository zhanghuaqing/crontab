<?php 
class Mail_MailFactory{
    public static function factory($sendmail_class = ''){
        if (empty($sendmail_class)){
            $sendmail_class = 'Mail_SmtpMail';
        }
        $class = new $sendmail_class;
        return $class;
    }
}
?>
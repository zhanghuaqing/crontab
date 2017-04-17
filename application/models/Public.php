<?php 
/**
 * 信息配置
 * @author huaqing1
 *
 */
class PublicModel{
    /**
     * 验证mac合法性
     * @param unknown $mac
     */
    public static function checkMacValid($mac){
    	if (empty($mac)) {
    		return false;
    	}
    	if (!preg_match("/^[A-F0-9]{2}(:[A-F0-9]{2}){5}$/i", $mac)) {
    		return false;
    	}
    	return true;
    }
    /**
     * 验证IP合法性
     * @param unknown $ip
     */
    public static function checkIpValid($ip){
    	if (empty($ip)) {
    		return false;
    	}
    	if (!preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/", $ip)) {
    		return false;
    	}
    	return true;
    }
    /**
     * 验证crontime输入格式
     * @param unknown $cron_time
     */
    public static function checkCronTime($cron_time){
        if (!preg_match('/^((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)$/i',trim($cron_time))) {
        	return false;
        }
        return true;
    }
    /**
     * 验证croncmd输入格式
     * @param unknown $cron_time
     */
    public static function checkCronCmd($cron_cmd){
        if (empty($cron_cmd)){
            return false;
        }
    	return true;
    }

    /**
     * 获取服务器端网卡信息（包括Mac、ip地址）
     * @return string
     */ 
    public static function getServerEthInfo(){
        $ips = array();
        $info = `/sbin/ifconfig   -a`;
        $infos = explode("\n\n", $info);
        foreach ($infos as $info) {
            $info = trim($info);
            if (substr($info, 0, 3) == 'eth') {
                $lines = explode("\n", $info);
                $interface = substr($lines[0], 0, strpos($lines[0], ' '));
                preg_match('/HWaddr ([A-Fa-f0-9]{2}(:[A-Fa-f0-9]{2}){5})/i', $lines[0], $matches);
                $mac = $matches ? $matches [1] : '';
                if ($mac == ''){
                    $mac = trim(substr($lines[0], strlen($lines[0]) - 19));
                }
                preg_match('/addr:([0-9\.]+)/i', $lines[1], $matches);
                if (empty($matches)){
                    preg_match('/inet ([0-9\.]+)/i', $lines[1], $matches);
                }
                $ip = $matches ? $matches[1] : '';
                $ips[$interface] = array('ip' => $ip, 'mac' => $mac);
            }
        }
        if (empty($ips)){
            throw new Exception('服务器网卡信息获取失败');
        }
        return $ips;
    }
    /**
     * 发送报警邮件
     * @param unknown $mail_content
     * @param string $sixin_content
     * @param string $subject
     * @return boolean
     */
    public static function sendWarnMail($mail_content, $subject = ''){
    	$mail_info = Mif_Registry::get('mail_conf');
    	
    	$mail_info ['subject'] = $subject;
    	$mail_info ['body'] = $mail_content;
    	$testmodel = new Mail_SmtpMail($mail_info);
    	return $testmodel->send();
    }
}

?>
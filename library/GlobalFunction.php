<?php
// 公用函数库
if (! function_exists ( "escape" )) {
	/**
	 * 对特殊字符转义
	 *
	 * @param String $string
	 *        	待转义字符
	 * @param String $esc_type
	 *        	转义类型
	 * @param String $char_set
	 *        	字符编码
	 */
	function escape($string, $esc_type = 'html', $char_set = 'UTF-8') {
		switch ($esc_type) {
			case 'html' :
				return htmlspecialchars ( $string, ENT_QUOTES, $char_set );

			case 'htmlall' :
				return htmlentities ( $string, ENT_QUOTES, $char_set );

			case 'url' :
				return rawurlencode ( $string );

			case 'urlpathinfo' :
				return str_replace ( '%2F', '/', rawurlencode ( $string ) );

			case 'quotes' :
				// escape unescaped single quotes
				return preg_replace ( "%(?<!\\\\)'%", "\\'", $string );

			case 'hex' :
				// escape every character into hex
				$return = '';
				for($x = 0; $x < strlen ( $string ); $x ++) {
					$return .= '%' . bin2hex ( $string [$x] );
				}
				return $return;

			case 'hexentity' :
				$return = '';
				for($x = 0; $x < strlen ( $string ); $x ++) {
					$return .= '&#x' . bin2hex ( $string [$x] ) . ';';
				}
				return $return;

			case 'decentity' :
				$return = '';
				for($x = 0; $x < strlen ( $string ); $x ++) {
					$return .= '&#' . ord ( $string [$x] ) . ';';
				}
				return $return;

			case 'javascript' :
				// escape quotes and backslashes, newlines, etc.
				return strtr ( $string, array (
						'\\' => '\\\\',
						"'" => "\\'",
						'"' => '\\"',
						"\r" => '\\r',
						"\n" => '\\n',
						'</' => '<\/'
						) );

			case 'mail' :
				// safe way to display e-mail address on a web page
				return str_replace ( array (
						'@',
						'.'
						), array (
						' [AT] ',
						' [DOT] '
						), $string );

			case 'nonstd' :
				// escape non-standard chars, such as ms document quotes
				$_res = '';
				for($_i = 0, $_len = strlen ( $string ); $_i < $_len; $_i ++) {
					$_ord = ord ( substr ( $string, $_i, 1 ) );
					// non-standard char, escape it
					if ($_ord >= 126) {
						$_res .= '&#' . $_ord . ';';
					} else {
						$_res .= substr ( $string, $_i, 1 );
					}
				}
				return $_res;

			default :
				return $string;
		}
	}
}

/**
 * 中英文混杂字符串截取
 *
 * @param string $string
 *        	原字符串
 * @param interger $length
 *        	截取的字符数
 * @param string $etc
 *        	省略字符
 * @param string $charset
 *        	原字符串的编码
 *
 * @return string
 *
 */
function substr_cn($string, $length = 80, $charset = 'UTF-8', $etc = '...') {
	if (mb_strwidth ( $string, 'UTF-8' ) < $length)
	return $string;
	return mb_strimwidth ( $string, 0, $length, $etc, $charset );
}
/**
 * 显示错误信息
 */
function show_error($msg) {
	echo $msg;
	exit ();
}
/**
 * 求取最长公共子序列,建议str2长度短于str1
 *
 * @param array $str1
 * @param array $str2
 * @return int
 */
function longest_common_subsequence($str1, $str2) {
	if (! is_array ( $str1 ) || ! is_array ( $str2 )) {
		return false;
	}
	$len1 = count ( $str1 );
	$len2 = count ( $str2 );
	$result = null;
	for($i = 0; $i <= $len2; $i ++) {
		$result [0] [$i] = 0;
		$result [1] [$i] = 0;
		$result [2] [$i] = 0;
	}
	$row = 0;
	for($i = 1; $i <= $len1; $i ++) {
		for($j = 1; $j <= $len2; $j ++) {
			$row = $i % 2 + 1;
			$last_row = ($i - 1) % 2 + 1;
			if (strcmp ( $str1 [$i - 1], $str2 [$j - 1] ) == 0) {
				$result [$row] [$j] = $result [$last_row] [$j - 1] + 1;
			}
			$result [$row] [$j] = max ( $result [$row] [$j], $result [$last_row] [$j] );
			$result [$row] [$j] = max ( $result [$row] [$j], $result [$row] [$j - 1] );
		}
	}
	return max ( $result [$row] );
}
/**
 * 判断字符串是否具有某前缀
 */
function start_with($str, $prefix) {
	$start_len = strlen ( $prefix );
	return strlen ( $str ) >= $start_len && substr ( $str, 0, $start_len ) == $prefix;
}
/**
 * 字符串拆分成数组，拆分规则：中文每个字都拆，英文以空格\符号\中文为分割拆
 */
function str2array($str, $encoding = "utf8") {
	$i = 0;
	$len = mb_strlen ( $str, $encoding );
	$result = array ();
	$tmp = "";
	while ( $i < $len ) {
		$next = mb_substr ( $str, $i, 1, $encoding );
		if ((ord ( $next ) > 64 && ord ( $next ) < 91) || (ord ( $next ) > 96 && ord ( $next ) < 123) || ord ( $next ) == 39) {
			$tmp .= $next;
			$i ++;
			continue;
		}
		if (! empty ( $tmp )) {
			array_push ( $result, $tmp );
			$tmp = "";
		}
		if (ord ( $next ) != 32) {
			array_push ( $result, $next );
		}
		$i ++;
	}
	if (! empty ( $tmp )) {
		array_push ( $result, $tmp );
	}
	return $result;
}
/**
 * 分解字符串为数组
 */
function mb_str_split($str, $length = 1, $encoding = "utf8") {
	if ($length < 1) {
		return false;
	}
	$result = array ();
	for($i = 0; $i < mb_strlen ( $str, $encoding ); $i += $length) {
		$result [] = mb_substr ( $str, $i, $length, $encoding );
	}
	return $result;
}
/**
 * 获取执行命令机器的ip和mac
 */
function get_network_interface() {
	$ips = array ();
	$info = `/sbin/ifconfig   -a`;
	$infos = explode ( "\n\n", $info );
	foreach ( $infos as $info ) {
		$info = trim ( $info );
		if (substr ( $info, 0, 3 ) == 'eth') {
			$lines = explode ( "\n", $info );
			$interface = substr ( $lines [0], 0, strpos ( $lines [0], '   ' ) );
			$mac = substr ( $lines [0], strlen ( $lines [0] ) - 19 );
			preg_match ( '/addr:([0-9\.]+)/i', $lines [1], $matches );
			$ip = $matches [1];
			$ips [$interface] = array (
					'ip' => trim($ip),
					'mac' => trim($mac)
			);
		} // end if
	} // end foreach
	return $ips;
}

/*
 * 根据id获取分表的后缀。 @param Mixed $id, 分表的对象id，如用户id @param int $n, 分表的个数
 */
function get_table_suffix($id, $n = 16) {
	$h = sprintf ( "%u", crc32 ( $id ) );
	$h1 = intval ( $h / $n );
	$h2 = $h1 % $n;
	$h3 = base_convert ( $h2, 10, 16 );
	$h4 = sprintf ( "%02s", $h3 );
	return $h4;
}
/*
 * lrc歌词顺序输出 hongfei 20121029 return array array(11) { ["00:00.00"]=> string(21)
 * "梁静茹 - 偶阵雨" ["00:01.90"]=> string(0) "" ["00:03.87"]=> string(40) "词：陈没
 * 曲：木兰号aka陈韦伶" ["00:13.76"]=> string(0) "" ["00:16.76"]=> string(31)
 * "未来就等来了再决定1111"
 */
function lyric_handler($ly, $withTimeStamp = true) {
	//$ly = preg_replace ( "/(\r\n|\n|\r)+/", "\n", trim ( $ly ) );
	$ly = preg_replace ( "/(\r\n|\n|\r)/", "\n", trim ( $ly ) );
	$ly_array = explode ( "\n", $ly );
	$result = array ();
	if (is_array ( $ly_array )) {
		foreach ( $ly_array as $la ) {
			$lysplit [] = preg_split ( "/(\[\d{2}:\d{2}.\d{2}\]|\[\d{2}:\d{2}:\d{2}\]|\[\d{2}:\d{2}\])/", $la, 0, PREG_SPLIT_DELIM_CAPTURE );
		}
	}
	foreach ( $lysplit as $ls ) {
		$lyunique = array ();
		foreach ( $ls as $lsi ) {
			if ($lsi) {
				$lyunique [] = $lsi;
			}
		}
		$lyArray [] = $lyunique;
	}
	// clean up end...
	foreach ( $lyArray as $la ) {
		$ii = 0;
		if (count ( $la ) == 1) {
			if (preg_match ( "/(\[\d{2}:\d{2}.\d{2}\]|\[\d{2}:\d{2}:\d{2}\]|\[\d{2}:\d{2}\])/", $la [0] )) {
				$time = preg_replace ( "/[\[|\]]/", '', $la [0] );
				$result [$time] = "";
				continue;
			}
		}
		foreach ( $la as $k => $text ) {
			if (! preg_match ( "/^\[/", $text )) {
				for($i = $ii; $i < $k; $i ++) {
					$time = preg_replace ( "/[\[|\]]/", '', $la [$i] );
					$result [$time] = $text;
				}
				$ii = $k + 1;
			}
		}
	}
	if (is_array ( $result ) && count ( $result ) > 0) {
		ksort ( $result );
		if (! $withTimeStamp) {
			return array_values ( $result );
		}
		return $result;
	} else {
		return $ly_array;
	}
}
function dealDate($date) {
	$date_arr = explode ( "-", $date );
	$date_str = '';
	if (isset ( $date_arr [0] ) && ! empty ( $date_arr [0] )) {
		$date_str .= $date_arr [0] . "年";
	}
	if (isset ( $date_arr [1] ) && ! empty ( $date_arr [1] )) {
		$date_str .= $date_arr [1] . "月";
	}
	if (isset ( $date_arr [2] ) && ! empty ( $date_arr [2] )) {
		$date_str .= $date_arr [2] . "日";
	}
	return $date_str;
}
function checkOS($user_OSagent = '') {
	if (stripos ( $user_OSagent, "iPhone" ) || stripos ( $user_OSagent, "iPod" )) {
		$visitor_os = "iphone";
	} elseif (stripos ( $user_OSagent, "iPad" )) {
		$visitor_os = "ipad";
	} elseif (stripos ( $user_OSagent, "Android" ) !== false) {
		$visitor_os = "android";
	} elseif (stripos ( $user_OSagent, "Windows Phone" )) {
		$visitor_os = "winphone";
	} elseif (stripos ( $user_OSagent, "NT 6.1" )) {
		$visitor_os = "Windows7";
	} elseif (preg_match ( '/NT 5.1/', $user_OSagent )) {
		$visitor_os = "Windows XP (SP2)";
	} elseif (stripos ( $user_OSagent, "Windows XP" )) {
		$visitor_os = "Windows XP";
	} elseif (stripos ( $user_OSagent, "NT 5.2" ) && stripos ( $user_OSagent, "WOW64" )) {
		$visitor_os = "Windows XP 64-bit Edition";
	} elseif (stripos ( $user_OSagent, "NT 5.2" )) {
		$visitor_os = "Windows 2003";
	} elseif (stripos ( $user_OSagent, "NT 6.0" )) {
		$visitor_os = "Windows Vista";
	} elseif (stripos ( $user_OSagent, "NT 5.0" )) {
		$visitor_os = "Windows 2000";
	} elseif (stripos ( $user_OSagent, "4.9" )) {
		$visitor_os = "Windows ME";
	} elseif (stripos ( $user_OSagent, "NT 4" )) {
		$visitor_os = "Windows NT 4.0";
	} elseif (stripos ( $user_OSagent, "98" )) {
		$visitor_os = "Windows 98";
	} elseif (stripos ( $user_OSagent, "95" )) {
		$visitor_os = "Windows 95";
	} elseif (stripos ( $user_OSagent, "NT 9.0" )) {
		$visitor_os = "Windows NT 9.0";
	} elseif (stripos ( $user_OSagent, "Mac" )) {
		$visitor_os = "Mac";
	} elseif (stripos ( $user_OSagent, "Linux" )) {
		$visitor_os = "Linux";
	} elseif (stripos ( $user_OSagent, "Unix" )) {
		$visitor_os = "Unix";
	} elseif (stripos ( $user_OSagent, "FreeBSD" )) {
		$visitor_os = "FreeBSD";
	} elseif (stripos ( $user_OSagent, "SunOS" )) {
		$visitor_os = "SunOS";
	} elseif (stripos ( $user_OSagent, "BeOS" )) {
		$visitor_os = "BeOS";
	} elseif (stripos ( $user_OSagent, "OS/2" )) {
		$visitor_os = "OS/2";
	} elseif (stripos ( $user_OSagent, "PC" )) {
		$visitor_os = "Macintosh";
	} elseif (stripos ( $user_OSagent, "AIX" )) {
		$visitor_os = "AIX";
	} elseif (stripos ( $user_OSagent, "IBM OS/2" )) {
		$visitor_os = "IBM OS/2";
	} elseif (stripos ( $user_OSagent, "BSD" )) {
		$visitor_os = "BSD";
	} elseif (stripos ( $user_OSagent, "NetBSD" )) {
		$visitor_os = "NetBSD";
	} elseif (preg_match ( '/Apple/i', $user_OSagent )) {
		$visitor_os = "Applepc";
	} elseif (stripos ( $user_OSagent, "NT 6.1" )) {
		$visitor_os = "Windows7";
	} elseif (preg_match ( '/Opera/i', $user_OSagent )) {
		$visitor_os = "Opera";
	} elseif (preg_match ( '/Nokia/i', $user_OSagent )) {
		$visitor_os = "nokia";
	} elseif (preg_match ( '/OPPO/i', $user_OSagent )) {
		$visitor_os = "OPPO ";
	} elseif (preg_match ( '/SymbianOS/i', $user_OSagent )) {
		$visitor_os = "SymbianOS ";
	} elseif (preg_match ( '/Windows Mobile/i', $user_OSagent )) {
		$visitor_os = "Windows Mobile";
	} elseif (preg_match ( '/DoCoMo/i', $user_OSagent )) {
		$visitor_os = "DoCoMo";
	} elseif (stripos ( $user_OSagent, "Red Hat" )) {
		$visitor_os = "linux";
	} elseif (preg_match ( '/SonyEricsson/i', $user_OSagent )) {
		$visitor_os = "SonyEricsson";
	} elseif (preg_match ( '/Lynx/i', $user_OSagent )) {
		$visitor_os = "Lynx";
	} elseif (preg_match ( '/ucweb|MQQBrowser|J2ME|IUC|3GW100|LG-MMS|i60|Motorola|MAUI|m9|ME860|maui|C8500|gt|k-touch|X8|htc|GT-S5660|UNTRUSTED|SCH|tianyu|lenovo|SAMSUNG/i', $user_OSagent )) {
		$visitor_os = "mobile";
	} else {
		$visitor_os = $user_OSagent;
	}
	return $visitor_os;
}
function appendRequestInfo(&$arr) {
	$arr_f = array (
			'SCRIPT_URL',
			'SCRIPT_NAME',
			'argv',
			'SCRIPT_URI',
			'SCRIPT_URI',
			'REQUEST_URI',
			'HTTP_USER_AGENT',
			'HTTP_REFERER',
			'REMOTE_ADDR',
			'HTTP_X_FORWARDED_FOR'
			);

			foreach ( $arr_f as $f ) {
				if (! empty ( $_SERVER [$f] )) {
					$arr [$f] = $_SERVER [$f];
				}
			}

			$arr [] = $_REQUEST;
}
// 输入一个字符串  把他变成一个bigint作为签名
function createsign64($s)
{
	$hash = md5($s, true);
	$high = substr($hash, 0, 8);
	$low = substr($hash, 8, 8);
	$sign = $high ^ $low;
	$sign1 = hexdec(bin2hex(substr($sign, 0, 4)));
	$sign2 = hexdec(bin2hex(substr($sign, 4, 4)));
	$ret = ($sign1 << 32) | $sign2;
	if($ret < 0) {
		$ret = sprintf('%u',$ret);
	}

	return $ret;
}

function getUserRealIP() {
	if (isset($_SERVER)) {
		if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
			$realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
		}
		elseif (isset($_SERVER["HTTP_CLIENT_IP"])) {
			$realip = $_SERVER["HTTP_CLIENT_IP"];
		}
		else {
			$realip = $_SERVER["REMOTE_ADDR"];
		}
	}
	else {
		if (getenv("HTTP_X_FORWARDED_FOR")) {
			$realip = getenv("HTTP_X_FORWARDED_FOR");
		}
		elseif (getenv("HTTP_CLIENT_IP")) {
			$realip = getenv("HTTP_CLIENT_IP");
		}
		elseif (getenv("REMOTE_ADDR")) {
			$realip = getenv("REMOTE_ADDR");
		} else {
			$realip = '';
		}
	}

	if(empty($realip)) $realip = '';
	return $realip;
}

function setServerIP($params){

	if(!empty($params['customip'])){
		$requestip = $params['customip'];
		if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
			$_SERVER['HTTP_X_FORWARDED_FOR'] = $requestip;
		}
		if(isset($_SERVER['HTTP_CLIENT_IP'])){
			$_SERVER['HTTP_CLIENT_IP'] = $requestip;
		}
		if(isset($_SERVER['REMOTE_ADDR'])){
			$_SERVER['REMOTE_ADDR'] = $requestip;
		}
	}
}
/**
 *将一数组转换成对象
 *
 * @param unknown_type $d
 * @return unknown
 */
function arrayToObject($d) {
    if (is_array($d)) {
        return (object) array_map(__FUNCTION__, $d);
    }  else {
       return $d;
    }
}
/**
 * 对象转成多维数组
 *
 * @param object $d
 * @return array
 */
function objectToArray($d) {
    if (is_object($d)) {
        $d = get_object_vars($d);
    }

    if (is_array($d)) {
        return array_map(__FUNCTION__, $d);
    }
    else {
        return $d;
    }
}
/**
 * 获取图片的
 *
 * @param unknown_type $url
 * @return unknown
 */
 function getImageType($url){
		$imdata = file_get_contents($url);
        $typeCode = intval(ord($imdata{0}).ord($imdata{1}));
        $fileType = '';
        switch ($typeCode) {
            case 255216:
                $fileType = 'jpeg';
                break;
            case 7173:
                $fileType = 'gif';
                break;
            case 6677:
                $fileType = 'wbmp';
                break;
            case 13780:
                $fileType = 'png';
                break;
            default:
                $fileType = 'unknown';
        }
        if (!in_array($fileType, array("jpeg", "gif", "png","wbmp"))) {
			return false;
        }
        return $fileType;
    }
   /**
    * 将src图片copy到image上
    *
    * 将 $src_image_url 图像中坐标从 src_x，src_y 开始，宽度为 src_w，高度为 src_h 的一部分拷贝到 $dst_image_url 图像中坐标为 dst_x 和 dst_y 的位置上。
    */
function copyImageToAnothor($dst_image_url, $src_image_url,$dst_x,$dst_y, $src_x =0 ,$src_y =0 , $src_w =-1, $src_h=-1){

		$type = getImageType($dst_image_url);
		if ($type!=='unknown') {
			$function = "imagecreatefrom".$type;
			$dst_resource = @$function($dst_image_url);
		}
			$type = getImageType($src_image_url);
		if ($type!=='unknown') {
			$function = "imagecreatefrom".$type;
			$src_resource = @$function($src_image_url);
		}

		if (!$dst_resource) {
			var_dump($big_pic ."read false");
		}
		if (!$src_resource) {
			var_dump($small_pic ."read false");
		}
		if ($src_w < 0) {
			$src_w = imagesx($src_resource);

		}
		if ($src_h < 0) {
			$src_h = imagesy($src_resource);
		}
		imagecopy($dst_resource,$src_resource,$dst_x,$dst_y,$src_x,$src_y,$src_w,$src_h);
		return $dst_resource;

}

/**
* 取数组指定的一列 (php5.5才有的函数)
*
* 用法见http://php.net/manual/zh/function.array-column.php
*/
if(!function_exists('array_column')) {
	function array_column($input, $column_key, $index_key=null)
	{
		$res = array();
		if($index_key===null) {
			foreach($input as $r) {
				$res[] = $r[$column_key];
			}
		} elseif($column_key===null) {
			foreach($input as $r) {
				$res[$r[$index_key]] = $r;
			}
		} else {
			foreach($input as $r) {
				$res[$r[$index_key]] = $r[$column_key];
			}
		}

		return $res;
	}
}
/**
 * 对url里的参数进行encode
 * http://movie.taobao.com/showDetail.htm?showId=141207&n_s=new
 *
 * @param unknown_type $url
 * @return unknown
 */
function encodeUrlParam( $url ) {
	$url_arr = explode("?", $url);
	$only_url = $url_arr[0];
	if(count($url_arr) > 1 ) {
		$params = explode("&", $url_arr[1] );
		foreach ($params as $one) {
			$k_v = explode("=", $one);
			$key = $k_v[0];
			$value = $k_v[1];
			$params_new[]= $key."=".urlencode($value);

		}
		$param_str = implode("&",$params_new);
		$url_new = $only_url."?".$param_str;
		return $url_new;
	}else {
		return $url;
	}
}
function checkHasChinese($str){
     if(preg_match('/[\x7f-\xff]/',$str)) {
        return true;

    }else {
      return false;
   }
  }
/**
 * 对一个数组或者一个对象应用传入的方法
 * @param  [type] &$var      [description]
 * @param  [type] $func_name [description]
 * @return [type]            [description]
 */
  function recurseFunOnObject(&$var , $func_name) {
  	switch (gettype($var)) {
		case 'string':
			$var = call_user_func($func_name, $var);
			break;

		case 'array':
			foreach ($var as $key => $value) {
				recurseFunOnObject($var[$key], $func_name);
			}
			break;
		case 'object':
			$vars = get_object_vars($var);

            $properties = array_map(array($this, 'name_value'),
                                    array_keys($vars),
                                    array_values($vars));
			$var = $properties;
			recurseFunOnObject($var,$func_name);
			break;
		default:
			break;
	}

  }





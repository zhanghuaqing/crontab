<?php 
/**
 * 输出接口
 * @author huaqing1
 *
 */
class ApiModel {
    const SUCCESS_ERROR_CODE = 1;

    const ADD_FAILD_ERROR_CODE = 1001;
    const UPDATE_FAILD_ERROR_CODE = 1002;
    const PARAM_ERROR_CODE = 2001;
    const MYSQL_ERROR_CODE = 2002;
    const EXEC_TIMEOUT_ERROR_CODE = 2003;
    const NO_PERMISSION_ERROR_CODE = 2004;
    const SIGN_TIMEOUT = 2005;
    const SING_FAILD_ERROR_CODE = 2006;

    public static $ERROR_CODE = array (
        self::SUCCESS_ERROR_CODE => array (
            'status' => self::SUCCESS_ERROR_CODE,
            'message' => 'ok'
        ),
        self::ADD_FAILD_ERROR_CODE => array (
            'status' => self::ADD_FAILD_ERROR_CODE,
            'message' => '添加失败'
        ),
        self::UPDATE_FAILD_ERROR_CODE => array (
            'status' => self::UPDATE_FAILD_ERROR_CODE,
            'message' => '更新失败'
        ),
        self::PARAM_ERROR_CODE => array (
            'status' => self::PARAM_ERROR_CODE,
            'message' => 'param_error'
        ),
        self::EXEC_TIMEOUT_ERROR_CODE => array (
            'status' => self::EXEC_TIMEOUT_ERROR_CODE,
            'message' => 'exec_timeout'
        ),
        self::MYSQL_ERROR_CODE => array (
            'status' => self::MYSQL_ERROR_CODE,
            'message' => 'mysql_error'
        ),
        self::SIGN_TIMEOUT => array (
            'status' => self::SIGN_TIMEOUT,
            'message' => 'sign_timeout'
        ),
        self::SING_FAILD_ERROR_CODE => array (
            'status' => self::SING_FAILD_ERROR_CODE,
            'message' => 'sign_faild'
        ),
        self::NO_PERMISSION_ERROR_CODE => array (
            'status' => self::NO_PERMISSION_ERROR_CODE,
            'message' => 'no_permission'
        )
    );
    /**
     * 接口内容输出
     *
     * @param int $status
     * @param mix $data
    */
    public static function echoResult($status, $data = null) {
        $res = self::$ERROR_CODE [$status];
        if (isset ( $data )) {
            $res ['data'] = $data;
        }
        $json = json_encode ( $res );
        echo $json;
        return false;
    }
}


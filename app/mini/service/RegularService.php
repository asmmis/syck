<?php
namespace app\mini\service;

/**
 * 正则校验：手机号 邮箱 身份证等
 * Class RegularService
 * @package app\mini\service
 */
class RegularService
{
    /**
     * 手机号正则校验
     * @param $mobile
     * @return bool
     */
    public static function phoneRegular($mobile)
    {
        if(!preg_match("/^1[3456789]{1}\d{9}$/",$mobile)){
            return false;
        }else{
            return true;
        }
    }

    //邮箱
    //身份证号码

}

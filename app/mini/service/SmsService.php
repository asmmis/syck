<?php

namespace app\mini\service;

use app\mini\service\LogService;
use app\mini\http\ChuanglanSmsApi;

/**
 * 第三方短信
 * Class SmsService
 * @package app\mini\service
 */
class SmsService
{

    //短信发送
    public static function smsSend($phone,$content){
        $clapi = new ChuanglanSmsApi();
        //设置您要发送的内容：其中“【】”中括号为运营商签名符号，多签名内容前置添加提交
        $result=$clapi->sendSms($phone,$content);
        if(!is_null(json_decode($result))){
            $output=json_decode($result,true);
            if(isset($output['code'])&&$output['code']=='0'){
                return true;//发送成功
            }else{
                //发送失败写入日志
                LogService::writeLog('sms','smscodeerror.log',$result);
                return false;
            }
        }else{
            return false;
        }
    }
}
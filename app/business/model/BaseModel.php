<?php


namespace app\business\model;


use think\Model;

class BaseModel extends Model
{
    public static function ret($state,$msg,$data=null){
        return array($state,$msg,$data);
    }

    public static function ret_success($msg="成功",$data=null){
        return  self::ret(true,$msg,$data);
    }

    public static function ret_faild($msg="失败",$data=null){
        return self::ret(false,$msg,$data);
    }
}
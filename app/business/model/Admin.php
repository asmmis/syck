<?php


namespace app\business\model;


use think\Model;

class Admin extends Model
{
    public static function getAdminById($id){
        return self::where('id',$id)->where('status',1)->where('is_del',0)->find();
    }
}
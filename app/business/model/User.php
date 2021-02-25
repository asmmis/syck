<?php


namespace app\business\model;


use think\Exception;
use think\Model;

class User extends Model
{
    public static function getUserById($id){
        $user= User::where('uid',$id)->where('status',0)->find();
        return $user;
    }

    public static function getUserByAccount($account){
        $user=User::where('account',$account)->where('status',0)->find();
        return $user;
    }

    public static function getUserWhere($where){
        $user=User::where('status',0)->where($where)->find();
        return $user;
    }

    public static function updatePassword($id,$newpassword){
        $user=self::getUserById($id);
        if($user->isEmpty())
            throw new Exception('没有该用户');
        $user->password=$newpassword;
        return $user->save();
    }
}
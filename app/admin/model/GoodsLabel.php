<?php


namespace app\admin\model;


use think\Model;

class GoodsLabel extends Model
{
    public static function getGoodsLabels(){
        return self::where('is_del',0)->where('is_show',1)->select();
    }

    public static function getLabelById($id){
        return self::where('id',$id)->find();
    }
}
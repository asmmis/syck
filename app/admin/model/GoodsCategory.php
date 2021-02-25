<?php


namespace app\admin\model;


use think\Model;

class GoodsCategory extends Model
{
    public static function getCateGoryById($id){
        return self::where('id',$id)->find();
    }

    public static function getParentCategory($category){
        return self::getCateGoryById($category['pid']);
    }
}
<?php


namespace app\business\model;


use think\Model;

class ServiceCategory extends Model
{

    public static function getServiceCategoryTree($parentid){
        $cates=ServiceCategory::where('pid',$parentid)->select();
        if(count($cates)==0)
            return null;
        $ret=array();
        foreach ($cates as $cate){
            $ca=$cate->toArray();
            $ca['child']=self::getServiceCategoryTree($cate->id);
            $ret[]=$ca;
        }
        return $ret;
    }

    public static function getServiceCategoryById($id){
        return ServiceCategory::where('id',$id)->find();
    }

    public static function getServiceCategoryByLevel($level){
        return self::where('level',$level)->select();
    }

    public static function getServiceCategoryByParentId($pid){
        return ServiceCategory::where('pid',$pid)->select();
    }
}
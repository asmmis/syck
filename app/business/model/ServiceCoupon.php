<?php


namespace app\business\model;


use think\Model;

class ServiceCoupon extends  Model
{
    public static function listServiceCoupon($page,$pagesize){
        $where=['is_del'=>0,'cate_id'=>2,'is_pt'=>0];
        if($page>=0&&$pagesize>0)
            $limit=[max(0,($page-1)*$pagesize),$pagesize];
        else
            $limit=null;
        $ret=self::getServiceConponWhere($where,$limit);
        return $ret;
    }

    public static function getAllServiceCouponCount(){
        return self::where('is_del',0)->where('cate_id',2)->where('is_pt',0)->count();
    }

    private static function getServiceConponWhere($where,$limit=null){
        $where=self::where($where);
        if($limit)
            $where=$where->limit($limit[0],$limit[1]);
        $ret=$where->select();
        return $ret;
    }

    public static function saveService($id,$title,$info,$typeid,$uservalue,$value,$expiration){
        $coupon=null;
        if($id=-1){
            $coupon=new ServiceCoupon();
            $coupon->add_time=time();
        }else{
            $coupon=self::getServiceConponWhere(['id'=>$id]);
        }
        $coupon->cate_id=2;
        $coupon->title=$title;
        $coupon->info=$info;
        $coupon->typeid=$typeid;
        $coupon->user_value=$uservalue;
        $coupon->value=$value;
        $coupon->expiration=$expiration;
        $store=Store::getMyStore();
        $coupon->store_id=$store['store_id'];
        $coupon->is_pt=0;
        return $coupon->save();
    }
}
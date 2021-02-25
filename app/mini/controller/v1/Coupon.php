<?php
declare (strict_types = 1);

namespace app\mini\controller\v1;

use think\facade\Db;

/**
 * Class Coupon
 * @package app\mini\controller\v1
 * 用户优惠券
 */
class Coupon extends Base
{
    //优惠券列表
    public function couponList()
    {
        $uid = $this->request->userinfo['uid'];
        $cate_id = $this->request->param('cateid/d');//1=商品券 2=服务券
        $store_id = $this->request->param('store_id/d');
        $page = $this->request->param('page/d',1);//页码
        $status =  $this->request->param('status/d',0);//优惠券状态 0=待使用，1=已使用 2=过期 3=无效券【1，2】

        //优惠券到期时间到期的未使用改为已过期
        $wheres[] = ['uid','=',$uid];//
        $wheres[] = ['status','=',0];//未使用的
        $wheres[] =  ['expiration','<','UNIX_TIMESTAMP(NOW())'];
        Db::name('coupon_user')->where($wheres)->update(['status'=>2]);
        //查询条件
        $where[] =  ['cu.uid','=',$uid];
        $where[] =  ['cu.cate_id','=',$cate_id];
        $where[] =  ['c.is_del','=',0];//未删除的

        //优惠券状态
        if(in_array($status,[0,1,2])){
            $where[] =  ['cu.status','=',$status];//待使用的
        }elseif($status==3){
            $where[] = ['cu.status','in','1,2'];//无效券 1和2
        }else{
            return $this->ret_faild('status错误');
        }
        if($store_id){
            $where[] = ['cu.store_id','in',[0,$store_id]];//传了门店ID就是门店和平台  没传就是所有门店
        }
        if(!in_array($cate_id,[1,2]))  return $this->ret_faild('cateid错误');


        $field = ['cu.cu_id,c.title,c.user_value,c.value,c.typeid,cu.expiration,cu.update_time,cu.status'];
        $list = Db::name('coupon_user')
            ->field($field)
            ->alias('cu')
            ->join('coupon c','cu.coupon_id=c.id','left')
            ->where($where)
            ->page($page,$this->plimit)
            ->select()
            ->each(function ($item,$key){
                $item['expiration'] = date('Y-m-d H:i',$item['expiration']);//优惠券过期时间
                return $item;
            });
        if($list->isEmpty()) return $this->ret_success('暂无优惠券',['is_request'=>1,'list'=>$list]);
        return $this->ret_success('获取优惠券列表成功',['is_request'=>0,'list'=>$list]);
    }

    //优惠券选中使用/不选中
    //不用了2020.1.11
//    public function couponChange()
//    {
//
//        $uid = $this->request->userinfo['uid'];
//        $act = $this->request->param('act/s','');//选中=checkok  取消=checkno
//        $cu_id = $this->request->param('cu_id/d',0);//选中的优惠券ID
//
//        $find = Db::name('coupon_user')->where(['cu_id'=>$cu_id,'uid'=>$uid])->find();
//        if(!$find)  return $this->ret_faild('优惠券不存在');
//        if($find['status']!=0)  return $this->ret_faild('优惠券已使用或已过期');
//
//        if($act=='checkok'){
//            $update ['is_check'] = 1;//选中已使用
//            if($find['is_check']==1) return $this->ret_faild('该优惠券已经选中');
//
//        }elseif($act=='checkno'){
//            $update ['is_check'] = 0;//未选择未使用
//        }else{
//            return $this->ret_faild('act错误');
//        }
//
//        $r = Db::name('coupon_user')->where(['cu_id'=>$cu_id,'uid'=>$uid])->update($update);
//        if($r!==false)  return $this->ret_success('优惠券'.$act.'成功');
//        return $this->ret_faild('优惠券'.$act.'失败');
//    }
}
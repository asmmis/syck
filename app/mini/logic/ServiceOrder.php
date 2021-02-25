<?php
namespace app\mini\logic;

use think\facade\Db;
/**
 * Class ServiceOrder
 * @package app\mini\logic
 * 服务订单处理逻辑
 */
class ServiceOrder
{
    //服务订单创建
    //一家门店下单 1条支付订单记录 1条服务订单记录
    //N家门店下单 1条支付订单记录 N条服务订单记录 订单拆分
    //下单接口返回订单编号
    /**
     * @param array $list 服务购物车购买
     * @param array $param 前端提交信息
     * @param array $userinfo 下单用户信息

     */
    public static function createServiceOrder($list,$param,$userinfo)
    {
        $uid = $userinfo['uid'];
        $total_price_pay = 0;//支付订单总金额 =多个门店的总价格
        $order_ids = '';
        $order_sn =  create_service_ordersn($uid);//服务订单号
        // 启动事务
        Db::startTrans();
        try {
            foreach($list as $key=>$value){
                $total_price = 0;//服务订单总金额 =单个门店的总金额
                $body = $value['store_name'].'服务消费';
                $store_yongjin = 0;//门店总佣金
                $daiyan_yongjin = 0;//代言人总佣金
                $hehuo_yongjin = 0;//合伙人总佣金
                $pingtai_yongjin = 0;//平台总佣金
                foreach ($value['infos'] as $k=>$v){
                    $price = $v['price']*$v['num'];//单价*数量 =单个服务的价格
                    $total_price = $total_price+$price; //单个门店的总价
                    $service_bl = Db::name('service_bl')->where(['name'=>$v['bl_name']])->find();
                    if($service_bl){
                        $store_yongjin = $store_yongjin+$price*$service_bl['bl_store'];//门店佣金
                        $daiyan_yongjin = $daiyan_yongjin+$price*$service_bl['bl_daiyan'];//代言人佣金
                        $hehuo_yongjin = $hehuo_yongjin+$price*$service_bl['bl_hehuo'];//合伙人佣金
                        $pingtai_yongjin = $pingtai_yongjin+$price*$service_bl['bl_pingtai'];//平台佣金
                    }
                }
                $store_yongjin = round($store_yongjin,2);//门店总佣金
                $daiyan_yongjin = round($daiyan_yongjin,2);//代言人总佣金
                $hehuo_yongjin = round($hehuo_yongjin,2);//合伙人总佣金
                $pingtai_yongjin = round($pingtai_yongjin,2);//平台总佣金
                //用户优惠券
                $user_coupon_id = 0;//优惠券ID
                $user_coupon_name = '';//优惠券名称
                $user_coupon_price = 0;//优惠券抵扣金额
                $coupon =Db::name('coupon_user')
                    ->alias('cu')
                    ->join('coupon c','cu.coupon_id=c.id','left')
                    ->where(['cu.cu_id'=>$param[$key]['cu_id']])
                    ->find();
                if($coupon){
                    $user_coupon_id = $param[$key]['cu_id'];
                    $user_coupon_name = $coupon['title'];
                    if($coupon['typeid']==1){ //满减券
                        $user_coupon_price = $coupon['value'];
                    }elseif($coupon['typeid']==2){//折扣券
                        $user_coupon_price = round($total_price*$coupon['value'],2);
                    }
                    //优惠券改为已经用使用
                    Db::name('coupon_user')->where(['cu_id'=>$user_coupon_id])->update(['status'=>1,'update_time'=>time()]);
                }
                $total_price = $total_price-$user_coupon_price;//门店价格-优惠券价格
                $total_price_pay = $total_price_pay+$total_price;//多个门店的总价
                //创建服务订单
                //$i = $key+1;//创建订单号用到
                $service_order = [];
               // $service_order['order_sn']              = create_service_ordersn($uid,$i);
                $service_order['order_sn']              = $order_sn;
                $service_order['uid']                   = $uid;
                $service_order['user_type']             = $userinfo['user_type'];
                $service_order['real_name']             = $userinfo['real_name'];
                $service_order['phone']                 = $userinfo['phone'];
                $service_order['store_id']              = $value['store_id'];
                $service_order['service_id']            = $value['service_ids'];
                $service_order['service_info']          = json_encode($value['infos']);
                $service_order['total_price']           = $total_price; //门店总金额
                $service_order['cancel_price']          = $total_price;//核销金额
                $service_order['coupon_id']             = $user_coupon_id;//优惠券ID
                $service_order['coupon_name']           = $user_coupon_name;//优惠券名称
                $service_order['coupon_dk_price']       = $user_coupon_price;//优惠券抵扣金额
                $service_order['create_time']           = time();
                $service_order['verify_code']           = time().mt_rand(100000000,999999999);//核销码
                $service_order['appointment']           = strtotime($param[$key]['appo_time']);//预约时间
                $service_order['sopke_uid']             = $userinfo['sopke_uid'];
                $service_order['partner_uid']           = $userinfo['partner_uid'];
                $service_order['body']                  = $body;//消费内容
                $service_order['store_yongjin']         = $store_yongjin;
                $service_order['store_yongjin_real']    = $store_yongjin;
                $service_order['daiyan_yongjin']        = $daiyan_yongjin;
                $service_order['daiyan_yongjin_real']   = $daiyan_yongjin;
                $service_order['hehuo_yongjin']         = $hehuo_yongjin;
                $service_order['hehuo_yongjin_real']    = $hehuo_yongjin;
                $service_order['pingtai_yongjin']       = $pingtai_yongjin;
                $service_order['pingtai_yongjin_real']  = $pingtai_yongjin;
                $order_id = Db::name('service_order')->insertGetId($service_order);
                $order_ids .= $order_id.',';//服务订单关联到支付订单
            }
            //创建支付订单
//            $order_sn =  create_service_ordersn($uid);//服务订单号
            $service_order_pay = [];
            $service_order_pay['uid'] = $uid;
            $service_order_pay['order_sn'] = $order_sn;//服务订单号
            $service_order_pay['body'] = '莱美牙平台服务消费';
            $service_order_pay['total_price'] = $total_price_pay;//多个门店的总支付金额
            $service_order_pay['cancel_price'] = $total_price_pay;//核销金额 这个一般不会变就是=总金额
            $service_order_pay['add_time'] = time();
            $service_order_pay['order_ids'] = rtrim($order_ids,',');
            Db::name('service_order_pay')->insertGetId($service_order_pay);
            //把购物车选中支付的改为已购买
            Db::name('service_cart')->where(['uid'=>$uid,'is_pay'=>0,'is_del'=>0,'is_now'=>1])->update(['is_pay'=>1]);
            // 提交事务
            Db::commit();
            return ['status'=>1,'data'=>['order_sn'=>$order_sn,'total_price'=>keeptwodecimal($total_price_pay)]];
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return ['status'=>0,'data'=>['error'=>$e->getMessage()]];
        }
    }




}
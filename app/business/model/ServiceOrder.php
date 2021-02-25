<?php


namespace app\business\model;


use app\admin\controller\order;
use app\business\controller\Role;
use think\Exception;
use think\Model;

class ServiceOrder extends Model
{

    /**
     * @param $page 如果-1则不分页
     * @param $pagesize 如果-1则不分页
     * @param null $pay_status 支付状态
     * @param null $startDate
     * @param null $endDate
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function listMyStoreServiceOrder($page,$pagesize,$pay_status=null,$startDate=null,$endDate=null){
        $store=Store::getMyStore();
        $ret=array();
        $where=ServiceOrder::where('store_id',$store['store_id'])->where('is_del',0);
        if($pay_status)
            $where=$where->where('pay_status',$pay_status);
        if($startDate)
            $where=$where->where('pay_time','>',$startDate);
        if($endDate)
            $where=$where->where('pay_time','<=',$endDate);
        $ret['count']= $where->count();
        $info=array();
        $orderwhere=$where->order('pay_time','ASC');
        if($page!=-1&&$pagesize!=-1)
            $orderwhere=$orderwhere->limit(max(0,($page-1)*$pagesize),$pagesize);
        $orders=$orderwhere->select();
        foreach ($orders as $order){
            $t=$order->toArray();
            $t['user']=show_nickname(User::getUserById($order->uid)->nickname);
            $t['store']=Store::getStoreArrayById($order->store_id)['store_name'];
            $ser=Service::getServiceById($order->service_id);
            $t['service']=Service::getServiceById($order->service_id)['name'];
            $info[]=$t;
        }
        $ret['info']=$info;
        return $ret;
    }

    public static function getServiceOrderByWhere($where){
        $orders=ServiceOrder::where('is_del',0)->where($where)->select();
        return $orders;
    }

    public static function getMyStoreServiceOrderCount($startDate=null,$endDate=null){
            return count(self::getMyStoreServiceOrder($startDate,$endDate));
    }

    public static function getMyStoreServiceOrder($startDate=null,$endDate=null){
        $store=Store::getMyStore();
        $where=self::where('store_id',$store['store_id'])->where('is_del',0)->where('pay_status',1);
        if($startDate)
            $where=$where->where('pay_time','>=',$startDate);
        if($endDate)
            $where=$where->where('pay_time','<',$endDate);
        $orders=$where->select();
        return $orders;
    }

    public static function getMyStoreServiceOrderMoney($startDate=null,$endDate=null){
        $orders=self::getMyStoreServiceOrder($startDate,$endDate);
        $totalMoney=0;
        foreach ($orders as $order){
            $totalMoney+=$order->pay_price;
        }
        return $totalMoney;
    }

    public static function verify($service_order_id,$verify_code){
        $serviceOrder=self::getServiceOrderByWhere(array('id'=>$service_order_id));
        if($serviceOrder->isEmpty())
            throw new Exception('找不到该订单');
        $serviceOrder=$serviceOrder[0];
        if($serviceOrder->verify_code!=$verify_code)
            throw new Exception('核销码不一致');
        $serviceOrder->status=2;//待评价
        $business=Business::currentUser();
        $role=BusinessRole::getRoleById($business->role_id);
        $serviceOrder->verify_info=date('Y-m-d h:m:s')." ".$role->getAttr('name')." ".$business->account."核销了该笔订单";
        return $serviceOrder->save();
    }
}
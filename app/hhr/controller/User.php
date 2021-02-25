<?php


namespace app\hhr\controller;


use app\BaseController;
use app\business\model\Service;
use app\business\model\ServiceOrder;
use think\db\Where;
use think\Exception;
use think\Model;

class User extends BaseController
{
    public function apiLogin($account,$passwordmd5){
        try{
            $user=\app\business\model\User::getUserWhere(array('account'=>$account));
            if($user->isEmpty())
                throw new Exception('没有该用户');
            if($user->password!=$passwordmd5)
                throw new Exception('账号密码不匹配');
            if($user->user_type==0)
                throw new Exception('普通用户无法登陆');
            session('hhr_user_id',$user->uid);
            return  $this->ret_success('登陆成功');
        }catch (Exception $e){
            return $this->ret_faild($e->getMessage());
        }
    }

    public function apiLoginState(){
        if(session('hhr_user_id'))
            return $this->ret_success('已登陆');
        else
            return $this->ret_faild('未登陆');
    }

    public function apiLogout(){
        session('hhr_user_id',null);
        return $this->ret_success('退出登陆成功');
    }

    public function apiCurrentUser(){
        try {
            $user = \app\business\model\User::getUserById(session('hhr_user_id'));
            if ($user == null || $user->isEmpty())
                throw new Exception('找不到登陆用户');
            return $this->ret_success('成功',$user);
        }catch (Exception $e){
            return $this->ret_faild($e->getMessage());
        }
    }

    /**
     * 列出服务订单（当前用户和其下级）
     * @param null $startDate
     * @param null $endDate
     * @param int $page
     * @param int $pagesize
     * @return \think\response\Json
     */
    public function apiListServiceOrder($startDate=null,$endDate=null,$page=0,$pagesize=10){
        $ret=array();
        $info=array();
        $ret['info']=&$info;
        $orders=self::getServiceOrders($startDate,$endDate,$page,$pagesize);
        $ret['count']=count($orders);
        foreach ($orders as $order){
            $t=$order->toArray();
            $t['user']=\app\business\model\User::getUserById($order->uid)->getAttr('nickname');
            $service=Service::getServiceById($order->service_id);
            $t['service']=$service->getAttr('name');
            $info[]=$t;
        }
        return $this->ret_success('成功',$ret);
    }

    public static function getServiceOrders($startDate,$endDate,$page,$pagesize){
        $user=\app\business\model\User::getUserById(session('hhr_user_id'));
        $where=ServiceOrder::where('is_del',0);
        if($startDate)
            $where=$where->where('pay_time','>=',$startDate);
        if($endDate)
            $where=$where->where('pay_time','<',$endDate);
        if($user->user_type==1) {
            $where=$where->where('sopke_uid', $user->uid);
            $ret['count']=$where->count();
            if($page!=-1&&$pagesize!=-1)
                $where=$where->limit(max(0,($page-1)*$pagesize),$pagesize);
            $orders = $where->select();
        }
        else if($user->user_type==2) {
            $where=$where->where('partner_uid', $user->uid);
            $ret['count']=$where->count();
            if($page!=-1&&$pagesize!=-1)
                $where=$where->limit(max(0,($page-1)*$pagesize),$pagesize);
            $orders = $where->select();
        }
        return $orders;
    }

    public static function getServiceOrderMoney($orders){
        $money=0;
        foreach ($orders as $order){
            $money+=$order->pay_price;
        }
        return $money;
    }

    public function getStatistics(){
        try {
            $ret = array();
            $orders = self::getServiceOrders(null, null, -1, -1);
            $ret['total_order'] = count($orders);
            $ret['total_money'] = self::getServiceOrderMoney($orders);
            $orders = self::getServiceOrders(strtotime('today'), strtotime('tomorrow'), -1, -1);
            $ret['today_order'] = count($orders);
            $ret['today_money'] = self::getServiceOrderMoney($orders);
            return $this->ret_success('成功', $ret);
        }catch (Exception $e){
            return $this->ret_faild($e->getMessage());
        }
    }

    /**
     * @param $id
     * @param $newpassword md5密码
     * @return \think\response\Json
     */
    public function apiEditPassword($id,$newpassword){
        try{
            return \app\business\model\User::updatePassword($id,$newpassword)?$this->ret_success('更新成功'):$this->ret_faild('更新失败');
        }catch (Exception $e){
            return $this->ret_faild($e->getMessage());
        }
    }
}
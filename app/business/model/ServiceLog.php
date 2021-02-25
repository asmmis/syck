<?php


namespace app\business\model;


use think\Exception;
use think\Model;

class ServiceLog extends Model
{
    public static function hasNew(){
        $count=self::getNewMessageCount();
        return $count>0;
    }

    public static function getNewMessageCount(){
        $store=Store::getMyStore();
        $count=self::where('store_id',$store['store_id'])->where('is_read',0)->count();
        return $count;
    }

    public static function getNewMessage($page, $pagesize){
        $store=Store::getMyStore();
        $msgs=self::where('store_id',$store['store_id'])->order('is_read','ASC')->where('c_typeid',1)->order('add_time','DESC')
            ->limit(max(0,($page-1)*$pagesize),$pagesize)->select();
        return $msgs;
    }

    public static function readMessage($id){
        $msg=self::where('id',$id)->find();
        if($msg->c_typeid==2)
            throw new Exception('不能已读自己产生的操作记录');
        $msg->is_read=1;
        $msg->read_time=time();
        return $msg->save();
    }

    public static function readAllMessage(){
        $store=Store::getMyStore();
        self::update(array('is_read'=>1,'read_time'=>time()),array('store_id'=>$store['store_id'],'is_read'=>0,'c_typeid'=>1));
    }

    public static function addMessage($service_id,$type_id,$content){
        $serviceLog=new ServiceLog();
        $store=Store::getMyStore();
        $serviceLog->store_id=$store['store_id'];
        $serviceLog->service_id=$service_id;
        $serviceLog->typeid=$type_id;
        $serviceLog->c_typeid=2;
        $user=Business::currentUser();
        $serviceLog->c_id=$user->id;
        $serviceLog->content=$content;
        $serviceLog->add_time=time();
        return $serviceLog->save();
    }
}
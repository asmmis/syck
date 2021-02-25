<?php


namespace app\business\model;


use think\Model;

class ServiceMessage extends Model
{
    public  $name='service_leave';

    public  static function listMyStoreServiceMessage($page,$pagesize){
        $ret=array();
        $store=Store::getMyStore();
        $ret['count']=ServiceMessage::where('store_id',$store['store_id'])->where('is_del',0)->count();
        $info=array();
        $ret['info']=&$info;
        $messages=ServiceMessage::where('store_id',$store['store_id'])->where('is_del',0)->limit(max(0,($page-1)*$pagesize),$pagesize)->select();
        foreach ($messages as $msg){
            $t=$msg->toArray();
            $t['user']=show_nickname(User::getUserById($msg->uid)->getAttr('nickname'));
            $t['service']=Service::getServiceById($msg->service_id)->getAttr('name');
            $info[]=$t;
        }
        return $ret;
    }

    public static function getServiceMessageById($id){
        return ServiceMessage::where('id',$id)->where('is_del',0)->find();
    }

    public static function setServiceMessageIsShow($id,$isShow){
        $message=self::getServiceMessageById($id);
        $message->is_show=$isShow;
        return $message->save();
    }

    public static function setServiceMessageIsTui($id,$isTui){
        $message=self::getServiceMessageById($id);
        $message->is_tui=$isTui;
        return $message->save();
    }

    public static function delServiceMessage($id){
        $message=self::getServiceMessageById($id);
        $message->is_del=1;
        //应上头要求删除时更新显示状态
        $message->is_show=0;
        $message->is_tui=0;
        return $message->save();
    }
}
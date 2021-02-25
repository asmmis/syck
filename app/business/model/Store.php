<?php


namespace app\business\model;


use think\Model;

class Store extends Model
{
    /**
     * 通过id获取店铺名称
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getStoreNameById($id){
        return self::getStoreArrayById($id)['store_name'];
    }

    public static function getStoreArrayById($id){
         $store=Store::where("store_id",$id)->find();
        return $store->toArray();
    }

    public static function saveStore($id,$name,$info){
        $store=Store::where('store_id',$id)->find();
        if($store==null)
            return false;
        $store->setAttr('store_name',$name);
        $store->setAttr('info',$info);
        return $store->save();
    }

    public static function getMyStore(){
            $user=Business::currentUser();
            $store=Store::getStoreArrayById($user->store_id);
            return $store;
    }
}
<?php


namespace app\business\controller;


use app\BaseController;
use app\business\model\Business;
use think\Exception;
use app\business\model\Store as StoreModel;
class Store extends BaseController
{
    public function apiGetStoreById($id){
        try{
            $store=StoreModel::getStoreArrayById($id);
            return $this->ret_success('成功',$store);
        }catch (Exception $e){
            return $this->ret_faild($e->getMessage());
        }
    }

    public function apiGetMyStore(){
        try{
            $store=StoreModel::getMyStore();
            return $this->ret_success("成功",$store);
        }catch (Exception $e){
            return $this->ret_faild($e->getMessage());
        }
    }

    public function apiSaveStore($id,$name,$info){
        try{
            if(\app\business\model\Store::saveStore($id,$name,$info))
                return $this->ret_success('成功');
        }catch (Exception $e){
            return $this->ret_faild($e->getMessage());
        }
    }
}
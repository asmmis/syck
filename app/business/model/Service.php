<?php


namespace app\business\model;


use app\business\model\Service as ServiceModel;
use think\Exception;
use think\Model;

class Service extends Model
{
        public static function getServiceByStoreId($storeid){
            $services=Service::where("store_id",$storeid)->where('is_del',0)->select();
            return $services;
        }

        public static function saveService($id,$name,$cateid,$rule,$price,$info,$keyword,$img){
            if($id==-1)
                $service=new Service();
            else $service=Service::where('id',$id)->find();
            $service->setAttr('name',$name);
            if($cateid!=-1)
                $service->cate_id=$cateid;
            $service->price=$price;
            $service->info=$info;
            $service->kerword=$keyword;
            $service->rule=$rule;

            if($id==-1&&!$img)
                throw new Exception('新增服务图片不能为空');
            if($img) {
                //保存文件
                $imgSavePath = "/upload/images/service/" . date('Y') . "/" . date('m') . "/" . date('d') . '/fengmian' . '/';
                $imgpath = $_SERVER['DOCUMENT_ROOT'] . $imgSavePath;
                $imgpath = str_replace('/', DIRECTORY_SEPARATOR, $imgpath);
                $imgname = rand(100000, 999999) . '.' . $img->getOriginalExtension();
                $service->img = $imgSavePath . $imgname;
            }
            $service->add_time=time();
            $service->is_show=0;
            $service->status=2;
            $service->store_id=Store::getMyStore()['store_id'];
            if($service->save()){
                if($img) {
                    if (!is_dir($imgpath))
                        mkdir($imgpath, "0777", true);
                    move_uploaded_file($img->getRealPath(), $imgpath . $imgname);
                }
                //添加操作记录
                $user=Business::currentUser();
                ServiceLog::addMessage($service->id,$id==-1?1:3,$user->account.'于'.date('Y-m-d h:m:s').($id==-1?'新增服务':'编辑服务').$service->getAttr('name'));
                return true;
            }
            return false;
        }

    public static function getMyStoreService($page,$pagesize){
            $user=Business::currentUser();
            $services=ServiceModel::where("store_id",$user->store_id)->where('is_del',0)->limit(max(0,($page-1)*$pagesize),$pagesize)->select();
            $ret=array();
            $ret['count']=ServiceModel::where("store_id",$user->store_id)->where('is_del',0)->count();
            $info=array();
            $ret['info']=&$info;
            foreach ($services as $service){
                $ta=array();
                $ta['id']=$service->id;
                $ta['name']=$service?$service->getAttr('name'):"";
                $cate=ServiceCategory::getServiceCategoryById($service->cate_id);
                $ta['cate']=$cate?$cate->getAttr('name'):"";
                $ta['price']=$service->price;
                $ta['is_show']=$service->is_show;
                $ta['info']=$service->info;
                $ta['keyword']=$service->kerword;
                $ta['img']=$service->img;
                $info[]=$ta;
            }
            return $ret;
    }

    public static function delService($id){
            $service=Service::where('id',$id)->find();
            if(!$service)
                return false;
            $service->is_del=1;
            $service->is_show=0;
            $ret=  $service->save();
            if($ret) {
                $user = Business::currentUser();
                ServiceLog::addMessage($id, 2, $user->account . '于' . date('Y-m-d h:m:s') . '删除服务' . $service->getAttr('name'));
            }
            return  $ret;
    }

    public static function getServiceById($id){
            return Service::where('id',$id)->where('is_del',0)->find();
    }
}
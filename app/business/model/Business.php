<?php


namespace app\business\model;


use app\business\model\Business as BusinessModel;
use think\Exception;
use app\business\model\Store as StoreModel;
use think\facade\Request;

class Business extends BaseModel
{
    /**
     * 获取登陆状态
     * 返回bool
     */
    public static function getLoginState(){
        if(session('business_userid')!=null)
            return true;
        return false;
    }

    public static function  logout(){
        session('business_userid',null);
        return true;
    }

    public static function login($account,$passwordmd5){
        if(empty($account)||empty($passwordmd5))
            throw new Exception("账号密码为空！");
        $business=BusinessModel::where('account',$account)->where('is_del',0)->find();
        if($business->isEmpty())
            throw new Exception("没有找到该用户");
        if($business->password!=$passwordmd5)
            throw new Exception("账号密码不匹配！");
        //保存到session
        session('business_userid',$business->id);
        $business->login_count++;
        $business->last_login_time=time();
        $business->last_login_ip=Request::ip();
        if($business->save())
            return true;
        return false;
    }

    public static function loginCheck(){
        if(!self::getLoginState())
            throw new Exception('未登陆');
    }

    public static function list($page=0,$pagesize=10){
        $store=Store::getMyStore();
        $users=BusinessModel::where('is_del',0)->where('store_id',$store['store_id'])->limit(max(0,($page-1)*$pagesize),$pagesize)->select();
        $data=array();
        $data['count']=BusinessModel::where('is_del',0)->select()->count();
        $data['info']=array();
        foreach ($users as $u){
            $aa=$u->toArray();
            $role=BusinessRole::getRoleById($aa['role_id']);
            $aa['role']=$role->getAttr('name');
            $aa['roleDescription']=$role->description;
            $aa['store_name']=StoreModel::getStoreNameById($aa['store_id']);
            $data['info'][]=$aa;
        }
        return $data;
    }

    public static function del($id){
        $user=BusinessModel::where('id',$id)->where('is_del',0)->find();
        if($user->isEmpty())
            return self::ret_faild("查无此人,删除失败");
        $user->is_del=1;
        if($user->save())
            return self::ret_success("删除成功");
        else
            return self::ret_faild("删除失败");
    }

    public static function getUserRole($id){
        if(!self::getLoginState())
            throw new Exception("还未登陆");
        $user=BusinessModel::where("id",$id)->find();
        if($user->isEmpty())
            throw new Exception("找不到该用户");
        $roleid=$user->role_id;
        $role=BusinessRole::where("id",$roleid)->find();
        if($role->isEmpty())
            throw new Exception("没有找到用户所属角色");
        return $role;
    }

    public static function saveUser($pwd,$account,$userid=-1,$role=-1){
        if($userid==-1||empty($userid)) {
            //新增用户
            $business = new Business();
            $business->account=$account;
            $business->add_time=date('y-m-d H:i:s', time());
        }else{
            $business=Business::where("id",$userid)->find();
            if($business->isEmpty())
                return self::ret_faild("没有找到该用户");
        }
        if(empty($pwd)&&$userid==-1)
            throw  new Exception('新增用户必须有密码');
        $business->password=$pwd;
        if($role!=-1)
            $business->role_id=$role;
        $store=Store::getMyStore();
        $business->store_id=$store['store_id'];
        if($business->save())
            return self::ret_success("成功");
        else
            return self::ret_faild("失败");
    }

    public static function currentUser(){
        self::loginCheck();
        $id=session("business_userid");
        $user=BusinessModel::where($id)->find();
        return $user;
    }

    public static function getUserById($id){
        $user=Business::where('id',$id)->where('is_del',0)->find();
        return $user;
    }
}
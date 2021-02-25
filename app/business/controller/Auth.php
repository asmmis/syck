<?php


namespace app\business\controller;


use app\BaseController;
use app\business\model\Business;
use app\business\model\BusinessAuth;
use app\business\model\BusinessRole;
use think\Exception;

class Auth extends BaseController
{

    /**
     * 判断请求权限是否通过(未加full_path字段前)
     * @param $s 请求权限字符串，以/隔开
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    /*
    public function apiAuthCheck($s){
        $a=explode("/",$s);
        for($i=0;$i<count($a);$i++)
            if(empty($a[$i]))
                unset($a[$i]);
        $user=Business::currentUser();
        if($user==null)
            return $this->ret_faild('没有该用户');
        $parentid=null;
        $ismatch=true;
        for($i=0;$i<count($a);$i++){
            $auths=BusinessAuth::getAuthsByParentId($parentid);
            if($auths->path==$a[$i]){
                //match
                $parentid=$auths->id;
            }else{
                $ismatch=false;
                break;
            }
        }
        return $ismatch?$this->ret_success("允许"):$this->ret_faild("禁止");
    }
    */


    public function apiAuthCheck($auth){
        try {
            if(BusinessAuth::authCheck($auth))
                return $this->ret_success('允许');
            else
                return $this->ret_faild('禁止');
        }catch (Exception $e){
            return $this->ret_faild($e->getMessage());
        }
    }

    public function apiGetAuthsByParentid($parentid){
        try{
            $auths=BusinessAuth::getAuthsByParentId($parentid);
            return $this->ret_success("成功",$auths);
        }catch (Exception $e){
            return $this->ret_faild($e->getMessage());
        }
    }


    public function apiGetAuthsTree(){
        try{
            $auths=BusinessAuth::getAuthsTree();
            return $this->ret_success("成功",$auths);
        }catch (Exception $e){
            return $this->ret_faild($e->getMessage());
        }
    }

    /**
     * 获取用户所有权限
     * @return \think\response\Json
     */
    public function apiGetUserAllAuths(){
        try{
            $user=Business::currentUser();
            $auths=BusinessAuth::getRoleAuthsArray($user->role_id);
            return $this->ret_success("成功",$auths);
        }catch (Exception $e){
            return $this->ret_faild($e->getMessage());
        }
    }

}
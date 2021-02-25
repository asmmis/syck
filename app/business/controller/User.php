<?php


namespace app\business\controller;
use app\business\model\Business;
use app\business\model\BusinessAuth;
use app\business\model\BusinessRole;
use app\business\model\BusinessRole as RoleModel;
use app\business\model\BusinessAuth as AuthModel;
use app\business\model\Store as StoreModel;
use think\Exception;

class User extends \app\BaseController
{

    /**登陆
     * @param $account 账号
     * @param $passwordmd5 密码md5
     */
    public function apiLogin($account,$passwordmd5){
        try {
            if (Business::login($account, $passwordmd5))
                return $this->ret_success('登陆成功');
            return $this->ret_faild('登陆失败');
        }catch (Exception $e){
            return $this->ret_faild($e->getMessage());
        }
    }

    /**
     * API登陆状态查询,
     */
    public function apiLoginState(){
        if(Business::getLoginState()){
            return $this->ret_success("已登陆");
     }
       return $this->ret_faild('未登陆');
    }

    /**
     * API退出登陆
     * @return \think\response\Json
     */
    public function  apiLogout(){
        if(Business::logout())
            return $this->ret_success("成功退出");
        return $this->ret_faild("退出失败");
    }

    /**
     * 后台列出该门店的所有账号
     */
    public function apiList($page=0,$pagesize=10){
        try {
            $data = Business::list($page, $pagesize);
            return $this->ret_success('成功', $data);
        }catch (Exception $e){
            return  $this->ret_faild($e->getMessage());
        }
    }

    /**
     * 软删除用户
     * @param $id
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function apiDel($id){
        try{
            if(Business::del($id))
                return $this->ret_success('成功');
        }catch(Exception $e){
            return $this->ret_faild($e->getMessage());
        }
    }

    /**
     * 获取用户的角色信息
     * @param $id
     */
    public function apiGetUserRole($id){
        try {
            $role = Business::getUserRole($id);
            return $this->ret_success('成功',$role->toArray());
        }catch (Exception $e){
            return $this->ret_faild($e->getMessage());
        }
    }


    /**
     * 保存或者新增用户（根据userid,若为-1则新增)
     * @param int $userid
     * @param $pwd
     * @param int $role
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function apiSaveUser($pwd,$account,$userid=-1,$role=-1){
        try {
            if (Business::saveUser($pwd, $account, $userid, $role))
                return $this->ret_success('成功');
        }catch (Exception $e){
            return $this->ret_faild($e->getMessage());
        }
    }

    /**
     * 获取当前登陆用户信息
     */
    public  function apiCurrentUser(){
        $user=Business::currentUser();
        if($user!=null)
            return $this->ret_success("成功",$user->toArray());
        else
            return $this->ret_faild("还未登陆");
    }



    /**
     * 获取用户的该级别权限
     * @param $level
     * @return \think\response\Json
     */
    public function apiGetUserAuthLevel($level){
        try{
        $user=Business:: currentUser();
        $role=Business::getUserRole($user->id);
        $userAuths=AuthModel::getRoleAuthsArray($role->id);
        $levelAuths=AuthModel::getAuthsByLevel($level);
        $ret=array();
        foreach($userAuths as $uat){
            foreach ($levelAuths as $lat){
                if($uat['id']==$lat['id'])
                    $ret[]=$uat;
            }
        }
        return $this->ret_success('成功',$ret);
        }catch (Exception $e){
            return $this->ret_faild($e->getMessage());
        }
    }


}
<?php


namespace app\business\controller;
use app\BaseController;
use app\business\model\Business;
use app\business\model\BusinessAuth;
use app\business\model\BusinessAuth as AuthModel;
use app\business\model\BusinessRole;
use app\business\model\BusinessRole as RoleModel;
use app\business\model\BusinessRoleAuth;
use app\business\model\Store;
use think\Exception;

class Role extends BaseController
{
    public  const EXCEPT_PATH=[
      'test.html','home.html','business/User/login','business/User/logout','business/User/loginState'
    ];

    /**
     * 列出所有角色(以数组方式)
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
        public function apiList($page=0,$pagesize=10){
            try {
                $store=Store::getMyStore();
                $users=BusinessRole::where('is_del',0)->where('store_id',$store['store_id'])->where('id','<>',1)->limit(max(0,($page-1)*$pagesize),$pagesize)->select();
                $data=array();
                $data['count']=BusinessRole::where('is_del',0)->select()->count();
                $data['info']=array();
                foreach($users as $user){
                    $t=$user->toArray();
                    $data['info'][]=$t;
                }
                return $this->ret_success("查询成功", $data);
            }catch (Exception $e){
                return $this->ret_faild($e->getMessage());
            }
        }

    public function apiGetAuthsByRoleId($roleid){
            try{
                $auth=BusinessAuth::getRoleAuthsArray($roleid);
                return $this->ret_success('成功',$auth);
            }catch (Exception $e){
                return $this->ret_faild($e->getMessage());
            }
    }

    /**
     * 保存或编辑用户角色信息
     * @param int $id
     * @param $name
     * @param $description
     * @return \think\response\Json
     */
    public function apiSaveRole($id=-1,$name,$description){
            try{
                if(BusinessRole::saveRole($id,$name,$description))
                    return $this->ret_success('成功');
                else return $this->ret_faild('失败');
            }catch (Exception $e) {
                return $this->ret_faild($e->getMessage());
            }
    }

    /**
     * 保存角色权限信息
     * @param $roleid
     * @param $authStr 权限id字符串，用,隔开
     * @return \think\response\Json
     */
    /*
    public function apiSaveRoleAuths($roleid,$authStr){
        try{
        $authStrs=explode(",",$authStr);
        BusinessRole::delRoleAuths($roleid);
        $auths=array();
        foreach ($authStrs as $s){
            $auth=BusinessAuth::getAuthById($s);
            $auths[]=$auth;
        }
        $newAuths=array();
        //进行过滤
        foreach ($auths as $auth){
            $childrens=BusinessAuth::getAuthsByParentId($auth['id']);
            if(count($childrens)==0){
                $newAuths[]=$auth;
                continue;
            }

            //进行全等匹配
            $matchTimes=0;
            foreach ($childrens as $child){
                $ismatch=false;
                foreach($auths as $authMatch){
                if($child['id']==$auth->id){
                    $ismatch=true;
                    break;
                    }
                }
                if($ismatch)
                    $matchTimes++;
            }
            if($matchTimes==count($childrens)){
                $newAuths[]=$auth;
            }
        }
        $newAuths=BusinessAuth::authFilt($newAuths);
        if(!BusinessRole::addRoleAuths($roleid,$newAuths))
            return $this->ret_faild('失败');
        return  $this->ret_success('成功');
    }catch(Exception $e){
        return ret_faild($e->geMessage());
    }
    }
*/
    public function apiDel($id){
        try {
            if (BusinessRole::isSuperManager($id))
                return $this->ret_faild('超管不允许删除');
            $role = BusinessRole::getRoleById($id);
            $role->is_del = 1;
            if ($role->save())
                return $this->ret_success('成功');
            else return $this->ret_faild('失败');
        }catch (Exception $e){
            return $this->ret_faild($e->getMessage());
        }
    }

    public function apiGetRoleById($id){
        try{
            $role=BusinessRole::getRoleById($id);
            return $this->ret_success('成功',$role->toArray());
        }catch (Exception $e){
            return $this->ret_faild('失败',$e->getMessage());
        }
    }


    public function apiSaveRoleAuths($roleid,$authStr){
        try {
            $authsArr = explode(",", $authStr);
            BusinessRole::delRoleAuths($roleid);
            if(BusinessRole::addRoleAuths($roleid,$authsArr))
                return $this->ret_success("保存成功");
        }catch (Exception $e){
            return $this->ret_faild($e->getMessage());
        }
    }

    /**
     * 为前端tree定制开发
     * 如果子权限没有全部选择，那么就不选择父权限
     * @param $roleid
     * @return \think\response\Json
     */
    public function apiGetAuthsByRoleIdForTree($roleid)
    {
        try {
            $ret=array();
            $auths = BusinessAuth::getRoleAuthsArray($roleid);
            foreach ($auths as $auth){
                $childrenAuths=BusinessAuth::getAuthsByParentId($auth['id']);
                if(count($childrenAuths)==0)
                    $ret[]=$auth;
                else{
                    //如果是父权限,进行子权限匹配
                    //进行全等匹配
                    $matchTimes=0;
                    foreach ($childrenAuths as $child){
                        $ismatch=false;
                        foreach($auths as $authMatch){
                            if($child['id']==$auth['id']){
                                $ismatch=true;
                                break;
                            }
                        }
                        if($ismatch)
                            $matchTimes++;
                    }
                    if($matchTimes==count($childrenAuths)){
                        $ret[]=$auth;
                    }
                }
            }
            return  $this->ret_success('成功',$ret);
        } catch (Exception $e) {
            return $this->ret_faild($e->getMessage());
        }
    }

    /**
     * 为前端tree定制开发
     * 返回未拥有的权限
     * @param $roleid
     * @return \think\response\Json
     */
    /*
    public function apiGetAuthsByRoleIdForTree($roleid){
        try{
            $auth=BusinessAuth::getRoleAuthsArray($roleid);
            $allAuths=BusinessAuth::getAllAuths();
            $noAuths=array();
            foreach ($allAuths as $aaa){
                $has=false;
                foreach ($auth as $aa){
                    if($aa['id']==$aaa['id']) {
                        $has = true;
                        break;
                    }
                }
                if(!$has)
                    $noAuths[]=$aaa;
            }
            return $this->ret_success('成功',$noAuths);
        }catch (Exception $e){
            return $this->ret_faild($e->getMessage());
        }
    }
    */
}
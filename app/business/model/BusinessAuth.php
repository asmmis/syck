<?php


namespace app\business\model;


use app\business\model\BusinessAuth as AuthModel;
use think\Model;

class BusinessAuth extends BaseModel
{

    public static $ret;


    /**
     * 获取父ID相同的一组权限对象
     * @param $parentid
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getAuthsByParentId($parentid){
        if(empty($parentid))
            $parentid=null;
        $auths=AuthModel::where('parent_id',$parentid)->select();
        $at=array();
        foreach ($auths as $a){
            $at[]=$a->toArray();
        }
        return $at;
    }


    /**
     * 返回角色的权限数组
     */
    public static function getRoleAuthsArray($roleId){
        $aat=array();//保存所有权限的数组
        //如果是超管，返回该店铺所有权限
        if(BusinessRole::isSuperManager($roleId)){
            $auths=BusinessAuth::getAllAuths();
            foreach ($auths as $a)
                $aat[]=$a->toArray();
        }else {
            $auths = BusinessRoleAuth::where('role_id', $roleId)->select();
            foreach ($auths as $a) {
                $ta = $a->toArray();
                $aat[] = self::getAuthById($ta['auth_id']);
            }
            $aat = self::authFilt($aat);
        }
        return $aat;
    }
    /*
    //废弃，因为拥有父权限不代表拥有所有子权限
    public static function getRoleAuthsArray($roleId){
            $auths=BusinessRoleAuth::where('role_id',$roleId)->select();
            $aat=array();//保存所有权限的数组
            foreach ($auths as $a) {
                $ta = $a->toArray();
                $aat = array_merge(self::getChildAuths($ta['auth_id']), $aat);
                $aat[]= self::getAuthById($ta['auth_id']);
            }
            $aat=self::authFilt($aat);
            return $aat;
    }
    */

    public static function getAllAuths(){
        $auths=self::select();
        return $auths;
//        return self::getChildAuths(null);
    }

    /**
     * 获取所有子代权限（合成一个数组）
     * @param $parentid
     * @return mixed
     */
    public static function getChildAuths($parentid){
        $auths=array();
        self::_getChildAuths($parentid,$auths);
        $auths=self::authFilt($auths);
        return $auths;
    }

    private static function _getChildAuths($parentid,&$auths){
        $at=self::getAuthsByParentId($parentid);
        if(count($at)==0)
            return;
        $auths=array_merge($auths,$at);
        foreach($at as $a){
            self::_getChildAuths($a['id'],$auths);
        }
    }

    public static function getAuthById($id){
        $auth=AuthModel::where('id',$id)->find();
        if($auth==null)
            return null;
        else return $auth->toArray();
    }

    public static function getAuthsByLevel($level){
        $parentid=null;
        $levelArr=explode(",",$level);
        $levelLen=count($levelArr);
        $ret=array();
        self::_getAuthByLevel($levelLen,$ret,null);
        $ret=self::authFilt($ret);
        return $ret;
    }

    private static function _getAuthByLevel($level,&$auths,$parentid){
        if($level--<=0)
            return;
        $as = self::getAuthsByParentId($parentid);
        if (count($as) == 0)
            return;
        $auths=array_merge($auths,$as);
        foreach($as as $a){
            $parentid=$a['id'];
            self::_getAuthByLevel($level,$auths,$parentid);
        }
    }

    public static function authCheck($s){
        try{
            $ismatch = false;
            Business::loginCheck();
            $user=Business::currentUser();
            if(BusinessRole::isSuperManager($user->role_id))
                return true;
            $role=BusinessRole::getRoleById($user->role_id);
            $auths=BusinessAuth::getRoleAuthsArray($role->id);
            foreach ($auths as $a){
                if($a['full_path']==$s)
                    $ismatch=true;
            }
            return $ismatch;
        }catch(Exception $e){
            throw $e;
        }
    }

    public static function authFilt($auths){
        $a=array();
        foreach ($auths as $auth){
            $has=false;
            foreach ($a as $at){
                if($at['id']==$auth['id']){
                    $has=true;
                    break;
                }
            }
            if(!$has)
            $a[]=$auth;
        }
        return $a;

        //循环删除版本，太恶心人了
        /*
        while (true){
            Loop:
            for($i=0;$i<count($auths)-1;$i++){
                for($j=$i+1;$j<count($auths);$j++){
                    if($auths[$i]['id']==$auths[$j]['id']) {
                        unset($auths[$i]);
                        goto Loop;
                    }
                }
            }
        break;
        }
        return $auths;
        */
    }


    /**
     * 获取权限树
     */
    public static function getAuthsTree(){
        $ret=self::_getAuthsTree(null);
        return $ret;
    }


    /**
     * 返回一个数组，包括父ID未parentid的所有权限
     */
    private static function _getAuthsTree($parentid){
        $at=self::getAuthsByParentId($parentid);
        if(count($at)==0)
            return null;
        $ret=array();
        foreach ($at as $a){
            $node=array();
            $auths[]=&$node;
            $node['value']=$a;
            $node['children']=self::_getAuthsTree($a['id']);
            $ret[]=$node;
            // $achild=self::getAuthsByParentId($a['id']);
            // if(count($achild)==0)
            //     $node['child']=null;
            // else{
            //     //有子类
            //     $aa=array();
            //     $node['child']=&$aa;
            //     self::_getAuthsTree($a['id'],$aa);
            // }
        }
        return $ret;
    }

}
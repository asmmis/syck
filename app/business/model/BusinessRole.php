<?php


namespace app\business\model;

use app\business\model\BusinessAuth as AuthModel;
use think\Exception;
use think\Model;

class BusinessRole extends Model
{
    /**
     * 根据角色ID获取角色
     * @param $id
     * @return |null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getRoleById($id){
        if(empty($id))
            $id=null;
        $role=BusinessRole::where('id',$id)->find();
        if($role->isEmpty())
            return null;
        return $role;
    }

    public static function saveRole($id=-1,$name,$description){
        if($id==-1){
            $role=new BusinessRole();
            $role->add_time=date('y-m-d H:i:s', time());
        }else{
            if(self::isSuperManager($id))
                throw new Exception('超管账号不允许修改');
            $role=self::getRoleById($id);
        }
        $store=Store::getMyStore();
        $role->setAttr('store_id',$store['store_id']);
        $role->setAttr('name',$name);
        $role->setAttr('description',$description);
        if($role->save())
            return true;
        else
            throw new Exception('写入数据失败');
    }

    /**清空角色对应权限
     * @param $roldid
     */
    public static function delRoleAuths($roleid){
        if(self::isSuperManager($roleid))
            throw new Exception('超管账号不允许删除');
        $role=self::getRoleById($roleid);
        $mappings=BusinessRoleAuth::where('role_id',$roleid)->select();
        foreach ($mappings as $m){
            $m->delete();
        }
        return true;
    }

    public  static function addRoleAuths($roleid,$auths){
        if(self::isSuperManager($roleid))
            throw new Exception('超管账号不允许修改');
        foreach ($auths as $a){
            if(!self::addRoleAuth($roleid,$a))
                return false;
        }
        return true;
    }

    public static function addRoleAuth($roleid,$authid){
        if(self::isSuperManager($roleid))
            throw new Exception('超管账号不允许修改');
        $roleauth=new BusinessRoleAuth();
        $roleauth->role_id=$roleid;
        $roleauth->auth_id=$authid;
        if($roleauth->save())
            return true;
        return  false;
    }

    public static function isSuperManager($role){
        if($role==1)
            return true;
        if(($role instanceof BusinessRole)&&$role->id==1)
            return true;
        return false;
    }
}
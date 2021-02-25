<?php

declare(strict_types=1);

namespace app\admin\model;

use app\admin\model\AdminLogin as adminModel;

class AdminLogin extends \think\Model
{
    public $name='admin';
    /**
     * 显示资源列表
     */
    public static function login($account, $password)
    {
        if (empty($account) || empty($password))
            return false;
        $adminlogin = adminModel::where('account', $account)
            ->where('password', $password)
            ->where('is_del', 0) 
            ->find();
        if ($adminlogin == null)
            return false;
        //保存到session
        session('userid', $adminlogin['id']);
        session('admin_name', $adminlogin['account']);
        return true;
    }

    public static function LoginCheck()
    {
        if (session('userid') != null) {
            return true;
        } else {
            return false;
        }
    }

    public static function logout()
    {
        session('userid', null);
        return true;
    }
}

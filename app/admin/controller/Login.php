<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\admin\model\AdminLogin;
use app\BaseController;
use think\Request;

class Login extends BaseController
{
    // protected $middleware = [\app\admin\middleware\CheckLogin::class];

    /*
    *LOGIN
    */
    public function index($account, $password)
    {
        if (AdminLogin::login($account, $password))
            return returnMsg(200, '登陆成功!');
        return returnMsg(201, '登陆失败!');
    }


    /*
    *LOGIN验证
    */
    public function check()
    {
        if (AdminLogin::LoginCheck())
            return returnMsg(200, '已登陆');
        return returnMsg(201, '未登录');
    }


    public function loginOut()
    {
        if (AdminLogin::logout())
            return returnMsg(200);
        return returnMsg(201);
    }

  
}

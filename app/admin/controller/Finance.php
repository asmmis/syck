<?php
declare (strict_types = 1);

namespace app\admin\controller;


use think\Request;
use think\facade\Db;

class Finance
{
    /**
     * 显示资源列表
     */
    public function index()
    {
        //
    }

    /**
     *用户资金列表
     */
    public function balance()
    {
        $data = Db::name('user')->field('*')->limit(20)->select();
        if ($data) {
            return returnMsg(200, $data, '请求成功！');
        } else {
            return returnMsg(201, '请求失败！');
        }
    }

    /**
     * 保存新建的资源
     */
    public function save(Request $request)
    {
        //
    }

    /**
     * 显示指定的资源
     */
    public function read($id)
    {
        //
    }

   
}

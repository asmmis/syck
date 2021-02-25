<?php

declare(strict_types=1);

namespace app\admin\controller;


use think\Request;
use think\facade\Db;


class order
{
    /**
     * 显示资源列表
     */
    public function index()
    {
        $info = Db::name('goods_order')
            ->where('is_del', 0)
            ->field('id,order_sn,real_name,phone,total_price,status,pay_type,pay_time,express_name,express_sn,create_time')
            ->limit(20)
            ->select();
        $count = Db::name('goods_order')->where('is_del', 0)->count();
        $data = [
            'info' => $info,
            'count' => $count,
        ];
        if ($data) {
            return returnMsg(200, $data, '请求成功！');
        } else {
            return returnMsg(201, '请求失败！');
        }
    }

    /**
     * 显示待收货表
     */
    public function shoulist()
    {
        $info = Db::name('goods_order')
            ->where('is_del', 0)
            ->where('status', 3)
            ->field('id,order_sn,real_name,phone,total_price,status,pay_type,pay_time,express_name,express_sn,create_time')
            ->limit(20)
            ->select();
        $count = Db::name('goods_order')->where('status', 3)->where('is_del', 0)->count();
        $data = [
            'info' => $info,
            'count' => $count,
        ];
        if ($data) {
            return returnMsg(200, $data, '请求成功！');
        } else {
            return returnMsg(201, '请求失败！');
        }
    }

    /**
     * 显示待发货列表
     */
    public function fahuolist()
    {
        $info = Db::name('goods_order')
            ->where('is_del', 0)
            ->where('status', 2)
            ->field('id,order_sn,real_name,phone,total_price,status,pay_type,pay_time,express_name,express_sn,create_time')
            ->limit(20)
            ->select();
        $count = Db::name('goods_order')->where('status', 2)->where('is_del', 0)->count();
        $data = [
            'info' => $info,
            'count' => $count,
        ];
        if ($data) {
            return returnMsg(200, $data, '请求成功！');
        } else {
            return returnMsg(201, '请求失败！');
        }
    }

    /**
     * 显示待支付列表
     */
    public function no_paylist()
    {
        $info = Db::name('goods_order')
            ->where('is_del', 0)
            ->where('status', 1)
            ->field('id,order_sn,real_name,phone,total_price,status,pay_type,pay_time,express_name,express_sn,create_time')
            ->limit(20)
            ->select();
        $count = Db::name('goods_order')->where('status', 1)->where('is_del', 0)->count();
        $data = [
            'info' => $info,
            'count' => $count,
        ];
        if ($data) {
            return returnMsg(200, $data, '请求成功！');
        } else {
            return returnMsg(201, '请求失败！');
        }
    }

    /**
     * 显示已完成列表
     */
    public function finish_list()
    {
        $info = Db::name('goods_order')
            ->where('is_del', 0)
            ->where('status', 4)
            ->field('id,order_sn,real_name,phone,total_price,status,pay_type,pay_time,express_name,express_sn,create_time')
            ->limit(20)
            ->select();
        $count = Db::name('goods_order')->where('status', 1)->where('is_del', 0)->count();
        $data = [
            'info' => $info,
            'count' => $count,
        ];
        if ($data) {
            return returnMsg(200, $data, '请求成功！');
        } else {
            return returnMsg(201, '请求失败！');
        }
    }


    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //
    }

   
}

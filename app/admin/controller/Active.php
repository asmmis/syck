<?php

declare(strict_types=1);

namespace app\admin\controller;

use think\Request;
use think\facade\Db;

class Active
{
    /**
     * 显示拼团专场列表
     */
    public function index()
    {
        $data = Db::name('combination')->where('is_del', 0)->field('*')->limit(20)->select();
        if ($data) {
            return returnMsg(200, $data, '请求成功！');
        } else {
            return returnMsg(201, '请求失败！');
        }
    }

    /**
     * 添加拼团专场
     */
    public function add_session()
    {
        // halt($_POST['start_time']);
        $data = [
            'name' => addslashes($_POST['name']),
            'is_show' => $_POST['is_show'],
            'start_time' => $_POST['start_time'],
            'end_time' => $_POST['end_time'],
            'add_time' => time(),
            'is_del' => 0,
        ];
        $res = Db::name('combination')->insert($data); //商品属性详细表SKU
        if ($res) {
            return returnMsg(200, $res, '请求成功！');
        } else {
            return returnMsg(201, '请求失败！');
        }
    }

    /**
     * 删除专场
     */
    public function del()
    {
        $data = ['is_del' => 1];
        $res = Db::name('combination')->where('id', $_POST['id'])->update($data);
        if ($res) {
            return returnMsg(200, '删除成功！');
        } else {
            return returnMsg(201, '网络繁忙,请稍后再试！');
        }
    }

    /**
     * 是否展示
     */
    public function change()
    {
        $info = Db::name('combination')->where('id', $_POST['id'])->find();
        if ($info['is_show'] == 1) {
            $data = ['is_show' => 0];
            $res = Db::name('combination')->where('id', $_POST['id'])->update($data);
        } elseif ($info['is_show'] == 0) {
            $data1 = ['is_show' => 1];
            $res = Db::name('combination')->where('id', $_POST['id'])->update($data1);
        }
        if ($res) {
            return returnMsg(200, '提交成功！');
        } else {
            return returnMsg(201, '提交失败！');
        }
    }

    /**
     * 显示编辑信息
     */
    public function editshow()
    {
        $data = Db::name('combination')->where('id', $_GET['id'])->where('is_del', 0)->field('*')->limit(20)->select();
        if ($data) {
            return returnMsg(200, $data, '请求成功！');
        } else {
            return returnMsg(201, '请求失败！');
        }
    }

    /**
     * 编辑专场
     */
    public function mod_group()
    {
        $data = [
            'name' => $_POST['name'],
            'start_time' => $_POST['start_time'],
            'end_time' => $_POST['end_time'],
            'is_show' => $_POST['is_show'],
        ];
        $res = Db::name('combination')->where('id', $_POST['id'])->update($data);
        if ($res) {
            return returnMsg(200, '提交成功！');
        } else {
            return returnMsg(201, '提交失败！');
        }
    }


    /**
     * 专场商品显示列表
     */
    public function combination_list($id)
    {
        $info = Db::name('combination')->where('id', $id)->where('is_del', 0)->find(); //专场信息
        $com_goods = Db::name('goods')->where('is_pick', 1)->where('is_del', 0)->select(); //所有拼团商品
    }

    /**
     * 添加某个专场下的专场商品 从已有商品中添加
     */
    public function add_combination_goods()
    {

    }

    /**
     * 商品专场里面编辑 编辑限购 件数 参团人数 是否展示 开始结束时间必须在该专场时间之内
     */
    public function mod_combination()
    {
    }

    /**
     * 专场下的拼团用户列表 仅查看
     */
    public function user_combination_list()
    {
        $data = Db::name('combination_goods_user')->select();
        if ($data) {
            return returnMsg(200, '提交成功！');
        } else {
            return returnMsg(201, '提交失败！');
        }
    }
}

<?php

declare(strict_types=1);

namespace app\admin\controller;

use think\Request;
use think\facade\Db;

class Coupon
{
    /**
     * 显示优惠券列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $data = Db::name('coupon')->where('is_del', 0)->field('*')->select();
        if ($data) {
            return returnMsg(200, $data, '请求成功！');
        } else {
            return returnMsg(201, '请求失败！');
        }
    }

    /**
     *添加优惠券
     */
    public function add_coupon($is_show, $coupon_name, $coupon_info, $zhi, $expiration, $typeid, $user_value)
    {
        // $expira = str_replace('/','-',$expiration);
        $time = strtotime($expiration);
        $data = [
            'cate_id' => $is_show,
            'typeid' => $typeid,
            'title' => $coupon_name,
            'info' => $coupon_info,
            'value' => $zhi,
            'user_value' => $user_value,
            'expiration' => $time,
            'add_time' => time()
        ];
        $res = Db::name('coupon')->insert($data);
        if ($res) {
            return returnMsg(200, '添加成功！');
        } else {
            return returnMsg(201, '添加失败！');
        }
    }


    /**
     * 显示编辑优惠券表单页
     */
    public function editshow()
    {
        $id = $_GET['id'];
        // var_dump($id);
        $info = Db::name('coupon')->where('id', $id)->field('*')->find();
        return returnMsg(200, $info, '请求成功！');
    }

    /**
     * 编辑优惠券
     */
    public function mod_coupon()
    {
        //    print_r($_POST['img_arr']);
        $data = [];
        $res = Db::name('coupon')->where('id', $_POST['id'])->update($data);
        if ($res) {
            return returnMsg(200, '提交成功！');
        } else {
            return returnMsg(201, '提交失败！');
        }
    }

    /**
     * 展示优惠券类型下的优惠券
     */
    public function show_coupon_type($cate_id)
    {
        // halt($cate_id);
        $info = Db::name('coupon')
            ->field('id,cate_id,title,typeid,expiration,is_del')
            ->where('is_del', 0)
            ->where('cate_id', $cate_id)
            ->select();
        $data = [
            'info' => $info,
        ];
        if ($info) return returnMsg(200, $data, '提交成功！');
        return returnMsg(201, '提交失败！');
    }

    /**
     * 发放优惠券
     */
    public function issue_coupon($cateid, $phone, $coupon_id)
    {
        $info = Db::name('user')
            ->field('*')
            ->where('phone', $phone)
            ->where('status', 0)
            ->find();
        if (!$info) {
            return returnMsg(201, '该用户不存在或手机号输入错误！');
        }
        $cou_info = Db::name('coupon')->where('id', $coupon_id)->find();
        // halt($info);
        $data = [];
        $data['coupon_id'] = $coupon_id;
        $data['uid'] = $info['uid'];
        $data['cate_id'] = $cateid;
        $data['create_time'] = time();
        $data['expiration'] = $cou_info['expiration'];
        $res = Db::name('coupon_user')->insert($data);
        if ($res) return returnMsg(200, '发放成功！');
        return returnMsg(201, '发放失败！');
    }

    /**
     * 已发放优惠券列表 未过期
     */
    public function issue_coupon_list()
    {
        $info = Db::name('coupon')->where('expiration','>',time())->select();
        $count = Db::name('coupon')->where('expiration','>',time())->count();
        $data = [
            'info' => $info,
            'count' => $count,
        ];
        if ($data) return returnMsg(200, $data, '请求成功!');
        return returnMsg(201, '请求失败');
    }

     /**
     * 已发放优惠券列表 已过期
     */
    public function issue_coupon_end()
    {
        $info = Db::name('coupon')->where('expiration','<',time())->select();
        $count = Db::name('coupon')->where('expiration','<',time())->count();
        $data = [
            'info' => $info,
            'count' => $count,
        ];
        if ($data) return returnMsg(200, $data, '请求成功!');
        return returnMsg(201, '请求失败');
    }


    /**
     * 删除优惠券
     */
    public function del($id)
    {
        $data = ['is_del' => 1];
        $res = Db::name('coupon')->where('id', $id)->update($data);
        if ($res) {
            return returnMsg(200, '删除成功！');
        } else {
            return returnMsg(201, '网络繁忙,请稍后再试！');
        }
    }
}

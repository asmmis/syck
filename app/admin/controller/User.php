<?php

declare(strict_types=1);

namespace app\admin\controller;

use think\Request;
use think\facade\Db;

class User
{
    /**
     * 显示普通列表
     *
     */
    public function index()
    {
        $info = Db::name('user')
            ->where('user_type', 0)
            ->field('*')
            ->limit(20)
            ->select()
            ->each(function ($item, $key) {
                $item['nickname'] = show_nickname($item['nickname']);
                return $item;
            });

        $count = Db::name('user')->where('user_type', 0)->count();
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
     * 显示普通列表
     *
     */
    public function idol_index()
    {
        $info = Db::name('user')
            ->where('user_type', 1)
            ->field('*')
            ->limit(20)
            ->select()
            ->each(function ($item, $key) {
                $item['nickname'] = show_nickname($item['nickname']);
                return $item;
            });;
        $count = Db::name('user')->where('user_type', 1)->count();
        // var_dump($count);
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
     * 显示普通列表
     *
     */
    public function partner_index()
    {
        $info = Db::name('user')
            ->where('user_type', 2)
            ->field('*')->limit(20)
            ->select()
            ->each(function ($item, $key) {
                $item['nickname'] = show_nickname($item['nickname']);
                return $item;
            });;
        $count = Db::name('user')->where('user_type', 2)->count();
        // var_dump($count);
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
     * 普通->代言人1
     *
     */
    public function change()
    {
        $uid = $_POST['uid'];
        // 启动事务
        Db::startTrans();
        try {
            $data = ['user_type' => 1, 'sopke_uid' => 0, 'up_daiyan_time' => time()];
            //时间
            //spoke_uid == 0
            Db::name('user')->where('uid', $uid)->update($data); //升级

            $arr_id = ['sopke_uid' => $uid]; //下级
            //查询出我邀请的普通用户是否存在，把我邀请的普通用户的代言人ID 变成我
            $my_putong = Db::name('user')->where('spread_uid', $uid)->where('user_type', 0)->find();
            if ($my_putong) {
                $res = Db::name('user')->where('spread_uid', $uid)->where('user_type', 0)->update($arr_id);
            }

            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return returnMsg(201, '网络繁忙，请稍候再试！');
        }
        return returnMsg(200, '升级成功！');
    }

    /**
     * 代言人->合伙人2
     *
     */
    public function idol_change()
    {
        $uid = $_POST['uid'];
        // 启动事务
        Db::startTrans();
        try {
            $data = ['user_type' => 2, 'partner_uid' => 0, 'up_hehuo_time' => time()];
            //时间
            Db::name('user')->where('uid', $uid)->update($data);
            //我是合伙人了 查询出我邀请的所有人
            $my_invite = Db::name('user')->where('spread_uid', $uid)->where('user_type', '<>', 2)->select(); //我邀请的没有成为合伙人的用户//如果我邀请的人是合伙人了 ，不需要改动
            //如果我邀请的人是普通用户 把合伙人ID 改成我
            foreach ($my_invite as $value) {
                if ($value['user_type'] == 0) {
                    $partner_uid = ['partner_uid' => $uid];
                    Db::name('user')->where('spread_uid', $uid)->update($partner_uid); //更新下级

                } else if ($value['user_type'] == 1) {
                    $data_sopke = ['partner_uid' => $uid];
                    Db::name('user')->where('spread_uid', $uid)->update($data_sopke); //如果我邀请的人是代言人 把合伙人ID 改成我
                    // $my_invite_pt = Db::name('user')->where('spread_uid', $value['uid'])->where('user_type', 0)->select(); //我邀请的代言人下面的普通用户
                    // var_dump($my_invite_pt); //我邀請的代言人下的普通用户
                    // echo "111";
                    // foreach ($my_invite_pt as $v) {
                    $partner_uids = ['partner_uid' => $uid];
                    Db::name('user')->where('spread_uid', $value['uid'])->update($partner_uids); //更新下下级
                    // }
                }
            }
            // ---我邀请的人是代言人 并且代言人有下级普通用户 把这些普通用户并且邀请人是我邀请的代言人邀请进来的 合伙人ID改成我
            // $arr_id = ['partner_uid' => $uid, 'sopke_uid' => 0];
            // Db::name('user')->where('spread_uid', $uid)->where('user_type', '<>', 2)->where('uid', '<>', $uid)->update($arr_id); //更新下级

            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return returnMsg(201, '网络繁忙，请稍候再试！');
        }
        return returnMsg(200, '升级成功！');
    }
}
